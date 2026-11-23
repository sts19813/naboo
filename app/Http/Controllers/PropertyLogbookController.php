<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyLogbookAttachment;
use App\Models\PropertyLogbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyLogbookController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $this->ensurePropertyAccessible($property);
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:10000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:10', 'required_without:note'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:10240'],
        ], [
            'note.required_without' => 'Escribe una nota o agrega al menos una imagen.',
            'attachments.required_without' => 'Escribe una nota o agrega al menos una imagen.',
            'attachments.max' => 'Puedes agregar hasta 10 imágenes por nota.',
            'attachments.*.image' => 'Cada archivo debe ser una imagen válida.',
            'attachments.*.max' => 'Cada imagen no puede superar 10 MB.',
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $property, $validated, &$storedPaths): void {
            $entry = $property->logbookEntries()->create([
                'user_id' => $request->user()->id,
                'note' => filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null,
            ]);

            foreach ((array) $request->file('attachments', []) as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store("properties/{$property->id}/logbook/{$entry->id}", 'local');
                if ($path === false) {
                    throw new \RuntimeException('No fue posible guardar la imagen.');
                }
                $storedPaths[] = $path;
                $entry->attachments()->create([
                    'path' => $path,
                    'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => (int) ($file->getSize() ?: 0),
                ]);
            }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Nota agregada a la bitácora.')
            ->withFragment('tab-logbook');
    }

    public function preview(Property $property, PropertyLogbookAttachment $attachment): StreamedResponse
    {
        $this->ensurePropertyAccessible($property);
        $this->ensureAttachmentBelongsToProperty($property, $attachment);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"']
        );
    }

    public function download(Property $property, PropertyLogbookAttachment $attachment): StreamedResponse
    {
        $this->ensurePropertyAccessible($property);
        $this->ensureAttachmentBelongsToProperty($property, $attachment);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Property $property, PropertyLogbookEntry $entry): RedirectResponse
    {
        $this->ensurePropertyAccessible($property);
        abort_unless((int) $entry->property_id === (int) $property->id, 404);

        $paths = $entry->attachments()->pluck('path')->all();

        DB::transaction(fn() => $entry->delete());
        Storage::disk('local')->delete($paths);

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Nota eliminada de la bitácora.')
            ->withFragment('tab-logbook');
    }

    private function ensureAttachmentBelongsToProperty(Property $property, PropertyLogbookAttachment $attachment): void
    {
        $belongsToProperty = $attachment->entry()
            ->where('property_id', $property->id)
            ->exists();

        abort_unless($belongsToProperty, 404);
    }

    private function ensurePropertyAccessible(Property $property): void
    {
        if ($property->archived_at) {
            abort_unless(request()->user()?->can('propiedades.archivar'), 403);
        }
    }
}
