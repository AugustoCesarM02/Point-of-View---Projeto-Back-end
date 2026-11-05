<?php
require __DIR__ . '/../Conectivos/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/Login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

// Busca o usuário pelo e-mail (colunas conforme o Pov.sql)
$stmt = $conn->prepare('SELECT id_usuario, nome, senha, tipo_id FROM usuarios WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($u && password_verify($senha, $u['senha'])) {
    // Se o hash for antigo, atualiza para o algoritmo atual
    if (password_needs_rehash($u['senha'], PASSWORD_DEFAULT)) {
        $novo = password_hash($senha, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id_usuario = ?');
        $up->bind_param('si', $novo, $u['id_usuario']);
        $up->execute();
        $up->close();
    }

    session_regenerate_id(true);

    // Mesmas chaves de sessão usadas no cadastro_Control.php e no Index.php
    $_SESSION['id_usuario'] = (int)$u['id_usuario'];
    $_SESSION['nome']       = $u['nome'];
    $_SESSION['tipo_id']    = (int)$u['tipo_id'];

    header('Location: ../View/Index.php');
    exit;
}

// E-mail ou senha incorretos
header('Location: ../View/Login.html?erro=1');
exit;
