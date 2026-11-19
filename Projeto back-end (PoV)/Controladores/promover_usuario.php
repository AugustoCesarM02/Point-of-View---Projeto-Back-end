<?php
// Promove um usuário a administrador (tipo_id = 2).
// Chamado pelo botão "Promover" do DashboardU.php. Só administradores usam.
//
// O registro no log da mudança de tipo é feito pelo gatilho trg_usuarios_au
// (Banco/Pov.sql); o log da ação é feito pelo Controladores/log.php.
require __DIR__ . '/../Conectivos/exige_admin_post.php';   // deixa $idAdmin e $idAlvo prontos

// ---------- 1) Confere o usuário alvo ----------
$stmt = $conn->prepare('SELECT nome, tipo_id FROM usuarios WHERE id_usuario = ?');
if (!$stmt) {
    error_log('promover_usuario (select): ' . $conn->error);
    voltar_dashboard('erro', 'Não foi possível promover o usuário.');
}
$stmt->bind_param('i', $idAlvo);
$stmt->execute();
$stmt->bind_result($nomeAlvo, $tipoAlvo);
$achou = $stmt->fetch();
$stmt->close();

if (!$achou) {
    voltar_dashboard('erro', 'Usuário não encontrado. Ele pode já ter sido excluído.');
}

if ((int) $tipoAlvo === TIPO_ADMIN) {
    voltar_dashboard('aviso', $nomeAlvo . ' já é administrador.');
}

// ---------- 2) Promove ----------
// O "AND tipo_id <> ?" evita duas promoções ao mesmo tempo
$tipoAdmin = TIPO_ADMIN;
$stmt = $conn->prepare('UPDATE usuarios SET tipo_id = ? WHERE id_usuario = ? AND tipo_id <> ?');
if (!$stmt || !$stmt->bind_param('iii', $tipoAdmin, $idAlvo, $tipoAdmin) || !$stmt->execute()) {
    error_log('promover_usuario (update): ' . $conn->error);
    voltar_dashboard('erro', 'Não foi possível promover o usuário.');
}
$alterou = $stmt->affected_rows === 1;
$stmt->close();

if (!$alterou) {
    voltar_dashboard('aviso', 'Nada foi alterado. ' . $nomeAlvo . ' já pode ter sido promovido por outro administrador.');
}

voltar_dashboard('sucesso', 'Usuário #' . $idAlvo . ' (' . $nomeAlvo . ') promovido a administrador.');
