<?php
// Exclui UMA foto (id) ou TODAS (todas=1) da galeria do usuário logado.
// Só mexe em fotos que pertencem a quem está logado.
require __DIR__ . '/../Conectivos/exige_login_post.php';

$todas  = ($_POST['todas'] ?? '') === '1';
$idFoto = (int) ($_POST['id'] ?? 0);

if (!$todas && $idFoto <= 0) {
    json_out(['erro' => 'Foto inválida.'], 400);
}

// ---------- 1) Descobre quais arquivos são do usuário logado ----------
if ($todas) {
    $stmt = $conn->prepare('SELECT arquivo FROM fotos WHERE id_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('i', $idUsuario);
    }
} else {
    $stmt = $conn->prepare('SELECT arquivo FROM fotos WHERE id_foto = ? AND id_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('ii', $idFoto, $idUsuario);
    }
}

if (!$stmt || !$stmt->execute()) {
    error_log('fotos_excluir (select): ' . $conn->error);
    json_out(['erro' => 'Não foi possível excluir.'], 500);
}

$arquivos = [];
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
    $arquivos[] = $linha['arquivo'];
}
$stmt->close();

if (!$todas && !$arquivos) {
    json_out(['erro' => 'Foto não encontrada.'], 404);
}

// ---------- 2) Apaga os registros do banco ----------
if ($todas) {
    $stmt = $conn->prepare('DELETE FROM fotos WHERE id_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('i', $idUsuario);
    }
} else {
    $stmt = $conn->prepare('DELETE FROM fotos WHERE id_foto = ? AND id_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('ii', $idFoto, $idUsuario);
    }
}

if (!$stmt || !$stmt->execute()) {
    error_log('fotos_excluir (delete): ' . $conn->error);
    json_out(['erro' => 'Não foi possível excluir.'], 500);
}
$stmt->close();

// ---------- 3) Apaga os arquivos do disco ----------
foreach ($arquivos as $arquivo) {
    // Só mexe em caminhos no formato gerado pelo envio
    if (preg_match('#^Perfil/fotos/[a-f0-9]{32}\.(jpg|png|webp)$#', $arquivo)) {
        $caminho = __DIR__ . '/../' . $arquivo;
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
}

json_out(['ok' => true, 'removidas' => count($arquivos)]);
