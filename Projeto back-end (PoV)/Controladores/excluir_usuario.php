<?php
// Exclui a conta de um usuário (e tudo que é dele: posts, perfil, fotos e arquivos).
// Chamado pelo botão "Excluir" do DashboardU.php. Só administradores usam.
//
// Regras:
//  - o administrador não pode excluir a própria conta;
//  - a exclusão é "tudo ou nada": se qualquer passo falhar, nada é apagado.
//
// O registro no log é feito pelos gatilhos do banco (Banco/Pov.sql) e pelo
// Controladores/log.php. Os logs antigos do usuário continuam existindo (o nome
// fica gravado neles; só a ligação com a conta some).
require __DIR__ . '/../Conectivos/exige_admin_post.php';   // deixa $idAdmin e $idAlvo prontos

if ($idAlvo === $idAdmin) {
    voltar_dashboard('erro', 'Você não pode excluir a própria conta.');
}

// ---------- 1) Confere o usuário alvo e guarda o que existe no disco ----------
$stmt = $conn->prepare('SELECT nome, foto_usuario FROM usuarios WHERE id_usuario = ?');
if (!$stmt) {
    error_log('excluir_usuario (select usuario): ' . $conn->error);
    voltar_dashboard('erro', 'Não foi possível excluir o usuário.');
}
$stmt->bind_param('i', $idAlvo);
$stmt->execute();
$stmt->bind_result($nomeAlvo, $fotoPerfil);
$achou = $stmt->fetch();
$stmt->close();

if (!$achou) {
    voltar_dashboard('erro', 'Usuário não encontrado. Ele pode já ter sido excluído.');
}

$arquivosFotos = [];
$stmt = $conn->prepare('SELECT arquivo FROM fotos WHERE id_usuario = ?');
if (!$stmt) {
    error_log('excluir_usuario (select fotos): ' . $conn->error);
    voltar_dashboard('erro', 'Não foi possível excluir o usuário.');
}
$stmt->bind_param('i', $idAlvo);
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
    $arquivosFotos[] = $linha['arquivo'];
}
$stmt->close();

// ---------- 2) Apaga do banco (tudo ou nada) ----------
// As tabelas postagem e perfil apontam para o usuário SEM "ON DELETE CASCADE",
// então precisam ser limpas antes. Apagar linha por linha aqui também faz os
// gatilhos registrarem cada post e foto removidos no log.
$passos = [
    'posts'   => 'DELETE FROM postagem WHERE id_usuario = ?',
    'perfil'  => 'DELETE FROM perfil WHERE id_usuario = ?',
    'fotos'   => 'DELETE FROM fotos WHERE id_usuario = ?',
    'usuario' => 'DELETE FROM usuarios WHERE id_usuario = ?',
];

$conn->begin_transaction();

$contaApagada = false;
foreach ($passos as $nomePasso => $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt || !$stmt->bind_param('i', $idAlvo) || !$stmt->execute()) {
        error_log('excluir_usuario (' . $nomePasso . '): ' . $conn->error);
        $conn->rollback();
        voltar_dashboard('erro', 'Não foi possível excluir o usuário. Nada foi apagado.');
    }
    if ($nomePasso === 'usuario') {
        $contaApagada = $stmt->affected_rows === 1;
    }
    $stmt->close();
}

if (!$contaApagada) {
    // Outro administrador excluiu a mesma conta no meio do caminho
    $conn->rollback();
    voltar_dashboard('aviso', 'Esse usuário já havia sido excluído.');
}

if (!$conn->commit()) {
    error_log('excluir_usuario (commit): ' . $conn->error);
    $conn->rollback();
    voltar_dashboard('erro', 'Não foi possível excluir o usuário. Nada foi apagado.');
}

// ---------- 3) Apaga os arquivos do disco ----------
// Só depois do commit: se o banco falhar, as fotos continuam intactas.
// Só mexe em caminhos no formato gerado pelo servidor (mesma regra dos outros controladores).
$apagar = [];
foreach ($arquivosFotos as $arquivo) {
    if (preg_match('#^Perfil/fotos/[a-f0-9]{32}\.(jpg|png|webp)$#', (string) $arquivo)) {
        $apagar[] = $arquivo;
    }
}
if ($fotoPerfil && preg_match('#^Perfil/usuarios/[a-f0-9]{32}\.(jpg|png|webp)$#', (string) $fotoPerfil)) {
    $apagar[] = $fotoPerfil;
}

foreach ($apagar as $arquivo) {
    $caminho = __DIR__ . '/../' . $arquivo;
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

voltar_dashboard('sucesso', 'Usuário #' . $idAlvo . ' (' . $nomeAlvo . ') excluído.');
