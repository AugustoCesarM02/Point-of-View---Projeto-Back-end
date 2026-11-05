<?php
require __DIR__ . '/conexao.php';
session_unset();
session_destroy();
header('Location: ../View/Index.php');
exit;
