<?php
// Conexão PHP <-> MySQL (ajuste usuário/senha conforme seu ambiente)
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'Povbd';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die('Erro de conexão com o banco de dados.');
}
$conn->set_charset('utf8mb4');

// Só inicia a sessão se ainda não houver uma (evita o aviso de "session already active")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
