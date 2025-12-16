<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Adds created_by / updated_by based on the authenticated user.
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function ($model) {
            $userId = Auth::id();
            if ($userId && !$model->getAttribute('created_by')) {
                $model->setAttribute('created_by', $userId);
            }
            if ($userId && !$model->getAttribute('updated_by')) {
                $model->setAttribute('updated_by', $userId);
            }
        });

        static::updating(function ($model) {
            $userId = Auth::id();
            if ($userId) {
                $model->setAttribute('updated_by', $userId);
            }
        });
    }
}
