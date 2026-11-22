<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TeamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->withCount('players')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where('name', 'ilike', $term)
                    ->orWhere('tla', 'ilike', $term);
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return TeamResource::collection($teams);
    }

    public function show(int $team_id): TeamResource
    {
        $team = Team::with(['players', 'competitions'])->findOrFail($team_id);

        return new TeamResource($team);
    }
}
