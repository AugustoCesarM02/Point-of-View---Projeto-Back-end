<?php
session_start();
require __DIR__ . '/../Conectivos/conexao.php';
require __DIR__ . '/../Conectivos/validacao_cadastro.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/Cadastro.html');
    exit;
}

// ---------- Configuração da foto de perfil ----------
const FOTO_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB
const FOTO_MAX_DIM   = 6000;              // largura/altura máxima aceita (px)
const FOTO_LADO      = 256;               // foto final: 256x256
const FOTO_PASTA     = 'Perfil/usuarios'; // relativo à raiz do projeto (pasta acima de Controladores/)

// O JS envia o formulário por fetch e espera JSON (assim a janela de erros aparece sem recarregar
// a página e a pessoa não perde o que digitou). Sem JS, o formulário comum continua funcionando:
// os erros voltam pela URL (?erro=codigo1,codigo2) e a própria página mostra a janela.
$ehAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

// ---------- Funções auxiliares ----------
function cpfValido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false; // tamanho errado ou todos os dígitos iguais
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}

/** Anota um erro: $campo é o id do campo na tela (para destacá-lo), $codigo escolhe a mensagem. */
function adicionar_erro(array &$erros, string $campo, string $codigo): void
{
    // Um erro por código (evita mensagem repetida)
    foreach ($erros as $existente) {
        if ($existente['codigo'] === $codigo) {
            return;
        }
    }
    $erros[] = ['campo' => $campo, 'codigo' => $codigo, 'mensagem' => mensagem_cadastro($codigo)];
}

/** Encerra a requisição mostrando os erros (JSON para o JS, ou volta para o formulário). */
function responder_erros(array $erros, bool $ehAjax): void
{
    $codigos = array_column($erros, 'codigo');

    // Aparece na tela de logs do admin como erro, sem guardar nenhum dado pessoal
    if (function_exists('log_marcar_erro')) {
        log_marcar_erro('Cadastro recusado: ' . implode(', ', $codigos));
    }

    if ($ehAjax) {
        json_out(['ok' => false, 'erros' => $erros], 422);
    }

    header('Location: ../View/Cadastro.html?erro=' . implode(',', $codigos));
    exit;
}

/**
 * Lê a imagem enviada, corrige a rotação (EXIF), recorta em quadrado,
 * redimensiona e salva como JPEG. Como a imagem é recodificada, metadados
 * (GPS etc.) e qualquer conteúdo embutido são descartados.
 */
function salvarFoto(string $tmp, string $mime, string $destino): bool
{
    // Sem GD: não dá para redimensionar/recodificar, então salva o arquivo original
    // (já validado como imagem real por getimagesize e com extensão definida pelo servidor)
    if (!extension_loaded('gd')) {
        return move_uploaded_file($tmp, $destino);
    }

    switch ($mime) {
        case 'image/jpeg':
            $img = @imagecreatefromjpeg($tmp);
            break;
        case 'image/png':
            $img = @imagecreatefrompng($tmp);
            break;
        case 'image/webp':
            $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false;
            break;
        default:
            $img = false;
    }
    if (!$img) {
        return false;
    }

    // Fotos de celular costumam vir "deitadas": corrige pela orientação EXIF
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $orientacao = $exif['Orientation'] ?? 1;
        $angulo = [3 => 180, 6 => -90, 8 => 90][$orientacao] ?? 0;
        if ($angulo !== 0) {
            $rot = imagerotate($img, $angulo, 0);
            if ($rot) {
                $img = $rot;
            }
        }
    }

    // Recorte quadrado central
    $w    = imagesx($img);
    $h    = imagesy($img);
    $lado = min($w, $h);
    $sx   = intdiv($w - $lado, 2);
    $sy   = intdiv($h - $lado, 2);

    $saida = imagecreatetruecolor(FOTO_LADO, FOTO_LADO);
    imagefill($saida, 0, 0, imagecolorallocate($saida, 255, 255, 255)); // fundo branco (PNG transparente)
    imagecopyresampled($saida, $img, 0, 0, $sx, $sy, FOTO_LADO, FOTO_LADO, $lado, $lado);

    return imagejpeg($saida, $destino, 85);
}

// ---------- Envio grande demais ----------
// Se passar do post_max_size do PHP, $_POST e $_FILES chegam vazios (sem isso a pessoa
// veria "nome inválido, e-mail inválido..." sem entender o porquê)
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $erros = [];
    adicionar_erro($erros, 'foto', 'envio_grande');
    responder_erros($erros, $ehAjax);
}

// ---------- Leitura dos campos ----------
$nome         = preg_replace('/\s+/u', ' ', campo_texto('nome')); // sem espaços repetidos
$email        = campo_texto('email');
$senha        = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
$conf         = is_string($_POST['confirmar'] ?? null) ? $_POST['confirmar'] : '';
$sexo         = campo_texto('sexo');
$data         = campo_texto('data_de_nascimento');
$nome_materno = campo_texto('nome_materno');
$endereco     = campo_texto('endereco');

// Mantém só os dígitos
$cpf              = preg_replace('/\D/', '', campo_texto('CPF'));
$telefone_celular = preg_replace('/\D/', '', campo_texto('telefone_celular'));
$telefone_fixo    = preg_replace('/\D/', '', campo_texto('telefone_fixo'));

// Daqui para baixo nada é interrompido no primeiro erro: tudo é conferido e
// a pessoa recebe a lista completa do que precisa corrigir.
$erros = [];

// ---------- Campos obrigatórios: nome, e-mail e senha ----------
$codigo = validar_nome($nome);
if ($codigo !== null) {
    adicionar_erro($erros, 'nome', $codigo);
}

$codigo = validar_email($email);
if ($codigo !== null) {
    adicionar_erro($erros, 'email', $codigo);
} else {
    // Formato certo: agora confere no banco se o e-mail já está em uso
    $emUso = valor_em_uso($conn, 'email', $email);
    if ($emUso === null) {
        adicionar_erro($erros, 'email', 'erro_interno');
    } elseif ($emUso) {
        adicionar_erro($erros, 'email', 'email_em_uso');
    }
}

if (strlen($senha) < 8) {
    adicionar_erro($erros, 'senha', 'senha_curta');
}
if ($conf === '' || $senha !== $conf) {
    adicionar_erro($erros, 'senha2', 'senha_confirmar');
}

// ---------- Campos opcionais (vazio vira NULL no banco) ----------
if ($cpf === '') {
    $cpf = null;
} elseif (!cpfValido($cpf)) {
    adicionar_erro($erros, 'cpf', 'cpf');
} else {
    $emUso = valor_em_uso($conn, 'cpf', $cpf);
    if ($emUso === null) {
        adicionar_erro($erros, 'cpf', 'erro_interno');
    } elseif ($emUso) {
        adicionar_erro($erros, 'cpf', 'cpf_duplicado');
    }
}

if ($data === '') {
    $data = null;
} else {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    $dataOk = $d && $d->format('Y-m-d') === $data;
    if (!$dataOk || $d > new DateTime('today') || $d < new DateTime('1900-01-01')) {
        adicionar_erro($erros, 'dataNasc', 'data');
    }
}

if ($sexo === '') {
    $sexo = null;
} elseif (mb_strlen($sexo) > 10) {
    adicionar_erro($erros, 'genero', 'sexo');
}

if ($nome_materno === '') {
    $nome_materno = null;
} elseif (mb_strlen($nome_materno) > 50) {
    adicionar_erro($erros, 'nomemae', 'nome_materno');
}

if ($endereco === '') {
    $endereco = null;
} elseif (mb_strlen($endereco) > 150) {
    adicionar_erro($erros, 'endereco', 'endereco');
}

$camposTelefone = ['telefone_celular' => 'celular', 'telefone_fixo' => 'fixo'];
foreach ($camposTelefone as $campo => $idNaTela) {
    if ($$campo === '') {
        $$campo = null;
    } elseif (strlen($$campo) < 10 || strlen($$campo) > 13) {
        adicionar_erro($erros, $idNaTela, $campo);
    }
}

// ---------- Validação da foto de perfil (opcional) ----------
$fotoTmp  = null;
$fotoMime = null;

if (
    isset($_FILES['foto']) && is_array($_FILES['foto']) && !is_array($_FILES['foto']['error'])
    && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $f = $_FILES['foto'];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        adicionar_erro($erros, 'foto', 'foto_tamanho');
    } elseif ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        adicionar_erro($erros, 'foto', 'foto');
    } elseif ($f['size'] > FOTO_MAX_BYTES) {
        adicionar_erro($erros, 'foto', 'foto_tamanho');
    } else {
        // getimagesize lê o conteúdo real do arquivo (não confia em extensão nem no Content-Type do navegador)
        $info = @getimagesize($f['tmp_name']);
        if ($info === false || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            adicionar_erro($erros, 'foto', 'foto_tipo');
        } elseif ($info[0] < 1 || $info[1] < 1 || $info[0] > FOTO_MAX_DIM || $info[1] > FOTO_MAX_DIM) {
            adicionar_erro($erros, 'foto', 'foto');
        } else {
            $fotoTmp  = $f['tmp_name'];
            $fotoMime = $info['mime'];
        }
    }
}

// ---------- Tem algum erro? Volta para o formulário com a lista ----------
if ($erros) {
    responder_erros($erros, $ehAjax);
}

// ---------- Salva a foto no disco (nome gerado pelo servidor) ----------
$fotoCaminho = null; // o que vai para o banco: "Perfil/usuarios/<hash>.jpg"
$fotoArquivo = null; // caminho absoluto no disco

if ($fotoTmp !== null) {
    $pasta = __DIR__ . '/../' . FOTO_PASTA;
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
        error_log('Cadastro: não foi possível criar a pasta ' . $pasta);
        adicionar_erro($erros, 'foto', 'foto');
        responder_erros($erros, $ehAjax);
    }

    // Com GD a imagem sempre vira .jpg; sem GD mantém o formato original (extensão vem do tipo real, nunca do nome enviado)
    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = extension_loaded('gd') ? 'jpg' : $extensoes[$fotoMime];

    // Proteção extra: a pasta de uploads nunca executa scripts nem lista arquivos
    $htaccess = $pasta . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Options -Indexes\n<FilesMatch \"\\.(?i:php|phtml|phar)$\">\n    Require all denied\n</FilesMatch>\n"
        );
    }

    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $ext;
    $fotoArquivo = $pasta . '/' . $nomeArquivo;

    if (!salvarFoto($fotoTmp, $fotoMime, $fotoArquivo)) {
        if (is_file($fotoArquivo)) {
            unlink($fotoArquivo);
        }
        adicionar_erro($erros, 'foto', 'foto');
        responder_erros($erros, $ehAjax);
    }
    $fotoCaminho = FOTO_PASTA . '/' . $nomeArquivo;
}

// ---------- Inserção ----------
$hash = password_hash($senha, PASSWORD_DEFAULT);

// tipo_id = 1 (usuário normal) sempre, independente do que vier do formulário
$sql = 'INSERT INTO usuarios
            (nome, email, senha, cpf, data_de_nascimento, sexo,
             nome_materno, telefone_celular, telefone_fixo, endereco, foto_usuario, tipo_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)';

$ok     = false;
$errno  = 0;
$novoId = 0;

// IMPORTANTE: o Conectivos/conexao.php desliga as exceções do mysqli (MYSQLI_REPORT_OFF).
// Por isso um INSERT que falha NÃO lança erro: o execute() só devolve false.
// (Era isso que fazia o e-mail repetido "passar" e levar a pessoa para o Index sem aviso.)
// Aqui o resultado é sempre conferido; o try/catch cobre o caso de as exceções serem ligadas um dia.
try {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            'sssssssssss',
            $nome, $email, $hash, $cpf, $data, $sexo,
            $nome_materno, $telefone_celular, $telefone_fixo, $endereco, $fotoCaminho
        );
        $ok     = $stmt->execute();
        $errno  = (int) $stmt->errno;
        $novoId = (int) $conn->insert_id;
        if (!$ok) {
            error_log('Erro no cadastro (execute): ' . $stmt->error);
        }
        $stmt->close();
    } else {
        $errno = (int) $conn->errno;
        error_log('Erro no cadastro (prepare): ' . $conn->error);
    }
} catch (mysqli_sql_exception $ex) {
    $ok    = false;
    $errno = (int) $ex->getCode();
    error_log('Erro no cadastro: ' . $ex->getMessage());
}

if (!$ok || $novoId <= 0) {
    // O cadastro falhou: não deixa a foto órfã no disco
    if ($fotoArquivo !== null && is_file($fotoArquivo)) {
        unlink($fotoArquivo);
    }

    if ($errno === 1062) {
        // Alguém usou o e-mail (ou CPF) entre a conferência lá de cima e o INSERT (ou o banco barrou antes).
        // Descobre qual dos dois foi, consultando de novo.
        if (valor_em_uso($conn, 'email', $email)) {
            adicionar_erro($erros, 'email', 'email_em_uso');
        }
        if ($cpf !== null && valor_em_uso($conn, 'cpf', $cpf)) {
            adicionar_erro($erros, 'cpf', 'cpf_duplicado');
        }
    }
    if (!$erros) {
        adicionar_erro($erros, '', 'erro_interno');
    }
    responder_erros($erros, $ehAjax);
}

// ---------- Sucesso: já entra logado ----------
session_regenerate_id(true);
$_SESSION['id_usuario'] = $novoId;
$_SESSION['nome'] = $nome;
$_SESSION['tipo_id'] = 1;

if ($ehAjax) {
    json_out(['ok' => true, 'redirect' => '../View/Index.php?ok=1']);
}

header('Location: ../View/Index.php?ok=1');
exit;
