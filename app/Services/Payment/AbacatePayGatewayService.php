<?php

namespace App\Services\Payment;

use App\Exceptions\ApiException;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class AbacatePayGatewayService
{
    /**
     * Cria uma cobrança Pix avulsa (Checkout Transparente) na Abacate Pay.
     *
     * O formato exato do corpo da requisição/resposta não pôde ser confirmado
     * com certeza contra a documentação oficial (ver plano de implementação) —
     * validar contra uma chamada real em sandbox antes de ir para produção.
     *
     * @return array{id: ?string, brCode: ?string, brCodeBase64: ?string, expiresAt: ?string}
     */
    public function createPixCharge(Payment $payment): array
    {
        $response = Http::withToken(config('services.abacate_pay.api_key'))
            ->baseUrl(config('services.abacate_pay.base_url'))
            ->post('/transparents/create', [
                'amount' => (int) round(((float) $payment->amount) * 100),
                'expiresIn' => 3600,
                'metadata' => [
                    'external_id' => $payment->id,
                ],
            ]);

        if ($response->failed()) {
            throw new ApiException('Não foi possível gerar a cobrança Pix. Tente novamente.', 502, $response->json() ?? []);
        }

        return $response->json('data') ?? [];
    }
}
