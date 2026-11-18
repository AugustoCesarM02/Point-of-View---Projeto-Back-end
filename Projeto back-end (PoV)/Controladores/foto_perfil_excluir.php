<?php
// Remove a foto de perfil do usuário logado (o perfil volta a mostrar a foto padrão).
// Só mexe na foto de quem está logado.
require __DIR__ . '/../Conectivos/exige_login_post.php';

// ---------- 1) Descobre qual é o arquivo atual ----------
$fotoAtual = null;

$stmt = $conn->prepare('SELECT foto_usuario FROM usuarios WHERE id_usuario = ?');
if (!$stmt) {
    error_log('foto_perfil_excluir (select): ' . $conn->error);
    json_out(['erro' => 'Não foi possível remover a foto.'], 500);
}
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$stmt->bind_result($fotoAtual);
$achou = $stmt->fetch();
$stmt->close();

if (!$achou) {
    json_out(['erro' => 'Usuário não encontrado.'], 404);
}

// ---------- 2) Limpa o registro no banco ----------
$stmt = $conn->prepare('UPDATE usuarios SET foto_usuario = NULL WHERE id_usuario = ?');
if (!$stmt || !$stmt->bind_param('i', $idUsuario) || !$stmt->execute()) {
    error_log('foto_perfil_excluir (update): ' . $conn->error);
    json_out(['erro' => 'Não foi possível remover a foto.'], 500);
}
$stmt->close();

// ---------- 3) Apaga o arquivo do disco ----------
// Só mexe em caminhos no formato gerado pelo servidor
if ($fotoAtual && preg_match('#^Perfil/usuarios/[a-f0-9]{32}\.(jpg|png|webp)$#', $fotoAtual)) {
    $caminho = __DIR__ . '/../' . $fotoAtual;
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

// Foto padrão (relativa à pasta View/, onde fica o Perfil.php)
json_out(['ok' => true, 'url' => '../Perfil/Perfil_Sf.png']);
