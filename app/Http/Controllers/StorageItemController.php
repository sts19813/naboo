<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorageItemRequest;
use App\Models\StorageItem;
use App\Models\StorageItemLog;
use App\Models\StorageWarehouse;
use App\Models\StorageZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'view' => ['nullable', 'in:grid,table'],
        ]);
        [$defaultWarehouseId, $defaultZoneId] = $this->ensureCatalogDefaults();

        $search = trim((string) ($filters['q'] ?? ''));
        $viewMode = (string) ($filters['view'] ?? 'table');

        $items = StorageItem::query()
            ->with(['warehouse:id,name,location,maps_url', 'zone:id,storage_warehouse_id,name'])
            ->orderByDesc('created_at')
            ->get();
        $warehouses = StorageWarehouse::query()->with('zones:id,storage_warehouse_id,name')->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'location', 'maps_url', 'is_default']);
        $zones = StorageZone::query()->orderByDesc('is_default')->orderBy('name')->get(['id', 'storage_warehouse_id', 'name', 'is_default']);

        return view('storage_items.index', [
            'items' => $items,
            'viewMode' => $viewMode,
            'search' => $search,
            'warehouses' => $warehouses,
            'zones' => $zones,
            'defaultWarehouseId' => $defaultWarehouseId,
            'defaultZoneId' => $defaultZoneId,
        ]);
    }

    public function create()
    {
        [$defaultWarehouseId, $defaultZoneId] = $this->ensureCatalogDefaults();
        $warehouses = StorageWarehouse::query()->with('zones:id,storage_warehouse_id,name')->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'location', 'maps_url', 'is_default']);
        $zones = StorageZone::query()->orderByDesc('is_default')->orderBy('name')->get(['id', 'storage_warehouse_id', 'name', 'is_default']);

        return view('storage_items.create', compact('warehouses', 'zones', 'defaultWarehouseId', 'defaultZoneId'));
    }

    public function store(StorageItemRequest $request)
    {
        [$defaultWarehouseId, $defaultZoneId] = $this->ensureCatalogDefaults();
        $data = $request->only(['storage_warehouse_id', 'storage_zone_id', 'product_type', 'name', 'description', 'brand', 'condition', 'quantity']);
        if (!$data['storage_warehouse_id'] ?? false) {
            $data['storage_warehouse_id'] = $defaultWarehouseId;
        }
        if (!$data['storage_zone_id'] ?? false) {
            $data['storage_zone_id'] = $defaultZoneId;
        }
        [$data['storage_warehouse_id'], $data['storage_zone_id']] = $this->resolveWarehouseAndZone((int) $data['storage_warehouse_id'], (int) $data['storage_zone_id']);

        $uploadedPhoto = $request->file('photo') ?? $request->file('photo_camera');
        if ($uploadedPhoto) {
            $data['photo'] = $this->saveCompressedImage($uploadedPhoto);
        }

        $item = StorageItem::create($data);

        StorageItemLog::create([
            'storage_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'note' => null,
            'changes' => $item->toArray(),
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item creado correctamente');
    }

    public function show(StorageItem $storage_item)
    {
        return view('storage_items.show', ['item' => $storage_item->load(['warehouse:id,name,location,maps_url', 'zone:id,name'])]);
    }

    public function edit(StorageItem $storage_item)
    {
        [$defaultWarehouseId, $defaultZoneId] = $this->ensureCatalogDefaults();
        $warehouses = StorageWarehouse::query()->with('zones:id,storage_warehouse_id,name')->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'location', 'maps_url', 'is_default']);
        $zones = StorageZone::query()->orderByDesc('is_default')->orderBy('name')->get(['id', 'storage_warehouse_id', 'name', 'is_default']);

        return view('storage_items.edit', [
            'item' => $storage_item,
            'warehouses' => $warehouses,
            'zones' => $zones,
            'defaultWarehouseId' => $defaultWarehouseId,
            'defaultZoneId' => $defaultZoneId,
        ]);
    }

    public function update(StorageItemRequest $request, StorageItem $storage_item)
    {
        [$defaultWarehouseId, $defaultZoneId] = $this->ensureCatalogDefaults();
        $before = $storage_item->toArray();

        $data = $request->only(['storage_warehouse_id', 'storage_zone_id', 'product_type', 'name', 'description', 'brand', 'condition', 'quantity']);
        if (!$data['storage_warehouse_id'] ?? false) {
            $data['storage_warehouse_id'] = $defaultWarehouseId;
        }
        if (!$data['storage_zone_id'] ?? false) {
            $data['storage_zone_id'] = $defaultZoneId;
        }
        [$data['storage_warehouse_id'], $data['storage_zone_id']] = $this->resolveWarehouseAndZone((int) $data['storage_warehouse_id'], (int) $data['storage_zone_id']);

        if ($request->hasFile('photo')) {
            if ($storage_item->photo) {
                Storage::disk('public')->delete($storage_item->photo);
            }
            $data['photo'] = $this->saveCompressedImage($request->file('photo'));
        }

        $storage_item->update($data);

        $changes = array_diff_assoc($storage_item->toArray(), $before);

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'note' => null,
            'changes' => $changes ?: null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item actualizado');
    }

    public function destroy(StorageItem $storage_item)
    {
        // Soft delete - no elimina la foto
        $storage_item->delete();

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'soft_deleted',
            'note' => null,
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item marcado como eliminado');
    }

    // Extra endpoint to add a note / move action for traceability
    public function addNote(Request $request, StorageItem $storage_item)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'note',
            'note' => $request->input('note'),
            'changes' => null,
        ]);

        return back()->with('success', 'Nota agregada');
    }

    // Ver items eliminados
    public function trashed()
    {
        $items = StorageItem::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(20);
        return view('storage_items.trashed', compact('items'));
    }

    // Restaurar item eliminado
    public function restore($id)
    {
        $item = StorageItem::withTrashed()->findOrFail($id);
        $item->restore();

        StorageItemLog::create([
            'storage_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'restored',
            'note' => null,
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item restaurado');
    }

    // Obtener la lista de eliminados en una modal (AJAX)
    public function deleteWithNote(Request $request, StorageItem $storage_item)
    {
        $request->validate(['delete_note' => 'required|string|max:2000']);

        $storage_item->delete();

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'soft_deleted',
            'note' => $request->input('delete_note'),
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item eliminado con nota');
    }

    public function storeWarehouse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:storage_warehouses,name'],
            'location' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
        ]);
        $warehouse = StorageWarehouse::query()->create([
            'name' => trim((string) $validated['name']),
            'location' => filled($validated['location'] ?? null) ? trim((string) $validated['location']) : null,
            'maps_url' => filled($validated['maps_url'] ?? null) ? trim((string) $validated['maps_url']) : null,
            'is_default' => false,
        ]);
        $zone = StorageZone::query()->create([
            'storage_warehouse_id' => $warehouse->id,
            'name' => 'Zona principal',
            'is_default' => true,
        ]);

        return response()->json([
            'success' => true,
            'warehouse' => $warehouse->only(['id', 'name', 'location', 'maps_url', 'is_default']),
            'default_zone' => $zone->only(['id', 'storage_warehouse_id', 'name', 'is_default']),
        ]);
    }

    public function updateWarehouse(Request $request, StorageWarehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:storage_warehouses,name,' . $warehouse->id],
            'location' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
        ]);
        $warehouse->update([
            'name' => trim((string) $validated['name']),
            'location' => filled($validated['location'] ?? null) ? trim((string) $validated['location']) : null,
            'maps_url' => filled($validated['maps_url'] ?? null) ? trim((string) $validated['maps_url']) : null,
        ]);

        return redirect()->back()->with('success', 'Almacén actualizado');
    }

    public function storeZone(Request $request): JsonResponse
    {
        [$defaultWarehouseId] = $this->ensureCatalogDefaults();
        $validated = $request->validate([
            'storage_warehouse_id' => ['nullable', 'integer', 'exists:storage_warehouses,id'],
            'name' => ['required', 'string', 'max:190'],
        ]);
        $warehouseId = (int) ($validated['storage_warehouse_id'] ?? $defaultWarehouseId);
        $existing = StorageZone::query()
            ->where('storage_warehouse_id', $warehouseId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $validated['name']))])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'zone' => $existing->only(['id', 'storage_warehouse_id', 'name', 'is_default']),
            ]);
        }

        $zone = StorageZone::query()->create([
            'storage_warehouse_id' => $warehouseId,
            'name' => trim((string) $validated['name']),
            'is_default' => false,
        ]);

        return response()->json([
            'success' => true,
            'zone' => $zone->only(['id', 'storage_warehouse_id', 'name', 'is_default']),
        ]);
    }

    public function updateZone(Request $request, StorageZone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'storage_warehouse_id' => ['required', 'integer', 'exists:storage_warehouses,id'],
            'name' => ['required', 'string', 'max:190'],
        ]);
        $warehouseId = (int) $validated['storage_warehouse_id'];
        $name = trim((string) $validated['name']);
        $exists = StorageZone::query()
            ->where('id', '!=', $zone->id)
            ->where('storage_warehouse_id', $warehouseId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Ya existe una zona con ese nombre en el almacén seleccionado.');
        }

        $zone->update([
            'storage_warehouse_id' => $warehouseId,
            'name' => $name,
        ]);

        return redirect()->back()->with('success', 'Zona actualizada');
    }

    public function destroyZone(StorageZone $zone): RedirectResponse
    {
        DB::transaction(function () use ($zone): void {
            $warehouseId = (int) $zone->storage_warehouse_id;
            $targetZone = StorageZone::query()
                ->where('storage_warehouse_id', $warehouseId)
                ->where('id', '!=', $zone->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            if (!$targetZone) {
                $targetZone = StorageZone::query()->create([
                    'storage_warehouse_id' => $warehouseId,
                    'name' => 'Zona principal',
                    'is_default' => true,
                ]);
            }

            StorageItem::query()
                ->where('storage_zone_id', $zone->id)
                ->update([
                    'storage_zone_id' => $targetZone->id,
                    'storage_warehouse_id' => $targetZone->storage_warehouse_id,
                ]);

            $wasDefault = (bool) $zone->is_default;
            $zone->delete();

            if ($wasDefault) {
                StorageZone::query()
                    ->where('storage_warehouse_id', $targetZone->storage_warehouse_id)
                    ->update(['is_default' => false]);
                $targetZone->update(['is_default' => true]);
            }
        });

        return redirect()->back()->with('success', 'Zona eliminada');
    }

    private function ensureCatalogDefaults(): array
    {
        $warehouse = StorageWarehouse::query()->where('is_default', true)->first();
        if (!$warehouse) {
            $warehouse = StorageWarehouse::query()->first();
        }
        if (!$warehouse) {
            $warehouse = StorageWarehouse::query()->create([
                'name' => 'Almacén principal',
                'location' => null,
                'maps_url' => null,
                'is_default' => true,
            ]);
        } elseif (!$warehouse->is_default) {
            $warehouse->update(['is_default' => true]);
        }

        $zone = StorageZone::query()
            ->where('storage_warehouse_id', $warehouse->id)
            ->where('is_default', true)
            ->first();
        if (!$zone) {
            $zone = StorageZone::query()
                ->where('storage_warehouse_id', $warehouse->id)
                ->first();
        }
        if (!$zone) {
            $zone = StorageZone::query()->create([
                'storage_warehouse_id' => $warehouse->id,
                'name' => 'Zona principal',
                'is_default' => true,
            ]);
        } elseif (!$zone->is_default) {
            $zone->update(['is_default' => true]);
        }

        StorageItem::query()
            ->whereNull('storage_warehouse_id')
            ->update(['storage_warehouse_id' => $warehouse->id]);
        StorageItem::query()
            ->whereNull('storage_zone_id')
            ->update(['storage_zone_id' => $zone->id]);

        return [(int) $warehouse->id, (int) $zone->id];
    }

    private function resolveWarehouseAndZone(int $warehouseId, int $zoneId): array
    {
        $warehouse = StorageWarehouse::query()->find($warehouseId);
        if (!$warehouse) {
            [$warehouseId, $zoneId] = $this->ensureCatalogDefaults();
            return [$warehouseId, $zoneId];
        }

        $zone = StorageZone::query()
            ->where('id', $zoneId)
            ->where('storage_warehouse_id', $warehouse->id)
            ->first();
        if ($zone) {
            return [$warehouse->id, $zone->id];
        }

        $fallbackZone = StorageZone::query()
            ->where('storage_warehouse_id', $warehouse->id)
            ->where('is_default', true)
            ->first() ?: StorageZone::query()
                ->where('storage_warehouse_id', $warehouse->id)
                ->first();
        if (!$fallbackZone) {
            $fallbackZone = StorageZone::query()->create([
                'storage_warehouse_id' => $warehouse->id,
                'name' => 'Zona principal',
                'is_default' => true,
            ]);
        }

        return [$warehouse->id, (int) $fallbackZone->id];
    }

    protected function saveCompressedImage($file)
    {
        $filename = 'storage_items/'.time().'_'.Str::random(8).'.jpg';

        if (class_exists('\Intervention\Image\ImageManagerStatic')) {
            $img = \Intervention\Image\ImageManagerStatic::make($file)->orientate();
            $img->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk('public')->put($filename, (string) $img->encode('jpg', 75));
            return $filename;
        }

        // Fallback using GD
        $raw = file_get_contents($file->getRealPath());
        $im = imagecreatefromstring($raw);
        if ($im === false) {
            return null;
        }

        // Resize if wider than 1200
        $width = imagesx($im);
        $height = imagesy($im);
        if ($width > 1200) {
            $newWidth = 1200;
            $newHeight = intval($height * ($newWidth / $width));
            $tmp = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);
            $im = $tmp;
        }

        ob_start();
        imagejpeg($im, null, 75);
        $data = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($filename, $data);

        return $filename;
    }
}

