<?php
// Lista usuários (qualquer usuário logado pode consultar). Aceita ?busca=texto
require 'conexao.php';
if (!isset($_SESSION['id'])) json_out(['erro' => 'Não autenticado'], 401);

$busca = '%' . trim($_GET['busca'] ?? '') . '%';
$stmt = $conn->prepare(
    'SELECT u.id, u.nome, u.email, t.descricao AS tipo
     FROM usuarios u JOIN tipos_usuario t ON t.id = u.tipo_id
     WHERE u.nome LIKE ? OR u.email LIKE ? ORDER BY u.nome');
$stmt->bind_param('ss', $busca, $busca);
$stmt->execute();
json_out($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
