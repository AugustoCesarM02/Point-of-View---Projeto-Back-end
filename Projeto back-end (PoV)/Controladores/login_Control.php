<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../html/login.html'); exit; }

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

$stmt = $conn->prepare('SELECT id, nome, senha, tipo_id FROM usuarios WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($u && password_verify($senha, $u['senha'])) {
    // Se o hash for antigo (ex.: o do admin no SQL), atualiza para bcrypt
    if (password_needs_rehash($u['senha'], PASSWORD_DEFAULT)) {
        $novo = password_hash($senha, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $up->bind_param('si', $novo, $u['id']);
        $up->execute();
    }
    session_regenerate_id(true);
    $_SESSION['id'] = (int)$u['id'];
    $_SESSION['nome'] = $u['nome'];
    $_SESSION['tipo'] = (int)$u['tipo_id'];
    header('Location: ../html/perfil.html');
} else {
    header('Location: ../html/login.html?erro=1');
}
