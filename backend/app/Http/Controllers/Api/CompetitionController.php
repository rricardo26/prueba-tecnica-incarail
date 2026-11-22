<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetitionResource;
use App\Jobs\SyncCompetitionJob;
use App\Services\CompetitionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CompetitionController extends Controller
{
    public function __construct(private readonly CompetitionSyncService $syncService) {}

    public function index(): AnonymousResourceCollection
    {
        $competitions = $this->syncService->listCompetitions();

        return CompetitionResource::collection($competitions);
    }

    public function show(Request $request, int $competition_id): CompetitionResource
    {
        $forceRefresh = $request->boolean('refresh') && $request->user()->can('competitions.import');

        $result = $this->syncService->importOrRefreshCompetition($competition_id, $forceRefresh);

        return (new CompetitionResource($result['competition']))
            ->additional([
                'meta' => [
                    'source' => $result['source'],
                    'stale' => $result['stale'],
                ],
            ]);
    }

    public function syncAsync(int $competition_id): JsonResponse
    {
        SyncCompetitionJob::dispatch($competition_id);

        return response()->json([
            'success' => true,
            'message' => 'Sincronización encolada. Se procesará respetando el límite de 10 req/min.',
        ], 202);
    }
}