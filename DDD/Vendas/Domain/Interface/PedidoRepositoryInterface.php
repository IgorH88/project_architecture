<?php

namespace Vendas\Domain\Interface;

use Vendas\Domain\Entity\Pedido;

interface PedidoRepositoryInterface
{
    public function salvar(Pedido $pedido): void;

    public function buscarPorId(int $id): ?Pedido;
}