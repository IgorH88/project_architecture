<?php

namespace Vendas\Domain\ValueObject;

final class Dinheiro
{
    private float $valor;
    private string $moeda;

    public function __construct(float $valor, string $moeda)
    {
        if($valor < 0) {
            throw new \InvalidArgumentException("O valor do dinheiro não pode ser negativo.");
        }

        $this->valor = $valor;
        $this->moeda = $moeda;
    }

    public function valor(): float
    {
        return $this->valor;
    }

    public function getMoeda(): string
    {
        return $this->moeda;
    }

    public function sumar(Dinheiro $outro): self
    {
        $this->assertMesmaMoeda($outro);

        return new Dinheiro($this->valor + $outro->valor(), $this->moeda);
    }

    public function assertMesmaMoeda(Dinheiro $outro): void
    {
        if ($this->moeda !== $outro->getMoeda()) {
            throw new \InvalidArgumentException("As moedas devem ser iguais para realizar operações.");
        }
    }

    public function multiplicar(float $quantidade): self
    {
        if($quantidade < 0) {
            throw new \InvalidArgumentException("O quantidade não pode ser negativo.");
        }

        return new self($this->valor * $quantidade, $this->moeda);
    }
}