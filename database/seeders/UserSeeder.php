<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $baseDomain = $this->baseDomain();

        Tenant::query()->each(function (Tenant $tenant) use ($baseDomain) {

            $email = "super@{$tenant->slug}.{$baseDomain}";

            $user = User::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email' => $email,
                ],
                [
                    'name' => "Super Usuário",
                    'password' => Hash::make('senha123'),
                    'email_verified_at' => now(),
                    'is_admin' => $tenant->slug === 'dpanel',
                ]
            );

            $companies = $tenant->companies()
                ->orderBy('id')
                ->get();

            if ($companies->isEmpty()) {
                $user->update(['default_company_id' => null]);
                return;
            }

            $defaultCompany = $companies->first();
            $user->update(['default_company_id' => $defaultCompany->id]);

            $pivotData = $companies->mapWithKeys(function ($company) use ($tenant) {
                return [
                    $company->id => ['tenant_id' => $tenant->id],
                ];
            })->toArray();

            $user->companies()->syncWithoutDetaching($pivotData);
        });
    }

    private function baseDomain(): string
    {
        return config('app.base_domain', 'draxel.test');
    }
}
