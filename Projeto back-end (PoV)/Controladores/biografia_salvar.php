<?php
// Salva a biografia do usuário logado (até 150 caracteres).
require __DIR__ . '/../Conectivos/exige_login_post.php';

$bio = trim((string) ($_POST['biografia'] ?? ''));

if (!mb_check_encoding($bio, 'UTF-8')) {
    json_out(['erro' => 'Texto inválido.'], 400);
}

// Transforma quebras de linha e espaços repetidos em um espaço só
$bio = trim((string) preg_replace('/\s+/u', ' ', $bio));

if (mb_strlen($bio) > 150) {
    json_out(['erro' => 'A biografia pode ter no máximo 150 caracteres.'], 400);
}

// Campo vazio vira NULL no banco
$valor = $bio === '' ? null : $bio;

$stmt = $conn->prepare('UPDATE usuarios SET biografia = ? WHERE id_usuario = ?');
if (!$stmt || !$stmt->bind_param('si', $valor, $idUsuario) || !$stmt->execute()) {
    error_log('biografia_salvar: ' . $conn->error);
    json_out(['erro' => 'Não foi possível salvar a biografia. O banco precisa ser atualizado (Banco/atualizacao_perfil_publico.sql).'], 500);
}
$stmt->close();

json_out(['ok' => true, 'biografia' => $bio]);
