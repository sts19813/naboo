<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferProofRequest;
use App\Mail\ChargeCompletedMail;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Support\NotificationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class ChargePaymentController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $charge = $this->findChargeByToken($token)->loadMissing([
            'tenant:id,full_name,email',
            'property.owners:id,name,phone,email,bank_name,clabe,account_holder',
        ]);

        if ($charge->status === Charge::STATUS_PAID) {
            return response()
                ->view('charges.public-paid', ['charge' => $charge]);
        }

        return response()
            ->view('charges.public-pay', [
                'charge' => $charge,
                'bankOwner' => $charge->property?->owners->first(),
            ]);
    }

    public function createCheckoutSession(Request $request, string $token): RedirectResponse
    {
        $charge = $this->findChargeByToken($token)->loadMissing(['tenant:id,full_name,email', 'property:id,internal_name']);

        if (!in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL], true) || $charge->outstanding_amount <= 0) {
            return redirect()
                ->route('charges.public.show', ['token' => $charge->payment_token])
                ->with('error', 'Este cargo ya no requiere pago.');
        }

        $stripeSecret = (string) config('services.stripe.secret');
        if ($stripeSecret === '') {
            return redirect()
                ->route('charges.public.show', ['token' => $charge->payment_token])
                ->with('error', 'Stripe no esta configurado en este ambiente.');
        }

        Stripe::setApiKey($stripeSecret);

        $amountCents = (int) round($charge->outstanding_amount * 100);
        $currency = strtolower((string) config('services.stripe.currency', 'mxn'));
        $description = sprintf(
            '%s | %s/%s',
            $charge->type_label,
            str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT),
            $charge->period_year,
        );

        $checkoutPayload = [
            'mode' => 'payment',
            'success_url' => route('charges.public.success', ['token' => $charge->payment_token]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('charges.public.show', ['token' => $charge->payment_token]) . '?cancelled=1',
            'payment_method_types' => ['card'],
            'client_reference_id' => (string) $charge->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => $charge->concept,
                        'description' => $description,
                    ],
                ],
            ]],
            'metadata' => [
                'charge_id' => (string) $charge->id,
                'charge_uuid' => $charge->uuid,
                'charge_token' => $charge->payment_token,
            ],
        ];

        if (filled($charge->tenant?->email)) {
            $checkoutPayload['customer_email'] = $charge->tenant->email;
        }

        $session = Session::create($checkoutPayload);

        ChargePayment::updateOrCreate(
            ['stripe_checkout_session_id' => $session->id],
            [
                'charge_id' => $charge->id,
                'amount' => $charge->outstanding_amount,
                'currency' => $currency,
                'status' => ChargePayment::STATUS_PENDING,
                'source' => ChargePayment::SOURCE_STRIPE,
                'payment_method' => ChargePayment::METHOD_CARD,
                'payment_date' => now()->toDateString(),
                'payload' => $session->toArray(),
            ],
        );

        return redirect()->away($session->url);
    }

    public function storeTransferProof(StoreTransferProofRequest $request, string $token): RedirectResponse
    {
        $charge = $this->findChargeByToken($token)->loadMissing(['tenant:id,full_name,email', 'property:id,internal_name']);

        if (!in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION], true) || $charge->outstanding_amount <= 0) {
            return redirect()
                ->route('charges.public.show', ['token' => $charge->payment_token])
                ->with('error', 'Este cargo ya no requiere pago.');
        }

        $validated = $request->validated();
        $amount = (float) $validated['amount'];
        if ($amount > $charge->outstanding_amount) {
            return redirect()
                ->route('charges.public.show', ['token' => $charge->payment_token])
                ->with('error', 'El monto reportado no puede ser mayor al saldo pendiente.');
        }

        DB::transaction(function () use ($request, $validated, $charge, $amount): void {
            $receiptPath = $request->file('receipt')->store("charges/{$charge->id}/public-proofs", 'public');

            $charge->payments()->create([
                'amount' => $amount,
                'currency' => strtolower((string) config('services.stripe.currency', 'mxn')),
                'status' => ChargePayment::STATUS_PENDING_VALIDATION,
                'source' => ChargePayment::SOURCE_PUBLIC_TRANSFER,
                'payment_method' => ChargePayment::METHOD_TRANSFER,
                'reference' => $validated['reference'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['payment_date'],
                'registered_by' => null,
            ]);

            $charge->refreshPaymentStatus();
        });

        return redirect()
            ->route('charges.public.show', ['token' => $charge->payment_token])
            ->with('success', 'Comprobante enviado. Tu pago quedo en validacion.');
    }

    public function success(Request $request, string $token): Response
    {
        $charge = $this->findChargeByToken($token)->loadMissing(['tenant:id,full_name', 'property:id,internal_name']);
        $sessionId = trim((string) $request->query('session_id', ''));

        if ($sessionId !== '' && $charge->status !== Charge::STATUS_PAID) {
            $stripeSecret = (string) config('services.stripe.secret');
            if ($stripeSecret !== '') {
                Stripe::setApiKey($stripeSecret);

                try {
                    $session = Session::retrieve($sessionId);
                    $this->applySuccessfulSession($session->toArray(), null);
                    $charge->refresh();
                } catch (Throwable) {
                    // The webhook will complete reconciliation in background.
                }
            }
        }

        return response()->view('charges.public-success', [
            'charge' => $charge,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature');
        $webhookSecret = (string) config('services.stripe.webhook_secret');
        $eventType = '';
        $eventId = null;
        $sessionData = [];

        try {
            if ($webhookSecret !== '') {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
                $eventType = (string) $event->type;
                $eventId = (string) $event->id;
                $session = $event->data->object;
                $sessionData = is_array($session) ? $session : $session->toArray();
            } else {
                $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
                $eventType = (string) ($decoded['type'] ?? '');
                $eventId = filled($decoded['id'] ?? null) ? (string) $decoded['id'] : null;
                $sessionData = (array) ($decoded['data']['object'] ?? []);
            }
        } catch (SignatureVerificationException|Throwable) {
            return response()->json(['error' => 'Invalid Stripe payload'], 400);
        }

        if (!in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->json(['received' => true]);
        }

        $paymentStatus = (string) ($sessionData['payment_status'] ?? '');

        if ($paymentStatus !== 'paid') {
            return response()->json(['received' => true]);
        }

        $this->applySuccessfulSession($sessionData, $eventId);

        return response()->json(['received' => true]);
    }

    private function findChargeByToken(string $token): Charge
    {
        return Charge::query()
            ->where('payment_token', $token)
            ->firstOrFail();
    }

    private function applySuccessfulSession(array $sessionData, ?string $eventId): void
    {
        $chargeId = (int) (($sessionData['metadata']['charge_id'] ?? null) ?: ($sessionData['client_reference_id'] ?? 0));
        if ($chargeId <= 0) {
            return;
        }

        $becamePaid = false;
        $charge = null;

        DB::transaction(function () use ($chargeId, $sessionData, $eventId, &$becamePaid, &$charge): void {
            $charge = Charge::query()->lockForUpdate()->find($chargeId);
            if (!$charge) {
                return;
            }

            $amount = ((int) ($sessionData['amount_total'] ?? 0)) / 100;
            $sessionId = (string) ($sessionData['id'] ?? '');
            $currency = strtolower((string) ($sessionData['currency'] ?? config('services.stripe.currency', 'mxn')));
            $paidAt = isset($sessionData['created']) ? now()->setTimestamp((int) $sessionData['created']) : now();

            if ($sessionId === '') {
                return;
            }

            ChargePayment::updateOrCreate(
                ['stripe_checkout_session_id' => $sessionId],
                [
                    'charge_id' => $charge->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => ChargePayment::STATUS_SUCCEEDED,
                    'source' => ChargePayment::SOURCE_STRIPE,
                    'payment_method' => ChargePayment::METHOD_CARD,
                    'stripe_payment_intent_id' => filled($sessionData['payment_intent'] ?? null)
                        ? (string) $sessionData['payment_intent']
                        : null,
                    'stripe_event_id' => $eventId,
                    'paid_at' => $paidAt,
                    'payment_date' => $paidAt->toDateString(),
                    'payload' => $sessionData,
                ],
            );

            $becamePaid = $charge->refreshPaymentStatus();
        });

        if ($becamePaid && $charge) {
            $this->sendCompletedMail($charge);
        }
    }

    private function sendCompletedMail(Charge $charge): void
    {
        $charge->loadMissing(['tenant:id,email,full_name']);
        if (!filled($charge->tenant?->email)) {
            return;
        }
        if (!NotificationSettings::allows(NotificationSettings::ROLE_TENANT, NotificationSettings::EVENT_PAYMENT_CONFIRMED)) {
            return;
        }

        Mail::to($charge->tenant->email)->send(new ChargeCompletedMail($charge));
    }
}
