<?php

namespace Vendas\Domain\Entity;

use Vendas\Domain\ValueObject\Dinheiro;

final class ItemPedido
{
    private int $produtoId;
    private int $quantidade;
    private Dinheiro $precoUnitario;    
    private string $descricao;

    public function __construct(int $produtoId, int $quantidade, Dinheiro $precoUnitario, string $descricao)
    {
        $this->validarPedido($quantidade);

        $this->produtoId = $produtoId;
        $this->quantidade = $quantidade;
        $this->precoUnitario = $precoUnitario;
        $this->descricao = $descricao;
    }

    public function validarPedido(int $quantidade ): void
    {
        if($quantidade <= 0) {
            throw new \InvalidArgumentException("A quantidade deve ser maior que zero.");
        }
    }

    public function qantidade()
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

    public function subtotal(): Dinheiro
    {
        return $this->precoUnitario->multiplicar($this->quantidade);
    }

}