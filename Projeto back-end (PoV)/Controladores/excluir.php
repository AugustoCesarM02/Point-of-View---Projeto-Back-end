<?php
// Somente admin (tipo 2) pode excluir
require 'conexao.php';
if (($_SESSION['tipo'] ?? 0) !== 2) json_out(['erro' => 'Sem permissão'], 403);

$id = (int)($_POST['id'] ?? 0);
if ($id === $_SESSION['id']) json_out(['erro' => 'Você não pode excluir a si mesmo'], 400);

$stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ? AND tipo_id <> 2');
$stmt->bind_param('i', $id);
$stmt->execute();
json_out($stmt->affected_rows ? ['ok' => true] : ['erro' => 'Usuário não encontrado'], $stmt->affected_rows ? 200 : 404);
