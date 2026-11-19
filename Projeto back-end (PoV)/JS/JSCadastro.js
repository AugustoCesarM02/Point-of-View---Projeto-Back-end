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
    // REGRAS DE VALIDAÇÃO
    // São as mesmas do PHP (Conectivos/validacao_cadastro.php). O JS dá o aviso
    // rápido; quem decide de verdade é sempre o PHP.
    // Só nome, e-mail e senha são obrigatórios: o resto é opcional.
    // -----------------------------------------------------
    const URL_CADASTRO = "../Controladores/cadastro_Control.php";
    const URL_VERIFICAR_EMAIL = "../Controladores/verificar_email.php";
    const FOTO_PADRAO = document.getElementById("fotoPreview")?.getAttribute("src") || "";
    const LADO_FOTO_CADASTRO = 1024;

    const LETRAS = "A-Za-zÀ-ÖØ-öø-ÿ";
    const regexNome = new RegExp("^[" + LETRAS + "'-]+(?: [" + LETRAS + "'-]+)*$");
    const regexLetra = new RegExp("[" + LETRAS + "]", "g");
    const regexEmail = /^[A-Za-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$/;

    const MENSAGENS = {
        nome_curto: "O nome precisa ter pelo menos 3 letras.",
        nome_invalido: "O nome só pode ter letras, espaços, hífen e apóstrofo (sem números ou símbolos).",
        nome_longo: "O nome pode ter no máximo 100 caracteres.",
        email_vazio: "Informe seu e-mail.",
        email_sem_arroba: "O e-mail precisa ter o símbolo \"@\" (exemplo: nome@email.com).",
        email_sem_com: "O e-mail precisa terminar em \".com\" (exemplo: nome@email.com).",
        email_formato: "O formato do e-mail é inválido (exemplo: nome@email.com).",
        email_longo: "O e-mail pode ter no máximo 60 caracteres.",
        email_em_uso: "Este e-mail já está em uso. Use outro e-mail ou faça login.",
        senha_curta: "A senha precisa ter pelo menos 8 caracteres.",
        senha_confirmar: "A confirmação precisa ser igual à senha.",
        cpf: "CPF inválido.",
        cpf_duplicado: "Este CPF já está cadastrado.",
        data: "Data de nascimento inválida.",
        sexo: "Gênero inválido.",
        nome_materno: "O nome da mãe pode ter no máximo 50 caracteres.",
        endereco: "O endereço pode ter no máximo 150 caracteres.",
        telefone_celular: "Celular inválido (use DDD + número).",
        telefone_fixo: "Telefone fixo inválido (use DDD + número).",
        foto: "Não foi possível processar a foto de perfil. Tente outra imagem.",
        foto_tipo: "Formato de foto não permitido. Use JPG, PNG ou WebP.",
        foto_tamanho: "A foto de perfil é grande demais (máximo de 2 MB).",
        foto_ilegivel: "Não foi possível ler essa imagem. Escolha um arquivo JPG, PNG ou WebP.",
        envio_grande: "Os dados enviados são grandes demais. Tente uma foto menor.",
        conexao: "Não foi possível conectar ao servidor. Verifique sua internet e tente novamente.",
        erro_interno: "Não foi possível concluir o cadastro agora. Tente novamente em instantes."
    };

    // Nome: devolve o código do erro (ou null se estiver certo). Espaços repetidos viram um só.
    function normalizarNome(valor) {
        return valor.replace(/\s+/g, " ").trim();
    }

    function validarNome(nome) {
        if (nome !== "" && !regexNome.test(nome)) return "nome_invalido";
        // Conta só letras: espaço, hífen e apóstrofo não valem
        if (nome === "" || (nome.match(regexLetra) || []).length < 3) return "nome_curto";
        if (nome.length > 100) return "nome_longo";
        return null;
    }

    // E-mail: precisa ter "@", terminar em ".com" (ou ".com.br"...), ter formato válido e até 60 caracteres
    function validarEmail(email) {
        if (email === "") return "email_vazio";
        if (email.indexOf("@") === -1) return "email_sem_arroba";
        if (!/\.com(\.[a-z]{2})?$/i.test(email)) return "email_sem_com";

        const local = email.slice(0, email.lastIndexOf("@"));
        if (!regexEmail.test(email) || local.indexOf("..") !== -1 ||
            local.charAt(0) === "." || local.charAt(local.length - 1) === ".") {
            return "email_formato";
        }
        if (email.length > 60) return "email_longo";
        return null;
    }

    // -----------------------------------------------------
    // MARCAÇÃO DOS CAMPOS COM ERRO
    // -----------------------------------------------------
    const fotoArea = document.getElementById("fotoArea");

    function elementoDoCampo(id) {
        return id === "foto" ? fotoArea : document.getElementById(id);
    }

    function marcarCampo(id, estado) {
        const el = elementoDoCampo(id);
        if (!el) return;

        el.classList.remove("erro", "valido");
        if (estado) el.classList.add(estado);

        const caixa = el.closest(".campo");
        if (caixa) {
            caixa.classList.remove("campo--erro", "campo--ok");
            if (estado) caixa.classList.add(estado === "erro" ? "campo--erro" : "campo--ok");
        }
        if (el === fotoArea) {
            el.classList.toggle("foto-cadastro--erro", estado === "erro");
        }
    }

    function limparMarcas() {
        form.querySelectorAll("input, select").forEach(function (campo) {
            if (campo.id) marcarCampo(campo.id, null);
        });
        marcarCampo("foto", null);
    }

    // -----------------------------------------------------
    // JANELA (POP-UP) COM A LISTA DO QUE DEU ERRADO
    // -----------------------------------------------------
    const modal = document.getElementById("modalErros");
    const modalList = document.getElementById("modalErrosList");
    const modalFechar = document.getElementById("modalFechar");
    const modalOverlay = document.getElementById("modalOverlay");
    let focoAntesDoModal = null;

    function mostrarErros(erros) {
        // erros = [{ campo: "id-do-campo", mensagem: "texto" }]
        modalList.replaceChildren();
        erros.forEach(function (erro) {
            const item = document.createElement("li");
            item.textContent = erro.mensagem;
            modalList.appendChild(item);
            if (erro.campo) marcarCampo(erro.campo, "erro");
        });

        focoAntesDoModal = document.activeElement;
        modal.classList.add("open");
        modal.setAttribute("aria-hidden", "false");
        modalFechar.focus();
    }

    function fecharModal() {
        if (!modal.classList.contains("open")) return;
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");

        // Leva a pessoa direto para o primeiro campo a corrigir
        const primeiro = form.querySelector("input.erro, select.erro");
        const destino = primeiro || focoAntesDoModal;
        if (destino && typeof destino.focus === "function") destino.focus();
    }

    modalFechar.addEventListener("click", fecharModal);
    modalOverlay.addEventListener("click", fecharModal);

    document.addEventListener("keydown", function (evento) {
        if (!modal.classList.contains("open")) return;
        if (evento.key === "Escape") {
            fecharModal();
        } else if (evento.key === "Tab") {
            evento.preventDefault(); // o foco fica preso no botão enquanto a janela está aberta
            modalFechar.focus();
        }
    });

    // -----------------------------------------------------
    // E-MAIL EM USO: AVISO ENQUANTO A PESSOA DIGITA
    // Pergunta ao PHP (que consulta o banco) se o e-mail já existe.
    // -----------------------------------------------------
    const campoEmail = document.getElementById("email");
    const avisoEmail = document.getElementById("avisoEmail");
    const emailsConsultados = new Map(); // e-mail (minúsculo) -> true se já está em uso
    let tempoEmail = null;
    let numeroConsultaEmail = 0;

    function mostrarAvisoEmail(texto, tipo) {
        // tipo: "erro", "ok", "neutro" ou null (esconde o aviso)
        if (!texto) {
            avisoEmail.hidden = true;
            avisoEmail.textContent = "";
            avisoEmail.className = "campo-aviso";
            return;
        }
        avisoEmail.hidden = false;
        avisoEmail.textContent = texto;
        avisoEmail.className = "campo-aviso campo-aviso--" + tipo;
    }

    // Devolve true (em uso), false (livre) ou null (não deu para verificar)
    async function emailEmUso(valor) {
        const chave = valor.toLowerCase();
        if (emailsConsultados.has(chave)) return emailsConsultados.get(chave);

        try {
            const dados = new FormData();
            dados.append("email", valor);

            const resposta = await fetch(URL_VERIFICAR_EMAIL, {
                method: "POST",
                body: dados,
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            const json = await resposta.json();

            if (!resposta.ok || json.erro || json.valido !== true) return null;

            emailsConsultados.set(chave, !json.disponivel);
            return !json.disponivel;
        } catch (erro) {
            return null; // sem rede ou resposta inesperada: o PHP confere de novo ao enviar
        }
    }

    // Mostra o resultado da verificação no campo. Com mostrarFormato = true, também avisa e-mails mal escritos
    async function verificarEmailNaTela(mostrarFormato) {
        const valor = campoEmail.value.trim();
        const consulta = ++numeroConsultaEmail;

        if (valor === "") {
            mostrarAvisoEmail(null);
            marcarCampo("email", null);
            return;
        }

        const codigo = validarEmail(valor);
        if (codigo) {
            if (mostrarFormato) {
                mostrarAvisoEmail(MENSAGENS[codigo], "erro");
                marcarCampo("email", "erro");
            } else {
                mostrarAvisoEmail(null);
            }
            return;
        }

        if (!emailsConsultados.has(valor.toLowerCase())) {
            mostrarAvisoEmail("Verificando e-mail…", "neutro");
        }

        const emUso = await emailEmUso(valor);

        // A pessoa continuou digitando enquanto a resposta vinha: ignora a resposta antiga
        if (consulta !== numeroConsultaEmail) return;

        if (emUso === true) {
            mostrarAvisoEmail(MENSAGENS.email_em_uso, "erro");
            marcarCampo("email", "erro");
        } else if (emUso === false) {
            mostrarAvisoEmail("E-mail disponível.", "ok");
            marcarCampo("email", "valido");
        } else {
            mostrarAvisoEmail(null);
            marcarCampo("email", null);
        }
    }

    campoEmail.addEventListener("input", function () {
        clearTimeout(tempoEmail);
        marcarCampo("email", null);
        mostrarAvisoEmail(null);
        tempoEmail = setTimeout(function () { verificarEmailNaTela(false); }, 600);
    });

    campoEmail.addEventListener("blur", function () {
        clearTimeout(tempoEmail);
        verificarEmailNaTela(true);
    });

    // -----------------------------------------------------
    // FOTO DE PERFIL (opcional) - mesma ideia do Perfil.php:
    // clique ou arraste; a foto é reduzida no navegador antes de enviar.
    // -----------------------------------------------------
    const fotoInput = document.getElementById("fotoInput");
    const fotoPreview = document.getElementById("fotoPreview");
    const fotoDica = document.getElementById("fotoDica");
    const fotoBotaoTexto = document.getElementById("fotoBotaoTexto");
    const fotoRemover = document.getElementById("fotoRemover");
    const DICA_PADRAO = fotoDica.textContent;

    let fotoBlob = null;   // foto já reduzida, pronta para enviar
    let fotoUrl = null;    // endereço temporário da prévia

    // Reduz a foto (lado máximo) e converte para JPEG
    function reduzirFoto(arquivo, ladoMaximo) {
        return new Promise(function (resolve, reject) {
            const endereco = URL.createObjectURL(arquivo);
            const imagem = new Image();

            imagem.onload = function () {
                const largura = imagem.naturalWidth;
                const altura = imagem.naturalHeight;
                const fator = Math.min(1, ladoMaximo / Math.max(largura, altura));

                const canvas = document.createElement("canvas");
                canvas.width = Math.max(1, Math.round(largura * fator));
                canvas.height = Math.max(1, Math.round(altura * fator));

                const ctx = canvas.getContext("2d");
                // Fundo branco: PNG com transparência não vira preto no JPEG
                ctx.fillStyle = "#fff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(imagem, 0, 0, canvas.width, canvas.height);

                URL.revokeObjectURL(endereco);

                canvas.toBlob(function (blob) {
                    blob ? resolve(blob) : reject(new Error("Falha ao converter a foto"));
                }, "image/jpeg", 0.86);
            };

            imagem.onerror = function () {
                URL.revokeObjectURL(endereco);
                reject(new Error("Formato não suportado"));
            };

            imagem.src = endereco;
        });
    }

    function removerFoto() {
        if (fotoUrl) URL.revokeObjectURL(fotoUrl);
        fotoUrl = null;
        fotoBlob = null;
        fotoInput.value = "";
        fotoPreview.src = FOTO_PADRAO;
        fotoArea.classList.remove("tem-foto", "arrastando");
        marcarCampo("foto", null);
        fotoDica.textContent = DICA_PADRAO;
        fotoBotaoTexto.textContent = "Escolher foto";
        fotoRemover.hidden = true;
    }

    async function escolherFoto(lista) {
        const arquivo = Array.prototype.find.call(lista, function (item) {
            return /^image\//.test(item.type);
        });

        if (!arquivo) {
            mostrarErros([{ campo: "foto", mensagem: MENSAGENS.foto_tipo }]);
            return;
        }

        const dicaAnterior = fotoDica.textContent;
        fotoDica.textContent = "Preparando a foto…";

        try {
            const blob = await reduzirFoto(arquivo, LADO_FOTO_CADASTRO);

            if (fotoUrl) URL.revokeObjectURL(fotoUrl);
            fotoBlob = blob;
            fotoUrl = URL.createObjectURL(blob);

            fotoPreview.src = fotoUrl;
            fotoArea.classList.add("tem-foto");
            marcarCampo("foto", null);
            fotoDica.textContent = arquivo.name;
            fotoBotaoTexto.textContent = "Trocar foto";
            fotoRemover.hidden = false;
        } catch (erro) {
            fotoDica.textContent = dicaAnterior; // mantém a foto que já estava escolhida (se havia)
            fotoInput.value = "";
            mostrarErros([{ campo: "foto", mensagem: MENSAGENS.foto_ilegivel }]);
        }
    }

    fotoInput.addEventListener("change", function (evento) {
        if (evento.target.files.length) escolherFoto(evento.target.files);
    });

    // Teclado: Enter ou espaço abrem o seletor (o input fica escondido)
    fotoArea.addEventListener("keydown", function (evento) {
        if (evento.key === "Enter" || evento.key === " ") {
            evento.preventDefault();
            fotoInput.click();
        }
    });

    // Arrastar e soltar
    ["dragenter", "dragover"].forEach(function (nome) {
        fotoArea.addEventListener(nome, function (evento) {
            evento.preventDefault();
            fotoArea.classList.add("arrastando");
        });
    });

    ["dragleave", "drop"].forEach(function (nome) {
        fotoArea.addEventListener(nome, function (evento) {
            evento.preventDefault();
            fotoArea.classList.remove("arrastando");
        });
    });

    fotoArea.addEventListener("drop", function (evento) {
        escolherFoto(evento.dataTransfer.files);
    });

    fotoRemover.addEventListener("click", removerFoto);

    // -----------------------------------------------------
    // ENVIO DO FORMULÁRIO
    // 1) confere tudo no navegador (inclusive se o e-mail já existe no banco)
    // 2) envia ao PHP por fetch; se o PHP recusar, a lista de erros aparece no pop-up
    //    sem recarregar a página (a pessoa não perde o que digitou)
    // -----------------------------------------------------
    const botaoEnviar = document.getElementById("cadastrar");
    const textoBotao = botaoEnviar.innerHTML;
    let enviando = false;

    function ocuparBotao(ocupado, texto) {
        botaoEnviar.disabled = ocupado;
        botaoEnviar.classList.toggle("carregando", ocupado);
        botaoEnviar.innerHTML = ocupado ? texto : textoBotao;
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (enviando) return;

        const nome = document.getElementById("nome");
        const senha = document.getElementById("senha");
        const confirmar = document.getElementById("senha2");
        const cpf = document.getElementById("cpf");
        const celular = document.getElementById("celular");
        const fixo = document.getElementById("fixo");

        const erros = [];
        limparMarcas();
        clearTimeout(tempoEmail);

        function registrarErro(campo, codigo) {
            marcarCampo(campo.id, "erro");
            erros.push({ campo: campo.id, mensagem: MENSAGENS[codigo] });
        }

        // Nome (obrigatório)
        const nomeValor = normalizarNome(nome.value);
        const erroNome = validarNome(nomeValor);
        erroNome ? registrarErro(nome, erroNome) : marcarCampo("nome", "valido");

        // E-mail (obrigatório): formato aqui, "já em uso" logo abaixo
        const emailValor = campoEmail.value.trim();
        const erroEmail = validarEmail(emailValor);
        if (erroEmail) registrarErro(campoEmail, erroEmail);

        // Senha (obrigatória)
        senha.value.length < 8 ? registrarErro(senha, "senha_curta") : marcarCampo("senha", "valido");

        if (confirmar.value === "" || senha.value !== confirmar.value) {
            registrarErro(confirmar, "senha_confirmar");
        } else {
            marcarCampo("senha2", "valido");
        }

        // Opcionais: só conferem se tiverem sido preenchidos
        if (cpf.value.trim() !== "" &&
            !/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11})$/.test(cpf.value.trim())) {
            registrarErro(cpf, "cpf");
        }

        [[celular, "telefone_celular"], [fixo, "telefone_fixo"]].forEach(function (par) {
            const numeros = par[0].value.replace(/\D/g, "");
            if (numeros !== "" && (numeros.length < 10 || numeros.length > 13)) {
                registrarErro(par[0], par[1]);
            }
        });

        enviando = true;

        try {
            // E-mail com formato certo: pergunta ao banco (via PHP) se já está em uso
            if (!erroEmail) {
                ocuparBotao(true, "Verificando…");
                const emUso = await emailEmUso(emailValor);

                if (emUso === true) {
                    registrarErro(campoEmail, "email_em_uso");
                    mostrarAvisoEmail(MENSAGENS.email_em_uso, "erro");
                } else {
                    marcarCampo("email", "valido");
                }
            }

            if (erros.length > 0) {
                ocuparBotao(false);
                mostrarErros(erros);
                return;
            }

            // Tudo certo no navegador: envia ao PHP
            ocuparBotao(true, "Criando sua conta…");

            const dados = new FormData(form);
            dados.set("nome", nomeValor);
            dados.delete("foto"); // troca a foto original pela versão reduzida (se houver)
            if (fotoBlob) dados.append("foto", fotoBlob, "foto.jpg");

            let resposta;
            let json = null;

            try {
                resposta = await fetch(URL_CADASTRO, {
                    method: "POST",
                    body: dados,
                    credentials: "same-origin",
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                });
            } catch (erro) {
                ocuparBotao(false);
                mostrarErros([{ campo: "", mensagem: MENSAGENS.conexao }]);
                return;
            }

            try {
                json = await resposta.json();
            } catch (erro) {
                // Resposta que não é JSON (um erro do PHP, por exemplo)
            }

            if (json && json.ok === true && json.redirect) {
                window.location.href = json.redirect;
                return; // o botão continua ocupado até a página trocar
            }

            ocuparBotao(false);

            if (json && Array.isArray(json.erros) && json.erros.length) {
                // E-mail que o PHP achou em uso: guarda para avisar na hora da próxima tentativa
                json.erros.forEach(function (erro) {
                    if (erro.codigo === "email_em_uso") {
                        emailsConsultados.set(emailValor.toLowerCase(), true);
                        mostrarAvisoEmail(MENSAGENS.email_em_uso, "erro");
                    }
                });
                mostrarErros(json.erros);
            } else {
                mostrarErros([{ campo: "", mensagem: MENSAGENS.erro_interno }]);
            }
        } finally {
            enviando = false;
        }
    });

    // -----------------------------------------------------
    // SEM JAVASCRIPT NO ENVIO: o PHP volta com ?erro=codigo1,codigo2
    // (o envio normal do formulário continua funcionando como alternativa)
    // -----------------------------------------------------
    const codigosNaUrl = new URLSearchParams(window.location.search).get("erro");
    if (codigosNaUrl) {
        const lista = codigosNaUrl.split(",").filter(function (codigo) {
            return Object.prototype.hasOwnProperty.call(MENSAGENS, codigo);
        }).map(function (codigo) {
            return { campo: "", mensagem: MENSAGENS[codigo] };
        });

        mostrarErros(lista.length ? lista : [{ campo: "", mensagem: MENSAGENS.erro_interno }]);
        history.replaceState(null, "", window.location.pathname); // limpa a URL
    }
});
