document.addEventListener("DOMContentLoaded", function () {
const toggle = document.getElementById("toggle-dark");
const iconSun = toggle.querySelector(".sun");
const iconMoon = toggle.querySelector(".moon");

function applyDark(isDark) {
    document.body.classList.toggle("dark-mode", !!isDark);
    iconSun.style.display = isDark ? "none" : "inline-block";
    iconMoon.style.display = isDark ? "inline-block" : "none";
    toggle.setAttribute("aria-pressed", isDark ? "true" : "false");
}

const savedTheme = localStorage.getItem("infomark-dark");
applyDark(savedTheme === "1");
toggle.addEventListener("click", function () {
    const nextDark = !document.body.classList.contains("dark-mode");
    applyDark(nextDark);
    localStorage.setItem("infomark-dark", nextDark ? "1" : "0");
});

const root = document.documentElement;
const savedFont = localStorage.getItem("infomark-fontsize");
if (savedFont) {
    root.style.fontSize = savedFont + "%";
} else {
    root.style.fontSize = "100%";
}

function setFontSize(size) {
    root.style.fontSize = size + "%";
    localStorage.setItem("infomark-fontsize", size);
}

document.getElementById("font-inc").addEventListener("click", () => {
    let current = parseInt(root.style.fontSize) || 100;
    setFontSize(Math.min(current + 10, 200));
});

document.getElementById("font-dec").addEventListener("click", () => {
    let current = parseInt(root.style.fontSize) || 100;
    setFontSize(Math.max(current - 10, 70));
});
});

// Impede números no campo Nome completo
const nomeInput = document.getElementById("nome");

nomeInput.addEventListener("input", function() {
    this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, "");
});


// Máscara para autoformatação do celular
function validarCelularInput(celularInput) {
  // Permite apenas números enquanto o usuário digita e limita a 11 dígitos
  celularInput.addEventListener("input", () => {
    celularInput.value = celularInput.value.replace(/\D/g, '').slice(0, 11);
  });
}

const celularInput = document.getElementById("celular")
validarCelularInput(celularInput)

// Máscara para autoformatação do fixo
function validarFixoInput(fixoInput) {
    // Permite apenas números enquanto o usuário digita e limita a 11 dígitos
    fixoInput.addEventListener("input", () => {
        fixoInput.value = fixoInput.value.replace(/\D/g, '').slice(0, 11);
    });
}

const telefoneFixoInput = document.getElementById("fixo")
validarFixoInput(telefoneFixoInput)

// Máscara para CPF: 000.000.000-00
const cpfInput = document.getElementById("cpf");

cpfInput.addEventListener("input", function() {
    let valor = this.value.replace(/\D/g, ""); // Remove tudo que não é número

    // Limita a 11 dígitos
    if (valor.length > 11) valor = valor.slice(0, 11);

    // Aplica a máscara conforme a quantidade de dígitos
    if (valor.length > 9) {
        valor = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, "$1.$2.$3-$4");
    } else if (valor.length > 6) {
        valor = valor.replace(/(\d{3})(\d{3})(\d{0,3})/, "$1.$2.$3");
    } else if (valor.length > 3) {
        valor = valor.replace(/(\d{3})(\d{0,3})/, "$1.$2");
    }

    this.value = valor;
});



// Validação do cadastro
document.getElementById("cadastrar").addEventListener("click", function() {
    const nome = document.getElementById("nome");
    const cpf = document.getElementById("cpf");
    const email = document.getElementById("email");
    const celular = document.getElementById("celular");
    const fixo = document.getElementById("fixo");
    const endereco = document.getElementById("endereco");
    const senha = document.getElementById("senha");
    const senha2 = document.getElementById("senha2");

    // Regex
    const regexNome = /^[\p{L}\s]{15,80}$/u;
    const regexNomemae = /^[\p{L}\s]{15,80}$/u;
    const regexCPF = /^(?:\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11})$/;
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const regexCelular = /^\d{11}$/;
    const regexFixo = /^\d{11}$/;
    const regexEndereco = /^.{10,}$/;
    const regexSenha = /^[A-Za-z0-9!@#\$%\^&\*\-_]{6,}$/;

    // Função para validar campo com Regex
    // Aplica classe "valido" quando corresponde ao regex e "erro" quando não corresponde
    const validar = (campo, regex) => {
        const valor = campo.value.trim();
        const ok = regex.test(valor);
        if (ok && valor !== "") {
            campo.classList.remove("erro");
            campo.classList.add("valido");
        } else {
            campo.classList.remove("valido");
            campo.classList.add("erro");
        }
        return ok;
    };

    // Função para validar se o campo é obrigatório
    const validarObrigatorio = (campo) => {
        const estaVazio = campo.value.trim() === "" || campo.value === null;
        if (estaVazio) {
            campo.classList.remove("valido");
            campo.classList.add("erro");
        } else {
            campo.classList.remove("erro");
            campo.classList.add("valido");
        }
        return !estaVazio;
    };

    // Validações com mensagens (para modal)
    const errors = [];

    // Nome
    if (!validarObrigatorio(nome)) {
        errors.push('Nome completo: campo obrigatório.');
    } else if (!validar(nome, regexNome)) {
        errors.push('Nome completo: digite entre 15 e 80 caracteres (apenas letras e espaços).');
    }

    if (!validarObrigatorio(nomemae)) {
        errors.push('Nome da mãe: campo obrigatório.');
    } else if (!validar(nomemae, regexNomemae)) {
        errors.push('Nome da mãe: digite entre 15 e 80 caracteres (apenas letras e espaços).');
    }

    // CPF
    if (!validarObrigatorio(cpf)) {
        errors.push('CPF: campo obrigatório.');
    } else if (!validar(cpf, regexCPF)) {
        errors.push('CPF: formato inválido. Use 000.000.000-00 ou 11 dígitos.');
    }

    // Gênero (select)
    const genero = document.getElementById('genero');
    if (!validarObrigatorio(genero)) {
        errors.push('Gênero: campo obrigatório.');
    }

    // Data de nascimento
    const dataNasc = document.getElementById('dataNasc');
    if (!validarObrigatorio(dataNasc)) {
        errors.push('Data de nascimento: campo obrigatório.');
    }

    // Celular
    if (!validarObrigatorio(celular)) {
        errors.push('Telefone celular: campo obrigatório.');
    } else if (!validar(celular, regexCelular)) {
        errors.push('Telefone celular: informe 11 dígitos (DDD) 9XXXX-XXXX.');
    }

    // E-mail
    if (!validarObrigatorio(email)) {
        errors.push('E-mail: campo obrigatório.');
    } else {
        const emailCheck = emailIsValid(email.value.trim());
        if (!emailCheck.isValid) {
            email.classList.remove('valido');
            email.classList.add('erro');
            errors.push('E-mail: ' + emailCheck.errorMessage);
        } else {
            email.classList.remove('erro');
            email.classList.add('valido');
        }
    }

    // Fixo (agora obrigatório)
    if (!validarObrigatorio(fixo)) {
        errors.push('Telefone fixo: campo obrigatório.');
    } else if (!validar(fixo, regexFixo)) {
        errors.push('Telefone fixo: informe 11 dígitos (DDD) XXXXX-XXXX.');
    }

    // Endereço
    if (!validarObrigatorio(endereco)) {
        errors.push('Endereço: campo obrigatório.');
    } else if (!validar(endereco, regexEndereco)) {
        errors.push('Endereço: deve conter pelo menos 10 caracteres.');
    }

    // Senhas
    if (!validarObrigatorio(senha)) {
        errors.push('Senha: campo obrigatório.');
    } else if (!validar(senha, regexSenha)) {
        errors.push('Senha: mínimo 6 caracteres. Caracteres aceitos: letras, números e caractéres especiais');
    }

    if (!validarObrigatorio(senha2)) {
        errors.push('Confirme sua senha: campo obrigatório.');
    }

    // Confirmação de senha
    const senhasIguais = senha.value === senha2.value && senha.value.trim() !== '';
    if (!senhasIguais) {
        senha.classList.remove('valido');
        senha.classList.add('erro');
        senha2.classList.remove('valido');
        senha2.classList.add('erro');
        errors.push('As senhas não conferem.');
    }

    // Mostrar modal com erros ou prosseguir
    const modal = document.getElementById('modalErros');
    const modalList = document.getElementById('modalErrosList');
    const modalFechar = document.getElementById('modalFechar');
    const modalOverlay = document.getElementById('modalOverlay');

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    modalFechar?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', closeModal);

    if (errors.length > 0) {
        // popular lista
        modalList.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        // colocar foco no botão fechar para acessibilidade
        modalFechar?.focus();
    } else {
        alert('Cadastro realizado com sucesso!');
        const form = document.getElementById('formCadastro');
        form.reset();
        // Remove classes de validação após o reset
        form.querySelectorAll('input, select').forEach(i => i.classList.remove('erro', 'valido'));
    }
});

function emailIsValid(value) {
    const validator = {
        isValid: true,
        errorMessage: null
    }

    if (!value) {
        validator.isValid = false;
        validator.errorMessage = 'O e-mail é obrigatório!';
        return validator;
    }

    const regex = new RegExp("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$");
    if (!regex.test(value)) {
        validator.isValid = false;
        validator.errorMessage = 'O e-mail precisa ser válido!';
        return validator;
    }

    return validator;
}

function salvartudo() {
    // Função para salvar os dados do formulário no localStorage
    const email = document.getElementById("email").value;
    localStorage.setItem("email", email);
    const senha = document.getElementById("senha").value;
    localStorage.setItem("senha", senha);
}