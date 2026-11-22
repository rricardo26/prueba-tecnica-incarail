<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class CaslPermissionService
{
    public function formatForUser(User $user): array
    {
        if ($user->hasRole('admin')) {
            return [
                ['action' => 'manage', 'subject' => 'all'],
            ];
        }

        return $user->getAllPermissions()
            ->map(function ($permission) {
                [$resource, $action] = explode('.', $permission->name);

                return [
                    'action' => $action,
                    'subject' => Str::studly(Str::singular($resource)),
                ];
            })
            ->values()
            ->all();
    }
}
