<?php

namespace App\Services;

use App\Events\LeagueImported;
use App\Exceptions\ExternalRateLimitExceededException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class CompetitionSyncService
{
    public function __construct(private readonly FootballDataClient $client) {}

    public function listCompetitions(): Collection
    {
        try {
            $remoteList = Cache::remember(
                'football.competitions.list',
                now()->addHours(6),
                fn () => $this->client->getCompetitions()
            );

            foreach ($remoteList as $item) {
                Competition::updateOrCreate(
                    ['external_id' => $item['id']],
                    [
                        'name' => $item['name'],
                        'code' => $item['code'] ?? null,
                        'area_name' => $item['area']['name'] ?? null,
                        'emblem_url' => $item['emblem'] ?? null,
                    ]
                );
            }
        } catch (ExternalRateLimitExceededException|ExternalServiceUnavailableException) {
            // Resiliencia (B5): si falla o no hay cupo, respondemos con lo almacenado localmente
        }

        return Competition::withCount('teams')->orderBy('name')->get();
    }

    public function importOrRefreshCompetition(int $competitionId, bool $forceRefresh = false): array
    {
        $competition = Competition::where('id', $competitionId)
            ->orWhere('external_id', $competitionId)
            ->first();

        $externalId = $competition?->external_id ?? $competitionId;
        $ttlMinutes = (int) config('services.football_data.cache_ttl', 30);

        $isFresh = $competition
            && $competition->last_synced_at
            && $competition->last_synced_at->gt(now()->subMinutes($ttlMinutes))
            && $competition->teams()->exists();

        if ($isFresh && ! $forceRefresh) {
            return [
                'competition' => $competition->load('teams.players'),
                'source' => 'local_cache',
                'stale' => false,
            ];
        }

        try {
            $detail = $this->client->getCompetitionDetail($externalId);
            $teamsPayload = $this->client->getCompetitionTeams($externalId);

            $synced = DB::transaction(function () use ($detail, $teamsPayload) {
                $comp = Competition::updateOrCreate(
                    ['external_id' => $detail['id']],
                    [
                        'name' => $detail['name'],
                        'code' => $detail['code'] ?? null,
                        'area_name' => $detail['area']['name'] ?? null,
                        'emblem_url' => $detail['emblem'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );

                $teamIds = [];
                $playersCount = 0;

                foreach ($teamsPayload as $teamData) {
                    $team = Team::updateOrCreate(
                        ['external_id' => $teamData['id']],
                        [
                            'name' => $teamData['name'],
                            'short_name' => $teamData['shortName'] ?? null,
                            'tla' => $teamData['tla'] ?? null,
                            'crest_url' => $teamData['crest'] ?? null,
                            'venue' => $teamData['venue'] ?? null,
                        ]
                    );

                    $teamIds[] = $team->id;

                    foreach ($teamData['squad'] ?? [] as $member) {
                        Player::updateOrCreate(
                            ['external_id' => $member['id']],
                            [
                                'team_id' => $team->id,
                                'name' => $member['name'],
                                'position' => $member['position'] ?? null,
                                'shirt_number' => $member['shirtNumber'] ?? null,
                            ]
                        );
                        $playersCount++;
                    }
                }

                $comp->teams()->syncWithoutDetaching($teamIds);

                return [
                    'competition' => $comp->load('teams.players'),
                    'teamsCount' => count($teamIds),
                    'playersCount' => $playersCount,
                ];
            });

            event(new LeagueImported(
                $synced['competition'],
                $synced['teamsCount'],
                $synced['playersCount']
            ));

            return [
                'competition' => $synced['competition'],
                'source' => 'external_api',
                'stale' => false,
            ];
        } catch (ExternalRateLimitExceededException|ExternalServiceUnavailableException $e) {
            if ($competition && $competition->teams()->exists()) {
                return [
                    'competition' => $competition->load('teams.players'),
                    'source' => 'local_fallback',
                    'stale' => true,
                ];
            }

            throw $e;
        }
    }
}