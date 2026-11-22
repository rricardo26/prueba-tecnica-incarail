<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PlayerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $players = Player::query()
            ->with('team')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('position'), function ($query) use ($request) {
                $query->where('position', $request->string('position'));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return PlayerResource::collection($players);
    }
}