<?php

namespace Alura\Solid\Model;

use Alura\Solid\Model\Pontuacao;

class Video implements Pontuacao
{
    /** @var bool */
    protected $assistido = false;
    /** @var string */
    protected $nome;
    /** @var \DateInterval */
    protected $duracao;

    public function __construct(string $nome)
    {
        $this->nome = $nome;
        $this->assistido = false;
        $this->duracao = \DateInterval::createFromDateString('0');
    }

    public function assistir(): void
    {
        $this->assistido = true;
    }

    public function minutosDeDuracao(): int
    {
        return $this->duracao->i;
    }

    public function recuperarUrl(): string
    {
        return 'http://videos.alura.com.br/' . http_build_query(['nome' => $this->nome]);
    }

    public function recuperaPontuacao(): int
    {
        return 100;
    }
}
