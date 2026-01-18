<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasUuid
{
    /**
     * Boot the trait and set up UUID generation
     *
     * For PostgreSQL: Database generates UUID via gen_random_uuid() default,
     * but we still generate here to ensure UUID is available before save
     * (useful for relationships, logging, etc.)
     *
     * For MySQL/SQLite: This is the primary UUID generation mechanism
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            // Only generate if UUID is not already set
            // This allows manual UUID assignment (e.g., in seeders) to take precedence
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Retrieve the model for route model binding.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        // Validate UUID format to prevent QueryException from invalid UUID strings
        if ($field === 'uuid' && !empty($value) && is_string($value)) {
            if (!\Illuminate\Support\Str::isUuid($value)) {
                return null;
            }
        }

        return static::where($field, $value)->first();
    }
}
