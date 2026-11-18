<?php
/* ==========================================================
   Point of View - Registro de logs
   Controladores/log.php

   O log do site tem duas fontes:

   1) MUDANÇAS NOS DADOS (tipo "mudanca")
      Gravadas pelos gatilhos do banco (veja Banco/Pov.sql) sempre que
      um usuário, uma foto ou um post é criado, editado ou excluído,
      não importa qual arquivo PHP fez a mudança.
      Este arquivo só avisa o banco QUEM está logado (log_definir_ator).

   2) AÇÕES (tipo "acao") e ERROS (tipo "erro")
      Gravadas aqui, automaticamente, ao final de toda requisição POST
      (login, cadastro, salvar biografia, enviar foto, promover,
      excluir...) e de logout. Erros fatais do PHP também entram.

   Ativação: o Conectivos/conexao.php chama log_iniciar() no final.

   Para registrar algo à mão em qualquer script:
       log_registrar($conn, 'acao', 'acesso', 'Senha alterada', 'Pelo perfil');
   Para enriquecer o registro automático da requisição atual:
       log_detalhe('Usuário 12 bloqueado');
       log_marcar_erro('Arquivo grande demais');
       log_pular();   // não registrar esta requisição
   ========================================================== */

function &log_estado()
{
    static $estado = [
        'iniciado' => false,
        'conn'     => null,
        'cred'     => null,
        'id'       => null,
        'nome'     => null,
        'ehAcao'   => false,
        'detalhe'  => null,
        'erro'     => null,
        'pular'    => false,
    ];
    return $estado;
}

function log_ip()
{
    // Só REMOTE_ADDR: cabeçalhos como X-Forwarded-For podem ser forjados
    return isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : null;
}

/** Texto que enriquece o registro automático da requisição atual. */
function log_detalhe($texto)
{
    $e =& log_estado();
    $e['detalhe'] = (string) $texto;
}

/** Marca a requisição atual como erro (ex.: pedido recusado). */
function log_marcar_erro($texto)
{
    $e =& log_estado();
    $e['erro'] = (string) $texto;
}

/** Não registra esta requisição. */
function log_pular()
{
    $e =& log_estado();
    $e['pular'] = true;
}


/**
 * Avisa o banco quem está agindo. Os gatilhos leem @log_usuario, @log_nome
 * e @log_ip. Devolve o nome do usuário (buscado no banco se a sessão não tiver).
 */
function log_definir_ator($conn, $id, $nome)
{
    try {
        if ($id && ($nome === null || $nome === '')) {
            $stmt = $conn->prepare('SELECT nome FROM usuarios WHERE id_usuario = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->bind_result($nomeBanco);
                if ($stmt->fetch()) {
                    $nome = $nomeBanco;
                }
                $stmt->close();
            }
        }

        $u  = $id ? (int) $id : 'NULL';
        $n  = ($nome !== null && $nome !== '') ? "'" . $conn->real_escape_string($nome) . "'" : 'NULL';
        $ip = log_ip();
        $i  = $ip ? "'" . $conn->real_escape_string($ip) . "'" : 'NULL';

        $conn->query("SET @log_usuario = $u, @log_nome = $n, @log_ip = $i");
    } catch (Throwable $ex) {
        error_log('log_definir_ator: ' . $ex->getMessage());
    }
    return $nome;
}


/**
 * Grava um evento no log. $extra['id_usuario'] e $extra['nome'] sobrescrevem
 * o usuário da sessão (use id_usuario => null para "visitante").
 */
function log_registrar($conn, $tipo, $categoria, $acao, $descricao = null, array $extra = [])
{
    try {
        if (array_key_exists('id_usuario', $extra)) {
            $id   = $extra['id_usuario'] ? (int) $extra['id_usuario'] : null;
            $nome = isset($extra['nome']) ? (string) $extra['nome'] : null;
        } else {
            $id   = (isset($_SESSION['id_usuario']) && (int) $_SESSION['id_usuario'] > 0) ? (int) $_SESSION['id_usuario'] : null;
            $nome = isset($_SESSION['nome']) ? (string) $_SESSION['nome'] : null;
        }

        $acao = mb_substr((string) $acao, 0, 100);
        if ($descricao !== null) {
            $descricao = mb_substr((string) $descricao, 0, 1000);
        }
        $ip  = log_ip();
        $sql = 'INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        // Se o usuário da sessão já não existir mais, tenta de novo sem ele (chave estrangeira)
        foreach ([$id, null] as $idTentativa) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ssssiss', $tipo, $categoria, $acao, $descricao, $idTentativa, $nome, $ip);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                return true;
            }
            if ($id === null) {
                break;
            }
        }
        return false;
    } catch (Throwable $ex) {
        error_log('log_registrar: ' . $ex->getMessage());
        return false;
    }
}


/** [categoria, nome legível] a partir do nome do script. */
function log_descrever_script($script)
{
    $nome = strtolower(pathinfo($script, PATHINFO_FILENAME));

    $categorias = [
        'logout'    => 'acesso',
        'sair'      => 'acesso',
        'login'     => 'acesso',
        'cadastr'   => 'acesso',
        'senha'     => 'acesso',
        'promover'  => 'admin',
        'excluir_usuario' => 'admin',
        'post'      => 'posts',
        'foto'      => 'fotos',
        'biografia' => 'perfil',
        'perfil'    => 'perfil',
    ];
    $rotulos = [
        'login'            => 'Login',
        'logout'           => 'Logout',
        'sair'             => 'Logout',
        'cadastro'         => 'Cadastro de conta',
        'promover_usuario' => 'Promoção de usuário',
        'excluir_usuario'  => 'Exclusão de usuário',
    ];

    $categoria = 'sistema';
    foreach ($categorias as $trecho => $cat) {
        if (strpos($nome, $trecho) !== false) {
            $categoria = $cat;
            break;
        }
    }

    $rotulo = isset($rotulos[$nome]) ? $rotulos[$nome] : ucfirst(str_replace(['_', '-'], ' ', $nome));
    return [$categoria, $rotulo];
}


/** Conexão para gravar no fim da requisição (reabre se o script já fechou a original). */
function log_conexao_viva(array $e)
{
    try {
        if ($e['conn'] && @$e['conn']->query('SELECT 1')) {
            return $e['conn'];
        }
    } catch (Throwable $ex) {
        // conexão fechada: tenta abrir outra abaixo
    }

    if (is_array($e['cred']) && count($e['cred']) === 4) {
        try {
            $nova = @new mysqli($e['cred'][0], $e['cred'][1], $e['cred'][2], $e['cred'][3]);
            if (!$nova->connect_error) {
                $nova->set_charset('utf8mb4');
                return $nova;
            }
        } catch (Throwable $ex) {
            // sem conexão: o log é descartado
        }
    }
    return null;
}


/**
 * Liga o sistema de logs. Chamado no final do Conectivos/conexao.php,
 * depois de abrir a sessão. Pode ser chamado mais de uma vez sem problema.
 * $cred = [host, usuário, senha, banco], usado só para reabrir a conexão
 * caso o script a feche antes do fim.
 */
function log_iniciar($conn, $cred = null)
{
    $e =& log_estado();
    if ($e['iniciado']) {
        return;
    }
    $e['iniciado'] = true;
    $e['conn']     = $conn;
    $e['cred']     = $cred;

    $id   = (isset($_SESSION['id_usuario']) && (int) $_SESSION['id_usuario'] > 0) ? (int) $_SESSION['id_usuario'] : null;
    $nome = isset($_SESSION['nome']) ? (string) $_SESSION['nome'] : null;

    $e['id']   = $id;
    $e['nome'] = log_definir_ator($conn, $id, $nome);

    $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
    $metodo = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

    // Ação = qualquer POST, ou um script de saída/exclusão chamado por link.
    // (Requisições GET comuns, como abrir páginas, não entram: seriam ruído.)
    $porLink = (bool) preg_match('/logout|sair|excluir|apagar|deletar|remover/i', $script);
    $ehLog   = (strpos(strtolower($script), 'log') === 0);   // log_exportar.php etc.

    $e['ehAcao'] = ($metodo === 'POST' || $porLink) && !$ehLog;

    register_shutdown_function('log_finalizar');
}


/** Roda no fim da requisição: grava a ação com o resultado final. */
function log_finalizar()
{
    $e =& log_estado();

    try {
        $ultimo = error_get_last();
        $fatal  = $ultimo && in_array($ultimo['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);

        if ((!$e['ehAcao'] && !$fatal) || $e['pular']) {
            return;
        }

        $conn = log_conexao_viva($e);
        if (!$conn) {
            return;
        }

        // Quem: a sessão de agora (login acabou de acontecer) ou a do início (logout)
        $idAgora = (isset($_SESSION['id_usuario']) && (int) $_SESSION['id_usuario'] > 0) ? (int) $_SESSION['id_usuario'] : 0;
        $id      = $idAgora > 0 ? $idAgora : $e['id'];
        $nome    = ($idAgora > 0 && isset($_SESSION['nome'])) ? (string) $_SESSION['nome'] : $e['nome'];
        $extra   = ['id_usuario' => $id, 'nome' => $nome];

        $textoFatal = $fatal
            ? basename($ultimo['file']) . ', linha ' . $ultimo['line'] . ': ' . mb_substr($ultimo['message'], 0, 300)
            : null;

        // Erro fatal numa página qualquer (que não era uma ação)
        if (!$e['ehAcao']) {
            log_registrar($conn, 'erro', 'sistema', 'Erro no sistema', $textoFatal, $extra);
            return;
        }

        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        $metodo = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        list($categoria, $acao) = log_descrever_script($script);

        $tipo   = 'acao';
        $partes = [];
        if ($e['detalhe'] !== null) {
            $partes[] = $e['detalhe'];
        }

        // Login: o resultado se descobre pela sessão (logou ou não logou)
        if ($categoria === 'acesso' && $acao === 'Login' && $metodo === 'POST') {
            if ($idAgora > 0 && $e['id'] !== $idAgora) {
                $acao = 'Login realizado';
            } elseif ($idAgora <= 0) {
                $acao = 'Falha de login';
                $tipo = 'erro';
                if (isset($_POST['email']) && is_string($_POST['email'])) {
                    $partes[] = 'E-mail informado: ' . mb_substr(trim($_POST['email']), 0, 60);
                }
            }
        }

        // O que foi enviado (nomes dos campos e ids de alvo; nunca valores de senha)
        if ($metodo === 'POST') {
            $alvos = [];
            foreach (['id', 'id_usuario', 'id_foto', 'id_post'] as $chave) {
                if (isset($_POST[$chave]) && is_string($_POST[$chave]) && ctype_digit($_POST[$chave])) {
                    $alvos[] = $chave . '=' . $_POST[$chave];
                }
            }
            if ($alvos) {
                $partes[] = 'Alvo: ' . implode(', ', $alvos);
            }

            $campos = [];
            foreach (array_keys($_POST) as $campo) {
                if (is_string($campo) && !preg_match('/senha|pass|csrf|token|retorno/i', $campo)) {
                    $campos[] = $campo;
                }
            }
            if ($campos && $categoria !== 'acesso') {
                $partes[] = 'Campos: ' . implode(', ', array_slice($campos, 0, 12));
            }
        }

        $status = http_response_code();
        if ($e['erro'] !== null) {
            $tipo     = 'erro';
            $partes[] = $e['erro'];
        } elseif ($status && $status >= 400) {
            $tipo     = 'erro';
            $partes[] = 'HTTP ' . $status;
        }
        if ($textoFatal !== null) {
            $tipo     = 'erro';
            $partes[] = $textoFatal;
        }

        log_registrar($conn, $tipo, $categoria, $acao, $partes ? implode(' - ', $partes) : null, $extra);
    } catch (Throwable $ex) {
        // O log nunca pode derrubar o site
    }
}