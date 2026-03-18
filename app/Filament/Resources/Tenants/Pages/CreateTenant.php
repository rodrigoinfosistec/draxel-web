<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        $modules = $this->data['modules'] ?? [];
        $clockDevices = $this->data['clock_devices'] ?? [];

        $this->record->modules()->sync(
            collect($modules)->mapWithKeys(fn ($moduleId) => [
                $moduleId => ['is_active' => true],
            ])->toArray()
        );

        $this->record->clockDevices()->sync($clockDevices);
    }
}
