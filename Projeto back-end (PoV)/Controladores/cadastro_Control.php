<?php
session_start();
require __DIR__ . '/../Conectivos/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/Cadastro.html');
    exit;
}

// ---------- Configuração da foto de perfil ----------
const FOTO_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB
const FOTO_MAX_DIM   = 6000;              // largura/altura máxima aceita (px)
const FOTO_LADO      = 256;               // foto final: 256x256
const FOTO_PASTA     = 'Perfil/usuarios'; // relativo à raiz do projeto (pasta acima de Controladores/)

// ---------- Leitura dos campos ----------
$nome         = trim($_POST['nome'] ?? '');
$email        = trim($_POST['email'] ?? '');
$senha        = $_POST['senha'] ?? '';
$conf         = $_POST['confirmar'] ?? '';
$sexo         = trim($_POST['sexo'] ?? '');
$data         = trim($_POST['data_de_nascimento'] ?? '');
$nome_materno = trim($_POST['nome_materno'] ?? '');
$endereco     = trim($_POST['endereco'] ?? '');

// Mantém só os dígitos
$cpf              = preg_replace('/\D/', '', $_POST['CPF'] ?? '');
$telefone_celular = preg_replace('/\D/', '', $_POST['telefone_celular'] ?? '');
$telefone_fixo    = preg_replace('/\D/', '', $_POST['telefone_fixo'] ?? '');

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

function erro(string $tipo): void
{
    header('Location: ../View/Cadastro.html?erro=' . $tipo);
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

// ---------- Validação obrigatória ----------
if (
    !preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\'-]+(?:\s[A-Za-zÀ-ÖØ-öø-ÿ\'-]+)*$/u', $nome)
    || mb_strlen($nome) < 2 || mb_strlen($nome) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 60
    || strlen($senha) < 8
    || $senha !== $conf
) {
    erro('dados');
}

// ---------- Validação dos campos opcionais ----------
// Campo vazio vira NULL no banco.

if ($cpf === '') {
    $cpf = null;
} elseif (!cpfValido($cpf)) {
    erro('cpf');
}

if ($data === '') {
    $data = null;
} else {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    $dataOk = $d && $d->format('Y-m-d') === $data;
    if (!$dataOk || $d > new DateTime('today') || $d < new DateTime('1900-01-01')) {
        erro('data');
    }
}

if ($sexo === '') {
    $sexo = null;
} elseif (mb_strlen($sexo) > 10) {
    erro('dados');
}

if ($nome_materno === '') {
    $nome_materno = null;
} elseif (mb_strlen($nome_materno) > 50) {
    erro('dados');
}

if ($endereco === '') {
    $endereco = null;
} elseif (mb_strlen($endereco) > 150) {
    erro('dados');
}

foreach (['telefone_celular', 'telefone_fixo'] as $campo) {
    if ($$campo === '') {
        $$campo = null;
    } elseif (strlen($$campo) < 10 || strlen($$campo) > 13) {
        erro('telefone');
    }
}

// ---------- Validação da foto de perfil (opcional) ----------
$fotoTmp  = null;
$fotoMime = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['foto'];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        erro('foto_tamanho');
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        erro('foto');
    }
    if ($f['size'] > FOTO_MAX_BYTES) {
        erro('foto_tamanho');
    }

    // getimagesize lê o conteúdo real do arquivo (não confia em extensão nem no Content-Type do navegador)
    $info = @getimagesize($f['tmp_name']);
    if ($info === false) {
        erro('foto_tipo');
    }
    if (!in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        erro('foto_tipo');
    }
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > FOTO_MAX_DIM || $info[1] > FOTO_MAX_DIM) {
        erro('foto');
    }

    $fotoTmp  = $f['tmp_name'];
    $fotoMime = $info['mime'];
}

// ---------- Salva a foto no disco (nome gerado pelo servidor) ----------
$fotoCaminho = null; // o que vai para o banco: "Perfil/usuarios/<hash>.jpg"
$fotoArquivo = null; // caminho absoluto no disco

if ($fotoTmp !== null) {
    $pasta = __DIR__ . '/../' . FOTO_PASTA;
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
        error_log('Cadastro: não foi possível criar a pasta ' . $pasta);
        erro('foto');
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
        erro('foto');
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

$stmt = null;

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssssss',
        $nome, $email, $hash, $cpf, $data, $sexo,
        $nome_materno, $telefone_celular, $telefone_fixo, $endereco, $fotoCaminho
    );

    $stmt->execute();
    $novoId = $conn->insert_id;
    $stmt->close();

    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $novoId;
    $_SESSION['nome'] = $nome;
    $_SESSION['tipo_id'] = 1;

    header('Location: ../View/Index.php?ok=1');
    exit;
} catch (mysqli_sql_exception $e) {
    if ($stmt) {
        $stmt->close();
    }

    // O cadastro falhou: não deixa a foto órfã no disco
    if ($fotoArquivo !== null && is_file($fotoArquivo)) {
        unlink($fotoArquivo);
    }

    if ((int)$e->getCode() === 1062) {
        // Duplicado: descobre se foi o e-mail ou o CPF
        erro(stripos($e->getMessage(), 'cpf') !== false ? 'cpf_duplicado' : 'email');
    }
    error_log('Erro no cadastro: ' . $e->getMessage());
    erro('dados');
}
