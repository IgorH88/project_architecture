# Resumo Estruturado de DDD com Exemplos em PHP

Vou montar isso como se eu estivesse explicando para alguém do time que nunca viu DDD, mas já sabe PHP. A ideia é: pouca teoria, bastante “como eu organizaria o código”.

---

## 1. O que é DDD (na prática)

**DDD (Domain-Driven Design)** é uma forma de organizar seu código onde **o negócio vem primeiro** e a tecnologia serve a ele.

Em vez de começar por “controllers, models, migrations”, você começa por:
- Quais conceitos existem no negócio? (Pedido, Cliente, Fatura…)
- O que eles fazem? (Confirmar pedido, cancelar, recalcular total…)
- Quais regras nunca podem ser quebradas? (Pedido não pode ter quantidade negativa, etc.)

DDD ajuda você a:
- Ter código que fala a mesma língua que o negócio.
- Isolar regras de negócio da infraestrutura (framework, DB, fila, etc.).
- Facilitar manutenção e evolução, porque o domínio fica claro.

---

## 2. Conceitos centrais de DDD

### 2.1. Ubiquitous Language (Linguagem Ubíqua)

É a **linguagem compartilhada** entre devs e negócio.

Exemplo (sistema de pedidos):
- “Pedido”
- “Item do Pedido”
- “Cliente”
- “Confirmar pedido”
- “Cancelar pedido”

Você usa **esses mesmos termos**:
- Nas conversas
- Nos casos de uso
- Nos nomes de classes e métodos

```php
$pedido->confirmar();
$pedido->cancelarPorAtrasoDePagamento();
```

Nada de `status = 3` se todo mundo fala “pedido cancelado”.

---

### 2.2. Bounded Context (Contexto Delimitado)

É um **pedaço do sistema** onde aquela linguagem faz sentido de forma consistente.

Exemplo:
- Contexto `Vendas`: Pedido, Produto, Cliente, Carrinho.
- Contexto `Faturamento`: Fatura, Boleto, Nota Fiscal.
- Contexto `Relatórios`: Dashboard, Indicadores, Gráficos.

Cada contexto:
- Tem seu **modelo próprio**.
- Pode até ter entidades com o mesmo nome, mas com significado diferente.

Na prática em PHP, você pode refletir isso na árvore de pastas:

```text
src/
  Vendas/
    Domain/
    Application/
    Infrastructure/
  Faturamento/
    Domain/
    Application/
    Infrastructure/
```

---

### 2.3. Entidade

- Tem **identidade** (ID) que importa mais do que os atributos.
- Muda ao longo do tempo.
- Tem comportamento (métodos) e não só getters/setters.

Exemplo: `Pedido`, `Cliente`.

---

### 2.4. Value Object

- Não tem identidade própria.
- É **imutável**.
- Representa um conceito de valor: `Dinheiro`, `Email`, `CPF`, `Endereco`.

Se o valor muda, você cria outro objeto.

---

### 2.5. Aggregate (Agregado) e Aggregate Root

- **Aggregate**: grupo de objetos de domínio que é tratado como um todo.
- **Aggregate Root**: a entidade “raiz” desse grupo, pela qual o resto é acessado.

Exemplo:
- Aggregate: `Pedido` + seus `ItensDePedido`.
- Aggregate Root: `Pedido`.

Regra prática:
- Você salva/carrega o aggregate sempre através da root.
- Não mexe nos itens direto por fora, sempre via `Pedido`.

---

## 3. Camadas típicas em um projeto PHP com DDD

Uma forma simples (estilo Clean Architecture / Hexagonal):

1. **Domain**
   - Entidades
   - Value Objects
   - Regras de negócio
   - Interfaces de repositório
   - Domain Services (quando uma regra não encaixa em uma entidade só)

2. **Application**
   - Casos de uso / Application Services
   - Orquestra o fluxo:
     - carrega entidades
     - chama métodos de domínio
     - persiste mudanças
   - Não tem regra de negócio complexa, só coordenação.

3. **Infrastructure**
   - Implementações concretas:
     - Repositórios (MySQL, Redis, etc.)
     - Mensageria, HTTP clients, etc.

4. **Interface (UI/API)**
   - Controllers HTTP, CLI, filas, etc.
   - Converte request → DTO/input do Application
   - Converte resposta do Application → HTTP/JSON, etc.

---

## 4. Estrutura de pastas exemplo (Contexto Vendas)

```text
src/
  Vendas/
    Domain/
      Entity/
      ValueObject/
      Repository/
      Service/
    Application/
      UseCase/
      DTO/
    Infrastructure/
      Persistence/
      Http/
```

Você aplica essa ideia também para seu `Dashboard` (outro contexto: `Relatorios`, por exemplo).

---

## 5. Exemplo prático completo em PHP

Vamos montar um mini contexto de **Vendas** com:

- Value Object `Dinheiro`
- Entidade/aggregate root `Pedido`
- Entidade `ItemPedido`
- Repositório de `Pedido` (interface + implementação usando PDO)
- Caso de uso `CriarPedido`
- Controller simples chamando o caso de uso

---

### 5.1. Value Object: Dinheiro

```php
<?php

namespace Vendas\Domain\ValueObject;

final class Dinheiro
{
    private float $valor;
    private string $moeda;

    public function __construct(float $valor, string $moeda = 'BRL')
    {
        if ($valor < 0) {
            throw new \InvalidArgumentException('Valor monetário não pode ser negativo.');
        }

        $this->valor = $valor;
        $this->moeda = $moeda;
    }

    public function valor(): float
    {
        return $this->valor;
    }

    public function moeda(): string
    {
        return $this->moeda;
    }

    public function somar(Dinheiro $outro): self
    {
        $this->assertMesmaMoeda($outro);

        return new self($this->valor + $outro->valor(), $this->moeda);
    }

    public function multiplicar(int $quantidade): self
    {
        if ($quantidade < 0) {
            throw new \InvalidArgumentException('Quantidade não pode ser negativa.');
        }

        return new self($this->valor * $quantidade, $this->moeda);
    }

    private function assertMesmaMoeda(Dinheiro $outro): void
    {
        if ($this->moeda !== $outro->moeda()) {
            throw new \InvalidArgumentException('Moedas diferentes não podem ser somadas.');
        }
    }
}
```

- **Invariantes**: não permite valor negativo, moeda deve ser igual quando somar.
- **Imutabilidade**: toda operação retorna um novo objeto.

---

### 5.2. Entidade ItemPedido

```php
<?php

namespace Vendas\Domain\Entity;

use Vendas\Domain\ValueObject\Dinheiro;

final class ItemPedido
{
    private int $produtoId;
    private string $descricao;
    private int $quantidade;
    private Dinheiro $precoUnitario;

    public function __construct(
        int $produtoId,
        string $descricao,
        int $quantidade,
        Dinheiro $precoUnitario
    ) {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException('Quantidade deve ser maior que zero.');
        }

        $this->produtoId = $produtoId;
        $this->descricao = $descricao;
        $this->quantidade = $quantidade;
        $this->precoUnitario = $precoUnitario;
    }

    public function subtotal(): Dinheiro
    {
        return $this->precoUnitario->multiplicar($this->quantidade);
    }

    public function quantidade(): int
    {
        return $this->quantidade;
    }

    public function produtoId(): int
    {
        return $this->produtoId;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }

    public function precoUnitario(): Dinheiro
    {
        return $this->precoUnitario;
    }
}
```

---

### 5.3. Entidade/Agregado: Pedido (Aggregate Root)

```php
<?php

namespace Vendas\Domain\Entity;

use DateTimeImmutable;
use Vendas\Domain\ValueObject\Dinheiro;

final class Pedido
{
    public const STATUS_RASCUNHO   = 'rascunho';
    public const STATUS_CONFIRMADO = 'confirmado';
    public const STATUS_CANCELADO  = 'cancelado';

    private ?int $id;
    /** @var ItemPedido[] */
    private array $itens = [];
    private string $status;
    private DateTimeImmutable $criadoEm;

    public function __construct(?int $id = null)
    {
        $this->id       = $id;
        $this->status   = self::STATUS_RASCUNHO;
        $this->criadoEm = new DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * Invariante: um pedido cancelado ou confirmado não pode ser alterado.
     */
    public function adicionarItem(ItemPedido $item): void
    {
        $this->assertPodeAlterar();

        $this->itens[] = $item;
    }

    public function confirmar(): void
    {
        if (empty($this->itens)) {
            throw new \DomainException('Não é possível confirmar um pedido sem itens.');
        }

        if ($this->status !== self::STATUS_RASCUNHO) {
            throw new \DomainException('Somente pedidos em rascunho podem ser confirmados.');
        }

        $this->status = self::STATUS_CONFIRMADO;
    }

    public function cancelar(): void
    {
        if ($this->status === self::STATUS_CANCELADO) {
            throw new \DomainException('Pedido já está cancelado.');
        }

        if ($this->status === self::STATUS_CONFIRMADO) {
            // aqui poderia haver uma regra de negócio mais sofisticada
            throw new \DomainException('Não é possível cancelar um pedido já confirmado.');
        }

        $this->status = self::STATUS_CANCELADO;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function total(): Dinheiro
    {
        $total = new Dinheiro(0);

        foreach ($this->itens as $item) {
            $total = $total->somar($item->subtotal());
        }

        return $total;
    }

    /**
     * Expor itens apenas para leitura (por exemplo, em views ou DTOs).
     *
     * @return ItemPedido[]
     */
    public function itens(): array
    {
        return $this->itens;
    }

    private function assertPodeAlterar(): void
    {
        if (!in_array($this->status, [self::STATUS_RASCUNHO], true)) {
            throw new \DomainException('Pedido não pode mais ser alterado.');
        }
    }
}
```

Repare:
- **Regras de negócio (invariantes)** estão DENTRO do `Pedido`.
- Ninguém altera `status` na mão de fora (`$pedido->status = ...`).
- Isso é **DDD + Encapsulamento**.

---

### 5.4. Repositório de Pedido (Interface no Domínio)

```php
<?php

namespace Vendas\Domain\Repository;

use Vendas\Domain\Entity\Pedido;

interface PedidoRepository
{
    public function salvar(Pedido $pedido): void;

    public function buscarPorId(int $id): ?Pedido;
}
```

- Interface no **Domínio** → não sabe se é MySQL, PostgreSQL, ORM, etc.
- **DIP (Dependency Inversion Principle)**: camada de fora (Infra) implementa isso.

---

### 5.5. Caso de uso (Application Layer)

```php
<?php

namespace Vendas\Application\UseCase;

use Vendas\Domain\Entity\ItemPedido;
use Vendas\Domain\Entity\Pedido;
use Vendas\Domain\Repository\PedidoRepository;
use Vendas\Domain\ValueObject\Dinheiro;

final class CriarPedidoHandler
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * @param array{produtoId:int, descricao:string, quantidade:int, precoUnitario:float}[] $itens
     */
    public function handle(array $itens): int
    {
        $pedido = new Pedido();

        foreach ($itens as $itemData) {
            $item = new ItemPedido(
                produtoId: $itemData['produtoId'],
                descricao: $itemData['descricao'],
                quantidade: $itemData['quantidade'],
                precoUnitario: new Dinheiro($itemData['precoUnitario'])
            );

            $pedido->adicionarItem($item);
        }

        $pedido->confirmar();

        $this->pedidoRepository->salvar($pedido);

        // Em um cenário real, o ID viria do DB após salvar.
        // Vamos supor que o repo atualiza o ID na entidade.
        return $pedido->id();
    }
}
```

Aqui:
- Sem regra de negócio “inteligente”, só orquestra:
  - cria pedido
  - adiciona itens
  - confirma
  - salva

Isso mantém a **regra no Domínio**, não no use case.

---

### 5.6. Implementação concreta do Repositório (Infra)

```php
<?php

namespace Vendas\Infrastructure\Persistence;

use PDO;
use Vendas\Domain\Entity\ItemPedido;
use Vendas\Domain\Entity\Pedido;
use Vendas\Domain\Repository\PedidoRepository;
use Vendas\Domain\ValueObject\Dinheiro;

final class PdoPedidoRepository implements PedidoRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function salvar(Pedido $pedido): void
    {
        if ($pedido->id() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pedidos (status, criado_em) VALUES (:status, :criado_em)'
            );
            $stmt->execute([
                'status'    => $pedido->status(),
                'criado_em' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            // atribui ID gerado
            $id = (int) $this->pdo->lastInsertId();
            $reflection = new \ReflectionObject($pedido);
            $property   = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($pedido, $id);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE pedidos SET status = :status WHERE id = :id'
            );
            $stmt->execute([
                'status' => $pedido->status(),
                'id'     => $pedido->id(),
            ]);

            // Apagar itens antigos em cenário simples
            $del = $this->pdo->prepare('DELETE FROM itens_pedido WHERE pedido_id = :id');
            $del->execute(['id' => $pedido->id()]);
        }

        // salvar itens
        $insertItem = $this->pdo->prepare(
            'INSERT INTO itens_pedido (pedido_id, produto_id, descricao, quantidade, preco_unitario) 
             VALUES (:pedido_id, :produto_id, :descricao, :quantidade, :preco_unitario)'
        );

        foreach ($pedido->itens() as $item) {
            $insertItem->execute([
                'pedido_id'      => $pedido->id(),
                'produto_id'     => $item->produtoId(),
                'descricao'      => $item->descricao(),
                'quantidade'     => $item->quantidade(),
                'preco_unitario' => $item->precoUnitario()->valor(),
            ]);
        }
    }

    public function buscarPorId(int $id): ?Pedido
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pedidos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $pedido = new Pedido((int) $row['id']);

        // carregar itens
        $stmtItens = $this->pdo->prepare(
            'SELECT * FROM itens_pedido WHERE pedido_id = :pedido_id'
        );
        $stmtItens->execute(['pedido_id' => $id]);

        while ($itemRow = $stmtItens->fetch(PDO::FETCH_ASSOC)) {
            $item = new ItemPedido(
                produtoId: (int) $itemRow['produto_id'],
                descricao: $itemRow['descricao'],
                quantidade: (int) $itemRow['quantidade'],
                precoUnitario: new Dinheiro((float) $itemRow['preco_unitario'])
            );

            $pedido->adicionarItem($item);
        }

        return $pedido;
    }
}
```

> Obs.: Aqui tem algumas “gambiarras” (Reflection) só para exemplo. Em produção, eu preferiria uma abordagem mais limpa (ORM ou mappers específicos).

---

### 5.7. Controller chamando o caso de uso (Interface/API)

Exemplo bem simples, sem framework específico:

```php
<?php

use Vendas\Application\UseCase\CriarPedidoHandler;

final class PedidoController
{
    public function __construct(
        private CriarPedidoHandler $criarPedidoHandler
    ) {}

    public function criar(): void
    {
        // Em um framework, você receberia isso via Request
        $payload = json_decode(file_get_contents('php://input'), true);

        $pedidoId = $this->criarPedidoHandler->handle($payload['itens']);

        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(['id' => $pedidoId]);
    }
}
```

---

## 6. Como isso conversa com SOLID

- **SRP (Responsabilidade Única)**
  - `Pedido`: lida com regras de pedido.
  - `CriarPedidoHandler`: orquestra o caso de uso.
  - `PdoPedidoRepository`: lida com persistência.

- **OCP (Aberto/Fechado)**
  - Você pode adicionar um novo tipo de repositório (`MongoPedidoRepository`, `InMemoryPedidoRepository`) sem modificar o domínio.

- **LSP**
  - Qualquer implementação de `PedidoRepository` deve se comportar como definido na interface, sem quebrar quem usa.

- **ISP**
  - Repositórios com interfaces pequenas, focadas no que o domínio precisa, não em tudo que o banco consegue fazer.

- **DIP**
  - Application depende da **abstração** `PedidoRepository`, não de `PdoPedidoRepository`.
  - A infraestrutura “conhece” o domínio, não o contrário.

---

## 7. Como começar a aplicar DDD no seu projeto

Sugestão de passo-a-passo, especialmente se o projeto já existe:

1. **Escolha um pedaço pequeno e importante**  
   Ex.: o fluxo de pedidos, ou o módulo de dashboard mais crítico.

2. **Defina a linguagem ubíqua**  
   Liste os termos que o negócio usa e fixe esses nomes no código.

3. **Identifique Entidades, Value Objects e Aggregates**
   - Entidade: precisa de ID, tem ciclo de vida.
   - Value Object: valor, imutável, sem ID.
   - Aggregate: o “pacote” em volta da entidade principal.

4. **Modele as regras dentro das entidades**
   - Tire `if` de regra de negócio de controllers e services “genéricos”.
   - Métodos ricos: `confirmar()`, `cancelar()`, `aplicarDesconto()` etc.

5. **Crie Application Services para orquestrar casos de uso**
   - Ex.: `CriarPedidoHandler`, `ConfirmarPedidoHandler`.

6. **Extraia interfaces de repositório para o domínio**
   - `PedidoRepository`, `UsuarioRepository`, etc.

7. **Implemente essas interfaces na Infraestrutura**
   - Use DB, ORM e o que precisar, mas mantendo o domínio isolado.
