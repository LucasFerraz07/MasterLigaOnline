================================================================================
  COMO FUNCIONA O MÉTODO INDEX COM COLLECTION NO LARAVEL
  Exemplo baseado em: ClientService / ClientController / ClientCollection
================================================================================

--------------------------------------------------------------------------------
VISÃO GERAL DO FLUXO
--------------------------------------------------------------------------------

  [HTTP Request]
       |
       v
  [FormRequest]  →  valida e prepara os parâmetros recebidos
       |
       v
  [Controller]   →  repassa os dados validados ao Service
       |
       v
  [Service]      →  monta a query, aplica filtros e paginação
       |
       v
  [Collection]   →  formata a resposta com os dados e metadados de paginação
       |
       v
  [JSON Response]


--------------------------------------------------------------------------------
1. O REQUEST — COMO OS PARÂMETROS SÃO ENVIADOS
--------------------------------------------------------------------------------

Os parâmetros são enviados via query string (GET), por exemplo:

  GET /api/clients?page=2&per_page=15&search=empresa&tenant_id=uuid-aqui

Parâmetros aceitos pelo ClientIndexRequest:

  - per_page    (integer, opcional) — quantidade de itens por página
  - page        (integer, opcional) — número da página atual
  - search      (string, opcional)  — termo de busca livre
  - tenant_id   (uuid, opcional)    — filtra por franqueado (requer permissão)

O FormRequest (ClientIndexRequest) é responsável por:

  a) Validar os tipos e formatos dos parâmetros recebidos.
  b) Sanitizar os valores via prepareForValidation() antes da validação,
     garantindo que tipos incorretos (ex: string onde espera inteiro) sejam
     tratados previamente.
  c) Retornar apenas os campos válidos via $request->validated(), que é o
     array passado para o Service.


--------------------------------------------------------------------------------
2. O CONTROLLER — PONTO DE ENTRADA
--------------------------------------------------------------------------------

  // App\Http\Controllers\Client\Client.php

  public function index(ClientIndexRequest $request): JsonResponse
  {
      $data = $this->clientService->index($request->validated());
      return ReturnApi::success($data, 'Clientes listados com sucesso!');
  }

O Controller não contém lógica de negócio. Ele:

  1. Recebe o request já validado pelo FormRequest.
  2. Chama $request->validated() para obter apenas os campos permitidos.
  3. Passa o array resultante diretamente ao Service.
  4. Devolve a resposta JSON formatada.


--------------------------------------------------------------------------------
3. O SERVICE — ONDE A LÓGICA VIVE
--------------------------------------------------------------------------------

  // App\Services\Client\ClientService.php

  public function index(array $data): ClientCollection
  {
      // 3.1 — Parâmetros de paginação com valores padrão
      $perPage = (int) ($data['per_page'] ?? 10);
      $page    = (int) ($data['page']     ?? 1);
      $search  = $data['search']          ?? null;

      // 3.2 — Construção da query base
      $query = Client::query()

          // Inclui registros deletados (soft delete) se solicitado
          ->when($data['with_trashed'] ?? false, fn ($q) => $q->withTrashed())

          // Eager loading dos relacionamentos necessários
          ->with([
              'address'                          => fn ($q) => $q->withTrashed(),
              'tenant.userOwnerCompanies.user'   => fn ($q) => $q->withTrashed(),
              'tenant.address'                   => fn ($q) => $q->withTrashed(),
          ])

          // Filtro por tenant_id (somente para usuários com permissão)
          ->when(Auth::user()->hasPermissionTo('tenant.query_by'), function ($query) use ($data) {
              $query->when($data['tenant_id'] ?? null, function ($query, $tenantId) {
                  $query->where('tenant_id', $tenantId);
              });
          })

          // Busca textual em múltiplos campos
          ->when($search, function ($query, $search) {
              $query->where(function ($q) use ($search) {
                  $q->where('cnpj',           'like',  "%{$search}%")
                    ->orWhere('corporate_name','ilike', "%{$search}%")
                    ->orWhere('trade_name',    'ilike', "%{$search}%")
                    ->orWhere('email',         'ilike', "%{$search}%")
                    ->orWhere('phone',         'like',  "%{$search}%");
              });
          })

          // Ordenação padrão
          ->orderByDesc('created_at');

      // 3.3 — Paginação: executa a query e retorna um LengthAwarePaginator
      $paginator = $query->paginate($perPage, ['*'], 'page', $page);

      // 3.4 — Retorna a Collection wrapeando o paginator
      return new ClientCollection($paginator);
  }

  ► NOTA SOBRE "like" vs "ilike":
    - 'like'  → busca case-sensitive (usada em campos como cnpj e phone,
                 que não precisam de case-insensitive).
    - 'ilike' → busca case-insensitive (PostgreSQL). Para MySQL, seria
                 necessário usar LOWER() ou COLLATE.

  ► NOTA SOBRE O MÉTODO when():
    O when() executa o callback somente se o primeiro argumento for truthy.
    Isso evita condicionais if/else espalhadas pela query e mantém a
    construção do Builder fluida e legível.


--------------------------------------------------------------------------------
4. A COLLECTION — FORMATAÇÃO DA RESPOSTA
--------------------------------------------------------------------------------

  // App\Http\Resources\Client\ClientCollection.php

  class ClientCollection extends ResourceCollection
  {
      public $collects = ClientResource::class;

      public function toArray(Request $request): array
      {
          return [
              'data'       => $this->collection,   // array de ClientResource
              'pagination' => [
                  'total'        => $this->total(),       // total de registros
                  'count'        => $this->count(),       // registros nesta página
                  'per_page'     => $this->perPage(),     // itens por página
                  'current_page' => $this->currentPage(), // página atual
                  'total_pages'  => $this->lastPage(),    // total de páginas
              ],
          ];
      }
  }

A ClientCollection recebe o LengthAwarePaginator vindo do Service.
O Laravel automaticamente disponibiliza os métodos de paginação (total(),
perPage(), currentPage(), etc.) porque a Collection estende ResourceCollection
e detecta que o objeto passado é um paginator.

A propriedade $collects = ClientResource::class indica que cada item
da coleção será transformado individualmente pelo ClientResource antes
de compor o array 'data'.


--------------------------------------------------------------------------------
5. EXEMPLO DE RESPOSTA JSON
--------------------------------------------------------------------------------

  {
    "message": "Clientes listados com sucesso!",
    "data": {
      "data": [
        {
          "id": "uuid-do-cliente",
          "corporate_name": "Empresa Exemplo LTDA",
          "trade_name": "Empresa Exemplo",
          "cnpj": "00.000.000/0001-00",
          "email": "contato@empresa.com",
          "phone": "(11) 99999-9999",
          ...
        },
        ...
      ],
      "pagination": {
        "total": 87,
        "count": 10,
        "per_page": 10,
        "current_page": 1,
        "total_pages": 9
      }
    }
  }


--------------------------------------------------------------------------------
6. RESUMO DO PADRÃO
--------------------------------------------------------------------------------

  ┌─────────────────────────────────────────────────────────────────────────┐
  │ RESPONSABILIDADE DE CADA CAMADA                                         │
  ├──────────────────┬──────────────────────────────────────────────────────┤
  │ FormRequest      │ Valida, sanitiza e permite apenas campos conhecidos   │
  ├──────────────────┼──────────────────────────────────────────────────────┤
  │ Controller       │ Orquestra: recebe request, chama service, devolve    │
  │                  │ resposta. Sem lógica de negócio.                     │
  ├──────────────────┼──────────────────────────────────────────────────────┤
  │ Service          │ Contém toda a lógica: monta query, aplica filtros,   │
  │                  │ pagina e retorna a Collection.                       │
  ├──────────────────┼──────────────────────────────────────────────────────┤
  │ Collection       │ Formata a saída: transforma cada Model em Resource   │
  │                  │ e adiciona metadados de paginação.                   │
  └──────────────────┴──────────────────────────────────────────────────────┘

================================================================================