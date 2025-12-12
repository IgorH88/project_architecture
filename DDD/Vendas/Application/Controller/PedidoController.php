<?php

use Vendas\Application\Service\CriarPedidoHandler;

final class PedidoController
{
    public function __construct(
        private CriarPedidoHandler $criarPedidoHandler
    ) {}

    public function criar(): void
    {
        $payload = [[
            'produto_id' => 1,
            'descricao' => 'Produto A',
            'quantidade' => 2,
            'preco_unitario' => 50.0,
        ], [
            'produto_id' => 2,
            'descricao' => 'Produto B',
            'quantidade' => 1,
            'preco_unitario' => 100.0,
        ]];

        $pedidoId = $this->criarPedidoHandler->handle($payload);

        echo $pedidoId;
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(['id' => $pedidoId]);
    }
}