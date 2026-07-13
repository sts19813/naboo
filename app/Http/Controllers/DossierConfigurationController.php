<?php

namespace App\Http\Controllers;

use App\Models\DossierDocumentRequirement;
use App\Models\OwnerDocument;
use App\Models\PropertyDocument;
use App\Models\TenantDocument;
use App\Services\DossierDocumentRequirementService;
use App\Support\DossierSettings;
use App\Support\DossierStorageUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DossierConfigurationController extends Controller
{
    private const CONFIGURE_PERMISSION = 'expedientes.configurar';

    public function __construct(private readonly DossierDocumentRequirementService $requirements)
    {
    }

    public function index(Request $request): View
    {
        $this->ensureAccess($request);

        return view('settings.dossiers.index', $this->viewData($request));
    }

    public function storage(Request $request): View
    {
        $this->ensureAccess($request);

        return view('settings.dossiers.storage', $this->storageViewData());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(array_keys(DossierDocumentRequirement::ENTITY_LABELS))],
            'label' => ['required', 'string', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $entityType = $validated['entity_type'];
        $nextOrder = ((int) DossierDocumentRequirement::query()
            ->where('entity_type', $entityType)
            ->max('sort_order')) + 10;

        DossierDocumentRequirement::query()->create([
            'entity_type' => $entityType,
            'document_type' => $this->requirements->buildDocumentType($entityType, $validated['label']),
            'label' => trim((string) $validated['label']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $nextOrder,
        ]);

        $this->requirements->flushCache();

        return $this->jsonModule($request, 'Documento configurado correctamente.');
    }

    public function update(Request $request, DossierDocumentRequirement $requirement): JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $requirement->update([
            'label' => trim((string) $validated['label']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? $requirement->sort_order,
        ]);

        $this->syncExistingDocumentLabels($requirement);
        $this->requirements->flushCache();

        return $this->jsonModule($request, 'Documento actualizado correctamente.');
    }

    public function destroy(Request $request, DossierDocumentRequirement $requirement): JsonResponse
    {
        $this->ensureAccess($request);

        $requirement->delete();
        $this->reorderEntity($requirement->entity_type);
        $this->requirements->flushCache();

        return $this->jsonModule($request, 'Documento eliminado de la configuración.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(array_keys(DossierDocumentRequirement::ENTITY_LABELS))],
            'items' => ['required', 'array'],
            'items.*' => ['integer', Rule::exists('dossier_document_requirements', 'id')],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach (array_values($validated['items']) as $index => $id) {
                DossierDocumentRequirement::query()
                    ->whereKey($id)
                    ->where('entity_type', $validated['entity_type'])
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        $this->requirements->flushCache();

        return $this->jsonModule($request, 'Orden actualizado correctamente.');
    }

    public function updateStorage(Request $request): JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'storage_limit_gb' => ['required', 'numeric', 'min:1', 'max:10240'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:51200'],
            'storage_warning_percent' => ['required', 'integer', 'min:50', 'max:100'],
        ], [
            'storage_limit_gb.min' => 'La capacidad contratada debe ser de al menos 1 GB.',
            'max_file_size_mb.min' => 'El tamano maximo por archivo debe ser de al menos 1 MB.',
        ]);

        DossierSettings::setMany([
            'dossiers.storage_limit_gb' => $validated['storage_limit_gb'],
            'dossiers.max_file_size_mb' => $validated['max_file_size_mb'],
            'dossiers.storage_warning_percent' => $validated['storage_warning_percent'],
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Configuración de almacenamiento actualizada.',
                'type' => 'success',
                'html' => view('settings.dossiers.partials.storage-module', $this->storageViewData())->render(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de almacenamiento actualizada.',
            'redirect' => route('settings.dossiers.storage'),
        ]);
    }

    private function viewData(Request $request): array
    {
        $activeEntity = $request->query('entity', DossierDocumentRequirement::ENTITY_PROPERTY);
        if (!array_key_exists($activeEntity, DossierDocumentRequirement::ENTITY_LABELS)) {
            $activeEntity = DossierDocumentRequirement::ENTITY_PROPERTY;
        }

        $requirementsByEntity = collect(array_keys(DossierDocumentRequirement::ENTITY_LABELS))
            ->mapWithKeys(fn (string $entityType): array => [
                $entityType => $this->requirements->forEntity($entityType, false),
            ]);

        return [
            'entityLabels' => DossierDocumentRequirement::ENTITY_LABELS,
            'activeEntity' => $activeEntity,
            'requirementsByEntity' => $requirementsByEntity,
        ];
    }

    private function storageViewData(): array
    {
        return [
            'dossierStorage' => app(DossierStorageUsage::class)->summary(),
            'dossierUploadLimit' => DossierSettings::uploadLimit(),
            'dossierStorageSettings' => [
                'storage_limit_gb' => DossierSettings::storageLimitGb(),
                'max_file_size_mb' => DossierSettings::maxFileSizeMb(),
                'storage_warning_percent' => DossierSettings::storageWarningPercent(),
            ],
        ];
    }

    private function jsonModule(Request $request, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'type' => 'success',
            'html' => view('settings.dossiers.partials.module', $this->viewData($request))->render(),
        ]);
    }

    private function reorderEntity(string $entityType): void
    {
        DossierDocumentRequirement::query()
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->values()
            ->each(function (DossierDocumentRequirement $requirement, int $index): void {
                $requirement->update(['sort_order' => ($index + 1) * 10]);
            });
    }

    private function syncExistingDocumentLabels(DossierDocumentRequirement $requirement): void
    {
        $modelClass = match ($requirement->entity_type) {
            DossierDocumentRequirement::ENTITY_PROPERTY => PropertyDocument::class,
            DossierDocumentRequirement::ENTITY_TENANT => TenantDocument::class,
            DossierDocumentRequirement::ENTITY_OWNER => OwnerDocument::class,
            default => null,
        };

        if (!$modelClass) {
            return;
        }

        $modelClass::query()
            ->where('document_type', $requirement->document_type)
            ->update(['label' => $requirement->label]);
    }

    private function ensureAccess(Request $request): void
    {
        $user = $request->user();
        $isAdmin = $user?->hasRole('administrador') || $user?->hasRole('admin');

        if (!$isAdmin && !$user?->can(self::CONFIGURE_PERMISSION)) {
            abort(403);
        }
    }
}
