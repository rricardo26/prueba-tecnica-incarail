<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\RateLimited;
use App\Services\CompetitionSyncService;

class SyncCompetitionJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $externalCompetitionId) {}

    public function middleware(): array
    {
        return [new RateLimited('football-data-queue')];
    }

    public function handle(CompetitionSyncService $syncService): void
    {
        $syncService->importOrRefreshCompetition($this->externalCompetitionId, forceRefresh: true);
    }
}
