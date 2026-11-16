<?php
/* ==========================================================
   Point of View - Consulta do log
   Controladores/log_consulta.php

   Funções usadas pelo View/DashboardL.php e pelo
   Controladores/log_exportar.php. Este arquivo só define funções:
   quem o inclui precisa ter aberto a conexão ($conn) antes
   (require_once '../Conectivos/conexao.php').
   ========================================================== */

const LOG_TIPOS = [
    'acao'    => 'Ação',
    'mudanca' => 'Mudança',
    'erro'    => 'Erro',
];

const LOG_CATEGORIAS = [
    'acesso'  => 'Acesso',
    'perfil'  => 'Perfil',
    'fotos'   => 'Fotos',
    'posts'   => 'Posts',
    'admin'   => 'Administração',
    'sistema' => 'Sistema',
];

const LOG_ICONES = [
    'acesso'  => 'bi-box-arrow-in-right',
    'perfil'  => 'bi-person-gear',
    'fotos'   => 'bi-image',
    'posts'   => 'bi-card-image',
    'admin'   => 'bi-shield-lock',
    'sistema' => 'bi-gear',
];

// Rótulo e trecho de SQL (fixo, nunca vem da URL)
const LOG_PERIODOS = [
    'tudo' => ['Todo o período',  null],
    'hoje' => ['Hoje',            'l.data_hora >= CURDATE()'],
    '7d'   => ['Últimos 7 dias',  'l.data_hora >= (NOW() - INTERVAL 7 DAY)'],
    '30d'  => ['Últimos 30 dias', 'l.data_hora >= (NOW() - INTERVAL 30 DAY)'],
];

const LOG_FROM = 'FROM logs l LEFT JOIN usuarios u ON u.id_usuario = l.id_usuario';


/* ---------- Banco ---------- */

/** SELECT preparado que devolve todas as linhas (null se der erro). */
function log_db_linhas($conn, $sql, $tipos = '', array $params = [])
{
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        if ($params) {
            $stmt->bind_param($tipos, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $meta = $stmt->result_metadata();
        if (!$meta) {
            $stmt->close();
            return [];
        }

        $linha = [];
        $refs  = [];
        while ($campo = $meta->fetch_field()) {
            $refs[] = &$linha[$campo->name];
        }
        $stmt->bind_result(...$refs);

        $saida = [];
        while ($stmt->fetch()) {
            $copia = [];
            foreach ($linha as $chave => $valor) {
                $copia[$chave] = $valor;
            }
            $saida[] = $copia;
        }
        $stmt->close();
        return $saida;
    } catch (Throwable $ex) {
        error_log('log_db_linhas: ' . $ex->getMessage());
        return null;
    }
}

function log_numero($conn, $sql, $tipos = '', array $params = [])
{
    $linhas = log_db_linhas($conn, $sql, $tipos, $params);
    return $linhas ? (int) reset($linhas[0]) : 0;
}

/** A tabela logs já tem as colunas novas (tipo, categoria, nome_usuario, ip)? */
function log_tabela_pronta($conn)
{
    $colunas = [];
    try {
        $r = $conn->query('SHOW COLUMNS FROM logs');
        if ($r) {
            while ($l = $r->fetch_assoc()) {
                $colunas[] = $l['Field'];
            }
        }
    } catch (Throwable $ex) {
        return false;
    }

    foreach (['tipo', 'categoria', 'nome_usuario', 'ip'] as $coluna) {
        if (!in_array($coluna, $colunas, true)) {
            return false;
        }
    }
    return true;
}


/* ---------- Acesso ---------- */

/**
 * Só deixa passar administradores (tipo_id = 2), conferido no banco.
 * $paraLogin / $paraInicio = para onde mandar quem não pode entrar,
 * relativo ao arquivo que chamou.
 */
function log_admin_exigir($conn, $paraLogin, $paraInicio)
{
    $id = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    if ($id <= 0) {
        header('Location: ' . $paraLogin);
        exit;
    }

    $linhas = log_db_linhas($conn, 'SELECT nome, tipo_id FROM usuarios WHERE id_usuario = ?', 'i', [$id]);

    if (!$linhas || (int) $linhas[0]['tipo_id'] !== 2) {
        if ($linhas && function_exists('log_registrar')) {
            log_registrar($conn, 'erro', 'admin', 'Acesso negado ao painel',
                'Tentativa de ' . $linhas[0]['nome'] . ' (ID ' . $id . ')');
        }
        header('Location: ' . $paraInicio);
        exit;
    }

    return ['id' => $id, 'nome' => (string) $linhas[0]['nome']];
}


/* ---------- Filtros ---------- */

function log_param($nome, $max = 80)
{
    return (isset($_GET[$nome]) && is_string($_GET[$nome])) ? mb_substr(trim($_GET[$nome]), 0, $max) : '';
}

/** Filtros da URL, já validados: busca, tipo, categoria, periodo, pagina. */
function log_filtros_da_url()
{
    $tipo      = log_param('tipo');
    $categoria = log_param('categoria');
    $periodo   = log_param('periodo');
    $pagina    = log_param('pagina', 6);

    return [
        'busca'     => log_param('busca'),
        'tipo'      => array_key_exists($tipo, LOG_TIPOS) ? $tipo : '',
        'categoria' => array_key_exists($categoria, LOG_CATEGORIAS) ? $categoria : '',
        'periodo'   => array_key_exists($periodo, LOG_PERIODOS) ? $periodo : 'tudo',
        'pagina'    => ($pagina !== '' && ctype_digit($pagina)) ? max(1, (int) $pagina) : 1,
    ];
}

/** [sql do WHERE, tipos do bind_param, valores] */
function log_montar_where(array $f)
{
    $where  = [];
    $tipos  = '';
    $params = [];

    if ($f['busca'] !== '') {
        $like     = '%' . addcslashes($f['busca'], '%_\\') . '%';
        $where[]  = '(l.acao LIKE ? OR l.descricao LIKE ? OR COALESCE(l.nome_usuario, u.nome) LIKE ? OR l.ip LIKE ?)';
        $tipos   .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    if ($f['tipo'] !== '') {
        $where[]  = 'l.tipo = ?';
        $tipos   .= 's';
        $params[] = $f['tipo'];
    }
    if ($f['categoria'] !== '') {
        $where[]  = 'l.categoria = ?';
        $tipos   .= 's';
        $params[] = $f['categoria'];
    }
    if (LOG_PERIODOS[$f['periodo']][1] !== null) {
        $where[] = LOG_PERIODOS[$f['periodo']][1];
    }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $tipos, $params];
}


/* ---------- Consultas ---------- */

function log_contar($conn, array $f)
{
    list($where, $tipos, $params) = log_montar_where($f);
    return log_numero($conn, 'SELECT COUNT(*) ' . LOG_FROM . " $where", $tipos, $params);
}

function log_listar($conn, array $f, $limite, $inicio)
{
    list($where, $tipos, $params) = log_montar_where($f);

    $linhas = log_db_linhas(
        $conn,
        'SELECT l.id_log, l.data_hora, DATE(l.data_hora) AS dia, l.tipo, l.categoria, l.acao, l.descricao,
                l.id_usuario, COALESCE(l.nome_usuario, u.nome) AS nome_usuario, l.ip '
        . LOG_FROM . " $where
          ORDER BY l.data_hora DESC, l.id_log DESC
          LIMIT ? OFFSET ?",
        $tipos . 'ii',
        array_merge($params, [(int) $limite, (int) $inicio])
    );
    return $linhas ?: [];
}

/** Números da faixa superior. */
function log_resumo($conn)
{
    return [
        'total' => log_numero($conn, 'SELECT COUNT(*) FROM logs'),
        'hoje'  => log_numero($conn, 'SELECT COUNT(*) FROM logs WHERE data_hora >= CURDATE()'),
        'erros' => log_numero($conn, "SELECT COUNT(*) FROM logs WHERE tipo = 'erro' AND data_hora >= (NOW() - INTERVAL 1 DAY)"),
    ];
}

/** Hoje e ontem segundo o relógio do banco (é com ele que o data_hora é gravado). */
function log_datas_banco($conn)
{
    $l = log_db_linhas($conn, 'SELECT CURDATE() AS hoje, (CURDATE() - INTERVAL 1 DAY) AS ontem');
    return $l ? ['hoje' => (string) $l[0]['hoje'], 'ontem' => (string) $l[0]['ontem']]
              : ['hoje' => date('Y-m-d'), 'ontem' => date('Y-m-d', strtotime('-1 day'))];
}

/** Eventos por dia nos últimos 7 dias (do mais antigo para hoje), para o gráfico. */
function log_atividade_semana($conn)
{
    $hoje   = log_datas_banco($conn)['hoje'];
    $linhas = log_db_linhas(
        $conn,
        'SELECT DATE(data_hora) AS dia, COUNT(*) AS n
           FROM logs
          WHERE data_hora >= (CURDATE() - INTERVAL 6 DAY)
          GROUP BY DATE(data_hora)'
    ) ?: [];

    $contagem = [];
    foreach ($linhas as $l) {
        $contagem[(string) $l['dia']] = (int) $l['n'];
    }

    $siglas = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    $saida  = [];
    $d      = new DateTime($hoje);
    $d->modify('-6 day');

    for ($i = 0; $i < 7; $i++) {
        $chave   = $d->format('Y-m-d');
        $saida[] = [
            'dia'    => $chave,
            'data'   => $d->format('d/m'),
            'sigla'  => $siglas[(int) $d->format('w')],
            'n'      => isset($contagem[$chave]) ? $contagem[$chave] : 0,
            'hoje'   => $chave === $hoje,
        ];
        $d->modify('+1 day');
    }
    return $saida;
}

/** Título de cada dia na lista: ["Hoje", "terça-feira, 17 de novembro"] */
function log_rotulo_dia($dia, array $datas)
{
    $semana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    $meses  = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    $d = DateTime::createFromFormat('Y-m-d', $dia);
    if (!$d) {
        return [$dia, ''];
    }

    $diaSemana = $semana[(int) $d->format('w')];
    $data      = $d->format('j') . ' de ' . $meses[(int) $d->format('n') - 1];

    if ($dia === $datas['hoje']) {
        return ['Hoje', $diaSemana . ', ' . $data];
    }
    if ($dia === $datas['ontem']) {
        return ['Ontem', $diaSemana . ', ' . $data];
    }
    return [ucfirst($diaSemana), $data . ' de ' . $d->format('Y')];
}
