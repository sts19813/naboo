<?php

namespace App\Http\Controllers;

use App\Services\CentralBillingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request, CentralBillingService $billing): View
    {
        $user = $request->user();
        abort_unless($user?->hasAnyRole(['administrador', 'admin']), 403);

        $snapshot = $billing->snapshot();
        $central = null;
        $syncError = null;

        try {
            $central = $billing->report();
        } catch (ConnectionException) {
            $syncError = 'No fue posible conectar con la facturación central. Intenta nuevamente.';
        } catch (Throwable $exception) {
            report($exception);
            $syncError = 'No fue posible actualizar el importe de la suscripción.';
        }

        return view('subscription.index', [
            'snapshot' => $snapshot,
            'central' => $central,
            'syncError' => $syncError,
            'billingUrl' => $billing->billingUrl(),
        ]);
    }
}
