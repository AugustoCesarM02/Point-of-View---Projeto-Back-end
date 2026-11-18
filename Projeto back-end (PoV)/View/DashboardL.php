<?php

require_once '../Conectivos/conexao.php';

/* BUSCAR LOGS NO BANCO DE DADOS */

$sql = "SELECT
            l.id_log,
            l.acao,
            l.descricao,
            l.data_hora,
            u.nome
        FROM logs l
        LEFT JOIN usuarios u
            ON l.id_usuario = u.id_usuario
        ORDER BY l.data_hora DESC";

$resultado = $conn->query($sql);

$logs = [];

if (!$resultado) {
    die("Erro ao consultar os logs: " . $conn->error);
}

while ($log = $resultado->fetch_assoc()) {
    $logs[] = $log;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard PoV</title>
    <link rel="stylesheet" href="../CSS/CssDashboardL.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
</head>
<body>
    <header>
        <div class="header_topo">
            <div class="logo">
                <h2 id="img_logo" onclick="window.location.href='Index.html'">Point of View</h2>
            </div>
            <div class = "botões_laterais">
                <div class="Icone_Usuário">
                    <img src="">
                    <button id="deslogar">Sair</button>
                </div>
                <div class="font-darkmode-controls">
                    <button id="toggle-dark" class="toggle-dark" title="Ativar/Desativar Modo Escuro" aria-pressed="false">
                        <i class="bi bi-brightness-high-fill sun"></i>
                        <i class="bi bi-moon-stars moon"></i>
                    </button>
                <div class="font-controls">
                    <button id="font-inc" title="Aumentar Fonte">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button id="font-dec" title="Diminuir Fonte">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="menu_lateral">
            <h2>Dashboard</h2>
            <hr>
            <div class="btn_adm">
                <a href="../View/DashboardU.php">Usuários</a>
                <a href="../View/DashboardL.php">Log</a>
                <a>Excluir Posts</a>
            </div>    
        </div>
        <div class="tela_principal">
            <h3>Log</h3>
            <div class="tela_log">
<div class="tela_log">

    <?php if (count($logs) > 0): ?>

        <?php foreach ($logs as $log): ?>

            <div class="log-item">

                <h4>
                    <?= htmlspecialchars($log['acao']) ?>
                </h4>

                <p>
                    <?= htmlspecialchars($log['descricao'] ?? '') ?>
                </p>

                <small>
                    Usuário:
                    <?= htmlspecialchars($log['nome'] ?? 'Usuário removido') ?>
                </small>

                <br>

                <small>
                    Data:
                    <?= date(
                        'd/m/Y H:i',
                        strtotime($log['data_hora'])
                    ) ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p>Nenhuma atividade registrada.</p>

    <?php endif; ?>
        </div>
    </main>
</body>
</html>