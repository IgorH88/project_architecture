<?php

namespace Vendas\Application\Service;

use Vendas\Domain\Entity\ItemPedido;
use Vendas\Domain\Entity\Pedido;
use Vendas\Domain\Interface\PedidoRepositoryInterface; 
use Vendas\Domain\ValueObject\Dinheiro;

final class CriarPedidoHandler
{
    public function __construct(
        private PedidoRepositoryInterface $pedidoRepository
    ) {}

    public function handle(array $itens): int
    {
        $pedido = new Pedido();

        foreach ($itens as $itemData) {
            $item = new ItemPedido(
                produtoId: $itemData['produtoId'],
                descricao: $itemData['descricao'],
                quantidade: $itemData['quantidade'],
                precoUnitario: new Dinheiro($itemData['precoUnitario'], 'BRL')
            );

            $pedido->adicionarItem($item);
        }

        $pedido->confirmar();

        $this->pedidoRepository->salvar($pedido);

        return $pedido->id();
    }
}