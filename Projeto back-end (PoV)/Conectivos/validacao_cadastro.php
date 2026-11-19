<?php
// Regras de validação do cadastro, usadas por:
//   - Controladores/cadastro_Control.php   (criação da conta)
//   - Controladores/verificar_email.php    (aviso de e-mail em uso enquanto a pessoa digita)
// As mesmas regras existem em JS/JSCadastro.js (o PHP é sempre quem decide de verdade).

// Letras aceitas no nome (mesmo conjunto que o cadastro já usava)
const CADASTRO_LETRAS = 'A-Za-zÀ-ÖØ-öø-ÿ';

/** Lê um campo de texto do POST. Devolve '' se faltar, se vier como array ou se não for UTF-8. */
function campo_texto(string $chave): string
{
    $valor = $_POST[$chave] ?? '';
    if (!is_string($valor) || !mb_check_encoding($valor, 'UTF-8')) {
        return '';
    }
    return trim($valor);
}

/**
 * Valida o nome. Devolve o código do erro ou null se estiver tudo certo.
 * Regras: só letras (com espaço, hífen e apóstrofo), pelo menos 3 LETRAS, no máximo 100 caracteres.
 * $nome já deve vir sem espaços repetidos.
 */
function validar_nome(string $nome): ?string
{
    $L = CADASTRO_LETRAS;

    if ($nome !== '' && !preg_match("/^[$L'-]+(?: [$L'-]+)*$/u", $nome)) {
        return 'nome_invalido';
    }
    // Conta só letras: espaços, hífens e apóstrofos não valem
    if ($nome === '' || preg_match_all("/[$L]/u", $nome) < 3) {
        return 'nome_curto';
    }
    if (mb_strlen($nome) > 100) {
        return 'nome_longo';
    }
    return null;
}

/**
 * Valida o formato do e-mail (não consulta o banco).
 * Regras: precisa ter "@", terminar em ".com" (ou ".com.br", ".com.pt"...), ser um e-mail válido e ter até 60 caracteres.
 */
function validar_email(string $email): ?string
{
    if ($email === '') {
        return 'email_vazio';
    }
    if (strpos($email, '@') === false) {
        return 'email_sem_arroba';
    }
    if (!preg_match('/\.com(\.[a-z]{2})?$/i', $email)) {
        return 'email_sem_com';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'email_formato';
    }
    if (strlen($email) > 60) {
        return 'email_longo';
    }
    return null;
}

/**
 * Consulta o banco: já existe uma conta com este valor?
 * $coluna é uma lista fixa (nunca vem do usuário). Devolve true/false, ou null se a consulta falhar.
 */
function valor_em_uso(mysqli $conn, string $coluna, string $valor): ?bool
{
    if (!in_array($coluna, ['email', 'cpf'], true)) {
        return null;
    }

    try {
        $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE $coluna = ? LIMIT 1");
        if (!$stmt) {
            error_log('valor_em_uso (prepare): ' . $conn->error);
            return null;
        }
        $stmt->bind_param('s', $valor);
        if (!$stmt->execute()) {
            error_log('valor_em_uso (execute): ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    } catch (Throwable $ex) {
        error_log('valor_em_uso: ' . $ex->getMessage());
        return null;
    }
}

/** Textos mostrados na janela de erros (um por código). */
function mensagem_cadastro(string $codigo): string
{
    $mensagens = [
        'nome_curto'       => 'O nome precisa ter pelo menos 3 letras.',
        'nome_invalido'    => 'O nome só pode ter letras, espaços, hífen e apóstrofo (sem números ou símbolos).',
        'nome_longo'       => 'O nome pode ter no máximo 100 caracteres.',
        'email_vazio'      => 'Informe seu e-mail.',
        'email_sem_arroba' => 'O e-mail precisa ter o símbolo "@" (exemplo: nome@email.com).',
        'email_sem_com'    => 'O e-mail precisa terminar em ".com" (exemplo: nome@email.com).',
        'email_formato'    => 'O formato do e-mail é inválido (exemplo: nome@email.com).',
        'email_longo'      => 'O e-mail pode ter no máximo 60 caracteres.',
        'email_em_uso'     => 'Este e-mail já está em uso. Use outro e-mail ou faça login.',
        'senha_curta'      => 'A senha precisa ter pelo menos 8 caracteres.',
        'senha_confirmar'  => 'A confirmação precisa ser igual à senha.',
        'cpf'              => 'CPF inválido.',
        'cpf_duplicado'    => 'Este CPF já está cadastrado.',
        'data'             => 'Data de nascimento inválida.',
        'sexo'             => 'Gênero inválido.',
        'nome_materno'     => 'O nome da mãe pode ter no máximo 50 caracteres.',
        'endereco'         => 'O endereço pode ter no máximo 150 caracteres.',
        'telefone_celular' => 'Celular inválido (use DDD + número).',
        'telefone_fixo'    => 'Telefone fixo inválido (use DDD + número).',
        'foto'             => 'Não foi possível processar a foto de perfil. Tente outra imagem.',
        'foto_tipo'        => 'Formato de foto não permitido. Use JPG, PNG ou WebP.',
        'foto_tamanho'     => 'A foto de perfil é grande demais (máximo de 2 MB).',
        'envio_grande'     => 'Os dados enviados são grandes demais. Tente uma foto menor.',
        'erro_interno'     => 'Não foi possível concluir o cadastro agora. Tente novamente em instantes.',
    ];

    return $mensagens[$codigo] ?? $mensagens['erro_interno'];
}
