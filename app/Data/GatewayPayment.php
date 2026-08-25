<?php

namespace App\Data;

final readonly class GatewayPayment
{
    public function __construct(
        public string $id, public string $status, public ?string $statusDetail,
        public int $amountCents, public string $currency, public string $method,
        public ?string $externalReference, public ?string $qrCodeBase64 = null,
        public ?string $copyPasteCode = null, public ?string $ticketUrl = null,
        public ?string $expiresAt = null,
    ) {}
}
