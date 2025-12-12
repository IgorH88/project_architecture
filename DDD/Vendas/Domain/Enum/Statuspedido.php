<?php

namespace Vendas\Domain\Enum;

enum StatusPedido: string
{
    case RASCUNHO   = 'rascunho';
    case CONFIRMADO = 'confirmado';
    case CANCELADO  = 'cancelado';

    public function podeSerAlterado(): bool
    {
        return $this === self::RASCUNHO;
    }

    public function podeSerConfirmado(): bool
    {
        return $this === self::RASCUNHO;
    }

    public function podeSerCancelado(): bool
    {
        return $this !== self::CANCELADO;
    }
}
