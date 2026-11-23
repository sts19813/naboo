<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyAirbnbController extends Controller
{
    public function update(Request $request, Property $property, PropertyDeletionService $payments): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['administrador', 'admin']), 403);
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $validated['enabled'];

        $property = DB::transaction(function () use ($property, $payments, $enabled): Property {
            $locked = Property::query()->lockForUpdate()->findOrFail($property->id);
            if ($enabled) {
                abort_if($locked->archived_at || $locked->tenant_id || in_array($locked->status, [Property::STATUS_OCCUPIED, Property::STATUS_RENTED], true), 422, 'La propiedad debe estar activa y sin inquilino.');
                abort_if(! $locked->airbnb_enabled_at && $locked->status !== Property::STATUS_AVAILABLE, 422, 'La propiedad debe estar disponible antes de habilitar Airbnb.');
                abort_if($payments->hasPendingPayments($locked), 422, 'La propiedad tiene cobranza abierta.');
                $locked->forceFill(['airbnb_enabled_at' => $locked->airbnb_enabled_at ?? now(), 'status' => Property::STATUS_BLOCKED])->save();
            } else {
                abort_if(filled($locked->airbnb_external_property_id), 409, 'Libera la propiedad desde la integración antes de desactivarla.');
                if ($locked->airbnb_enabled_at) {
                    $locked->forceFill(['airbnb_enabled_at' => null, 'status' => $locked->status === Property::STATUS_BLOCKED ? Property::STATUS_AVAILABLE : $locked->status])->save();
                }
            }

            return $locked;
        });

        return response()->json(['enabled' => (bool) $property->airbnb_enabled_at]);
    }
}
