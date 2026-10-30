<?php
// Informa à página de perfil quem está logado
require 'conexao.php';
if (!isset($_SESSION['id'])) json_out(['logado' => false], 401);
json_out(['logado' => true, 'id' => $_SESSION['id'], 'nome' => $_SESSION['nome'], 'tipo' => $_SESSION['tipo']]);
