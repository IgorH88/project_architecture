<?php

class UsuarioService
{
    // ❌ Viola SRP: cria usuário, valida, escreve no "banco", envia e-mail e faz log
    public function registrar(string $nome, string $email): void
    {

        // "Persistência"
        echo "Salvando usuário {$nome} <{$email}> no banco...\n";

        // "Envio de e-mail"
        echo "Enviando e-mail de boas-vindas para {$email}...\n";

        // "Log"
        echo "[LOG] Usuário {$nome} registrado com sucesso.\n";
    }
}

class User 
{
    private string $nome;
}

$email = UserValidete::validate('anaexample.com');
echo $email ;



// // --- Exemplo de uso ---
// $svc = new UsuarioService();
// $svc->registrar('Ana', 'ana@example.com');

