<?php
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    json_out(['logado' => false], 401);
}

json_out([
    'logado' => true,
    'id'     => $_SESSION['id_usuario'],
    'nome'   => $_SESSION['nome'],
    'tipo'   => $_SESSION['tipo_id']
]);
