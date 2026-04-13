<?php

namespace App\Modules\PurchaseReceipt\Services;

use App\Modules\PurchaseReceipt\Enums\PurchaseReceiptStatus;
use App\Modules\PurchaseReceipt\Models\PurchaseReceipt;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReceiptService
{
    public function store(array $data): PurchaseReceipt
    {
        return DB::transaction(function () use ($data) {
            $tenantId = TenantContext::id();
            $companyId = CompanyContext::id();

            $receipt = PurchaseReceipt::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'received_by' => Auth::id(),
                'number' => $data['number'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'receipt_date' => $data['receipt_date'],
                'status' => PurchaseReceiptStatus::Draft->value,
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $subtotal = round($quantity * $unitCost, 2);

                $receipt->items()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalAmount += $subtotal;
            }

            $receipt->update([
                'total_amount' => $totalAmount,
            ]);

            return $receipt->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function update(PurchaseReceipt $receipt, array $data): PurchaseReceipt
    {
        return DB::transaction(function () use ($receipt, $data) {
            if (! $receipt->isDraft()) {
                abort(422, 'Somente recebimentos em digitação podem ser alterados.');
            }

            $receipt->update([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'number' => $data['number'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'receipt_date' => $data['receipt_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $receipt->items()->delete();

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $subtotal = round($quantity * $unitCost, 2);

                $receipt->items()->create([
                    'tenant_id' => $receipt->tenant_id,
                    'company_id' => $receipt->company_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalAmount += $subtotal;
            }

            $receipt->update([
                'total_amount' => $totalAmount,
            ]);

            return $receipt->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function receive(PurchaseReceipt $receipt): PurchaseReceipt
    {
        return DB::transaction(function () use ($receipt) {
            if (! $receipt->isDraft()) {
                abort(422, 'Somente recebimentos em digitação podem ser lançados.');
            }

            if ($receipt->items()->count() === 0) {
                abort(422, 'Não é possível lançar um recebimento sem itens.');
            }

            $receipt->update([
                'status' => PurchaseReceiptStatus::Received->value,
                'received_at' => now(),
            ]);

            return $receipt->fresh(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function cancel(PurchaseReceipt $receipt): PurchaseReceipt
    {
        return DB::transaction(function () use ($receipt) {
            if ($receipt->isCanceled()) {
                abort(422, 'Este recebimento já foi cancelado.');
            }

            $receipt->update([
                'status' => PurchaseReceiptStatus::Canceled->value,
                'canceled_at' => now(),
            ]);

            return $receipt->fresh(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function delete(PurchaseReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            if (! $receipt->isDraft()) {
                abort(422, 'Somente recebimentos em digitação podem ser excluídos.');
            }

            $receipt->delete();
        });
    }
}
