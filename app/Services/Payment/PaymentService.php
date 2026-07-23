<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Exceptions\ApiException;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AbacatePayGatewayService $gateway,
    ) {}

    public function store(array $data): PaymentResource
    {
        return DB::transaction(function () use ($data): PaymentResource {
            $user = Auth::user();

            if ($user->league_id !== null) {
                throw new ApiException('Este usuário já possui uma liga.', 409);
            }

            $subscription = Subscription::findOrFail($data['subscription_id']);
            $months = (int) $data['months'];

            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'months' => $months,
                'league_name' => $data['league_name'],
                'owner_full_name' => $data['owner_full_name'],
                'amount' => $subscription->price * $months,
                'status' => PaymentStatus::PENDING,
            ]);

            $charge = $this->gateway->createPixCharge($payment);

            $payment->update([
                'external_id' => $charge['id'] ?? null,
                'pix_qr_code' => $charge['brCodeBase64'] ?? null,
                'pix_br_code' => $charge['brCode'] ?? null,
                'expires_at' => isset($charge['expiresAt']) ? Carbon::parse($charge['expiresAt']) : null,
            ]);

            $payment->load('subscription');

            return new PaymentResource($payment);
        });
    }
}
