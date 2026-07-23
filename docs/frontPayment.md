# Guia de Front-end — Assinatura e Pagamento (Abacate Pay)

> Guia de consumo pro front: quais rotas existem, o que cada uma espera/retorna, de onde tirar o QR Code, e como saber que o pagamento foi confirmado. Referência de negócio/decisões em `docs/gateway.md`.

---

## Visão geral do fluxo

```
1. POST /auth/register  (ou /auth/login, se já tiver conta)
2. GET  /subscription/catalog   → lista de planos
3. POST /payment                → gera a cobrança Pix, retorna QR Code
4. (tela "aguardando pagamento") → GET /payment/{id} em polling
5. Quando payment.league_id deixar de ser null → liga foi criada, redireciona pro dashboard
```

Não existe redirecionamento para fora do seu site em nenhum momento — é Checkout Transparente, o Pix é gerado e pago sem sair da sua página.

---

## Rotas

Todas (exceto `register`/`login`) exigem `Authorization: Bearer <token>` obtido no registro/login.

### `POST /api/auth/register`

Cadastra um usuário novo, sem liga ainda.

**Request:**
```json
{
  "username": "string",
  "email": "string",
  "password": "string (min 8)",
  "phone": "string"
}
```

**Response `200`:**
```json
{
  "error": false,
  "message": "Cadastro realizado com sucesso.",
  "data": {
    "user": { "id": "uuid", "username": "...", "email": "...", "phone": "...", "league_id": null, "user_type": "user", "roles": [], "permissions": [], "is_active": true, "created_at": "...", "updated_at": "..." },
    "token": "jwt...",
    "refresh_expires_in": 1209600
  }
}
```

### `POST /api/auth/login`

Mesmo formato de resposta do `register`. Request: `{ "email": "...", "password": "..." }`.

### `GET /api/auth/me`

Retorna o `user` autenticado (mesmo shape de `data.user` acima, mas sem envelope `user`/`token` — é o `UserResource` direto). Útil como alternativa de polling (ver seção de atualização abaixo), mas prefira `GET /payment/{id}`.

### `GET /api/subscription/catalog`

Lista os planos disponíveis (paginado).

**Response `200`:**
```json
{
  "error": false,
  "message": "Planos listados com sucesso.",
  "data": {
    "data": [
      { "id": "uuid", "name": "Padrão", "user_limit": 30, "price": "49.90", "created_at": "...", "updated_at": "..." }
    ],
    "pagination": { "total": 1, "count": 1, "per_page": 10, "current_page": 1, "total_pages": 1 }
  }
}
```

### `POST /api/payment`

Gera a cobrança Pix. Falha com `409` se o usuário autenticado já tiver `league_id` (já é dono de uma liga).

**Request:**
```json
{
  "subscription_id": "uuid (id do plano escolhido)",
  "months": 1,
  "league_name": "string (nome da liga a ser criada)",
  "owner_full_name": "string (nome completo do responsável)"
}
```

**Response `200`:** `PaymentResource` — ver shape completo abaixo.

### `GET /api/payment/{id}`

Consulta o status de um pagamento. Só o próprio comprador (dono do pagamento) pode ver — outro usuário autenticado recebe `403`.

**Response `200`:** mesmo `PaymentResource` do `POST /payment` — é essa rota que você vai chamar em loop.

### `PaymentResource` (shape de `data` em `POST /payment` e `GET /payment/{id}`)

```json
{
  "id": "uuid",
  "status": "pending | paid | expired | failed | refunded",
  "months": 1,
  "amount": "49.90",
  "league_name": "Liga Teste",
  "league_id": null,
  "pix_qr_code": "data:image/png;base64,iVBORw0KG...",
  "pix_br_code": "00020101021126580014BR.GOV.BCB.PIX...6304B14F",
  "expires_at": "2026-07-23T02:40:40.000000Z",
  "subscription": { "id": "uuid", "name": "Padrão", "user_limit": 30, "price": "49.90", "created_at": "...", "updated_at": "..." },
  "created_at": "2026-07-23 01:40:40"
}
```

---

## De onde tirar o QR Code

Direto do `PaymentResource`, sem nenhum processamento:

- **`pix_qr_code`** — já é uma *data URI* completa (`data:image/png;base64,...`). Usa direto num `<img src={pix_qr_code} />`, não precisa concatenar prefixo nem decodificar nada.
- **`pix_br_code`** — o "copia e cola". Mostra num campo de texto com botão de copiar, pra quem preferir colar no app do banco em vez de escanear a imagem.
- **`expires_at`** — depois desse horário o Pix morre. Compare com `new Date()` no front; se já passou e `status` continua `pending`, mostra "expirado" e deixa o usuário gerar um novo (`POST /payment` de novo).

---

## Como saber que o pagamento foi confirmado (sem WebSocket)

Direto ao ponto: **você não precisa configurar WebSocket nenhum.** O projeto não tem nenhuma infraestrutura de broadcast real hoje — `BROADCAST_CONNECTION=log` no `.env` significa que não existe servidor de WebSocket (nem Reverb, nem Pusher, nem Soketi) rodando ou mesmo instalado. Se um dia quisermos push em tempo real de verdade, isso é trabalho novo de backend (configurar Reverb, disparar um evento broadcast quando o webhook confirmar o pagamento) — não existe hoje.

O que existe, e é o suficiente pra essa tela: **polling em `GET /payment/{id}`**. A confirmação leva poucos segundos (é o tempo do usuário pagar o Pix + a Abacate Pay entregar o webhook pra gente), então um polling de 3-5s é imperceptível pro usuário — é assim que a imensa maioria dos checkouts Pix funciona na prática.

### Sobre o `useMemo` que você mencionou

Vale um ajuste de raciocínio: `useMemo` não faz o papel de "esperar a informação atualizar" — ele só memoiza um valor derivado de algo que **já mudou** na sua árvore de componentes. Quem efetivamente busca a informação de novo, de tempos em tempos, é o `refetchInterval` do TanStack Query. `useMemo` (ou simplesmente uma variável derivada, nem precisa de memo pra um booleano barato como esse) entra só depois, pra reagir ao dado já atualizado.

### Exemplo com TanStack Query

```tsx
function useWaitForPayment(paymentId: string) {
  return useQuery({
    queryKey: ['payment', paymentId],
    queryFn: () => api.get(`/payment/${paymentId}`).then(r => r.data.data),
    // refaz a busca a cada 3s enquanto ainda estiver pendente;
    // para de buscar sozinho assim que sair do status "pending"
    refetchInterval: (query) => {
      const status = query.state.data?.status;
      return status === 'pending' ? 3000 : false;
    },
  });
}

function PaymentWaitingScreen({ paymentId }: { paymentId: string }) {
  const { data: payment } = useWaitForPayment(paymentId);
  const navigate = useNavigate();

  useEffect(() => {
    if (payment?.league_id) {
      navigate('/dashboard');
    }
  }, [payment?.league_id, navigate]);

  const isExpired = payment && new Date(payment.expires_at) < new Date() && payment.status === 'pending';

  if (isExpired) return <PaymentExpired />;

  return (
    <div>
      <img src={payment?.pix_qr_code} alt="QR Code Pix" />
      <CopyButton text={payment?.pix_br_code} />
      <p>Aguardando confirmação do pagamento…</p>
    </div>
  );
}
```

O gatilho de redirecionamento é o `useEffect` reagindo a `payment?.league_id` — o `refetchInterval` é quem garante que esse valor eventualmente chega atualizado, sem você precisar de WebSocket, polling manual com `setInterval`, nem nada além do que o TanStack Query já oferece pronto.
