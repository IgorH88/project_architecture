<?php

namespace Alura\Solid\Model;

use Alura\Solid\Model\Pontuacao;

class AluraMais extends Video implements Pontuacao
{
    private $categoria;

    public function __construct(string $nome, string $categoria)
    {
        parent::__construct($nome);
        $this->categoria = $categoria;
    }

    public function recuperarUrl(): string
    {
        return str_replace(' ', '-', strtolower($this->categoria));
    }

    public function recuperaPontuacao(): int
    {
        return $this->minutosDeDuracao() * 2;
    }
}
