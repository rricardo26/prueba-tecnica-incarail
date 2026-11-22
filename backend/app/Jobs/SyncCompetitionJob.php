<?php

namespace App\Jobs;

use App\Services\CompetitionSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

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
