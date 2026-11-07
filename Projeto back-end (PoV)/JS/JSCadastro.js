document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formCadastro");

    // -----------------------------------------------------
    // MODO ESCURO E TAMANHO DA FONTE
    // -----------------------------------------------------
    const toggle = document.getElementById("toggle-dark");
    const iconSun = toggle?.querySelector(".sun");
    const iconMoon = toggle?.querySelector(".moon");

    function applyDark(isDark) {
        document.body.classList.toggle("dark-mode", Boolean(isDark));
        if (iconSun) iconSun.style.display = isDark ? "inline-block" : "none";
        if (iconMoon) iconMoon.style.display = isDark ? "none" : "inline-block";
        toggle?.setAttribute("aria-pressed", isDark ? "true" : "false");
    }

    const savedTheme = localStorage.getItem("infomark-dark");
    applyDark(savedTheme === "1");

    toggle?.addEventListener("click", function () {
        const nextDark = !document.body.classList.contains("dark-mode");
        applyDark(nextDark);
        localStorage.setItem("infomark-dark", nextDark ? "1" : "0");
    });

    const root = document.documentElement;
    const savedFont = localStorage.getItem("infomark-fontsize");
    root.style.fontSize = (savedFont || "100") + "%";

    function setFontSize(size) {
        root.style.fontSize = size + "%";
        localStorage.setItem("infomark-fontsize", String(size));
    }

    document.getElementById("font-inc")?.addEventListener("click", function () {
        const current = parseInt(root.style.fontSize, 10) || 100;
        setFontSize(Math.min(current + 10, 150));
    });

    document.getElementById("font-dec")?.addEventListener("click", function () {
        const current = parseInt(root.style.fontSize, 10) || 100;
        setFontSize(Math.max(current - 10, 80));
    });

    // -----------------------------------------------------
    // MÁSCARAS: CPF E TELEFONES
    // -----------------------------------------------------
    const cpfInput = document.getElementById("cpf");

    cpfInput?.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "").slice(0, 11);

        if (valor.length > 9) {
            valor = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, "$1.$2.$3-$4");
        } else if (valor.length > 6) {
            valor = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, "$1.$2.$3");
        } else if (valor.length > 3) {
            valor = valor.replace(/(\d{3})(\d{1,3})/, "$1.$2");
        }

        this.value = valor;
    });

    ["celular", "fixo"].forEach(function (id) {
        const telefone = document.getElementById(id);
        telefone?.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, "").slice(0, 13);
        });
    });

    // -----------------------------------------------------
    // MOSTRAR / OCULTAR SENHA
    // -----------------------------------------------------
    document.querySelectorAll(".mostrar-senha").forEach(function (botao) {
        botao.addEventListener("click", function () {
            const campo = document.getElementById(this.dataset.target);
            const icone = this.querySelector("i");

            if (!campo) return;

            const mostrar = campo.type === "password";
            campo.type = mostrar ? "text" : "password";
            icone.classList.toggle("bi-eye", !mostrar);
            icone.classList.toggle("bi-eye-slash", mostrar);
            this.setAttribute("aria-label", mostrar ? "Ocultar senha" : "Mostrar senha");
        });
    });

    // -----------------------------------------------------
    // VALIDAÇÃO ANTES DE ENVIAR AO PHP
    // Os campos opcionais continuam opcionais, como no PHP.
    // -----------------------------------------------------
    const modal = document.getElementById("modalErros");
    const modalList = document.getElementById("modalErrosList");
    const modalFechar = document.getElementById("modalFechar");
    const modalOverlay = document.getElementById("modalOverlay");

    function fecharModal() {
        modal?.classList.remove("open");
        modal?.setAttribute("aria-hidden", "true");
    }

    modalFechar?.addEventListener("click", fecharModal);
    modalOverlay?.addEventListener("click", fecharModal);

    form?.addEventListener("submit", function (event) {
        const nome = document.getElementById("nome");
        const email = document.getElementById("email");
        const senha = document.getElementById("senha");
        const confirmar = document.getElementById("senha2");
        const cpf = document.getElementById("cpf");
        const celular = document.getElementById("celular");
        const fixo = document.getElementById("fixo");

        const erros = [];

        // Limpa marcações anteriores.
        form.querySelectorAll("input, select").forEach(function (campo) {
            campo.classList.remove("erro", "valido");
        });

        function marcarErro(campo, mensagem) {
            campo.classList.add("erro");
            erros.push(mensagem);
        }

        const nomeValor = nome.value.trim();
        if (nomeValor.length < 2 || nomeValor.length > 100 ||
            !/^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/u.test(nomeValor)) {
            marcarErro(nome, "Informe seu nome completo usando letras (de 2 a 100 caracteres).");
        } else {
            nome.classList.add("valido");
        }

        const emailValor = email.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValor) || emailValor.length > 60) {
            marcarErro(email, "Informe um endereço de e-mail válido.");
        } else {
            email.classList.add("valido");
        }

        if (senha.value.length < 8) {
            marcarErro(senha, "A senha precisa ter pelo menos 8 caracteres.");
        } else {
            senha.classList.add("valido");
        }

        if (confirmar.value === "" || senha.value !== confirmar.value) {
            marcarErro(confirmar, "A confirmação precisa ser igual à senha.");
        } else {
            confirmar.classList.add("valido");
        }

        // CPF é opcional. Se preenchido, confere o formato;
        // a validação completa do CPF continua sendo feita no PHP.
        if (cpf.value.trim() !== "" &&
            !/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11})$/.test(cpf.value.trim())) {
            marcarErro(cpf, "Confira o formato do CPF.");
        }

        // Telefones são opcionais; se preenchidos, precisam ter 10 a 13 dígitos.
        [celular, fixo].forEach(function (telefone) {
            const numeros = telefone.value.replace(/\D/g, "");
            if (numeros !== "" && (numeros.length < 10 || numeros.length > 13)) {
                marcarErro(telefone, "Confira o telefone informado: use DDD e número.");
            }
        });

        if (erros.length > 0) {
            event.preventDefault();
            modalList.innerHTML = erros.map(function (erro) {
                const item = document.createElement("li");
                item.textContent = erro;
                return item.outerHTML;
            }).join("");

            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            modalFechar?.focus();
        }
        // Se não houver erros, o navegador envia normalmente os dados
        // para ../Controladores/cadastro_Control.php.
    });
});
