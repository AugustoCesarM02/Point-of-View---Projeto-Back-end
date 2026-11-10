<?php
// Usado pelos endpoints do perfil (fotos e biografia).
// Só deixa passar: método POST, usuário logado e token CSRF válido.
// Depois do require, a variável $idUsuario já está pronta para uso.
require __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['erro' => 'Método não permitido.'], 405);
}

if (!isset($_SESSION['id_usuario'])) {
    json_out(['erro' => 'Faça login para continuar.'], 401);
}

// Se o envio passa do limite do PHP (post_max_size), $_POST e $_FILES chegam vazios
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    json_out(['erro' => 'O arquivo é grande demais.'], 413);
}

// Token gerado em Perfil.php (mesma chave de sessão usada no DashboardU.php)
$tokenRecebido = $_POST['csrf'] ?? '';
if (
    !isset($_SESSION['csrf'])
    || !is_string($tokenRecebido)
    || !hash_equals((string) $_SESSION['csrf'], $tokenRecebido)
) {
    json_out(['erro' => 'Sessão expirada. Recarregue a página e tente de novo.'], 403);
}

$idUsuario = (int) $_SESSION['id_usuario'];
