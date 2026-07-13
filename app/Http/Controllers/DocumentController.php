<?php

namespace App\Http\Controllers;

use App\Models\DossierDeletedFile;
use App\Models\Owner;
use App\Models\OwnerDocument;
use App\Models\OwnerDocumentVersion;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyDocumentVersion;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\TenantDocumentVersion;
use App\Services\DossierDocumentRequirementService;
use App\Support\DossierSettings;
use App\Support\DossierStorageUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    private const DELETE_FILES_PERMISSION = 'expedientes.eliminar_archivos';

    public function __construct(private readonly DossierDocumentRequirementService $requirements)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['all', 'expired'])],
        ]);

        $activeView = (string) ($filters['view'] ?? 'all');

        $documents = $this->buildDocumentsCollection();
        $documents = $documents->whereNotNull('file_url')->sortByDesc('updated_at')->values();

        $stats = [
            'total' => $documents->count(),
            'properties' => $documents->where('entity_type', 'property')->count(),
            'tenants' => $documents->where('entity_type', 'tenant')->count(),
            'owners' => $documents->where('entity_type', 'owner')->count(),
            'expired' => $documents->filter(fn (array $document) => $document['is_expired'])->count(),
        ];

        $currentDocuments = $documents
            ->reject(fn (array $document) => $document['is_expired'])
            ->values();
        $expiredDocuments = $documents
            ->filter(fn (array $document) => $document['is_expired'])
            ->values();

        return view('documents.index', [
            'currentDocuments' => $currentDocuments,
            'expiredDocuments' => $expiredDocuments,
            'filters' => [
                'view' => $activeView,
            ],
            'stats' => $stats,
            'dossierStorage' => app(DossierStorageUsage::class)->summary(),
        ]);
    }

    public function expired(Request $request): View
    {
        $request->merge(['view' => 'expired']);

        return $this->index($request);
    }

    public function propertyDossier(Property $property): View
    {
        $this->ensurePropertyDocuments($property);

        $property->load([
            'type',
            'zone',
            'documents.versions' => fn ($query) => $query->orderByDesc('version_number'),
        ]);

        $requiredDocuments = $this->requirements->labelsForEntity('property');
        $documents = collect($requiredDocuments)
            ->map(function (string $label, string $documentType) use ($property) {
                return $property->documents->firstWhere('document_type', $documentType)
                    ?? new PropertyDocument([
                        'document_type' => $documentType,
                        'label' => $label,
                        'status' => PropertyDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $property->documents
            ->whereNotIn('document_type', array_keys($requiredDocuments))
            ->values();

        return view('documents.property-dossier', [
            'property' => $property,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
            'canDeleteDossierFiles' => $this->canDeleteDossierFiles(request()),
            'dossierStorage' => app(DossierStorageUsage::class)->summary(),
            'dossierUploadLimit' => DossierSettings::uploadLimit(),
        ]);
    }

    public function tenantDossier(Tenant $tenant): View
    {
        $this->ensureTenantDocuments($tenant);

        $tenant->load([
            'documents.versions' => fn ($query) => $query->orderByDesc('version_number'),
        ]);

        $requiredDocuments = $this->requirements->labelsForEntity('tenant');
        $documents = collect($requiredDocuments)
            ->map(function (string $label, string $documentType) use ($tenant) {
                return $tenant->documents->firstWhere('document_type', $documentType)
                    ?? new TenantDocument([
                        'document_type' => $documentType,
                        'label' => $label,
                        'status' => TenantDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $tenant->documents
            ->whereNotIn('document_type', array_keys($requiredDocuments))
            ->values();

        return view('documents.tenant-dossier', [
            'tenant' => $tenant,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
            'canDeleteDossierFiles' => $this->canDeleteDossierFiles(request()),
            'dossierStorage' => app(DossierStorageUsage::class)->summary(),
            'dossierUploadLimit' => DossierSettings::uploadLimit(),
        ]);
    }

    public function ownerDossier(Owner $owner): View
    {
        $this->ensureOwnerDocuments($owner);

        $owner->load([
            'documents.versions' => fn ($query) => $query->orderByDesc('version_number'),
        ]);

        $requiredDocuments = $this->requirements->labelsForEntity('owner');
        $documents = collect($requiredDocuments)
            ->map(function (string $label, string $documentType) use ($owner) {
                return $owner->documents->firstWhere('document_type', $documentType)
                    ?? new OwnerDocument([
                        'document_type' => $documentType,
                        'label' => $label,
                        'status' => OwnerDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $owner->documents
            ->whereNotIn('document_type', array_keys($requiredDocuments))
            ->values();

        return view('documents.owner-dossier', [
            'owner' => $owner,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
            'canDeleteDossierFiles' => $this->canDeleteDossierFiles(request()),
            'dossierStorage' => app(DossierStorageUsage::class)->summary(),
            'dossierUploadLimit' => DossierSettings::uploadLimit(),
        ]);
    }

    public function deletedFilesLog(Request $request): View
    {
        if (!$request->user()?->can('expedientes.ver_bitacora_eliminados') && !$request->user()?->can(self::DELETE_FILES_PERMISSION)) {
            abort(403);
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'entity_type' => ['nullable', Rule::in(['property', 'tenant', 'owner'])],
            'document_group' => ['nullable', Rule::in(['property', 'tenant', 'owner'])],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $entityType = (string) ($filters['entity_type'] ?? '');
        $documentGroup = (string) ($filters['document_group'] ?? '');

        $deletedFiles = DossierDeletedFile::query()
            ->with('deletedBy:id,name,email')
            ->when($entityType !== '', fn($query) => $query->where('entity_type', $entityType))
            ->when($documentGroup !== '', fn($query) => $query->where('document_group', $documentGroup))
            ->when($search !== '', function ($query) use ($search): void {
                $like = "%{$search}%";
                $query->where(function ($inner) use ($like): void {
                    $inner->where('entity_name', 'like', $like)
                        ->orWhere('document_label', 'like', $like)
                        ->orWhere('document_type', 'like', $like)
                        ->orWhere('original_name', 'like', $like)
                        ->orWhere('file_path', 'like', $like)
                        ->orWhere('delete_reason', 'like', $like);
                });
            })
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('documents.deleted-files-log', [
            'deletedFiles' => $deletedFiles,
            'filters' => [
                'q' => $search,
                'entity_type' => $entityType,
                'document_group' => $documentGroup,
            ],
        ]);
    }

    public function uploadPropertyDocument(Request $request, Property $property, string $documentType): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->documentUploadRules());

        $document = $property->documents()->where('document_type', $documentType)->first();
        $requiredLabel = $this->requirements->labelFor('property', $documentType);
        $isRequiredDocument = filled($requiredLabel);

        if (!$document && !$isRequiredDocument) {
            abort(404);
        }

        if (!$document && $isRequiredDocument) {
            $document = $property->documents()->create([
                'document_type' => $documentType,
                'label' => $requiredLabel,
                'status' => PropertyDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }

        if ($isRequiredDocument) {
            $document->update([
                'label' => $requiredLabel,
            ]);
        }

        $file = $validated['file'];
        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("properties/{$property->id}/documents", 'public');
        $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        $document->update([
            'file_path' => $storedPath,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? $document->expires_at,
        ]);

        return $this->uploadResponse($request, 'Documento de propiedad actualizado. Se genero una nueva version.');
    }

    public function uploadTenantDocument(Request $request, Tenant $tenant, string $documentType): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->documentUploadRules());

        $document = $tenant->documents()->where('document_type', $documentType)->first();
        $requiredLabel = $this->requirements->labelFor('tenant', $documentType);
        $isRequiredDocument = filled($requiredLabel);

        if (!$document && !$isRequiredDocument) {
            abort(404);
        }

        if (!$document && $isRequiredDocument) {
            $document = $tenant->documents()->create([
                'document_type' => $documentType,
                'label' => $requiredLabel,
                'status' => TenantDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }

        if ($isRequiredDocument) {
            $document->update([
                'label' => $requiredLabel,
            ]);
        }

        $file = $validated['file'];
        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("tenants/{$tenant->id}/documents", 'public');
        $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        $document->update([
            'file_path' => $storedPath,
            'status' => TenantDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? $document->expires_at,
        ]);

        return $this->uploadResponse($request, 'Documento de inquilino actualizado. Se genero una nueva version.');
    }

    public function uploadOwnerDocument(Request $request, Owner $owner, string $documentType): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->documentUploadRules());

        $document = $owner->documents()->where('document_type', $documentType)->first();
        $requiredLabel = $this->requirements->labelFor('owner', $documentType);
        $isRequiredDocument = filled($requiredLabel);

        if (!$document && !$isRequiredDocument) {
            abort(404);
        }

        if (!$document && $isRequiredDocument) {
            $document = $owner->documents()->create([
                'document_type' => $documentType,
                'label' => $requiredLabel,
                'status' => OwnerDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }

        if ($isRequiredDocument) {
            $document->update([
                'label' => $requiredLabel,
            ]);
        }

        $file = $validated['file'];
        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("owners/{$owner->id}/documents", 'public');
        $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        $document->update([
            'file_path' => $storedPath,
            'status' => OwnerDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? $document->expires_at,
        ]);

        return $this->uploadResponse($request, 'Documento de propietario actualizado. Se genero una nueva version.');
    }

    public function updatePropertyDocumentMetadata(Request $request, Property $property, string $documentType): RedirectResponse|JsonResponse
    {
        $document = $property->documents()->where('document_type', $documentType)->firstOrFail();

        return $this->updateDocumentMetadata($request, $document, 'Documento de propiedad actualizado.');
    }

    public function updateTenantDocumentMetadata(Request $request, Tenant $tenant, string $documentType): RedirectResponse|JsonResponse
    {
        $document = $tenant->documents()->where('document_type', $documentType)->firstOrFail();

        return $this->updateDocumentMetadata($request, $document, 'Documento de inquilino actualizado.');
    }

    public function updateOwnerDocumentMetadata(Request $request, Owner $owner, string $documentType): RedirectResponse|JsonResponse
    {
        $document = $owner->documents()->where('document_type', $documentType)->firstOrFail();

        return $this->updateDocumentMetadata($request, $document, 'Documento de propietario actualizado.');
    }

    public function storeCustomPropertyDocument(Request $request, Property $property): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:150'],
            'file' => $this->documentFileRules(),
            'expires_at' => ['nullable', 'date'],
        ]);

        $file = $validated['file'];
        $label = $this->resolveCustomDocumentLabel($validated['label'] ?? null, $file->getClientOriginalName());
        $documentType = $this->buildCustomDocumentType(
            existingTypes: $property->documents()->pluck('document_type')->all(),
            label: $label,
        );

        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("properties/{$property->id}/documents", 'public');

        $document = $property->documents()->create([
            'document_type' => $documentType,
            'label' => $label,
            'file_path' => $storedPath,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return $this->uploadResponse($request, 'Documento personalizado agregado al expediente de la propiedad.');
    }

    public function storeCustomTenantDocument(Request $request, Tenant $tenant): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:150'],
            'file' => $this->documentFileRules(),
            'expires_at' => ['nullable', 'date'],
        ]);

        $file = $validated['file'];
        $label = $this->resolveCustomDocumentLabel($validated['label'] ?? null, $file->getClientOriginalName());
        $documentType = $this->buildCustomDocumentType(
            existingTypes: $tenant->documents()->pluck('document_type')->all(),
            label: $label,
        );

        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("tenants/{$tenant->id}/documents", 'public');

        $document = $tenant->documents()->create([
            'document_type' => $documentType,
            'label' => $label,
            'file_path' => $storedPath,
            'status' => TenantDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return $this->uploadResponse($request, 'Documento personalizado agregado al expediente del inquilino.');
    }

    public function storeCustomOwnerDocument(Request $request, Owner $owner): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:150'],
            'file' => $this->documentFileRules(),
            'expires_at' => ['nullable', 'date'],
        ]);

        $file = $validated['file'];
        $label = $this->resolveCustomDocumentLabel($validated['label'] ?? null, $file->getClientOriginalName());
        $documentType = $this->buildCustomDocumentType(
            existingTypes: $owner->documents()->pluck('document_type')->all(),
            label: $label,
        );

        $this->ensureDossierStorageCapacity((int) $file->getSize());
        $storedPath = $file->store("owners/{$owner->id}/documents", 'public');

        $document = $owner->documents()->create([
            'document_type' => $documentType,
            'label' => $label,
            'file_path' => $storedPath,
            'status' => OwnerDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return $this->uploadResponse($request, 'Documento personalizado agregado al expediente del propietario.');
    }

    public function destroyPropertyDocument(Request $request, Property $property, string $documentType): RedirectResponse
    {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $property->documents()->where('document_type', $documentType)->firstOrFail();
        $isRequired = $this->requirements->isConfigured('property', $documentType);
        $this->deleteAllDocumentVersions(
            request: $request,
            entityType: 'property',
            entityId: $property->id,
            entityName: $property->internal_name,
            documentGroup: 'property',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versions: $document->versions()->get(),
            reason: $request->input('delete_reason'),
        );
        $document->versions()->delete();

        if ($isRequired) {
            $document->update([
                'file_path' => null,
                'uploaded_at' => null,
                'expires_at' => null,
                'status' => PropertyDocument::STATUS_PENDING,
            ]);
        } else {
            $document->delete();
        }

        return back()->with('success', 'Documento eliminado del expediente de propiedad.');
    }

    public function destroyTenantDocument(Request $request, Tenant $tenant, string $documentType): RedirectResponse
    {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $tenant->documents()->where('document_type', $documentType)->firstOrFail();
        $isRequired = $this->requirements->isConfigured('tenant', $documentType);
        $this->deleteAllDocumentVersions(
            request: $request,
            entityType: 'tenant',
            entityId: $tenant->id,
            entityName: $tenant->full_name,
            documentGroup: 'tenant',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versions: $document->versions()->get(),
            reason: $request->input('delete_reason'),
        );
        $document->versions()->delete();

        if ($isRequired) {
            $document->update([
                'file_path' => null,
                'uploaded_at' => null,
                'expires_at' => null,
                'status' => TenantDocument::STATUS_PENDING,
            ]);
        } else {
            $document->delete();
        }

        return back()->with('success', 'Documento eliminado del expediente de inquilino.');
    }

    public function destroyOwnerDocument(Request $request, Owner $owner, string $documentType): RedirectResponse
    {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $owner->documents()->where('document_type', $documentType)->firstOrFail();
        $isRequired = $this->requirements->isConfigured('owner', $documentType);
        $this->deleteAllDocumentVersions(
            request: $request,
            entityType: 'owner',
            entityId: $owner->id,
            entityName: $owner->name,
            documentGroup: 'owner',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versions: $document->versions()->get(),
            reason: $request->input('delete_reason'),
        );
        $document->versions()->delete();

        if ($isRequired) {
            $document->update([
                'file_path' => null,
                'uploaded_at' => null,
                'expires_at' => null,
                'status' => OwnerDocument::STATUS_PENDING,
            ]);
        } else {
            $document->delete();
        }

        return back()->with('success', 'Documento eliminado del expediente de propietario.');
    }

    public function destroyPropertyDocumentVersion(
        Request $request,
        Property $property,
        string $documentType,
        PropertyDocumentVersion $version,
    ): RedirectResponse {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $property->documents()->where('document_type', $documentType)->firstOrFail();
        if ((int) $version->property_document_id !== (int) $document->id) {
            abort(404);
        }

        $this->deleteSinglePropertyVersion($request, $property, $document, $version);

        return back()->with('success', 'Versión eliminada del expediente de propiedad.');
    }

    public function destroyTenantDocumentVersion(
        Request $request,
        Tenant $tenant,
        string $documentType,
        TenantDocumentVersion $version,
    ): RedirectResponse {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $tenant->documents()->where('document_type', $documentType)->firstOrFail();
        if ((int) $version->tenant_document_id !== (int) $document->id) {
            abort(404);
        }

        $this->deleteSingleTenantVersion($request, $tenant, $document, $version);

        return back()->with('success', 'Versión eliminada del expediente de inquilino.');
    }

    public function destroyOwnerDocumentVersion(
        Request $request,
        Owner $owner,
        string $documentType,
        OwnerDocumentVersion $version,
    ): RedirectResponse {
        $this->ensureDeleteDossierFilesPermission($request);

        $document = $owner->documents()->where('document_type', $documentType)->firstOrFail();
        if ((int) $version->owner_document_id !== (int) $document->id) {
            abort(404);
        }

        $this->deleteSingleOwnerVersion($request, $owner, $document, $version);

        return back()->with('success', 'Versión eliminada del expediente de propietario.');
    }

    private function buildDocumentsCollection(): Collection
    {
        $propertyDocuments = PropertyDocument::query()
            ->with(['property:id,uuid,internal_name', 'latestVersion'])
            ->withCount('versions')
            ->get()
            ->map(fn (PropertyDocument $document) => $this->mapPropertyDocument($document));

        $tenantDocuments = TenantDocument::query()
            ->with(['tenant:id,uuid,full_name', 'latestVersion'])
            ->withCount('versions')
            ->get()
            ->map(fn (TenantDocument $document) => $this->mapTenantDocument($document));

        $ownerDocuments = OwnerDocument::query()
            ->with(['owner:id,uuid,name', 'latestVersion'])
            ->withCount('versions')
            ->get()
            ->map(fn (OwnerDocument $document) => $this->mapOwnerDocument($document));

        return $propertyDocuments->concat($tenantDocuments)->concat($ownerDocuments)->values();
    }

    private function mapPropertyDocument(PropertyDocument $document): array
    {
        return [
            'id' => 'property-' . $document->id,
            'label' => $document->label,
            'document_type' => $document->document_type,
            'entity_type' => 'property',
            'entity_type_label' => 'Propiedad',
            'entity_name' => $document->property?->internal_name ?? 'Propiedad eliminada',
            'entity_url' => $document->property ? route('dossiers.properties.show', $document->property) : null,
            'expires_at' => $document->expires_at,
            'is_expired' => $document->expires_at?->lt(today()) ?? false,
            'file_name' => $document->latestVersion?->original_name,
            'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            'versions_count' => $document->versions_count,
            'updated_at' => $document->updated_at,
        ];
    }

    private function mapTenantDocument(TenantDocument $document): array
    {
        return [
            'id' => 'tenant-' . $document->id,
            'label' => $document->label,
            'document_type' => $document->document_type,
            'entity_type' => 'tenant',
            'entity_type_label' => 'Inquilino',
            'entity_name' => $document->tenant?->full_name ?? 'Inquilino eliminado',
            'entity_url' => $document->tenant ? route('dossiers.tenants.show', $document->tenant) : null,
            'expires_at' => $document->expires_at,
            'is_expired' => $document->expires_at?->lt(today()) ?? false,
            'file_name' => $document->latestVersion?->original_name,
            'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            'versions_count' => $document->versions_count,
            'updated_at' => $document->updated_at,
        ];
    }

    private function mapOwnerDocument(OwnerDocument $document): array
    {
        return [
            'id' => 'owner-' . $document->id,
            'label' => $document->label,
            'document_type' => $document->document_type,
            'entity_type' => 'owner',
            'entity_type_label' => 'Propietario',
            'entity_name' => $document->owner?->name ?? 'Propietario eliminado',
            'entity_url' => $document->owner ? route('dossiers.owners.show', $document->owner) : null,
            'expires_at' => $document->expires_at,
            'is_expired' => $document->expires_at?->lt(today()) ?? false,
            'file_name' => $document->latestVersion?->original_name,
            'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            'versions_count' => $document->versions_count,
            'updated_at' => $document->updated_at,
        ];
    }

    private function ensurePropertyDocuments(Property $property): void
    {
        foreach ($this->requirements->labelsForEntity('property') as $documentType => $label) {
            $existingDocument = $property->documents()->where('document_type', $documentType)->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $property->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => PropertyDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }
    }

    private function ensureTenantDocuments(Tenant $tenant): void
    {
        foreach ($this->requirements->labelsForEntity('tenant') as $documentType => $label) {
            $existingDocument = $tenant->documents()->where('document_type', $documentType)->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $tenant->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => TenantDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }
    }

    private function ensureOwnerDocuments(Owner $owner): void
    {
        foreach ($this->requirements->labelsForEntity('owner') as $documentType => $label) {
            $existingDocument = $owner->documents()->where('document_type', $documentType)->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $owner->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => OwnerDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }
    }

    private function buildCustomDocumentType(array $existingTypes, string $label): string
    {
        $base = 'custom_' . Str::slug($label, '_');
        if ($base === 'custom_') {
            $base = 'custom_documento';
        }

        $candidate = $base;
        $counter = 2;

        while (in_array($candidate, $existingTypes, true)) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveCustomDocumentLabel(?string $label, string $originalFileName): string
    {
        $label = trim((string) $label);

        return $label !== '' ? $label : $originalFileName;
    }

    private function ensureDeleteDossierFilesPermission(Request $request): void
    {
        if (!$this->canDeleteDossierFiles($request)) {
            abort(403);
        }
    }

    private function canDeleteDossierFiles(Request $request): bool
    {
        return (bool) $request->user()?->can(self::DELETE_FILES_PERMISSION);
    }

    private function documentUploadRules(): array
    {
        return [
            'file' => $this->documentFileRules(),
            'expires_at' => ['nullable', 'date'],
        ];
    }

    private function documentFileRules(): array
    {
        return [
            'required',
            'file',
            'mimes:pdf,jpg,jpeg,png,zip',
            'max:' . DossierSettings::uploadLimit()['effective_kilobytes'],
        ];
    }

    private function ensureDossierStorageCapacity(int $fileSize): void
    {
        $storage = app(DossierStorageUsage::class)->summary();

        if ($storage['limit_bytes'] > 0 && $fileSize > $storage['available_bytes']) {
            throw ValidationException::withMessages([
                'file' => 'No hay espacio suficiente en el plan contratado. Disponible: ' . $storage['available_label'] . '.',
            ]);
        }
    }

    private function uploadResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => 'success',
                'reload' => true,
            ]);
        }

        return back()->with('success', $message);
    }

    private function updateDocumentMetadata(
        Request $request,
        PropertyDocument|TenantDocument|OwnerDocument $document,
        string $message,
    ): RedirectResponse|JsonResponse {
        $latestVersion = $document->versions()->orderByDesc('version_number')->first();

        if (!$latestVersion) {
            throw ValidationException::withMessages([
                'file_name' => 'No hay un archivo vigente para editar.',
            ]);
        }

        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:180'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $normalizedFileName = $this->normalizeDisplayFileName(
            trim((string) $validated['file_name']),
            (string) $latestVersion->original_name,
        );

        $latestVersion->update([
            'original_name' => $normalizedFileName,
        ]);

        $document->update([
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return $this->uploadResponse($request, $message);
    }

    private function normalizeDisplayFileName(string $requestedName, string $currentName): string
    {
        $requestedName = trim($requestedName);
        if ($requestedName === '') {
            return $currentName;
        }

        if (str_contains($requestedName, '.')) {
            return $requestedName;
        }

        $currentExtension = pathinfo($currentName, PATHINFO_EXTENSION);

        return $currentExtension !== ''
            ? $requestedName . '.' . $currentExtension
            : $requestedName;
    }

    private function deleteSinglePropertyVersion(
        Request $request,
        Property $property,
        PropertyDocument $document,
        PropertyDocumentVersion $version,
    ): void {
        $this->deleteFileAndLog(
            request: $request,
            entityType: 'property',
            entityId: $property->id,
            entityName: $property->internal_name,
            documentGroup: 'property',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versionId: $version->id,
            versionNumber: $version->version_number,
            originalName: $version->original_name,
            filePath: $version->file_path,
            mimeType: $version->mime_type,
            fileSize: $version->file_size,
            reason: $request->input('delete_reason'),
        );
        $version->delete();
        $this->refreshPropertyDocumentAfterVersionDelete($document);
    }

    private function deleteSingleTenantVersion(
        Request $request,
        Tenant $tenant,
        TenantDocument $document,
        TenantDocumentVersion $version,
    ): void {
        $this->deleteFileAndLog(
            request: $request,
            entityType: 'tenant',
            entityId: $tenant->id,
            entityName: $tenant->full_name,
            documentGroup: 'tenant',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versionId: $version->id,
            versionNumber: $version->version_number,
            originalName: $version->original_name,
            filePath: $version->file_path,
            mimeType: $version->mime_type,
            fileSize: $version->file_size,
            reason: $request->input('delete_reason'),
        );
        $version->delete();
        $this->refreshTenantDocumentAfterVersionDelete($document);
    }

    private function deleteSingleOwnerVersion(
        Request $request,
        Owner $owner,
        OwnerDocument $document,
        OwnerDocumentVersion $version,
    ): void {
        $this->deleteFileAndLog(
            request: $request,
            entityType: 'owner',
            entityId: $owner->id,
            entityName: $owner->name,
            documentGroup: 'owner',
            documentId: $document->id,
            documentType: $document->document_type,
            documentLabel: $document->label,
            versionId: $version->id,
            versionNumber: $version->version_number,
            originalName: $version->original_name,
            filePath: $version->file_path,
            mimeType: $version->mime_type,
            fileSize: $version->file_size,
            reason: $request->input('delete_reason'),
        );
        $version->delete();
        $this->refreshOwnerDocumentAfterVersionDelete($document);
    }

    private function refreshPropertyDocumentAfterVersionDelete(PropertyDocument $document): void
    {
        $latest = $document->versions()->orderByDesc('version_number')->first();
        if (!$latest) {
            $isRequired = $this->requirements->isConfigured('property', $document->document_type);
            if ($isRequired) {
                $document->update([
                    'file_path' => null,
                    'uploaded_at' => null,
                    'expires_at' => null,
                    'status' => PropertyDocument::STATUS_PENDING,
                ]);
            } else {
                $document->delete();
            }

            return;
        }

        $document->update([
            'file_path' => $latest->file_path,
            'uploaded_at' => $latest->uploaded_at,
            'status' => PropertyDocument::STATUS_UPLOADED,
        ]);
    }

    private function refreshTenantDocumentAfterVersionDelete(TenantDocument $document): void
    {
        $latest = $document->versions()->orderByDesc('version_number')->first();
        if (!$latest) {
            $isRequired = $this->requirements->isConfigured('tenant', $document->document_type);
            if ($isRequired) {
                $document->update([
                    'file_path' => null,
                    'uploaded_at' => null,
                    'expires_at' => null,
                    'status' => TenantDocument::STATUS_PENDING,
                ]);
            } else {
                $document->delete();
            }

            return;
        }

        $document->update([
            'file_path' => $latest->file_path,
            'uploaded_at' => $latest->uploaded_at,
            'status' => TenantDocument::STATUS_UPLOADED,
        ]);
    }

    private function refreshOwnerDocumentAfterVersionDelete(OwnerDocument $document): void
    {
        $latest = $document->versions()->orderByDesc('version_number')->first();
        if (!$latest) {
            $isRequired = $this->requirements->isConfigured('owner', $document->document_type);
            if ($isRequired) {
                $document->update([
                    'file_path' => null,
                    'uploaded_at' => null,
                    'expires_at' => null,
                    'status' => OwnerDocument::STATUS_PENDING,
                ]);
            } else {
                $document->delete();
            }

            return;
        }

        $document->update([
            'file_path' => $latest->file_path,
            'uploaded_at' => $latest->uploaded_at,
            'status' => OwnerDocument::STATUS_UPLOADED,
        ]);
    }

    private function deleteAllDocumentVersions(
        Request $request,
        string $entityType,
        int $entityId,
        ?string $entityName,
        string $documentGroup,
        int $documentId,
        string $documentType,
        ?string $documentLabel,
        Collection $versions,
        ?string $reason,
    ): void {
        foreach ($versions as $version) {
            $this->deleteFileAndLog(
                request: $request,
                entityType: $entityType,
                entityId: $entityId,
                entityName: $entityName,
                documentGroup: $documentGroup,
                documentId: $documentId,
                documentType: $documentType,
                documentLabel: $documentLabel,
                versionId: (int) $version->id,
                versionNumber: (int) $version->version_number,
                originalName: $version->original_name,
                filePath: $version->file_path,
                mimeType: $version->mime_type,
                fileSize: $version->file_size,
                reason: $reason,
            );
        }
    }

    private function deleteFileAndLog(
        Request $request,
        string $entityType,
        int $entityId,
        ?string $entityName,
        string $documentGroup,
        int $documentId,
        string $documentType,
        ?string $documentLabel,
        ?int $versionId,
        ?int $versionNumber,
        ?string $originalName,
        ?string $filePath,
        ?string $mimeType,
        ?int $fileSize,
        ?string $reason,
    ): void {
        $fileDeleted = false;
        if (filled($filePath) && Storage::disk('public')->exists($filePath)) {
            $fileDeleted = Storage::disk('public')->delete($filePath);
        }

        DossierDeletedFile::query()->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'document_group' => $documentGroup,
            'document_id' => $documentId,
            'document_type' => $documentType,
            'document_label' => $documentLabel,
            'version_id' => $versionId,
            'version_number' => $versionNumber,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file_deleted' => $fileDeleted,
            'delete_reason' => filled($reason) ? trim((string) $reason) : null,
            'deleted_by_user_id' => $request->user()?->id,
            'deleted_at' => now(),
        ]);
    }
}
