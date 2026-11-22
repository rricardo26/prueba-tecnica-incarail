<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
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
            'code' => $this->code,
            'area_name' => $this->area_name,
            'emblem_url' => $this->emblem_url,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'teams_count' => $this->whenCounted('teams'),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
