<?php

namespace Vendas\Infrastructure\Persistence;

use PDO;
use Vendas\Domain\Entity\ItemPedido;
use Vendas\Domain\Entity\Pedido;
use Vendas\Domain\Interface\PedidoRepositoryInterface;
use Vendas\Domain\ValueObject\Dinheiro;

final class PdoPedidoRepository implements PedidoRepositoryInterface
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
                precoUnitario: new Dinheiro((float) $itemRow['preco_unitario'], 'BRL')
            );

            $pedido->adicionarItem($item);
        }

        return $pedido;
    }
}