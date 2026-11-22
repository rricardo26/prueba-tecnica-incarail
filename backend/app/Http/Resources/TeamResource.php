<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'tla' => $this->tla,
            'crest_url' => $this->crest_url,
            'venue' => $this->venue,
            'players_count' => $this->whenCounted('players'),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'competitions' => CompetitionResource::collection($this->whenLoaded('competitions')),
        ];
    }
}
