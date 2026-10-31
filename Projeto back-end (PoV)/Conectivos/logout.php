<?php
require 'conexao.php';
session_destroy();
header('Location: ../view/Index.php');
