<?php

class MySqlConexao
{
    public function salvarPedido(string $dados): void
    {
        echo "[MySQL] Salvando: {$dados}\n";
    }
}

class EmailNotificador
{
    public function enviar(string $mensagem): void
    {
        echo "[EMAIL] {$mensagem}\n";
    }
}

class ProcessadorDePedido
{
    // ❌ Viola DIP: depende de implementações concretas
    private MySqlConexao $db;
    private EmailNotificador $notificador;

    public function __construct()
    {
        $this->db = new MySqlConexao();          // acoplamento rígido
        $this->notificador = new EmailNotificador(); // acoplamento rígido
    }

    public function processar(string $pedido): void
    {
        $this->db->salvarPedido($pedido);
        $this->notificador->enviar("Pedido processado: {$pedido}");
    }
}

// --- Exemplo de uso ---
$proc = new ProcessadorDePedido();
$proc->processar('Pedido #123');
