<?php
/* ==========================================================
   Point of View - Exportar log em CSV
   Controladores/log_exportar.php?busca=...&tipo=...&categoria=...&periodo=...

   Só administradores. Respeita os mesmos filtros da tela do log
   (até 10.000 eventos, do mais recente para o mais antigo).
   ========================================================== */

require_once __DIR__ . '/../Conectivos/conexao.php';
require_once __DIR__ . '/log_consulta.php';

$admin = log_admin_exigir($conn, '../View/Login.html', '../View/Index.php');

if (!log_tabela_pronta($conn)) {
    http_response_code(500);
    exit('O banco de dados precisa ser atualizado. Execute o arquivo Banco/Pov.sql e tente de novo.');
}

/** Evita que o Excel execute fórmulas vindas de texto digitado por usuários. */
function csv_seguro($valor)
{
    $valor = (string) $valor;
    if ($valor !== '' && strpos("=+-@\t\r", $valor[0]) !== false) {
        $valor = "'" . $valor;
    }
    return $valor;
}

$filtros = log_filtros_da_url();
$eventos = log_listar($conn, $filtros, 10000, 0);

log_registrar($conn, 'acao', 'admin', 'Log exportado',
    count($eventos) . ' evento(s) exportado(s) em CSV',
    ['id_usuario' => $admin['id'], 'nome' => $admin['nome']]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="log-pov-' . date('Y-m-d-His') . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF"); // o Excel reconhece os acentos
fputcsv($saida, ['Data e hora', 'Tipo', 'Categoria', 'Evento', 'Usuário', 'ID do usuário', 'Descrição', 'IP'], ';');

foreach ($eventos as $ev) {
    fputcsv($saida, [
        $ev['data_hora'],
        isset(LOG_TIPOS[$ev['tipo']]) ? LOG_TIPOS[$ev['tipo']] : $ev['tipo'],
        isset(LOG_CATEGORIAS[$ev['categoria']]) ? LOG_CATEGORIAS[$ev['categoria']] : $ev['categoria'],
        csv_seguro($ev['acao']),
        csv_seguro($ev['nome_usuario']),
        $ev['id_usuario'],
        csv_seguro($ev['descricao']),
        $ev['ip'],
    ], ';');
}

fclose($saida);