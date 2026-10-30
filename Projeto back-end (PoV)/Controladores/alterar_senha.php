<?php
require 'conexao.php';
if (!isset($_SESSION['id'])) json_out(['erro' => 'Não autenticado'], 401);

$atual = $_POST['atual'] ?? '';
$nova  = $_POST['nova'] ?? '';
if (strlen($nova) < 8) json_out(['erro' => 'A nova senha deve ter no mínimo 8 caracteres'], 400);

$stmt = $conn->prepare('SELECT senha FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u || !password_verify($atual, $u['senha'])) json_out(['erro' => 'Senha atual incorreta'], 400);

$hash = password_hash($nova, PASSWORD_DEFAULT);
$up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
$up->bind_param('si', $hash, $_SESSION['id']);
$up->execute();
json_out(['ok' => true]);
