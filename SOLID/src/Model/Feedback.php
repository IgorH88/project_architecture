<?php


class Feedback
{
    public function __construct(private int $nota, private ?string $depoimento)
    {
        if ($nota < 9 && empty($depoimento)) {
            throw new \DomainException('Depoimento obrigatório');
        }

        $this->nota = $nota;
        $this->depoimento = $depoimento;
    }   

    public function getNota(): int
    {
        return $this->nota;
    }
    
    public function getDepoimento(): ?string
    {
        return $this->depoimento;
    }

}