<?php

namespace Alura\Solid\Model;

use Alura\Solid\Model\Pontuacao;
use Alura\Solid\Model\Assistir;

class AluraMais extends Video implements Pontuacao, Assistir
{
    private $categoria;

    public function __construct(string $nome, string $categoria)
    {
        parent::__construct($nome);
        $this->categoria = $categoria;
    }

    public function recuperarUrl(): string
    {
        return 'http://videos.alura.com.br/' . str_replace(' ', '-', strtolower($this->categoria));
    }

    public function recuperaPontuacao(): int
    {
        return $this->minutosDeDuracao() * 2;
    }


}
