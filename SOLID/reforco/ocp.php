<?php

class CalculadoraDesconto
{
    // ❌ Viola OCP: cada novo tipo exige mudar este método
    public function calcular(float $valor, string $tipoCliente): float
    {
        if ($tipoCliente === 'vip') {
            return $valor * 0.80; // 20% off
        } elseif ($tipoCliente === 'novo') {
            return $valor * 0.90; // 10% off
        } elseif ($tipoCliente === 'estudante') {
            return $valor * 0.85; // 15% off
        } else {
            return $valor; // sem desconto
        }
    }
}

// --- Exemplo de uso ---
$calc = new CalculadoraDesconto();
echo $calc->calcular(100, 'vip') . PHP_EOL;
