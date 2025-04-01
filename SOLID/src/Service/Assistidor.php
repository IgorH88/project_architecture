<?php

namespace Alura\Solid\Service;

use Alura\Solid\Model\Assistir;

class Assistidor
{
    public function assistir(Assistir $conteudo): void
    {
        $conteudo->assistir();
    }
}
