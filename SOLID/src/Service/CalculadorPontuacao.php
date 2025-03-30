<?php

namespace Alura\Solid\Service;

use Alura\Solid\Model\Pontuacao;

class CalculadorPontuacao
{
    public function recuperarPontuacao(Pontuacao $conteudo)
    {
        return $conteudo->recuperaPontuacao();
    }
}
