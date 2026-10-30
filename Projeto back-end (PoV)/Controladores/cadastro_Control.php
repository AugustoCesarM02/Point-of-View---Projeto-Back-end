<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/Cadastro.html');
    exit;
}

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
    header('Location: ../html/cadastro.html?erro=' . $tipo);
    exit;
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

// ---------- Inserção ----------
$hash = password_hash($senha, PASSWORD_DEFAULT);

// tipo_id = 1 (usuário normal) sempre, independente do que vier do formulário
$sql = 'INSERT INTO usuarios
            (nome, email, senha, cpf, data_de_nascimento, sexo,
             nome_materno, telefone_celular, telefone_fixo, endereco, tipo_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)';

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssssss',
    $nome, $email, $hash, $cpf, $data, $sexo,
    $nome_materno, $telefone_celular, $telefone_fixo, $endereco
);

try {
    $stmt->execute();
    $stmt->close();
    header('Location: ../html/login.html?ok=1');
    exit;
} catch (mysqli_sql_exception $e) {
    $stmt->close();
    if ((int)$e->getCode() === 1062) {
        // Duplicado: descobre se foi o e-mail ou o CPF
        erro(stripos($e->getMessage(), 'cpf') !== false ? 'cpf_duplicado' : 'email');
    }
    error_log('Erro no cadastro: ' . $e->getMessage());
    erro('dados');
}