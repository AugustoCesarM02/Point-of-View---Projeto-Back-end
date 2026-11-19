<?php
// Responde (JSON) se um e-mail é válido e se já está em uso.
// Chamado pelo JS/JSCadastro.js enquanto a pessoa preenche o cadastro.
// Usa POST para o e-mail não aparecer na URL (histórico, logs do servidor).
require __DIR__ . '/../Conectivos/conexao.php';
require __DIR__ . '/../Conectivos/validacao_cadastro.php';

// Esta consulta acontece a cada e-mail digitado: não vale encher o log do sistema com ela
if (function_exists('log_pular')) {
    log_pular();
}

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['erro' => 'Método não permitido.'], 405);
}

$email = campo_texto('email');

$codigo = validar_email($email);
if ($codigo !== null) {
    json_out(['valido' => false, 'disponivel' => false, 'mensagem' => mensagem_cadastro($codigo)]);
}

$emUso = valor_em_uso($conn, 'email', $email);
if ($emUso === null) {
    json_out(['erro' => 'Não foi possível verificar o e-mail agora.'], 500);
}

json_out([
    'valido'     => true,
    'disponivel' => !$emUso,
    'mensagem'   => $emUso ? mensagem_cadastro('email_em_uso') : 'E-mail disponível.',
]);
