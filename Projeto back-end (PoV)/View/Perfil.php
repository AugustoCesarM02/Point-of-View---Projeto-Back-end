<?php
session_start();

// true  = qualquer pessoa com o link vê o perfil (mesmo sem login)
// false = só usuários logados veem o perfil de outras pessoas
const PERFIL_PUBLICO = true;

// ---------- Quem está logado e qual perfil será mostrado ----------
$idLogado = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
$temId    = isset($_GET['id']);

if ($temId) {
    // Só aceita número inteiro positivo; qualquer outra coisa vira 0 (perfil inexistente)
    $idPerfil = (is_string($_GET['id']) && ctype_digit($_GET['id'])) ? (int) $_GET['id'] : 0;
} else {
    $idPerfil = $idLogado; // sem ?id= na URL, mostra o perfil de quem está logado
}

// Sem ?id= e sem login, ou perfil de outra pessoa com PERFIL_PUBLICO desligado: vai para o login
if ($idLogado === 0 && (!$temId || !PERFIL_PUBLICO)) {
    header('Location: Login.html');
    exit;
}

// Dono = está vendo o próprio perfil (pode editar). Os demais só visualizam.
$ehDono = ($idLogado > 0 && $idPerfil === $idLogado);

require __DIR__ . '/../Conectivos/conexao.php';

if (!function_exists('e')) {
    function e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

// ---------- Dados do perfil ----------
$stmt = $conn->prepare(
    'SELECT nome, data_de_nascimento, endereco, biografia, foto_usuario
       FROM usuarios
      WHERE id_usuario = ?'
);

if (!$stmt) {
    error_log('Perfil: ' . $conn->error);
    http_response_code(500);
    exit('O banco de dados precisa ser atualizado. Execute o arquivo Banco/atualizacao_perfil_publico.sql e recarregue a página.');
}

$stmt->bind_param('i', $idPerfil);
$stmt->execute();
$stmt->bind_result($nome, $dataNasc, $endereco, $biografia, $fotoBanco);
$achou = $stmt->fetch();
$stmt->close();

if (!$achou) {
    if ($ehDono) {
        // O próprio usuário não existe mais no banco: encerra a sessão
        session_unset();
        session_destroy();
        header('Location: Login.html');
        exit;
    }

    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil não encontrado</title>
    <link rel="stylesheet" href="../CSS/Perfil.css">
</head>
<body>
    <main class="pagina-perfil">
        <div class="perfil">
            <div class="introducao-perfil">
                <div class="marca-formulario">
                    <span class="marca-ponto"></span>
                    <span>POINT OF VIEW</span>
                </div>

                <h1>Perfil não encontrado</h1>

                <p>
                    Este perfil não existe ou foi removido.
                    <a href="Index.php">Voltar para o início</a>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
<?php
    exit;
}

// ---------- Foto de perfil ----------
$foto = '../Perfil/Perfil_Sf.png'; // foto padrão

// Só aceita caminhos no formato gerado pelo cadastro e que existam no disco
if (
    $fotoBanco
    && preg_match('#^Perfil/usuarios/[a-f0-9]{32}\.(jpg|png|webp)$#', $fotoBanco)
    && is_file(__DIR__ . '/../' . $fotoBanco)
) {
    $foto = '../' . $fotoBanco;
}

// ---------- Detalhes (idade e localização só aparecem para o dono) ----------
$idade = null;
if (!empty($dataNasc)) {
    try {
        $idade = (new DateTime($dataNasc))->diff(new DateTime('today'))->y;
    } catch (Exception $ex) {
        $idade = null;
    }
}

$textoNome  = $nome ?: 'Não informado';
$textoIdade = $idade !== null ? $idade . ' anos' : 'Não informada';
$textoLocal = $endereco ?: 'Não informada';

// ---------- Fotos da galeria (da mais nova para a mais antiga) ----------
$fotosLista = [];

$stmt = $conn->prepare(
    'SELECT id_foto, arquivo
       FROM fotos
      WHERE id_usuario = ?
      ORDER BY criada_em DESC, id_foto DESC'
);

if ($stmt) {
    $stmt->bind_param('i', $idPerfil);
    $stmt->execute();
    $stmt->bind_result($idFoto, $arquivoFoto);

    while ($stmt->fetch()) {
        // Só entra na lista o que tem o formato esperado e existe no disco
        if (
            preg_match('#^Perfil/fotos/[a-f0-9]{32}\.(jpg|png|webp)$#', $arquivoFoto)
            && is_file(__DIR__ . '/../' . $arquivoFoto)
        ) {
            $fotosLista[] = ['id' => (int) $idFoto, 'url' => '../' . $arquivoFoto];
        }
    }
    $stmt->close();
} else {
    error_log('Perfil (fotos): ' . $conn->error);
}

// ---------- Token CSRF (só o dono envia/exclui; mesma chave usada no DashboardU.php) ----------
if ($ehDono) {
    $_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
}

$temBiografia = ($biografia !== null && $biografia !== '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Perfil<?= $ehDono ? '' : ' de ' . e($textoNome) ?></title>

    <link rel="stylesheet" href="../CSS/Perfil.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body
    data-perfil="<?= (int) $idPerfil ?>"
    data-dono="<?= $ehDono ? '1' : '0' ?>"
    <?php if ($ehDono): ?>data-csrf="<?= e($_SESSION['csrf']) ?>"<?php endif; ?>>
    <header>
        <div class="header_topo">
            <a class="logo" href="Index.php" aria-label="Voltar ao início">
                <span>Point <span class="logo-destaque">of</span> View</span>
            </a>

            <div class="font-darkmode-controls">
                <button
                    type="button"
                    id="toggle-dark"
                    class="toggle-dark"
                    title="Ativar modo escuro"
                    aria-label="Alternar modo escuro"
                    aria-pressed="false">

                    <i class="bi bi-brightness-high-fill sun"></i>
                    <i class="bi bi-moon-stars-fill moon"></i>
                </button>

                <div class="font-controls">
                    <button
                        type="button"
                        id="font-inc"
                        title="Aumentar fonte"
                        aria-label="Aumentar tamanho da fonte">
                        <i class="bi bi-zoom-in"></i>
                    </button>

                    <button
                        type="button"
                        id="font-dec"
                        title="Diminuir fonte"
                        aria-label="Diminuir tamanho da fonte">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="pagina-perfil">
    <div class="perfil">
        <div class="introducao-perfil">
            <div class="marca-formulario">
                <span class="marca-ponto"></span>
                <span>POINT OF VIEW</span>
            </div>

            <h1>Perfil</h1>

            <p><?= $ehDono
                ? 'Compartilhe seu olhar e mostre suas perspectivas.'
                : 'O ponto de vista de ' . e($textoNome) . '.' ?></p>
        </div>

        <img
            src="<?= e($foto) ?>"
            alt="Foto de perfil"
            class="foto-perfil">

        <!--Detalhes-->
        <section class="detalhes">
            <h2>Detalhes</h2>

            <div class="nome">
                <strong>Nome</strong>
                <p><?= e($textoNome) ?></p>
            </div>

            <?php if ($ehDono): ?>
            <div class="idade">
                <strong>Idade</strong>
                <p><?= e($textoIdade) ?></p>
            </div>

            <div class="localizacao">
                <strong>Localização</strong>
                <p><?= e($textoLocal) ?></p>
            </div>
            <?php endif; ?>
        </section>

        <!--Compartilhar perfil-->
        <div class="compartilhar">
            <button type="button" class="btn" id="perfil-compartilhar">
                <i class="bi bi-share" aria-hidden="true"></i>
                Compartilhar perfil
            </button>

            <p class="compartilhar-status" id="compartilhar-status" role="status" aria-live="polite"></p>
        </div>

        <!--Biografia (o dono edita; os visitantes só leem)-->
        <?php if ($ehDono): ?>
        <section class="biografia">
            <h2>Biografia</h2>

            <input
                type="text"
                id="biografia"
                name="biografia"
                maxlength="150"
                value="<?= e($biografia) ?>"
                placeholder="Escreva sua biografia"
                aria-label="Biografia"
                autocomplete="off">

            <p class="biografia-status" id="biografia-status" role="status" aria-live="polite"></p>
        </section>
        <?php elseif ($temBiografia): ?>
        <section class="biografia">
            <h2>Biografia</h2>

            <p class="biografia-texto"><?= e($biografia) ?></p>
        </section>
        <?php endif; ?>

        <!--Fotos (retrátil)-->
        <details class="fotos" id="fotos-secao" open>
            <summary>
                <h2>Fotos</h2>
                <i class="bi bi-chevron-down fotos-seta" aria-hidden="true"></i>
            </summary>

            <?php if ($ehDono): ?>
            <p class="fotos-status" id="fotos-status" role="status" aria-live="polite"></p>
            <?php endif; ?>

            <div class="fotos-barra" id="fotos-barra" hidden>
                <p id="fotos-total"></p>
                <?php if ($ehDono): ?>
                <button type="button" class="fotos-link" id="fotos-limpar">Apagar todas</button>
                <?php endif; ?>
            </div>

            <p class="fotos-vazio" id="fotos-vazio"><?= $ehDono
                ? 'Nenhuma foto ainda. Clique no + para adicionar a primeira.'
                : 'Nenhuma foto ainda.' ?></p>

            <div class="fotos-grade" id="fotos-grade">
                <?php if ($ehDono): ?>
                <!-- Botão + : primeiro item da grade, antes das fotos -->
                <label class="fotos-area" id="fotos-area" title="Adicionar fotos">
                    <span class="fotos-mais" aria-hidden="true">+</span>
                    <input type="file" id="fotos-input" accept="image/*" multiple aria-label="Adicionar fotos">
                </label>
                <?php endif; ?>
            </div>

            <!-- Fotos entregues pelo servidor junto com a página (o Perfil.js desenha a grade) -->
            <script type="application/json" id="dados-fotos"><?= json_encode(
                $fotosLista,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
            ) ?></script>

            <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Foto ampliada">
                <span class="lightbox-contador" id="lightbox-contador"></span>
                <button type="button" class="lightbox-botao lightbox-fechar" id="lightbox-fechar" aria-label="Fechar">&times;</button>
                <button type="button" class="lightbox-botao lightbox-anterior" id="lightbox-anterior" aria-label="Foto anterior">&lsaquo;</button>
                <img id="lightbox-foto" alt="">
                <button type="button" class="lightbox-botao lightbox-proximo" id="lightbox-proximo" aria-label="Próxima foto">&rsaquo;</button>
            </div>
        </details>

    </div>
    </main>

    <footer>
        <hr class="linha-header">

        <div class="footer">
            <ul class="footer-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">Quem somos</a></li>
                <li><a href="#">Categorias</a></li>
            </ul>

            <div class="footer-text">
                <img src="../img/logo.png" alt="Imagem do rodapé">
            </div>

            <div class="footer-social">
                <a href="#">Instagram</a>
                <a href="#">X/Twitter</a>
                <a href="#">Facebook</a>
            </div>
        </div>

        <div class="footer-bottom">
            <p>POINT OF VIEW</p>
        </div>
    </footer>

    <script src="../JS/Perfil.js"></script>
</body>
</html>
