<?php

namespace App\Listeners;

use App\Events\LeagueImported;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvalidateFootballCacheListener
{
    public function handle(LeagueImported $event): void
    {
        Cache::forget('football.competitions.list');

        Log::info('Liga sincronizada desde football-data.org', [
            'competition_id' => $event->competition->id,
            'external_id' => $event->competition->external_id,
            'teams' => $event->teamsSynced,
            'players' => $event->playersSynced,
        ]);
    }
}