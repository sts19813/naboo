<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Property;
use App\Models\PropertyInventoryArea;
use App\Models\PropertyInventoryPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use App\Models\PropertyInventoryItem;
use App\Models\PropertyInventoryItemPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryCheckController extends Controller
{
    public function index(Property $property): View
    {
        $property->load([
            'inventoryAreas.items.photos',
            'inventoryChecks.items',
        ]);

        $entryChecks = $property->inventoryChecks()
            ->where('type', InventoryCheck::TYPE_ENTRY)
            ->latest()
            ->paginate(10);

        $exitChecks = $property->inventoryChecks()
            ->where('type', InventoryCheck::TYPE_EXIT)
            ->latest()
            ->paginate(10);

        return view('inventory-checks.index', [
            'property' => $property,
            'entryChecks' => $entryChecks,
            'exitChecks' => $exitChecks,
        ]);
    }

    public function create(Property $property, string $type): View
    {
        if (!in_array($type, [InventoryCheck::TYPE_ENTRY, InventoryCheck::TYPE_EXIT])) {
            abort(400, 'Tipo de check inválido');
        }

        $property->load('inventoryAreas.items.photos');
        
        // Get available tenants for this property
        $tenants = \App\Models\Tenant::all();

        return view('inventory-checks.create', [
            'property' => $property,
            'type' => $type,
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:entry,exit'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check = $property->inventoryChecks()->create([
            'type' => $validated['type'],
            'tenant_id' => $validated['tenant_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => InventoryCheck::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        // Create items from all inventory items
        $allItems = $property->inventoryAreas()
            ->with('items')
            ->get()
            ->flatMap(fn($area) => $area->items);

        foreach ($allItems as $item) {
            $check->items()->create([
                'property_inventory_item_id' => $item->id,
                'item_name' => $item->name,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('inventory-checks.show', [$property, $check])
            ->with('success', 'Checklist creado correctamente.');
    }

    public function show(Property $property, InventoryCheck $check): View
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $check->load([
            'items.inventoryItem.photos',
            'items.inventoryItem.area',
            'tenant',
            'creator',
        ]);

        return view('inventory-checks.show', [
            'property' => $property,
            'check' => $check,
        ]);
    }

    public function updateItem(Request $request, Property $property, InventoryCheck $check, InventoryCheckItem $item)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,ok,damaged,missing'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $data = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(
                "properties/{$property->id}/checks/{$check->id}/items",
                'public'
            );
            $data['photo_path'] = $photoPath;
        }

        $item->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Elemento actualizado correctamente.']);
        }

        return back()->with('success', 'Elemento actualizado correctamente.');
    }

    public function bulkUpdateItems(Request $request, Property $property, InventoryCheck $check): RedirectResponse|JsonResponse
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.status' => ['required', 'in:pending,ok,damaged,missing'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $items = $check->items()->get()->keyBy('id');
        $updatedItems = [];

        DB::transaction(function () use ($validated, $items, &$updatedItems): void {
            foreach ($validated['items'] as $itemId => $itemData) {
                $inventoryCheckItem = $items->get((int) $itemId);
                if (!$inventoryCheckItem) {
                    continue;
                }

                $inventoryCheckItem->update([
                    'status' => $itemData['status'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $updatedItems[] = [
                    'id' => $inventoryCheckItem->id,
                    'status' => $inventoryCheckItem->status,
                    'notes' => $inventoryCheckItem->notes,
                ];
            }
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Checklist actualizado correctamente.',
                'items' => $updatedItems,
            ]);
        }

        return back()->with('success', 'Checklist actualizado correctamente.');
    }

    public function addItem(Request $request, Property $property, InventoryCheck $check)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'property_inventory_item_id' => ['required', 'exists:property_inventory_items,id'],
        ]);

        // Verify the item belongs to this property
        $inventoryItem = $property->inventoryAreas()
            ->with('items')
            ->get()
            ->flatMap(fn($area) => $area->items)
            ->firstWhere('id', $validated['property_inventory_item_id']);

        if (!$inventoryItem) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Elemento no encontrado'], 404);
            }
            abort(404, 'Elemento no encontrado');
        }

        // Check if item already exists in this check
        if ($check->items()->where('property_inventory_item_id', $inventoryItem->id)->exists()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Este elemento ya está en el checklist.'], 422);
            }
            return back()->with('error', 'Este elemento ya está en el checklist.');
        }

        $check->items()->create([
            'property_inventory_item_id' => $inventoryItem->id,
            'item_name' => $inventoryItem->name,
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Elemento agregado al checklist.']);
        }
        return back()->with('success', 'Elemento agregado al checklist.');
    }

    public function removeItem(Property $property, InventoryCheck $check, InventoryCheckItem $item): RedirectResponse
    {
        if ($check->property_id !== $property->id || $item->inventory_check_id !== $check->id) {
            abort(403, 'No autorizado');
        }

        $item->delete();

        return back()->with('success', 'Elemento removido del checklist.');
    }

    public function complete(Request $request, Property $property, InventoryCheck $check): RedirectResponse
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check->update([
            'status' => InventoryCheck::STATUS_COMPLETED,
            'notes' => $validated['notes'] ?? $check->notes,
            'completed_at' => now(),
        ]);

        return redirect()->route('inventory-checks.show', [$property, $check])
            ->with('success', 'Checklist completado correctamente.');
    }

    public function history(Property $property): View
    {
        $property->load('inventoryChecks');

        $checks = $property->inventoryChecks()
            ->with('items')
            ->latest('completed_at')
            ->paginate(20);

        return view('inventory-checks.history', [
            'property' => $property,
            'checks' => $checks,
        ]);
    }

    public function getItemHistory(Property $property, $itemId)
    {
 
        // Obtener todos los checks completados de esta propiedad
        $completedChecks = $property->inventoryChecks()
            ->where('status', InventoryCheck::STATUS_COMPLETED)
            ->with(['items' => function($query) use ($itemId) {
                $query->where('property_inventory_item_id', $itemId);
            }])
            ->get();

        // Obtener la foto actual del inventario
        $inventoryItem = $property->inventoryAreas()
            ->with('items.photos')
            ->get()
            ->flatMap(fn($area) => $area->items)
            ->firstWhere('id', $itemId);

        $history = [];

        // Agregar foto actual del inventario
        if ($inventoryItem && $inventoryItem->photos->isNotEmpty()) {
            $history[] = [
                'type' => 'inventory',
                'date' => $inventoryItem->created_at->format('d/m/Y H:i'),
                'check_type' => null,
                'status' => 'original',
                'photo_url' => \Illuminate\Support\Facades\Storage::url($inventoryItem->photos->first()->latestVersion->file_path),
                'notes' => null,
            ];
        }

        // Agregar fotos de checks completados
        foreach ($completedChecks as $check) {
            $checkItem = $check->items->first();
            if ($checkItem && $checkItem->photo_path) {
                $history[] = [
                    'type' => 'check',
                    'date' => $check->completed_at->format('d/m/Y H:i'),
                    'check_type' => $check->type,
                    'status' => $checkItem->status,
                    'photo_url' => \Illuminate\Support\Facades\Storage::url($checkItem->photo_path),
                    'notes' => $checkItem->notes,
                ];
            }
        }

        return response()->json($history);
    }

    public function exportPdf(Property $property)
    {
        $property->load([
            'inventoryAreas.photos',
            'inventoryAreas.items.photos.versions',
            'inventoryChecks.items',
            'tenant',
        ]);

        $itemIds = $property->inventoryAreas
            ->flatMap(fn(PropertyInventoryArea $area) => $area->items->pluck('id'))
            ->filter()
            ->values();

        $latestStatuses = collect();
        if ($itemIds->isNotEmpty()) {
            $latestStatuses = InventoryCheckItem::query()
                ->whereIn('property_inventory_item_id', $itemIds)
                ->whereHas('check', fn($query) => $query->where('property_id', $property->id))
                ->with('check:id,type,status,completed_at,created_at')
                ->orderByDesc('updated_at')
                ->get()
                ->groupBy('property_inventory_item_id')
                ->map(fn($rows) => $rows->first());
        }

        $pdf = Pdf::loadView('inventory-checks.export-pdf', [
            'property' => $property,
            'latestStatuses' => $latestStatuses,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $property->internal_name ?: 'propiedad');

        return $pdf->download('inventario_' . $safeName . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function addNewItem(Request $request, Property $property, InventoryCheck $check)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'area_name' => ['required', 'string', 'max:255'],
            'new_area_name' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        // Determinar el nombre del área
        $areaName = $validated['area_name'] === '__new__' ? $validated['new_area_name'] : $validated['area_name'];

        // Buscar o crear el área
        $area = $property->inventoryAreas()->firstOrCreate(
            ['name' => $areaName],
            ['name' => $areaName]
        );

        // Crear el nuevo elemento
        $inventoryItem = $area->items()->create([
            'name' => $validated['item_name'],
            'condition' => 'Nuevo elemento agregado durante check',
        ]);

        if ($request->hasFile('photo')) {
            $this->storeItemPhotos($request, $property, $inventoryItem, [$request->file('photo')]);
        }

        // Agregar el elemento al check
        $check->items()->create([
            'property_inventory_item_id' => $inventoryItem->id,
            'item_name' => $inventoryItem->name,
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Nuevo elemento creado y agregado al checklist.']);
        }
        return back()->with('success', 'Nuevo elemento creado y agregado al checklist.');
    }

    // Inventory Management Methods
    public function storeArea(Request $request, Property $property): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(
            $this->inventoryAreaRules(),
            $this->inventoryAreaMessages(),
            $this->inventoryAreaAttributes(),
        );

        $area = $property->inventoryAreas()->create([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $photos = $this->storeAreaPhotos($property, $area, $this->uploadedFiles($request, 'photos'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Área guardada correctamente.',
                'area' => $this->serializeArea($area->fresh(['photos', 'items.photos.latestVersion'])),
                'photos' => $photos,
            ]);
        }

        return back()->with('success', 'Área creada exitosamente.');
    }

    public function updateArea(Request $request, Property $property, PropertyInventoryArea $area): RedirectResponse|JsonResponse
    {
        $this->abortUnlessAreaBelongsToProperty($property, $area);

        $validated = $request->validate(
            $this->inventoryAreaRules(),
            $this->inventoryAreaMessages(),
            $this->inventoryAreaAttributes(),
        );

        $area->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $photos = $this->storeAreaPhotos($property, $area, $this->uploadedFiles($request, 'photos'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $photos ? 'Imagen guardada correctamente.' : 'Área actualizada correctamente.',
                'area' => $this->serializeArea($area->fresh(['photos', 'items.photos.latestVersion'])),
                'photos' => $photos,
            ]);
        }

        return back()->with('success', 'Área actualizada exitosamente.');
    }

    public function destroyArea(Request $request, Property $property, PropertyInventoryArea $area): RedirectResponse|JsonResponse
    {
        $this->abortUnlessAreaBelongsToProperty($property, $area);
        $area->load(['photos', 'items.photos.versions']);

        foreach ($area->photos as $photo) {
            $this->deleteStoragePath($photo->file_path);
        }
        foreach ($area->items as $item) {
            $this->deleteItemPhotoFiles($item->photos);
        }

        $area->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Área eliminada correctamente.']);
        }

        return back()->with('success', 'Área eliminada exitosamente.');
    }

    public function storeItem(Request $request, Property $property, PropertyInventoryArea $area): RedirectResponse|JsonResponse
    {
        $this->abortUnlessAreaBelongsToProperty($property, $area);

        $validated = $request->validate(
            $this->inventoryItemRules(),
            $this->inventoryItemMessages(),
            $this->inventoryItemAttributes(),
        );

        $item = $area->items()->create([
            'name' => $validated['name'],
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $photos = $this->storeItemPhotos($request, $property, $item, $this->uploadedFiles($request, 'photos'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Elemento guardado correctamente.',
                'item' => $this->serializeItem($item->fresh('photos.latestVersion')),
                'photos' => $photos,
            ]);
        }

        return back()->with('success', 'Elemento creado exitosamente.');
    }

    public function updateInventoryItem(Request $request, Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item): RedirectResponse|JsonResponse
    {
        $this->abortUnlessItemBelongsToArea($property, $area, $item);

        $validated = $request->validate(
            $this->inventoryItemRules(),
            $this->inventoryItemMessages(),
            $this->inventoryItemAttributes(),
        );

        $item->update([
            'name' => $validated['name'],
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $photos = $this->storeItemPhotos($request, $property, $item, $this->uploadedFiles($request, 'photos'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $photos ? 'Imagen guardada correctamente.' : 'Elemento actualizado correctamente.',
                'item' => $this->serializeItem($item->fresh('photos.latestVersion')),
                'photos' => $photos,
            ]);
        }

        return back()->with('success', 'Elemento actualizado exitosamente.');
    }

    public function destroyInventoryItem(Request $request, Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item): RedirectResponse|JsonResponse
    {
        $this->abortUnlessItemBelongsToArea($property, $area, $item);
        $item->load('photos.versions');
        $this->deleteItemPhotoFiles($item->photos);
        $item->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Elemento eliminado correctamente.']);
        }

        return back()->with('success', 'Elemento eliminado exitosamente.');
    }

    public function destroyAreaPhoto(Request $request, Property $property, PropertyInventoryArea $area, PropertyInventoryPhoto $photo): RedirectResponse|JsonResponse
    {
        $this->abortUnlessAreaBelongsToProperty($property, $area);
        if ((int) $photo->property_inventory_area_id !== (int) $area->id) {
            abort(403, 'No autorizado');
        }

        $this->deleteStoragePath($photo->file_path);
        $photo->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Imagen eliminada correctamente.']);
        }

        return back()->with('success', 'Imagen eliminada correctamente.');
    }

    public function destroyItemPhoto(Request $request, Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item, PropertyInventoryItemPhoto $photo): RedirectResponse|JsonResponse
    {
        $this->abortUnlessItemBelongsToArea($property, $area, $item);
        if ((int) $photo->property_inventory_item_id !== (int) $item->id) {
            abort(403, 'No autorizado');
        }

        $photo->load('versions');
        $this->deleteItemPhotoFiles([$photo]);
        $photo->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Imagen eliminada correctamente.']);
        }

        return back()->with('success', 'Imagen eliminada correctamente.');
    }

    private function inventoryAreaRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    private function inventoryAreaMessages(): array
    {
        return [
            'name.required' => 'El nombre del espacio es obligatorio.',
        ];
    }

    private function inventoryAreaAttributes(): array
    {
        return [
            'name' => 'nombre del espacio',
            'notes' => 'notas del espacio',
            'photos.*' => 'foto del espacio',
        ];
    }

    private function inventoryItemRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    private function inventoryItemMessages(): array
    {
        return [
            'name.required' => 'El nombre del item es obligatorio.',
        ];
    }

    private function inventoryItemAttributes(): array
    {
        return [
            'name' => 'nombre del item',
            'condition' => 'estado del item',
            'notes' => 'notas del item',
            'photos.*' => 'foto del item',
        ];
    }

    private function abortUnlessAreaBelongsToProperty(Property $property, PropertyInventoryArea $area): void
    {
        if ((int) $area->property_id !== (int) $property->id) {
            abort(403, 'No autorizado');
        }
    }

    private function abortUnlessItemBelongsToArea(Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item): void
    {
        $this->abortUnlessAreaBelongsToProperty($property, $area);
        if ((int) $item->property_inventory_area_id !== (int) $area->id) {
            abort(403, 'No autorizado');
        }
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key, []);
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return collect($files)
            ->filter(fn($file) => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, array<string, mixed>>
     */
    private function storeAreaPhotos(Property $property, PropertyInventoryArea $area, array $files): array
    {
        $storedPhotos = [];
        foreach ($files as $index => $photo) {
            $stored = $this->storeCompressedInventoryImage($photo, "properties/{$property->id}/inventory/{$area->id}");
            $photoRecord = $area->photos()->create([
                'file_path' => $stored['path'],
                'display_order' => (int) $area->photos()->count() + $index,
            ]);

            $storedPhotos[] = $this->serializeAreaPhoto($photoRecord);
        }

        return $storedPhotos;
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, array<string, mixed>>
     */
    private function storeItemPhotos(Request $request, Property $property, PropertyInventoryItem $item, array $files): array
    {
        $storedPhotos = [];
        foreach ($files as $photo) {
            $stored = $this->storeCompressedInventoryImage($photo, "properties/{$property->id}/inventory/items/{$item->id}");
            $photoRecord = $item->photos()->create([
                'name' => $photo->getClientOriginalName(),
                'status' => PropertyInventoryItemPhoto::STATUS_ACTIVE,
            ]);

            $photoRecord->versions()->create([
                'file_path' => $stored['path'],
                'file_name' => $photo->getClientOriginalName(),
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'uploaded_by' => $request->user()->id,
            ]);

            $storedPhotos[] = $this->serializeItemPhoto($photoRecord->fresh('latestVersion'));
        }

        return $storedPhotos;
    }

    private function serializeArea(PropertyInventoryArea $area): array
    {
        $area->loadMissing(['photos', 'items.photos.latestVersion']);

        return [
            'id' => $area->id,
            'name' => $area->name,
            'notes' => $area->notes,
            'photos' => $area->photos->map(fn($photo) => $this->serializeAreaPhoto($photo))->values()->all(),
            'items' => $area->items->map(fn($item) => $this->serializeItem($item))->values()->all(),
        ];
    }

    private function serializeItem(PropertyInventoryItem $item): array
    {
        $item->loadMissing('photos.latestVersion');

        return [
            'id' => $item->id,
            'name' => $item->name,
            'condition' => $item->condition,
            'notes' => $item->notes,
            'photos' => $item->photos->map(fn($photo) => $this->serializeItemPhoto($photo))->values()->all(),
        ];
    }

    private function serializeAreaPhoto(PropertyInventoryPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'url' => Storage::url($photo->file_path),
        ];
    }

    private function serializeItemPhoto(PropertyInventoryItemPhoto $photo): array
    {
        $photo->loadMissing('latestVersion');
        $path = $photo->latestVersion?->file_path;

        return [
            'id' => $photo->id,
            'url' => $path ? Storage::url($path) : null,
        ];
    }

    /**
     * @return array{path: string, mime_type: string, file_size: int}
     */
    private function storeCompressedInventoryImage(UploadedFile $photo, string $directory): array
    {
        $encoded = $this->encodeCompressedInventoryImage($photo);

        if ($encoded === null) {
            $path = $photo->store($directory, 'public');

            return [
                'path' => $path,
                'mime_type' => $photo->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => (int) ($photo->getSize() ?: 0),
            ];
        }

        $path = trim($directory, '/') . '/' . Str::uuid() . '.' . $encoded['extension'];
        Storage::disk('public')->put($path, $encoded['binary']);

        return [
            'path' => $path,
            'mime_type' => $encoded['mime_type'],
            'file_size' => strlen($encoded['binary']),
        ];
    }

    /**
     * @return array{binary: string, extension: string, mime_type: string}|null
     */
    private function encodeCompressedInventoryImage(UploadedFile $photo): ?array
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $sourcePath = $photo->getRealPath();
        if (!$sourcePath) {
            return null;
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return null;
        }

        $sourceWidth = (int) ($imageInfo[0] ?? 0);
        $sourceHeight = (int) ($imageInfo[1] ?? 0);
        $imageType = (int) ($imageInfo[2] ?? 0);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
        if (!$sourceImage) {
            return null;
        }

        $maxBytes = 512000;
        $maxDimension = 2200;
        $largestSide = max($sourceWidth, $sourceHeight);
        $baseScale = $largestSide > $maxDimension ? $maxDimension / $largestSide : 1;
        $baseWidth = max(1, (int) round($sourceWidth * $baseScale));
        $baseHeight = max(1, (int) round($sourceHeight * $baseScale));
        $bestMatch = null;

        try {
            foreach ([1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3] as $scale) {
                $targetWidth = max(1, (int) round($baseWidth * $scale));
                $targetHeight = max(1, (int) round($baseHeight * $scale));
                $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
                if (!$resizedImage) {
                    continue;
                }

                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

                foreach ([82, 76, 70, 64, 58, 52, 46, 40] as $quality) {
                    $encoded = $this->encodeImageBinary($resizedImage, $quality);
                    if ($encoded === null || $encoded['binary'] === '') {
                        continue;
                    }

                    if ($bestMatch === null || strlen($encoded['binary']) < strlen($bestMatch['binary'])) {
                        $bestMatch = $encoded;
                    }

                    if (strlen($encoded['binary']) <= $maxBytes) {
                        imagedestroy($resizedImage);

                        return $encoded;
                    }
                }

                imagedestroy($resizedImage);
            }
        } finally {
            imagedestroy($sourceImage);
        }

        return $bestMatch;
    }

    /**
     * @return array{binary: string, extension: string, mime_type: string}|null
     */
    private function encodeImageBinary($image, int $quality): ?array
    {
        if (function_exists('imagewebp')) {
            ob_start();
            $encoded = @imagewebp($image, null, $quality);
            $binary = (string) ob_get_clean();

            if ($encoded && $binary !== '') {
                return [
                    'binary' => $binary,
                    'extension' => 'webp',
                    'mime_type' => 'image/webp',
                ];
            }
        }

        ob_start();
        $encoded = @imagejpeg($image, null, $quality);
        $binary = (string) ob_get_clean();

        if (!$encoded || $binary === '') {
            return null;
        }

        return [
            'binary' => $binary,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
        ];
    }

    private function deleteItemPhotoFiles(iterable $photos): void
    {
        foreach ($photos as $photo) {
            foreach ($photo->versions ?? [] as $version) {
                $this->deleteStoragePath($version->file_path ?? null);
            }
        }
    }

    private function deleteStoragePath(?string $path): void
    {
        if (!filled($path)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
