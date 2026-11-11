<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyDeletionService
{
    public const PENDING_PAYMENT_MESSAGE = 'No es posible eliminar la propiedad mientras tenga cargos o pagos pendientes, parciales o en validación.';

    public function hasPendingPayments(Property $property): bool
    {
        $hasOpenCharges = DB::table('charges')
            ->where('property_id', $property->id)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->exists();

        if ($hasOpenCharges) {
            return true;
        }

        return DB::table('charge_payments')
            ->join('charges', 'charges.id', '=', 'charge_payments.charge_id')
            ->where('charges.property_id', $property->id)
            ->whereIn('charge_payments.status', [ChargePayment::STATUS_PENDING, ChargePayment::STATUS_PENDING_VALIDATION])
            ->exists();
    }

    public function delete(Property $property): void
    {
        $storage = DB::transaction(function () use ($property): array {
            $lockedProperty = Property::query()->lockForUpdate()->findOrFail($property->id);

            if ($this->hasPendingPayments($lockedProperty)) {
                throw ValidationException::withMessages(['property' => self::PENDING_PAYMENT_MESSAGE]);
            }

            $propertyId = $lockedProperty->id;
            $chargeIds = DB::table('charges')->where('property_id', $propertyId)->pluck('id');
            $paymentIds = $chargeIds->isEmpty() ? collect() : DB::table('charge_payments')->whereIn('charge_id', $chargeIds)->pluck('id');
            $expenseIds = DB::table('expenses')->where('property_id', $propertyId)->pluck('id');
            $recurringItemIds = DB::table('recurring_expense_items')->where('property_id', $propertyId)->pluck('id');
            $ticketIds = DB::table('maintenance_tickets')->where('property_id', $propertyId)->pluck('id');
            $publicPaths = $this->collectPublicPaths($lockedProperty, $chargeIds, $expenseIds, $recurringItemIds, $ticketIds);
            $localPaths = $this->collectLocalPaths($propertyId);

            $this->removeCashCutItems($paymentIds);
            $this->removeMaintenanceCutItems($ticketIds);

            if (Schema::hasTable('dossier_deleted_files')) {
                DB::table('dossier_deleted_files')->where('entity_type', 'property')->where('entity_id', $propertyId)->delete();
            }

            DB::table('charges')->whereIn('id', $chargeIds)->delete();
            DB::table('expenses')->whereIn('id', $expenseIds)->delete();
            DB::table('recurring_expense_items')->whereIn('id', $recurringItemIds)->delete();
            DB::table('maintenance_tickets')->whereIn('id', $ticketIds)->delete();
            DB::table('properties')->where('id', $propertyId)->delete();

            return [
                'public_paths' => $publicPaths,
                'local_paths' => $localPaths,
                'public_directories' => collect([
                    "properties/{$propertyId}",
                    ...$chargeIds->map(fn ($id): string => "charges/{$id}")->all(),
                    ...$expenseIds->map(fn ($id): string => "expenses/{$id}")->all(),
                    ...$recurringItemIds->map(fn ($id): string => "recurring-expense-items/{$id}")->all(),
                    ...$ticketIds->map(fn ($id): string => "maintenance/{$id}")->all(),
                ])->all(),
                'local_directories' => ["properties/{$propertyId}"],
            ];
        });

        Storage::disk('public')->delete($storage['public_paths']);
        Storage::disk('local')->delete($storage['local_paths']);
        foreach ($storage['public_directories'] as $directory) {
            Storage::disk('public')->deleteDirectory($directory);
        }
        foreach ($storage['local_directories'] as $directory) {
            Storage::disk('local')->deleteDirectory($directory);
        }
    }

    private function collectPublicPaths(Property $property, Collection $chargeIds, Collection $expenseIds, Collection $recurringItemIds, Collection $ticketIds): array
    {
        $propertyId = $property->id;
        $paths = collect([$property->facade_photo_path])
            ->merge(DB::table('property_documents')->where('property_id', $propertyId)->pluck('file_path'))
            ->merge(DB::table('property_document_versions')->join('property_documents', 'property_documents.id', '=', 'property_document_versions.property_document_id')->where('property_documents.property_id', $propertyId)->pluck('property_document_versions.file_path'))
            ->merge(DB::table('property_inventory_photos')->join('property_inventory_areas', 'property_inventory_areas.id', '=', 'property_inventory_photos.property_inventory_area_id')->where('property_inventory_areas.property_id', $propertyId)->pluck('property_inventory_photos.file_path'))
            ->merge(DB::table('property_inventory_item_photo_versions')->join('property_inventory_item_photos', 'property_inventory_item_photos.id', '=', 'property_inventory_item_photo_versions.property_inventory_item_photo_id')->join('property_inventory_items', 'property_inventory_items.id', '=', 'property_inventory_item_photos.property_inventory_item_id')->join('property_inventory_areas', 'property_inventory_areas.id', '=', 'property_inventory_items.property_inventory_area_id')->where('property_inventory_areas.property_id', $propertyId)->pluck('property_inventory_item_photo_versions.file_path'))
            ->merge(DB::table('inventory_check_items')->join('inventory_checks', 'inventory_checks.id', '=', 'inventory_check_items.inventory_check_id')->where('inventory_checks.property_id', $propertyId)->pluck('inventory_check_items.photo_path'));

        if ($expenseIds->isNotEmpty()) {
            $paths = $paths->merge(DB::table('expense_files')->whereIn('expense_id', $expenseIds)->pluck('path'));
        }
        if ($recurringItemIds->isNotEmpty()) {
            $paths = $paths->merge(DB::table('recurring_expense_item_files')->whereIn('recurring_expense_item_id', $recurringItemIds)->pluck('path'));
        }
        if ($ticketIds->isNotEmpty()) {
            $paths = $paths->merge(DB::table('maintenance_ticket_files')->whereIn('ticket_id', $ticketIds)->pluck('path'));
        }
        if ($chargeIds->isNotEmpty()) {
            $receiptColumns = ['receipt_path'];
            if (Schema::hasColumn('charge_payments', 'receipt_paths')) {
                $receiptColumns[] = 'receipt_paths';
            }
            DB::table('charge_payments')->whereIn('charge_id', $chargeIds)->get($receiptColumns)->each(function ($payment) use (&$paths): void {
                $paths->push($payment->receipt_path ?? null);
                $receiptPaths = $payment->receipt_paths ?? null;
                if (is_string($receiptPaths)) {
                    $receiptPaths = json_decode($receiptPaths, true);
                }
                if (is_array($receiptPaths)) {
                    $paths = $paths->merge($receiptPaths);
                }
            });
        }

        return $this->normalizePaths($paths);
    }

    private function collectLocalPaths(int $propertyId): array
    {
        if (! Schema::hasTable('property_logbook_attachments')) {
            return [];
        }

        return $this->normalizePaths(DB::table('property_logbook_attachments')->join('property_logbook_entries', 'property_logbook_entries.id', '=', 'property_logbook_attachments.property_logbook_entry_id')->where('property_logbook_entries.property_id', $propertyId)->pluck('property_logbook_attachments.path'));
    }

    private function normalizePaths(Collection $paths): array
    {
        return $paths->filter(fn ($path): bool => is_string($path) && filled($path))
            ->reject(fn (string $path): bool => str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ->unique()->values()->all();
    }

    private function removeCashCutItems(Collection $paymentIds): void
    {
        if ($paymentIds->isEmpty() || ! Schema::hasTable('cash_cut_items')) {
            return;
        }
        $cutIds = DB::table('cash_cut_items')->whereIn('charge_payment_id', $paymentIds)->pluck('cash_cut_id')->unique();
        DB::table('cash_cut_items')->whereIn('charge_payment_id', $paymentIds)->delete();
        foreach ($cutIds as $cutId) {
            $summary = DB::table('cash_cut_items')->where('cash_cut_id', $cutId)->selectRaw('COUNT(*) as item_count, COALESCE(SUM(amount), 0) as total')->first();
            if ((int) $summary->item_count === 0) {
                DB::table('cash_cuts')->where('id', $cutId)->delete();
            } else {
                DB::table('cash_cuts')->where('id', $cutId)->update(['payment_count' => (int) $summary->item_count, 'grand_total' => $summary->total, 'updated_at' => now()]);
            }
        }
    }

    private function removeMaintenanceCutItems(Collection $ticketIds): void
    {
        if ($ticketIds->isEmpty() || ! Schema::hasTable('maintenance_cut_items')) {
            return;
        }
        $cutIds = DB::table('maintenance_cut_items')->whereIn('ticket_id', $ticketIds)->pluck('maintenance_cut_id')->unique();
        DB::table('maintenance_cut_items')->whereIn('ticket_id', $ticketIds)->delete();
        foreach ($cutIds as $cutId) {
            $summary = DB::table('maintenance_cut_items')->where('maintenance_cut_id', $cutId)->selectRaw('COUNT(*) as item_count, COALESCE(SUM(labor_total), 0) as labor, COALESCE(SUM(material_total), 0) as material, COALESCE(SUM(grand_total), 0) as total')->first();
            if ((int) $summary->item_count === 0) {
                DB::table('maintenance_cuts')->where('id', $cutId)->delete();
            } else {
                DB::table('maintenance_cuts')->where('id', $cutId)->update(['ticket_count' => (int) $summary->item_count, 'labor_total' => $summary->labor, 'material_total' => $summary->material, 'grand_total' => $summary->total, 'updated_at' => now()]);
            }
        }
    }
}
