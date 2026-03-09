<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['modules'] = $this->record->modules()->pluck('modules.id')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $modules = $this->data['modules'] ?? [];

        $this->record->modules()->sync(
            collect($modules)->mapWithKeys(fn ($moduleId) => [
                $moduleId => ['is_active' => true],
            ])->toArray()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
