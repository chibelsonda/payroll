<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class CompanyScopedModel extends Model
{
    /**
     * Boot the model and apply company scope
     */
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            // Check if the binding exists before trying to resolve it
            if (app()->bound('active_company_id')) {
                $companyId = app('active_company_id');
                if ($companyId) {
                    $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
                }
            }
        });

        // Automatically set company_id when creating
        static::creating(function ($model) {
            // Check if the binding exists before trying to resolve it
            if (app()->bound('active_company_id')) {
                $companyId = app('active_company_id');
                if ($companyId && !$model->company_id) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    /**
     * Get a new query builder without the company scope
     */
    public static function withoutCompanyScope(): Builder
    {
        return static::withoutGlobalScope('company');
    }
}
