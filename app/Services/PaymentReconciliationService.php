<?php

namespace App\Services;

use App\Data\GatewayPayment;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentApproved;
use App\Events\PaymentRefunded;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReconciliationService
{
    public function apply(Payment $payment, GatewayPayment $remote): Payment
    {
        [$payment, $dispatchApproved, $dispatchRefunded, $mismatch] = DB::transaction(function () use ($payment, $remote) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $valid = $payment->gateway === 'mercado_pago'
                && $remote->externalReference === $payment->id
                && $remote->amountCents === $payment->amount_cents
                && $remote->currency === $payment->currency
                && $remote->method === 'pix';

            if (! $valid) {
                $payment->update(['external_id' => $remote->id, 'status' => PaymentStatus::REQUIRES_ACTION, 'status_detail' => 'gateway_data_mismatch']);
                $payment->checkout()->update(['status' => CheckoutStatus::REQUIRES_ACTION]);

                return [$payment, false, false, true];
            }

            $status = match ($remote->status) {
                'approved' => PaymentStatus::APPROVED,
                'pending' => PaymentStatus::PENDING,
                'in_process' => PaymentStatus::IN_PROCESS,
                'rejected' => PaymentStatus::REJECTED,
                'cancelled', 'canceled' => PaymentStatus::CANCELED,
                'refunded', 'charged_back' => PaymentStatus::REFUNDED,
                default => PaymentStatus::UNKNOWN,
            };

            // Notificações podem chegar fora de ordem. Estados financeiros finais
            // nunca podem regredir para pendente, rejeitado ou desconhecido.
            if ($payment->status === PaymentStatus::REFUNDED) {
                return [$payment, false, true, false];
            }
            if ($payment->status === PaymentStatus::REQUIRES_ACTION
                || ($payment->status === PaymentStatus::APPROVED
                    && ! in_array($status, [PaymentStatus::APPROVED, PaymentStatus::REFUNDED], true))) {
                return [$payment, false, false, false];
            }

            $payment->update([
                'external_id' => $remote->id, 'status' => $status, 'status_detail' => $remote->statusDetail,
                'pix_qr_code_base64' => $remote->qrCodeBase64, 'pix_copy_paste_code' => $remote->copyPasteCode,
                'pix_ticket_url' => $remote->ticketUrl, 'expires_at' => $remote->expiresAt,
                'approved_at' => $status === PaymentStatus::APPROVED ? ($payment->approved_at ?? now()) : $payment->approved_at,
                'refunded_at' => $status === PaymentStatus::REFUNDED ? ($payment->refunded_at ?? now()) : $payment->refunded_at,
            ]);

            $checkout = $payment->checkout()->lockForUpdate()->firstOrFail();
            if (in_array($status, [PaymentStatus::PENDING, PaymentStatus::IN_PROCESS], true)
                && in_array($checkout->status, [CheckoutStatus::OPEN, CheckoutStatus::PAYMENT_PENDING], true)) {
                $checkout->update(['status' => CheckoutStatus::PAYMENT_PENDING]);
            }
            if ($status === PaymentStatus::APPROVED && $checkout->status !== CheckoutStatus::FULFILLED) {
                $checkout->update([
                    'status' => CheckoutStatus::PAID,
                    'paid_at' => $checkout->paid_at ?? now(),
                ]);
            }

            // Os handlers são idempotentes e devem rodar novamente para recuperar
            // uma falha ocorrida depois da confirmação financeira.
            return [$payment, $status === PaymentStatus::APPROVED, $status === PaymentStatus::REFUNDED, false];
        });

        if ($mismatch) {
            Log::warning('mercado_pago.payment.data_mismatch', [
                'checkout_id' => $payment->checkout_id,
                'payment_id' => $payment->id,
                'external_id' => $remote->id,
            ]);
        }
        if ($dispatchApproved) {
            event(new PaymentApproved($payment->id));
        }
        if ($dispatchRefunded) {
            event(new PaymentRefunded($payment->id));
        }

        return $payment->refresh();
    }
}
