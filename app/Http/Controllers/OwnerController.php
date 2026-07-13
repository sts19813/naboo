<?php

namespace App\Http\Controllers;

use App\Models\DossierDeletedFile;
use App\Http\Requests\StoreOwnerRequest;
use App\Models\Owner;
use App\Models\OwnerDocument;
use App\Models\OwnerDocumentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OwnerController extends Controller
{
    private const DELETE_PERMISSION = 'propietarios.eliminar';

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $owners = Owner::query()
            ->withCount('properties')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('owners.index', [
            'owners' => $owners,
            'search' => $search,
            'ownerTypes' => Owner::OWNER_TYPE_LABELS,
            'paymentMethods' => Owner::PAYMENT_METHOD_LABELS,
        ]);
    }

    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Owner::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'owner_type' => $validated['owner_type'] ?? Owner::OWNER_INDIVIDUAL,
            'bank_name' => $validated['bank_name'] ?? null,
            'clabe' => $validated['clabe'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'payment_method' => $validated['payment_method'] ?? Owner::PAYMENT_METHOD_TRANSFER,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('owners.index')
            ->with('success', 'El propietario se creó correctamente.');
    }

    public function show(Owner $owner): View
    {
        $owner->load([
            'properties' => fn ($query) => $query
                ->with(['type:id,name', 'zone:id,name'])
                ->latest('properties.created_at'),
        ])->loadCount(['properties', 'documents']);

        return view('owners.show', [
            'owner' => $owner,
        ]);
    }

    public function edit(Owner $owner): View
    {
        return view('owners.edit', [
            'owner' => $owner,
            'ownerTypes' => Owner::OWNER_TYPE_LABELS,
            'paymentMethods' => Owner::PAYMENT_METHOD_LABELS,
        ]);
    }

    public function update(StoreOwnerRequest $request, Owner $owner): RedirectResponse
    {
        $validated = $request->validated();

        $owner->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'owner_type' => $validated['owner_type'] ?? Owner::OWNER_INDIVIDUAL,
            'bank_name' => $validated['bank_name'] ?? null,
            'clabe' => $validated['clabe'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'payment_method' => $validated['payment_method'] ?? Owner::PAYMENT_METHOD_TRANSFER,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('owners.index')
            ->with('success', 'El propietario se actualizó correctamente.');
    }

    public function destroy(Request $request, Owner $owner): RedirectResponse|JsonResponse
    {
        $this->ensureCanDeleteOwners($request);

        $propertyCount = $owner->properties()->count();
        $documentCount = $owner->documents()->count();

        DB::transaction(function () use ($request, $owner): void {
            $owner->loadMissing('documents.versions');

            $this->deleteOwnerDossierFiles($request, $owner);

            $owner->properties()->detach();

            foreach ($owner->documents as $document) {
                $document->versions()->delete();
                $document->delete();
            }

            $owner->delete();
        });

        $message = 'El propietario se eliminó correctamente.';

        if ($propertyCount > 0 || $documentCount > 0) {
            $message .= ' Sus propiedades quedaron sin este propietario y su expediente fue eliminado.';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => 'success',
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('owners.index')
            ->with('success', $message);
    }

    private function ensureCanDeleteOwners(Request $request): void
    {
        $user = $request->user();
        $isAdmin = $user?->hasRole('administrador') || $user?->hasRole('admin');

        if (!$isAdmin && !$user?->can(self::DELETE_PERMISSION)) {
            abort(403);
        }
    }

    private function deleteOwnerDossierFiles(Request $request, Owner $owner): void
    {
        foreach ($owner->documents as $document) {
            $loggedPaths = [];

            foreach ($document->versions as $version) {
                $this->deleteOwnerDossierFileAndLog($request, $owner, $document, $version);

                if (filled($version->file_path)) {
                    $loggedPaths[$version->file_path] = true;
                }
            }

            if (filled($document->file_path) && !isset($loggedPaths[$document->file_path])) {
                $this->deleteOwnerDossierFileAndLog($request, $owner, $document);
            }
        }
    }

    private function deleteOwnerDossierFileAndLog(
        Request $request,
        Owner $owner,
        OwnerDocument $document,
        ?OwnerDocumentVersion $version = null,
    ): void {
        $filePath = $version?->file_path ?? $document->file_path;
        $fileDeleted = false;

        if (filled($filePath) && Storage::disk('public')->exists($filePath)) {
            $fileDeleted = Storage::disk('public')->delete($filePath);
        }

        DossierDeletedFile::query()->create([
            'entity_type' => 'owner',
            'entity_id' => $owner->id,
            'entity_name' => $owner->name,
            'document_group' => 'owner',
            'document_id' => $document->id,
            'document_type' => $document->document_type,
            'document_label' => $document->label,
            'version_id' => $version?->id,
            'version_number' => $version?->version_number,
            'original_name' => $version?->original_name,
            'file_path' => $filePath,
            'mime_type' => $version?->mime_type,
            'file_size' => $version?->file_size,
            'file_deleted' => $fileDeleted,
            'delete_reason' => 'Propietario eliminado',
            'deleted_by_user_id' => $request->user()?->id,
            'deleted_at' => now(),
        ]);
    }
}
