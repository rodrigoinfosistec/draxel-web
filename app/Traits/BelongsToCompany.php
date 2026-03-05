<?php

namespace App\Traits;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model) {
            if (! $model->company_id) {
                $companyId = CompanyContext::id();

                if (! $companyId) {
                    throw new RuntimeException('Company not resolved.');
                }

                $model->company_id = $companyId;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = CompanyContext::id();

            if ($companyId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    $companyId
                );
            }
        });
    }

    public function scopeWithoutCompany(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
