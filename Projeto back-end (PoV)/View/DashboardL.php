<?php
require_once __DIR__ . '/../Conectivos/conexao.php';                 // abre $conn e a sessão
require_once __DIR__ . '/../Controladores/log_consulta.php';        // filtros e consultas do log


/* PROTEÇÃO DA PÁGINA
   Precisa estar logado e ser admin (tipo_id = 2), conferido no banco. */

$admin     = log_admin_exigir($conn, 'Login.html', 'Index.php');
$nomeAdmin = $admin['nome'] !== '' ? $admin['nome'] : 'Administrador';


/* FUNÇÕES AUXILIARES */

if (!function_exists('e')) {
    function e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('iniciais')) {
    function iniciais($nome)
    {
        $partes = preg_split('/\s+/', trim((string) $nome));
        $letras = mb_substr($partes[0] ?? '', 0, 1);

        if (count($partes) > 1) {
            $letras .= mb_substr(end($partes), 0, 1);
        }

        return mb_strtoupper($letras);
    }
}

/** Monta a query string mantendo os filtros (?busca=...&tipo=...). */
function log_url(array $f, array $extra = [])
{
    $q = [];
    foreach (['busca', 'tipo', 'categoria'] as $chave) {
        if ($f[$chave] !== '') {
            $q[$chave] = $f[$chave];
        }
    }
    if ($f['periodo'] !== 'tudo') {
        $q['periodo'] = $f['periodo'];
    }
    return http_build_query(array_merge($q, $extra));
}


/* DADOS DA PÁGINA */

$pronto    = log_tabela_pronta($conn);   // false = o Pov.sql atualizado ainda não foi executado
$filtros   = log_filtros_da_url();
$porPagina = 25;
$datas     = log_datas_banco($conn);

$resumo        = ['total' => 0, 'hoje' => 0, 'erros' => 0];
$semana        = [];
$totalFiltrado = 0;
$eventos       = [];

if ($pronto) {
    $resumo        = log_resumo($conn);
    $semana        = log_atividade_semana($conn);
    $totalFiltrado = log_contar($conn, $filtros);
}

$totalPaginas = max(1, (int) ceil($totalFiltrado / $porPagina));
$pagina       = min($filtros['pagina'], $totalPaginas);
$inicio       = ($pagina - 1) * $porPagina;

if ($pronto && $totalFiltrado > 0) {
    $eventos = log_listar($conn, $filtros, $porPagina, $inicio);
}

$filtrando = ($filtros['busca'] !== '' || $filtros['tipo'] !== '' || $filtros['categoria'] !== '' || $filtros['periodo'] !== 'tudo');

$totalSemana = 0;
$maiorDia    = 1;
foreach ($semana as $dia) {
    $totalSemana += $dia['n'];
    $maiorDia     = max($maiorDia, $dia['n']);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log - Dashboard PoV</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../CSS/CssDashboardL.css">
</head>
<body>

    <header>
        <div class="header_topo">
            <a class="marca" href="../View/Index.php" aria-label="Voltar para a página inicial">
                <img src="../img/logo.png" alt="Point of View">
                <span class="marca-texto">Painel administrativo</span>
            </a>

            <div class="botoes_laterais">
                <div class="usuario-logado">
                    <span class="avatar avatar--pequeno" data-tom="1" aria-hidden="true"><?= e(iniciais($nomeAdmin)) ?></span>
                    <span class="usuario-logado-nome"><?= e($nomeAdmin) ?></span>
                    <a href="../Conectivos/logout.php" class="btn-sair" id="deslogar">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>

                <div class="font-darkmode-controls">
                    <button id="toggle-dark" class="toggle-dark" title="Ativar/Desativar Modo Escuro" aria-pressed="false">
                        <i class="bi bi-brightness-high-fill sun"></i>
                        <i class="bi bi-moon-stars moon"></i>
                    </button>
                    <div class="font-controls">
                        <button id="font-inc" title="Aumentar Fonte" aria-label="Aumentar fonte">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        <button id="font-dec" title="Diminuir Fonte" aria-label="Diminuir fonte">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="painel">

        <aside class="menu_lateral">
            <div class="menu-titulo">
                <h2>Dashboard</h2>
                <p>Gerencie a plataforma</p>
            </div>

            <nav class="btn_adm" aria-label="Seções do painel">
                <a href="../View/DashboardU.php">
                    <i class="bi bi-people-fill"></i> Usuários
                </a>
                <a href="../View/DashboardL.php" class="ativo" aria-current="page">
                    <i class="bi bi-journal-text"></i> Log
                </a>
                <a href="#">
                    <i class="bi bi-images"></i> Excluir Posts
                </a>
            </nav>

            <div class="menu-rodape">
                <a href="../View/Index.php"><i class="bi bi-arrow-left"></i> Voltar ao site</a>
            </div>
        </aside>

        <main class="tela_principal">

            <section class="faixa">
                <div class="faixa-interna">
                    <div class="faixa-topo">

                        <div class="faixa-texto">
                            <h1>Log do sistema</h1>
                            <p class="faixa-descricao">Tudo o que acontece no Point of View: ações dos usuários, mudanças nos dados e erros, do mais recente para o mais antigo.</p>

                            <div class="resumo">
                                <div class="resumo-item">
                                    <span class="resumo-numero" data-contar="<?= (int) $resumo['total'] ?>"><?= (int) $resumo['total'] ?></span>
                                    <span class="resumo-rotulo">eventos registrados</span>
                                </div>
                                <div class="resumo-item">
                                    <span class="resumo-numero" data-contar="<?= (int) $resumo['hoje'] ?>"><?= (int) $resumo['hoje'] ?></span>
                                    <span class="resumo-rotulo">eventos hoje</span>
                                </div>
                                <div class="resumo-item">
                                    <span class="resumo-numero" data-contar="<?= (int) $resumo['erros'] ?>"><?= (int) $resumo['erros'] ?></span>
                                    <span class="resumo-rotulo">erros nas últimas 24 h</span>
                                </div>
                            </div>
                        </div>

                        <?php if ($semana): ?>
                        <figure class="atividade">
                            <figcaption>
                                <span>Atividade dos últimos 7 dias</span>
                                <strong><?= (int) $totalSemana ?> <?= $totalSemana === 1 ? 'evento' : 'eventos' ?></strong>
                            </figcaption>

                            <div class="grafico">
                                <?php foreach ($semana as $i => $dia):
                                    $altura = $dia['n'] > 0 ? max(8, (int) round($dia['n'] / $maiorDia * 100)) : 3;
                                ?>
                                <div class="barra<?= $dia['hoje'] ? ' barra--hoje' : '' ?>" style="--i: <?= (int) $i ?>"
                                     title="<?= e($dia['data']) ?>: <?= (int) $dia['n'] ?> <?= $dia['n'] === 1 ? 'evento' : 'eventos' ?>">
                                    <div class="barra-area">
                                        <span class="barra-valor"><?= (int) $dia['n'] ?></span>
                                        <span class="barra-coluna" style="--h: <?= (int) $altura ?>%"></span>
                                    </div>
                                    <span class="barra-dia"><?= e($dia['sigla']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </figure>
                        <?php endif; ?>

                    </div>
                </div>
            </section>

            <div class="conteudo">

                <?php if (!$pronto): ?>
                <div class="aviso aviso--erro" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>A tabela de logs ainda não tem as colunas novas. Execute o arquivo Banco/Pov.sql atualizado no banco de dados e recarregue a página.</span>
                </div>
                <?php endif; ?>

                <section class="cartao">

                    <div class="barra-ferramentas">
                        <h2>Registro de eventos</h2>

                        <form class="filtros" id="form-filtros" method="get" action="DashboardL.php">
                            <label class="busca">
                                <i class="bi bi-search"></i>
                                <input type="search" id="busca" name="busca" value="<?= e($filtros['busca']) ?>"
                                       placeholder="Buscar evento, usuário ou IP" autocomplete="off" aria-label="Buscar no log">
                            </label>

                            <select name="tipo" aria-label="Filtrar por tipo">
                                <option value="">Todos os tipos</option>
                                <?php foreach (LOG_TIPOS as $chave => $rotulo): ?>
                                    <option value="<?= e($chave) ?>"<?= $filtros['tipo'] === $chave ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="categoria" aria-label="Filtrar por categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach (LOG_CATEGORIAS as $chave => $rotulo): ?>
                                    <option value="<?= e($chave) ?>"<?= $filtros['categoria'] === $chave ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="periodo" aria-label="Filtrar por período">
                                <?php foreach (LOG_PERIODOS as $chave => $item): ?>
                                    <option value="<?= e($chave) ?>"<?= $filtros['periodo'] === $chave ? ' selected' : '' ?>><?= e($item[0]) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($filtrando): ?>
                                <a class="limpar-filtros" href="DashboardL.php"><i class="bi bi-x-lg"></i> Limpar</a>
                            <?php endif; ?>

                            <noscript><button type="submit" class="btn-exportar">Filtrar</button></noscript>
                        </form>
                    </div>

                    <div class="faixa-acoes">
                        <span class="faixa-acoes-texto">
                            <?= (int) $totalFiltrado ?> <?= $totalFiltrado === 1 ? 'evento encontrado' : 'eventos encontrados' ?>
                        </span>

                        <div class="faixa-acoes-botoes">
                            <label class="alternar" title="Recarrega a página a cada 30 segundos">
                                <input type="checkbox" id="auto-atualizar">
                                <span class="alternar-trilho" aria-hidden="true"></span>
                                <span>Atualizar sozinho</span>
                            </label>

                            <?php if ($pronto && $totalFiltrado > 0): ?>
                            <a class="btn-exportar" href="../Controladores/log_exportar.php?<?= e(log_url($filtros)) ?>">
                                <i class="bi bi-download"></i> Exportar CSV
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tabela_log">
                        <table class="tabela tabela-log" id="tabela-log">
                            <thead>
                                <tr>
                                    <th scope="col">Horário</th>
                                    <th scope="col">Usuário</th>
                                    <th scope="col">Evento</th>
                                    <th scope="col">Descrição</th>
                                    <th scope="col">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $diaAtual = null;
                                foreach ($eventos as $ev):

                                    // Faixa com o dia, sempre que o dia muda
                                    if ($ev['dia'] !== $diaAtual):
                                        $diaAtual = $ev['dia'];
                                        list($tituloDia, $subDia) = log_rotulo_dia($diaAtual, $datas);
                                ?>
                                <tr class="dia">
                                    <td colspan="5"><strong><?= e($tituloDia) ?></strong> <span><?= e($subDia) ?></span></td>
                                </tr>
                                <?php
                                    endif;

                                    $tipo     = isset(LOG_TIPOS[$ev['tipo']]) ? $ev['tipo'] : 'acao';
                                    $categoria = isset(LOG_CATEGORIAS[$ev['categoria']]) ? $ev['categoria'] : 'sistema';
                                    $icone    = $tipo === 'erro' ? 'bi-exclamation-triangle' : LOG_ICONES[$categoria];
                                    $nomeUser = trim((string) $ev['nome_usuario']);
                                    $idUser   = (int) $ev['id_usuario'];
                                    $hora     = substr((string) $ev['data_hora'], 11, 8);
                                    $descricao = trim((string) $ev['descricao']);
                                    $ip       = trim((string) $ev['ip']);
                                ?>
                                <tr data-tipo="<?= e($tipo) ?>">
                                    <td data-label="Horário">
                                        <span class="hora" title="<?= e($ev['data_hora']) ?>"><?= e($hora) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($nomeUser !== ''): ?>
                                        <div class="usuario">
                                            <span class="avatar avatar--pequeno" data-tom="<?= $idUser % 5 ?>" aria-hidden="true"><?= e(iniciais($nomeUser)) ?></span>
                                            <div class="usuario-nome"><?= e($nomeUser) ?></div>
                                        </div>
                                        <?php else: ?>
                                        <span class="usuario-anonimo"><i class="bi bi-person"></i> Visitante</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="evento">
                                            <span class="evento-icone evento-icone--<?= e($tipo) ?>"><i class="bi <?= e($icone) ?>"></i></span>
                                            <div>
                                                <div class="evento-nome"><?= e($ev['acao']) ?></div>
                                                <div class="evento-meta">
                                                    <span class="tipo tipo--<?= e($tipo) ?>"><?= e(LOG_TIPOS[$tipo]) ?></span>
                                                    <span class="evento-categoria"><?= e(LOG_CATEGORIAS[$categoria]) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Descrição">
                                        <?php if ($descricao !== ''): ?>
                                            <div class="descricao-log"><?= e($descricao) ?></div>
                                        <?php else: ?>
                                            <span class="data-cadastro">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="IP"><span class="id"><?= e($ip !== '' ? $ip : '-') ?></span></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if (!$eventos): ?>
                                <tr class="linha-vazia">
                                    <td colspan="5">
                                        <div class="vazio">
                                            <i class="bi bi-journal-text"></i>
                                            <strong>Nenhum evento encontrado</strong>
                                            <span><?= $filtrando ? 'Ajuste a busca, os filtros ou o período.' : 'Os eventos aparecem aqui assim que alguém usar o site.' ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="rodape-tabela">
                        <span id="contagem">
                            <?php if ($totalFiltrado > 0): ?>
                                Mostrando <?= $inicio + 1 ?> a <?= $inicio + count($eventos) ?> de <?= (int) $totalFiltrado ?> eventos
                            <?php else: ?>
                                Nenhum evento para mostrar
                            <?php endif; ?>
                        </span>

                        <nav class="paginacao" aria-label="Paginação">
                            <?php if ($pagina > 1): ?>
                                <a href="?<?= e(log_url($filtros, ['pagina' => $pagina - 1])) ?>"><i class="bi bi-chevron-left"></i> Mais recentes</a>
                            <?php else: ?>
                                <span class="desativado"><i class="bi bi-chevron-left"></i> Mais recentes</span>
                            <?php endif; ?>

                            <span class="pagina-atual">Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span>

                            <?php if ($pagina < $totalPaginas): ?>
                                <a href="?<?= e(log_url($filtros, ['pagina' => $pagina + 1])) ?>">Mais antigos <i class="bi bi-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="desativado">Mais antigos <i class="bi bi-chevron-right"></i></span>
                            <?php endif; ?>
                        </nav>
                    </div>

                </section>
            </div>

        </main>
    </div>

    <script src="../JS/dashboardL.js" defer></script>
</body>
</html>
