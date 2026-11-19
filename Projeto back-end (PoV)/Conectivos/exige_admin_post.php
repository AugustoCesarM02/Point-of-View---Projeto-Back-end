<?php
// Usado pelos controladores do painel administrativo (promover e excluir usuário).
// Só deixa passar: método POST, usuário logado, administrador (conferido no banco)
// e token CSRF válido.
//
// Depois do require, estas variáveis estão prontas:
//   $idAdmin   - id de quem está logado (administrador)
//   $nomeAdmin - nome dele
//   $idAlvo    - id do usuário que vai sofrer a ação (já validado como inteiro > 0)
//
// Diferente do exige_login_post.php (que responde em JSON), aqui a resposta é um
// redirecionamento de volta ao DashboardU.php, porque o formulário do painel é
// enviado pelo navegador de forma comum (sem fetch).
require_once __DIR__ . '/conexao.php';

const TIPO_ADMIN        = 2;   // tipos_usuario.tipo_id do administrador (veja Banco/Pov.sql)
const DASHBOARD_USUARIOS = '../View/DashboardU.php';

/**
 * Guarda a mensagem para o painel mostrar e volta para ele.
 * $tipo: 'sucesso', 'aviso' ou 'erro'. Também alimenta o log da requisição.
 */
function voltar_dashboard(string $tipo, string $mensagem): void
{
    $_SESSION['flash_dashboard'] = ['tipo' => $tipo, 'texto' => $mensagem];

    if ($tipo === 'erro' && function_exists('log_marcar_erro')) {
        log_marcar_erro($mensagem);
    } elseif ($tipo !== 'erro' && function_exists('log_detalhe')) {
        log_detalhe($mensagem);
    }

    header('Location: ' . DASHBOARD_USUARIOS);
    exit;
}

// 1) Só aceita POST. Abrir o controlador pela barra de endereço não faz nada.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (function_exists('log_pular')) {
        log_pular();
    }
    header('Location: ' . DASHBOARD_USUARIOS);
    exit;
}

// 2) Precisa estar logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../View/Login.html');
    exit;
}

$idAdmin = (int) $_SESSION['id_usuario'];

// 3) Precisa ser admin, conferido no banco e não só na sessão
$stmt = $conn->prepare('SELECT nome, tipo_id FROM usuarios WHERE id_usuario = ?');
if (!$stmt) {
    error_log('exige_admin_post (select): ' . $conn->error);
    voltar_dashboard('erro', 'Não foi possível verificar suas permissões. Tente de novo.');
}
$stmt->bind_param('i', $idAdmin);
$stmt->execute();
$stmt->bind_result($nomeAdmin, $tipoAdmin);
$achouAdmin = $stmt->fetch();
$stmt->close();

if (!$achouAdmin) {
    // A conta de quem está logado não existe mais
    header('Location: ../Conectivos/logout.php');
    exit;
}

if ((int) $tipoAdmin !== TIPO_ADMIN) {
    if (function_exists('log_marcar_erro')) {
        log_marcar_erro('Tentativa sem permissão de administrador');
    }
    header('Location: ../View/Index.php');
    exit;
}

// 4) Token CSRF (gerado no DashboardU.php, na mesma chave de sessão do Perfil.php)
$tokenRecebido = $_POST['csrf'] ?? '';
if (
    !isset($_SESSION['csrf'])
    || !is_string($tokenRecebido)
    || !hash_equals((string) $_SESSION['csrf'], $tokenRecebido)
) {
    voltar_dashboard('erro', 'Sessão expirada. Recarregue a página e tente de novo.');
}

// 5) Usuário alvo
$idAlvo = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idAlvo === false || $idAlvo === null) {
    voltar_dashboard('erro', 'Usuário inválido.');
}
$idAlvo = (int) $idAlvo;
