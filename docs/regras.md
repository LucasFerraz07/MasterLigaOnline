# Master Liga — Regras do Sistema

## Visão Geral

Master Liga é uma plataforma de fantasy football onde grupos de amigos criam ligas virtuais, montam elencos com jogadores reais, disputam partidas entre si e participam de um mercado de transferências. Cada liga é independente e gerenciada por um dono (owner), que define as regras específicas da sua competição.

---

## Papéis no Sistema

**Administrador do Sistema (System Admin)**
Responsável pela gestão global da plataforma. Cadastra e mantém o catálogo de jogadores e clubes disponíveis, gerencia os planos de assinatura e tem visibilidade total sobre todas as ligas.

**Dono da Liga (Owner)**
Cria e administra uma liga. Define as configurações da competição, controla as temporadas e também participa como competidor com seu próprio clube e elenco.

**Participante**
Membro de uma liga. Gerencia seu clube, seu elenco e suas negociações dentro da liga em que está inscrito.

---

## Ligas

Cada liga é uma competição isolada. Participantes de ligas diferentes não interagem entre si — mercado, partidas e classificação são sempre dentro da mesma liga.

Ao criar uma liga, o owner assina um plano que define o número máximo de participantes permitidos. Quando esse limite é atingido, novos participantes não podem entrar até que o plano seja atualizado.

A liga possui uma assinatura ativa com data de vencimento. Quando a assinatura vence sem renovação, o acesso à liga é bloqueado automaticamente.

---

## Clubes

O sistema possui um catálogo global de clubes, organizados por região (Américas, Europa e Seleções). Cada participante escolhe um clube do catálogo para representar na temporada. Dentro da mesma liga e temporada, dois participantes não podem usar o mesmo clube.

---

## Temporadas

Uma liga pode ter múltiplas temporadas ao longo do tempo, sempre de forma sequencial — uma temporada precisa ser encerrada antes de a próxima começar.

Cada temporada passa pelas seguintes fases:

1. **Janela Inicial** — participantes montam seus elencos, podendo adquirir jogadores livres e comprar jogadores de outros participantes por multa.
2. **Primeiro Turno** — fase de disputas. O mercado de transferências fica fechado.
3. **Janela Intermediária** — mercado reabre para negociações entre participantes e compras de jogadores livres. Compras por multa não são permitidas nessa janela.
4. **Segundo Turno** — fase final de disputas. O mercado fecha novamente.

Ao encerrar uma temporada, todos os dados históricos são preservados.

---

## Elenco

Cada participante monta seu elenco com jogadores do catálogo global. Um jogador só pode pertencer a um único participante por temporada — não é possível que dois participantes tenham o mesmo jogador ao mesmo tempo.

O owner pode definir um limite máximo de jogadores por categoria no elenco de cada participante. Esse limite é informativo durante a temporada — não bloqueia contratações — mas ao final de cada janela de transferências, participantes que ultrapassarem o teto perdem automaticamente os jogadores excedentes (os de maior overall), que voltam ao mercado livre sem compensação financeira.

---

## Jogadores e Preços

O catálogo de jogadores é global e compartilhado entre todas as ligas. Cada jogador possui um overall e pertence a uma categoria (branca, bronze, prata, dourada ou preta), que determina seu valor de mercado e de multa.

O preço de um jogador é calculado automaticamente com base no seu overall e categoria. O valor de multa corresponde ao dobro do preço de mercado.

---

## Mercado de Transferências

### Compra de Jogador Livre
Qualquer participante pode comprar um jogador que não pertença a nenhum elenco na temporada vigente, desde que tenha saldo suficiente. Ao concluir a compra, o saldo é debitado e o jogador passa a integrar o elenco do comprador.

### Negociações entre Participantes
Participantes podem enviar propostas de negociação entre si. Uma proposta pode envolver qualquer combinação de jogadores e valores em dinheiro de cada lado — troca pura de jogadores, dinheiro por jogador, ou qualquer outra combinação. Cada lado da proposta deve conter pelo menos um item.

O participante que recebe a proposta pode aceitá-la, recusá-la ou ignorá-la. Quem enviou pode cancelar a proposta enquanto ela estiver pendente. Ao ser aceita, todos os itens da proposta são processados simultaneamente.

### Compra por Multa
Na janela inicial, um participante pode adquirir um jogador de outro participante pagando o valor de multa, sem necessidade de aceite do vendedor. O valor é debitado do comprador e creditado ao vendedor.

O owner define quantas compras por multa cada participante pode realizar e quantas perdas por multa cada participante pode sofrer por temporada. Jogadores adquiridos na própria janela inicial estão protegidos contra compra por multa na mesma janela.

---

## Partidas e Classificação

Ao iniciar o primeiro turno, o sistema gera automaticamente todos os confrontos da temporada em formato de pontos corridos — cada participante enfrenta todos os outros exatamente uma vez por turno. O segundo turno é o espelho do primeiro, com mandante e visitante invertidos.

Se o número de participantes for ímpar, um participante fica de folga por rodada, rotacionando para que todos fiquem de folga o mesmo número de vezes. A folga equivale a uma vitória automática.

A classificação é atualizada automaticamente após cada partida finalizada, considerando pontos, vitórias, empates, derrotas e saldo de gols.

---

## Finanças

Cada participante possui um saldo individual que é atualizado automaticamente a cada movimentação — compras, vendas, multas e créditos iniciais. Todas as movimentações ficam registradas no histórico financeiro do participante. Uma contratação só é bloqueada quando o saldo for insuficiente para cobrir o valor da operação.