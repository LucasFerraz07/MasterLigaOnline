# Gateway de Pagamento — Abacate Pay

> Igual a `pendencias.md`, mas focado só na integração de pagamento: o que já foi testado contra o sandbox real da Abacate Pay, e o que ainda falta decidir/construir antes de considerar isso pronto pra produção.

---

## O que já está validado

Fluxo completo testado duas vezes ponta a ponta contra o sandbox real (não só localmente): `POST /auth/register` → `GET /subscription/catalog` → `POST /payment` (gera cobrança Pix real na Abacate Pay) → `POST /transparents/simulate-payment` → webhook real entregue via túnel público → `POST /webhooks/abacate-pay` processa e cria a liga.

- Cobrança Pix (`AbacatePayGatewayService::createPixCharge`) — corpo da requisição confirmado contra a doc real (`{method: "PIX", data: {amount, expiresIn, externalId}}`), não é mais suposição.
- Autenticação do webhook (`PaymentService::verifyWebhookSignature`) — confirmada contra entrega real: secret em texto puro no header `X-Webhook-Secret`, comparação direta (`hash_equals`), não é HMAC.
- Caminho do payload do webhook (`data.transparent.id`, não `data.id`) — confirmado contra entrega real.
- Idempotência (`payment_webhook_events`, unique `event`+`external_id`) — testada com reentrega manual e com uma falha real de processamento (ver abaixo), nos dois casos sem duplicar liga/período.
- Provisionamento da liga (`LeagueService::provisionLeagueForUser`) — liga, Owner (sem cpf), papel `league_admin`, clube automático e preços de categoria, tudo criado atomicamente na confirmação do pagamento.
- Bloqueio de ações quando a assinatura vence (`EnsureLeagueSubscriptionActive`) e expiração automática (`subscriptions:expire`) — testados manualmente forçando `subscription_end` no passado.

---

## Pendências

- **Sem endpoint pra consultar o status de um pagamento específico.** Hoje só existe `POST /payment` (cria a cobrança). O front não tem como saber quando um pagamento foi confirmado a não ser fazendo polling em `GET /auth/me` e checando se `league_id` deixou de ser `null` — funciona, mas é indireto (não diferencia "ainda pendente" de "expirado" de "falhou", por exemplo). Decisão em aberto: criar `GET /payment/{id}` (ou algo equivalente) antes do front construir a tela de "aguardando confirmação do Pix".

- **Verificação do webhook só foi confirmada com `devMode: true` (sandbox).** Não sabemos com certeza se o header `X-Webhook-Secret` se comporta igual com uma chave de API de produção — a Abacate Pay também manda um header `X-Webhook-Signature` (aparenta ser HMAC) que não usamos porque não foi possível confirmar o algoritmo exato a partir do payload capturado. Vale repetir o teste com credenciais de produção antes de operar com dinheiro real.

- **Só o evento `transparent.completed` é tratado.** `PaymentService::handleWebhookEvent` ignora silenciosamente qualquer outro evento (`transparent.refunded`, `transparent.disputed`, `transparent.lost`, etc. — a lista completa está documentada no plano de implementação). Hoje, se um Pix for estornado depois de confirmado, a liga continua ativa normalmente — nada reage a isso.

- **Nenhuma limpeza de cobrança Pix abandonada.** Se o usuário gera uma cobrança (`POST /payment`) e nunca paga, o `Payment` fica `pending` pra sempre — não há job que marque como `expired` depois do `expires_at`, nem nada que impeça o mesmo usuário (ainda sem liga) de gerar várias cobranças `pending` em paralelo. Inofensivo hoje (não trava nada), mas suja a tabela e pode confundir suporte.

- **Renovação manual pelo admin (`PUT /league/renew-subscription/{id}`) não gera `SubscriptionPeriod`.** Esse endpoint (`LeagueService::renewSubscription`) continua existindo para o `system_admin` renovar uma liga manualmente sem passar pela Abacate Pay — ele atualiza `subscription_id`/`subscription_start`/`subscription_end`/`deactivated_at` corretamente (bug antigo de recálculo já corrigido), mas não insere linha em `subscription_periods`. Resultado: o histórico de períodos fica incompleto para ligas que já foram renovadas manualmente por um admin — `subscription_periods` reflete só os pagamentos que passaram pelo webhook.

- **`clubs` precisa estar populada antes do primeiro pagamento real.** `provisionLeagueForUser` sempre tenta atribuir um clube aleatório (`ClubIdentityService::assignRandomClub`); se a tabela estiver vazia, a `ApiException` estoura dentro da mesma transação e a liga não nasce (o `Payment` fica `pending`, o webhook fica marcado como não-processado, dá pra reprocessar depois via "Reenviar" no painel da Abacate Pay). Aconteceu duas vezes durante os testes desta sessão, sempre por causa de `migrate:fresh` limpando o clube de teste — não é bug, é dependência de dado real que o dono do projeto já vai popular por fora.

- **Sem teste automatizado.** Igual a todo o resto do projeto (ver `pendencias.md`) — todo o fluxo (registro, catálogo, pagamento, webhook, expiração, middleware) foi validado manualmente via `tinker`/`curl`/sandbox real, nada em PHPUnit/Pest cobre ainda.

- **Refund/estorno não tem fluxo nenhum.** A chave de API nem tem a permissão `REFUND:CREATE` habilitada — se decidirmos oferecer estorno no futuro, é feature nova (endpoint, permissão na chave, e tratar o evento `transparent.refunded` no webhook, ver item acima).

---

## Decisões já tomadas (pra não reabrir a discussão à toa)

- **Só Pix avulso, sem assinatura recorrente nativa da Abacate Pay** — usuário escolhe quantos meses paga de uma vez; renovação é uma nova cobrança, não cobrança automática recorrente. Deliberado para manter simples agora sem fechar a porta pra cartão/recorrência depois (o design de `subscription_periods` é agnóstico de método de pagamento).
- **Liga vencida é desativada, não excluída** (`leagues.deactivated_at`, distinto de `deleted_at`) — dados continuam visíveis, só ações de escrita são bloqueadas (`EnsureLeagueSubscriptionActive`). Decisão de retenção: bloquear leitura também não traria ganho de performance real e prejudicaria a conversão de renovação.
- **Usuário recém-cadastrado nasce `user_type=user`, sem role nenhuma, sem `league_id`.** Só vira `league_admin` (com role, `league_id` e `Owner`) atomicamente em `provisionLeagueForUser`, quando o pagamento é confirmado. Deliberado: o `TenantScope` desliga o filtro de liga quando `league_id` é `null`, então promover o papel antes de existir liga abriria uma janela real de acesso cross-tenant.
- **`cpf` removido de `owners`** — não é mais coletado em lugar nenhum do fluxo de assinatura própria.
