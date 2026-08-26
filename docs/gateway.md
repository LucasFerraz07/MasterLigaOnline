# Cobrança Pix com Mercado Pago

Este projeto usa o Payment Brick no frontend e a Payments API (`POST /v1/payments`) no backend. Nesta primeira versão, somente Pix avulso é aceito. A confirmação definitiva acontece pelo webhook; a resposta da criação do Pix serve para exibir a cobrança, não para conceder acesso.

## Arquitetura

```text
Plan -> PlanPrice -> Checkout -> Payment
                              | aprovado e confirmado
                              v
              LeagueSubscription -> SubscriptionPeriod -> League
```

- `plans` representa o produto.
- `plan_prices` guarda ofertas imutáveis e versionadas de 1, 2, 6 ou 12 meses. O valor total em centavos é a fonte da verdade.
- `checkouts` congela preço, moeda e dados de provisionamento.
- `payments` registra cada tentativa financeira e suas chaves de idempotência.
- `league_subscriptions` representa o contrato de acesso da liga.
- `subscription_periods` registra, de forma imutável, o benefício financiado por cada pagamento.
- `payment_webhook_events` é a inbox idempotente das notificações.

O frontend nunca envia preço nem duração. Ele seleciona um `plan_price_id`, e o Laravel consulta a oferta ativa e congela seus dados no checkout.

## Configuração

O SDK PHP oficial está instalado com:

```bash
composer require mercadopago/dx-php
```

Configure somente no backend:

```dotenv
MERCADO_PAGO_ACCESS_TOKEN=
MERCADO_PAGO_WEBHOOK_SECRET=
MERCADO_PAGO_NOTIFICATION_URL=https://api.exemplo.com/api/webhooks/mercado-pago
```

A Public Key pertence exclusivamente ao frontend e deve ser exposta pelo Vite, por exemplo:

```dotenv
VITE_MERCADO_PAGO_PUBLIC_KEY=
```

Use credenciais de teste em desenvolvimento e credenciais de produção somente no ambiente de produção. Nunca versione `.env`, Access Token, segredo de webhook ou documentos reais. Depois de alterar variáveis em um ambiente com cache, execute `php artisan config:cache`.

O Laravel lê todas as credenciais por `config('services.mercado_pago...')`. A implementação concreta é vinculada ao contrato `App\Contracts\PaymentGateway`, permitindo testes com gateway fake.

## Catálogo e administração

`GET /api/catalog/plans` é público e retorna somente planos e preços ativos. Cada preço inclui:

```json
{
  "id": "uuid",
  "code": "PRO-12M-V1",
  "version": 1,
  "interval_months": 12,
  "amount_cents": 26990,
  "amount": "269.90",
  "currency": "BRL",
  "monthly_equivalent_cents": 2249,
  "discount_percent": 24.78,
  "active": true
}
```

`amount`, valor mensal equivalente e desconto são campos de apresentação. Toda decisão financeira usa `amount_cents` armazenado no servidor.

Rotas administrativas, protegidas por JWT e permissões:

- `GET /api/plans`
- `POST /api/plans`
- `PUT /api/plans/{plan}`
- `POST /api/plans/{plan}/prices`
- `PATCH /api/plans/prices/{price}/deactivate` (alterna entre ativo e inativo; não apaga)

Um preço não possui endpoint de edição. O reajuste cria uma nova versão e desativa a versão anterior. O código do plano também é imutável após a criação.

## Fluxo da API

Todas as respostas normais usam o envelope:

```json
{ "error": false, "message": "...", "data": {} }
```

As três rotas de checkout exigem JWT. Os dois `POST` também exigem um UUID novo no header `Idempotency-Key`. Repetir a mesma operação com a mesma chave retorna o recurso existente. Reutilizar uma chave de checkout com dados diferentes retorna `409`.

### 1. Criar checkout

```http
POST /api/checkouts
Authorization: Bearer <jwt>
Idempotency-Key: <uuid>
Content-Type: application/json

{
  "plan_price_id": "uuid-da-oferta",
  "league_name": "Liga dos Amigos",
  "owner_full_name": "Nome do responsável"
}
```

`league_name` e `owner_full_name` são obrigatórios somente para a primeira contratação. Se o usuário já tem uma liga, o servidor cria uma renovação para ela e ignora dados de criação de outra liga.

Resposta relevante em `data`:

```json
{
  "checkout_id": "uuid",
  "type": "initial",
  "status": "open",
  "plan_price_id": "uuid-da-oferta",
  "amount_cents": 26990,
  "amount": 269.9,
  "currency": "BRL",
  "expires_at": "2026-08-24T20:00:00.000000Z",
  "payment_configuration": {
    "payment_methods": { "bankTransfer": "pix" }
  },
  "payment": null
}
```

O `amount` e a configuração retornados pelo backend devem inicializar o Brick. O frontend não deve reconstruir esses valores a partir do catálogo.

### 2. Inicializar o Payment Brick no React

```tsx
import { initMercadoPago, Payment } from '@mercadopago/sdk-react';

initMercadoPago(import.meta.env.VITE_MERCADO_PAGO_PUBLIC_KEY, {
  locale: 'pt-BR',
});

export function PixPaymentBrick({ checkout, token, onUpdated }) {
  const initialization = { amount: checkout.amount };
  const customization = {
    paymentMethods: checkout.payment_configuration.payment_methods,
  };

  return (
    <Payment
      initialization={initialization}
      customization={customization}
      onSubmit={async ({ formData }) => {
        const response = await fetch(
          `/api/checkouts/${checkout.checkout_id}/payments`,
          {
            method: 'POST',
            headers: {
              Authorization: `Bearer ${token}`,
              'Content-Type': 'application/json',
              'Idempotency-Key': crypto.randomUUID(),
            },
            body: JSON.stringify({ payment: formData }),
          },
        );

        const body = await response.json();
        if (!response.ok) throw new Error(body.message ?? 'Falha ao gerar o Pix');
        onUpdated(body.data);
      }}
      onError={(error) => console.error('Payment Brick', error)}
    />
  );
}
```

Guarde a chave de idempotência da tentativa até receber resposta. Se houver timeout de rede, reenvie a mesma requisição com a mesma chave. O backend também reutiliza a mesma `X-Idempotency-Key` ao Mercado Pago quando uma tentativa ficou `unknown`.

O backend aceita do Brick somente `payment_method_id=pix` e a identificação `CPF` ou `CNPJ`. E-mail vem do usuário autenticado. Valor, moeda, descrição, expiração, `external_reference` e `notification_url` são definidos no Laravel.

### 3. Exibir Pix e consultar estado

A resposta do pagamento e `GET /api/checkouts/{checkout}` retornam o pagamento mais recente:

```json
{
  "id": "uuid-local-do-pagamento",
  "gateway": "mercado_pago",
  "method": "pix",
  "status": "pending",
  "status_detail": "pending_waiting_payment",
  "amount_cents": 26990,
  "amount": 269.9,
  "currency": "BRL",
  "expires_at": "2026-08-24T20:00:00.000000Z",
  "approved_at": null,
  "pix": {
    "qr_code_base64": "...",
    "copy_paste_code": "...",
    "ticket_url": "https://..."
  }
}
```

Exiba a imagem como `data:image/png;base64,<qr_code_base64>` e ofereça o `copy_paste_code`. Não registre esses campos em logs nem ferramentas de analytics.

Faça polling moderado de `GET /api/checkouts/{checkout}` (por exemplo, a cada 3–5 segundos enquanto a tela estiver aberta) até:

- `fulfilled`: pagamento aprovado e benefício provisionado;
- `paid`: pagamento aprovado, provisionamento ainda em retry;
- `requires_action`: divergência ou segundo pagamento aprovado, requer revisão;
- `expired` ou `canceled`: fluxo encerrado.

`approved` no pagamento não substitui `fulfilled` no checkout como confirmação de que a liga já está pronta.

## Webhook e reconciliação

Cadastre no painel do Mercado Pago a URL HTTPS:

```text
POST /api/webhooks/mercado-pago
```

Habilite o tópico `payment`. O segredo exibido na configuração do webhook deve ser salvo em `MERCADO_PAGO_WEBHOOK_SECRET`.

O controller valida `x-signature` com `x-request-id`, `data.id` e tolerância temporal de 300 segundos usando o validador do SDK oficial. Assinaturas inválidas retornam `401`. Notificações válidas são persistidas de forma idempotente na inbox e recebem `200` antes do processamento assíncrono.

O job consulta novamente o pagamento na API do Mercado Pago. O status recebido no webhook nunca é confiado. Antes de aprovar, ele confere:

- `external_reference` igual ao UUID local do pagamento;
- ID externo do Mercado Pago;
- gateway e método Pix;
- valor integral em centavos;
- moeda BRL.

Divergências ficam em `requires_action` e não provisionam benefício. Eventos repetidos são inofensivos. Locks e restrições únicas garantem que somente o primeiro pagamento aprovado efetive o checkout.

Na contratação inicial são criados exatamente uma liga, Owner, papel, clube inicial, assinatura e período. Na renovação, o novo período começa em `max(agora, access_expires_at)`, sem perder tempo vigente. Um plano comprado antecipadamente fica `scheduled` e passa a ser o plano vigente quando seu período começar.

Reembolso ou chargeback marca o pagamento como `refunded`, revoga o período que ele financiou, recalcula a validade pelos demais períodos pagos e desativa a liga se não houver acesso válido.

## Workers, scheduler e recuperação

Em todos os ambientes compartilhados, mantenha ativos:

```bash
php artisan queue:work --tries=5
php artisan schedule:work
```

O scheduler executa `subscriptions:sync` de hora em hora para ativar períodos agendados, expirar vencidos, atualizar o plano vigente e desativar ligas sem acesso.

Procedimentos de recuperação:

- Pagamento `unknown`: o cliente repete `POST /payments` com a mesma chave e os mesmos dados. O Laravel repete a chamada com a mesma chave do Mercado Pago.
- Checkout `paid`, mas não `fulfilled`: corrija a causa e reexecute o job do webhook; o provisionamento é idempotente.
- Evento `failed`: inspecione IDs sanitizados e coloque o job novamente na fila.
- Pagamento `requires_action`: compare o recurso local com `GET /v1/payments/{id}` no painel/API antes de decidir sobre reembolso. Nunca aprove manualmente sem essa conferência.
- Segundo pagamento aprovado para o mesmo checkout: não concede acesso e deve ser encaminhado para reembolso.

Os logs contêm somente IDs de checkout, pagamento, evento, recurso e tentativa. Não registre headers completos, tokens, QR Code, copia-e-cola ou documentos.

## Homologação e produção

1. Execute `php artisan migrate:fresh --seed` apenas em banco descartável. Os valores do seed são fictícios; cadastre preços reais pelo CRUD administrativo.
2. Use aplicação, credenciais, usuários e dados de teste do Mercado Pago.
3. Gere um Pix pelo Payment Brick e confirme que QR Code e copia-e-cola aparecem.
4. Use a simulação oficial de webhook e confirme assinatura, armazenamento da inbox e consulta do pagamento na API.
5. Confirme polling até `fulfilled`, criação única da liga e renovação sem nova liga.
6. Repita webhook, checkout e pagamento com a mesma chave; nenhum benefício deve duplicar.
7. Teste divergência de valor, falha de provisionamento e reembolso.
8. Em produção, troque Access Token, Public Key e segredo simultaneamente, confirme URL HTTPS pública e tópico `payment`, limpe/cacheie configuração e verifique worker e scheduler.

Os endpoints antigos `/api/subscription`, `/api/payment` e `/api/webhooks/abacate-pay` não existem mais. Cartões, boleto, carteira Mercado Pago, cartões salvos e recorrência automática estão deliberadamente fora desta entrega.
