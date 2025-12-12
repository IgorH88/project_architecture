<?php

namespace Vendas\Domain\Entity;

use Vendas\Domain\ValueObject\Dinheiro;
use Vendas\Domain\Enum\StatusPedido;
use DateTimeImmutable;

final class Pedido
{
    private ?int $id;
    private array $itens = [];
    private StatusPedido $status;
    private DateTimeImmutable $criadoEm;


    public function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->status = StatusPedido::RASCUNHO;
        $this->criadoEm = new DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function adicionarItem(ItemPedido $item): void
    {
        $this->assertPodeAlterar();

        $this->itens[] = $item;
    }

    public function confirmar(): void
    {
        if (!$this->status->podeSerConfirmado()) {
            throw new \DomainException("O pedido não pode ser confirmado no status atual.");
        }

        $this->status = StatusPedido::CONFIRMADO;
    }

    public function cancelar(): void
    {
        if (!$this->status->podeSerCancelado()) {
            throw new \DomainException("O pedido não pode ser cancelado no status atual.");
        }

        $this->status = StatusPedido::CANCELADO;
    }

    public function status(): StatusPedido
    {
        return $this->status;
    }

    public function total(): Dinheiro
    {
        $total = new Dinheiro(0, 'BRL');

        foreach ($this->itens as $item) {
            $total = $total->sumar($item->subtotal());
        }

        return $total;
    }

    public function itens(): array
    {
        return $this->itens;
    }

    public function assertPodeAlterar(): void
    {
        if (!$this->status->podeSerAlterado()) {
            throw new \DomainException("O pedido não pode ser alterado no status atual.");
        }
    }
}