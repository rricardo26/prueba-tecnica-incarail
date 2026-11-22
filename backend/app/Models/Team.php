<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'external_id', 'name', 'short_name', 'tla', 'crest_url', 'venue',
    ];

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
