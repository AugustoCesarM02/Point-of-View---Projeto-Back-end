<?php
// Recebe UMA foto de perfil e devolve JSON.
// Só o dono do perfil (usuário logado) troca a própria foto.
// Diferente da galeria, o usuário só tem uma foto de perfil: enviar outra SUBSTITUI a anterior.
require __DIR__ . '/../Conectivos/exige_login_post.php';

// ---------- Configuração ----------
const PERFIL_FOTO_MAX_BYTES  = 5 * 1024 * 1024;  // 5 MB (o php.ini também tem o seu limite)
const PERFIL_FOTO_MAX_DIM    = 8000;             // largura/altura máxima aceita (px)
const PERFIL_FOTO_MAX_PIXELS = 16000000;         // 16 megapixels: evita estourar a memória do PHP
const PERFIL_FOTO_LADO       = 512;              // foto final: 512x512 (quadrada)
const PERFIL_FOTO_PASTA      = 'Perfil/usuarios'; // relativo à raiz do projeto (pasta acima de Controladores/)

/**
 * Lê a imagem enviada, corrige a rotação (EXIF), recorta em quadrado no centro,
 * redimensiona e salva como JPEG. Como a imagem é recodificada, metadados
 * (GPS etc.) e qualquer conteúdo embutido são descartados.
 */
function salvarFotoPerfil(string $tmp, string $mime, string $destino): bool
{
    // Sem GD: não dá para recodificar, então salva o arquivo original
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

    $saida = imagecreatetruecolor(PERFIL_FOTO_LADO, PERFIL_FOTO_LADO);
    imagefill($saida, 0, 0, imagecolorallocate($saida, 255, 255, 255)); // fundo branco (PNG transparente)
    imagecopyresampled($saida, $img, 0, 0, $sx, $sy, PERFIL_FOTO_LADO, PERFIL_FOTO_LADO, $lado, $lado);

    return imagejpeg($saida, $destino, 86);
}

// ---------- Validação do arquivo ----------
if (!isset($_FILES['foto']) || !is_array($_FILES['foto']) || is_array($_FILES['foto']['error'])) {
    json_out(['erro' => 'Nenhuma foto recebida.'], 400);
}

$f = $_FILES['foto'];

if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
    json_out(['erro' => 'A foto é grande demais.'], 413);
}
if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    json_out(['erro' => 'Não foi possível receber a foto.'], 400);
}
if ($f['size'] > PERFIL_FOTO_MAX_BYTES) {
    json_out(['erro' => 'A foto é grande demais (máximo de 5 MB).'], 413);
}

// getimagesize lê o conteúdo real do arquivo (não confia em extensão nem no Content-Type do navegador)
$info = @getimagesize($f['tmp_name']);
if ($info === false || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
    json_out(['erro' => 'Formato não permitido. Use JPG, PNG ou WebP.'], 400);
}
if (
    $info[0] < 1 || $info[1] < 1
    || $info[0] > PERFIL_FOTO_MAX_DIM || $info[1] > PERFIL_FOTO_MAX_DIM
    || $info[0] * $info[1] > PERFIL_FOTO_MAX_PIXELS
) {
    json_out(['erro' => 'A foto tem dimensões grandes demais.'], 400);
}

$mime = $info['mime'];

// ---------- Foto atual (será apagada depois que a nova for salva) ----------
$fotoAntiga = null;

$stmt = $conn->prepare('SELECT foto_usuario FROM usuarios WHERE id_usuario = ?');
if (!$stmt) {
    error_log('foto_perfil_salvar (select): ' . $conn->error);
    json_out(['erro' => 'Não foi possível salvar a foto.'], 500);
}
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$stmt->bind_result($fotoAntiga);
$achou = $stmt->fetch();
$stmt->close();

if (!$achou) {
    json_out(['erro' => 'Usuário não encontrado.'], 404);
}

// ---------- Salva a foto no disco (nome gerado pelo servidor) ----------
$pasta = __DIR__ . '/../' . PERFIL_FOTO_PASTA;
if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
    error_log('foto_perfil_salvar: não foi possível criar a pasta ' . $pasta);
    json_out(['erro' => 'Não foi possível salvar a foto.'], 500);
}

// Proteção extra: a pasta nunca executa scripts nem lista arquivos
$htaccess = $pasta . '/.htaccess';
if (!is_file($htaccess)) {
    @file_put_contents(
        $htaccess,
        "Options -Indexes\n<FilesMatch \"\\.(?i:php|phtml|phar)$\">\n    Require all denied\n</FilesMatch>\n"
    );
}

// Com GD a imagem sempre vira .jpg; sem GD mantém o formato original (extensão vem do tipo real, nunca do nome enviado)
$extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$ext = extension_loaded('gd') ? 'jpg' : $extensoes[$mime];

$nomeArquivo  = bin2hex(random_bytes(16)) . '.' . $ext;
$arquivoDisco = $pasta . '/' . $nomeArquivo;

if (!salvarFotoPerfil($f['tmp_name'], $mime, $arquivoDisco)) {
    if (is_file($arquivoDisco)) {
        unlink($arquivoDisco);
    }
    json_out(['erro' => 'Não foi possível processar a foto. Tente outra imagem.'], 400);
}

// ---------- Registra no banco ----------
$caminho = PERFIL_FOTO_PASTA . '/' . $nomeArquivo; // o que vai para o banco: "Perfil/usuarios/<hash>.jpg"

$stmt = $conn->prepare('UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?');
if (!$stmt || !$stmt->bind_param('si', $caminho, $idUsuario) || !$stmt->execute()) {
    error_log('foto_perfil_salvar (update): ' . $conn->error);
    // O registro falhou: não deixa a foto órfã no disco
    if (is_file($arquivoDisco)) {
        unlink($arquivoDisco);
    }
    json_out(['erro' => 'Não foi possível salvar a foto.'], 500);
}
$stmt->close();

// ---------- Apaga a foto anterior do disco (só em caminhos no formato gerado pelo servidor) ----------
if (
    $fotoAntiga
    && $fotoAntiga !== $caminho
    && preg_match('#^Perfil/usuarios/[a-f0-9]{32}\.(jpg|png|webp)$#', $fotoAntiga)
) {
    $antigaDisco = __DIR__ . '/../' . $fotoAntiga;
    if (is_file($antigaDisco)) {
        @unlink($antigaDisco);
    }
}

// A URL é relativa à pasta View/, onde fica o Perfil.php
json_out(['ok' => true, 'url' => '../' . $caminho]);
