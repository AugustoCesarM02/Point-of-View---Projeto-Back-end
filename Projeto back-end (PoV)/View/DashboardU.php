<?php

require_once '../Conectivos/conexao.php';

/* PROTEÇÃO DA PÁGINA */

if (!isset($_SESSION['id']) || ($_SESSION['tipo'] ?? 0) != 2) {
    header('Location: Login.html');
    exit;
}

$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));

$nomeAdmin = $_SESSION['nome'] ?? 'Administrador';
$idAdmin   = $_SESSION['id'] ?? null;


/* FUNÇÕES AUXILIARES */

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function iniciais($nome)
{
    $partes = preg_split('/\s+/', trim((string) $nome));
    $letras = mb_substr($partes[0] ?? '', 0, 1);

    if (count($partes) > 1) {
        $letras .= mb_substr(end($partes), 0, 1);
    }

    return mb_strtoupper($letras);
}


/* BUSCAR USUÁRIOS NO BANCO */

$sql = "SELECT
            u.id_usuario AS id,
            u.nome,
            u.email,
            u.tipo_id,
            u.foto_perfil,
            t.descricao AS cargo,
            u.criado_em AS data_cadastro
        FROM usuarios u
        INNER JOIN tipos_usuario t
            ON u.tipo_id = t.tipo_id
        ORDER BY u.nome";

$resultado = $conn->query($sql);

$usuarios = [];

if (!$resultado) {
    die("Erro ao consultar usuários: " . $conn->error);
}

while ($usuario = $resultado->fetch_assoc()) {
    $usuarios[] = $usuario;
}


/* NÚMEROS DO RESUMO */

$total = count($usuarios);
$admins = 0;
$novos = 0;
$mesAtual = date('Y-m');

foreach ($usuarios as $u) {

    if (($u['cargo'] ?? 'usuario') === 'admin') {
        $admins++;
    }

    if (substr((string) ($u['data_cadastro'] ?? ''), 0, 7) === $mesAtual) {
        $novos++;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Dashboard PoV</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../CSS/CssDashboardU.css">
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
                <a href="../View/DashboardU.php" class="ativo" aria-current="page">
                    <i class="bi bi-people-fill"></i> Usuários
                </a>
                <a href="../View/DashboardL.php">
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
                    <h1>Usuários</h1>
                    <p class="faixa-descricao">Veja quem faz parte do Point of View, ajuste cargos e remova contas quando necessário.</p>

                    <div class="resumo">
                        <div class="resumo-item">
                            <span class="resumo-numero" data-contar="<?= (int) $total ?>"><?= (int) $total ?></span>
                            <span class="resumo-rotulo">usuários cadastrados</span>
                        </div>
                        <div class="resumo-item">
                            <span class="resumo-numero" data-contar="<?= (int) $admins ?>"><?= (int) $admins ?></span>
                            <span class="resumo-rotulo">administradores</span>
                        </div>
                        <div class="resumo-item">
                            <span class="resumo-numero" data-contar="<?= (int) $novos ?>"><?= (int) $novos ?></span>
                            <span class="resumo-rotulo">novos neste mês</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="conteudo">
                <section class="cartao">

                    <div class="barra-ferramentas">
                        <h2>Todos os usuários</h2>
                        <div class="filtros">
                            <label class="busca">
                                <i class="bi bi-search"></i>
                                <input type="search" id="busca" placeholder="Buscar por nome, e-mail ou ID" autocomplete="off" aria-label="Buscar usuário">
                            </label>
                            <select id="filtro-cargo" aria-label="Filtrar por cargo">
                                <option value="">Todos os cargos</option>
                                <option value="admin">Administradores</option>
                                <option value="usuario">Usuários</option>
                            </select>
                        </div>
                    </div>

                    <div class="tabela_usuarios">
                        <table class="tabela" id="tabela-usuarios">
                            <thead>
                                <tr>
                                    <th scope="col">Usuário</th>
                                    <th scope="col">ID</th>
                                    <th scope="col">Cargo</th>
                                    <th scope="col">Cadastro</th>
                                    <th scope="col" class="col-acoes">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u):
                                    $id       = $u['id'];
                                    $nome     = $u['nome'] ?? '';
                                    $email    = $u['email'] ?? '';
                                    $cargo    = ($u['cargo'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
                                    $ehAdmin  = $cargo === 'admin';
                                    $ehVoce   = $idAdmin !== null && (string) $idAdmin === (string) $id;
                                    $foto = $u['foto_perfil'] ?? '';
                                    $cadastro = !empty($u['data_cadastro']) ? date('d/m/Y', strtotime($u['data_cadastro'])) : '-';
                                    $idFmt    = '#' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
                                    $busca    = mb_strtolower($nome . ' ' . $email . ' ' . $idFmt . ' ' . $id);
                                ?>
                                <tr data-busca="<?= e($busca) ?>" data-cargo="<?= e($cargo) ?>">
                                    <td>
                                        <div class="usuario">
                                            <span class="avatar" data-tom="<?= (int) $id % 5 ?>">
                                                <?php if ($foto): ?>
                                                    <img src="<?= e($foto) ?>" alt="">
                                                <?php else: ?>
                                                    <?= e(iniciais($nome)) ?>
                                                <?php endif; ?>
                                            </span>
                                            <div>
                                                <div class="usuario-nome">
                                                    <?= e($nome) ?>
                                                    <?php if ($ehVoce): ?><span class="tag-voce">Você</span><?php endif; ?>
                                                </div>
                                                <?php if ($email): ?>
                                                    <div class="usuario-email"><?= e($email) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="ID"><span class="id"><?= e($idFmt) ?></span></td>
                                    <td data-label="Cargo">
                                        <?php if ($ehAdmin): ?>
                                            <span class="cargo cargo--admin"><i class="bi bi-shield-check"></i> Administrador</span>
                                        <?php else: ?>
                                            <span class="cargo cargo--usuario">Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Cadastro"><span class="data-cadastro"><?= e($cadastro) ?></span></td>
                                    <td class="col-acoes">
                                        <div class="acoes">
                                            <a class="btn-icone" href="../View/Perfil.php?id=<?= urlencode((string) $id) ?>"
                                               title="Ver perfil" aria-label="Ver perfil de <?= e($nome) ?>">
                                                <i class="bi bi-person-vcard"></i>
                                            </a>

                                            <button type="button" class="btn-icone btn-icone--promover"
                                                    data-acao="promover"
                                                    data-url="../Conectivos/promover_usuario.php"
                                                    data-id="<?= e($id) ?>" data-nome="<?= e($nome) ?>"
                                                    title="<?= $ehAdmin ? 'Já é administrador' : 'Promover a administrador' ?>"
                                                    aria-label="Promover <?= e($nome) ?>"
                                                    <?= $ehAdmin ? 'disabled' : '' ?>>
                                                <i class="bi bi-arrow-up-circle"></i>
                                            </button>

                                            <button type="button" class="btn-icone btn-icone--excluir"
                                                    data-acao="excluir"
                                                    data-url="../Conectivos/excluir_usuario.php"
                                                    data-id="<?= e($id) ?>" data-nome="<?= e($nome) ?>"
                                                    title="<?= $ehVoce ? 'Você não pode excluir a própria conta' : 'Excluir usuário' ?>"
                                                    aria-label="Excluir <?= e($nome) ?>"
                                                    <?= $ehVoce ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <tr class="linha-vazia" id="linha-vazia" <?= $total > 0 ? 'hidden' : '' ?>>
                                    <td colspan="5">
                                        <div class="vazio">
                                            <i class="bi bi-people"></i>
                                            <strong>Nenhum usuário encontrado</strong>
                                            <span>Ajuste a busca ou o filtro de cargo.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rodape-tabela">
                        <span id="contagem">Mostrando <?= (int) $total ?> de <?= (int) $total ?> usuários</span>
                        <!-- Quando paginar no SQL, troque os spans por links: ?pagina=2 -->
                        <nav class="paginacao" aria-label="Paginação">
                            <span class="desativado"><i class="bi bi-chevron-left"></i> Anterior</span>
                            <span class="desativado">Próxima <i class="bi bi-chevron-right"></i></span>
                        </nav>
                    </div>

                </section>
            </div>

        </main>
    </div>

    <!-- Confirmação usada por "Excluir" e "Promover" -->
    <dialog class="modal modal--excluir" id="modal-acao" aria-labelledby="modal-titulo">
        <form method="post" action="">
            <input type="hidden" name="id" id="modal-id">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div class="modal-icone"><i class="bi bi-trash3" id="modal-icone"></i></div>
            <h3 id="modal-titulo">Excluir usuário?</h3>
            <p id="modal-texto"></p>

            <div class="modal-botoes">
                <button type="button" class="btn" data-fechar>Cancelar</button>
                <button type="submit" class="btn btn-confirmar" id="modal-confirmar">Confirmar</button>
            </div>
        </form>
    </dialog>

    <script src="../JS/JSDashboardU.js" defer></script>
</body>
</html>
