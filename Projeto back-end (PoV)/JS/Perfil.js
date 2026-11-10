document.addEventListener("DOMContentLoaded", function () {

    // Seleciona os botões
    const botaoTema = document.getElementById("toggle-dark");
    const aumentarFonte = document.getElementById("font-inc");
    const diminuirFonte = document.getElementById("font-dec");

    // Recupera as preferências salvas
    let tamanhoFonte =
        Number(localStorage.getItem("perfil-font-size")) || 1;

    const temaSalvo = localStorage.getItem("perfil-dark-mode");

    // Guarda o tamanho original da fonte de cada elemento
    const elementos = document.querySelectorAll("body, body *");

    elementos.forEach(function (elemento) {
        elemento.dataset.tamanhoOriginal =
            parseFloat(getComputedStyle(elemento).fontSize);
    });

    // Aplica o tamanho da fonte
    function aplicarTamanhoFonte() {
        elementos.forEach(function (elemento) {
            const tamanhoOriginal =
                Number(elemento.dataset.tamanhoOriginal);

            elemento.style.fontSize =
                (tamanhoOriginal * tamanhoFonte) + "px";
        });
    }

    // Ativa ou desativa o modo escuro
    function aplicarTema(modoEscuro) {
        document.body.classList.toggle("dark-mode", modoEscuro);

        botaoTema.setAttribute(
            "aria-pressed",
            String(modoEscuro)
        );

        botaoTema.title = modoEscuro
            ? "Ativar modo claro"
            : "Ativar modo escuro";

        localStorage.setItem(
            "perfil-dark-mode",
            String(modoEscuro)
        );
    }

    // Restaura o tema salvo
    aplicarTema(temaSalvo === "true");

    // Restaura o tamanho da fonte salvo
    aplicarTamanhoFonte();

    // Alterna entre modo claro e escuro
    botaoTema.addEventListener("click", function () {
        const modoEscuro =
            !document.body.classList.contains("dark-mode");

        aplicarTema(modoEscuro);
    });

    // Aumenta a fonte
    aumentarFonte.addEventListener("click", function () {
        if (tamanhoFonte < 1.5) {
            tamanhoFonte = Math.min(
                tamanhoFonte + 0.1,
                1.5
            );

            aplicarTamanhoFonte();

            localStorage.setItem(
                "perfil-font-size",
                tamanhoFonte
            );
        }
    });

    // Diminui a fonte
    diminuirFonte.addEventListener("click", function () {
        if (tamanhoFonte > 0.7) {
            tamanhoFonte = Math.max(
                tamanhoFonte - 0.1,
                0.7
            );

            aplicarTamanhoFonte();

            localStorage.setItem(
                "perfil-font-size",
                tamanhoFonte
            );
        }
    });

    // Cabeçalho: some quando a página é rolada para cima
    const cabecalho = document.querySelector("header");
    let ultimaPosicao = window.scrollY;

    window.addEventListener("scroll", function () {
        const posicaoAtual = window.scrollY;

        // No topo da página o cabeçalho sempre aparece
        if (posicaoAtual <= cabecalho.offsetHeight) {
            cabecalho.classList.remove("header-oculto");
            ultimaPosicao = posicaoAtual;
            return;
        }

        const diferenca = posicaoAtual - ultimaPosicao;

        // Ignora rolagens muito pequenas
        if (Math.abs(diferenca) < 8) {
            return;
        }

        // Rolando para baixo esconde, rolando para cima mostra
        cabecalho.classList.toggle("header-oculto", diferenca > 0);

        ultimaPosicao = posicaoAtual;
    }, { passive: true });

    // Se o teclado focar em algo do cabeçalho, ele volta a aparecer
    cabecalho.addEventListener("focusin", function () {
        cabecalho.classList.remove("header-oculto");
    });

    // Compartilhar perfil
    const botaoCompartilhar = document.getElementById("perfil-compartilhar");
    const statusCompartilhar = document.getElementById("compartilhar-status");
    let tempoMensagem = null;

    // Mostra uma mensagem curta que some sozinha
    function mensagemCompartilhar(texto) {
        statusCompartilhar.textContent = texto;

        clearTimeout(tempoMensagem);
        tempoMensagem = setTimeout(function () {
            statusCompartilhar.textContent = "";
        }, 4000);
    }

    // Copia o texto (com alternativa para páginas sem HTTPS)
    function copiarTexto(texto) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(texto);
        }

        return new Promise(function (resolve, reject) {
            const campo = document.createElement("textarea");
            campo.value = texto;
            campo.setAttribute("readonly", "");
            campo.style.position = "fixed";
            campo.style.opacity = "0";
            document.body.appendChild(campo);
            campo.select();

            const copiou = document.execCommand("copy");
            document.body.removeChild(campo);

            if (copiou) {
                resolve();
            } else {
                reject(new Error("Falha ao copiar"));
            }
        });
    }

    botaoCompartilhar.addEventListener("click", async function () {
        const id = document.body.dataset.perfil || "";
        const link = location.origin + location.pathname + "?id=" + encodeURIComponent(id);

        // Celulares e navegadores com menu de compartilhamento
        if (navigator.share) {
            try {
                await navigator.share({
                    title: "Meu perfil no Point of View",
                    url: link
                });
                return;
            } catch (erro) {
                // Se a pessoa só fechou o menu, não faz mais nada
                if (erro.name === "AbortError") {
                    return;
                }
            }
        }

        // Alternativa: copiar o link do perfil
        try {
            await copiarTexto(link);
            mensagemCompartilhar("Link do perfil copiado!");
        } catch (erro) {
            mensagemCompartilhar("Não foi possível copiar. Link: " + link);
        }
    });

    //Fotos

    // Só o dono do perfil envia e exclui fotos; os visitantes só veem
    const ehDono = document.body.dataset.dono === "1";
    const tokenCsrf = document.body.dataset.csrf || "";

    // Seleciona os elementos da galeria (alguns só existem para o dono)
    const areaFotos = document.getElementById("fotos-area");
    const inputFotos = document.getElementById("fotos-input");
    const statusFotos = document.getElementById("fotos-status");
    const barraFotos = document.getElementById("fotos-barra");
    const totalFotos = document.getElementById("fotos-total");
    const vazioFotos = document.getElementById("fotos-vazio");
    const botaoLimpar = document.getElementById("fotos-limpar");
    const gradeFotos = document.getElementById("fotos-grade");

    const lightbox = document.getElementById("lightbox");
    const fotoAmpliada = document.getElementById("lightbox-foto");
    const contadorFotos = document.getElementById("lightbox-contador");
    const botaoFechar = document.getElementById("lightbox-fechar");
    const botaoAnterior = document.getElementById("lightbox-anterior");
    const botaoProximo = document.getElementById("lightbox-proximo");

    // Endereços do servidor (relativos à pasta View, onde fica o Perfil.php)
    const URL_ENVIAR_FOTO = "../Controladores/fotos_upload.php";
    const URL_EXCLUIR_FOTO = "../Controladores/fotos_excluir.php";
    const URL_SALVAR_BIOGRAFIA = "../Controladores/biografia_salvar.php";
    const LADO_MAXIMO = 1800;

    let fotos = [];        // [{ id, url }], da mais nova para a mais antiga
    let fotoAtual = -1;
    let ultimoFoco = null;

    // Fotos que o servidor entregou junto com a página
    try {
        fotos = JSON.parse(document.getElementById("dados-fotos").textContent) || [];
    } catch (erro) {
        fotos = [];
    }

    // Mostra uma mensagem para o dono do perfil
    function mostrarMensagem(texto, erro) {
        if (!statusFotos) {
            return;
        }

        statusFotos.textContent = texto || "";
        statusFotos.className = "fotos-status" + (erro ? " erro" : "");
    }

    // Envia um formulário ao servidor e devolve a resposta em JSON.
    // Se algo der errado, lança um erro com a mensagem para mostrar ao usuário.
    async function enviarAoServidor(url, dados) {
        dados.append("csrf", tokenCsrf);

        let resposta;

        try {
            resposta = await fetch(url, {
                method: "POST",
                body: dados,
                credentials: "same-origin"
            });
        } catch (erro) {
            throw new Error("Sem conexão com o servidor.");
        }

        let json = null;

        try {
            json = await resposta.json();
        } catch (erro) {
            // Resposta que não é JSON (um erro do PHP, por exemplo)
        }

        if (!resposta.ok || !json || json.erro) {
            throw new Error(
                (json && json.erro) ||
                "Erro ao falar com o servidor (" + resposta.status + ")."
            );
        }

        return json;
    }

    // Reduz o tamanho da foto antes de enviar (economiza internet e espaço)
    function reduzirFoto(arquivo) {
        return new Promise(function (resolve, reject) {
            const endereco = URL.createObjectURL(arquivo);
            const imagem = new Image();

            imagem.onload = function () {
                const largura = imagem.naturalWidth;
                const altura = imagem.naturalHeight;
                const fator = Math.min(1, LADO_MAXIMO / Math.max(largura, altura));

                const canvas = document.createElement("canvas");
                canvas.width = Math.round(largura * fator);
                canvas.height = Math.round(altura * fator);
                canvas.getContext("2d").drawImage(imagem, 0, 0, canvas.width, canvas.height);

                URL.revokeObjectURL(endereco);

                canvas.toBlob(function (blob) {
                    if (blob) {
                        resolve(blob);
                    } else {
                        reject(new Error("Falha ao converter a foto"));
                    }
                }, "image/jpeg", 0.86);
            };

            imagem.onerror = function () {
                URL.revokeObjectURL(endereco);
                reject(new Error("Formato não suportado"));
            };

            imagem.src = endereco;
        });
    }

    // Desenha a galeria na tela
    function desenharGaleria() {
        // Remove só as miniaturas; o botão + (do dono) fica no começo da grade
        gradeFotos.querySelectorAll(".fotos-celula").forEach(function (celula) {
            celula.remove();
        });

        vazioFotos.hidden = fotos.length > 0;
        barraFotos.hidden = fotos.length === 0;
        totalFotos.textContent = fotos.length === 1
            ? "1 foto"
            : fotos.length + " fotos";

        fotos.forEach(function (foto, indice) {
            const celula = document.createElement("div");
            celula.className = "fotos-celula";

            const miniatura = document.createElement("button");
            miniatura.type = "button";
            miniatura.className = "fotos-miniatura";
            miniatura.setAttribute("aria-label", "Abrir foto " + (indice + 1));

            const imagem = document.createElement("img");
            imagem.src = foto.url;
            imagem.alt = "Foto " + (indice + 1);
            imagem.loading = "lazy";
            miniatura.appendChild(imagem);

            miniatura.addEventListener("click", function () {
                abrirFoto(indice, miniatura);
            });

            celula.appendChild(miniatura);

            // O botão de excluir só aparece para o dono do perfil
            if (ehDono) {
                const excluir = document.createElement("button");
                excluir.type = "button";
                excluir.className = "fotos-excluir";
                excluir.textContent = "\u00d7";
                excluir.setAttribute("aria-label", "Excluir foto " + (indice + 1));

                excluir.addEventListener("click", function () {
                    excluirFoto(foto.id);
                });

                celula.appendChild(excluir);
            }

            gradeFotos.appendChild(celula);
        });
    }

    // Exclui uma foto
    function excluirFoto(id) {
        const dados = new FormData();
        dados.append("id", id);

        enviarAoServidor(URL_EXCLUIR_FOTO, dados).then(function () {
            fotos = fotos.filter(function (foto) {
                return foto.id !== id;
            });

            desenharGaleria();
            mostrarMensagem("Foto excluída.");
        }).catch(function (erro) {
            mostrarMensagem(erro.message || "Não foi possível excluir a foto.", true);
        });
    }

    // Adiciona as fotos escolhidas (uma de cada vez)
    async function adicionarFotos(lista) {
        const arquivos = Array.prototype.filter.call(lista, function (arquivo) {
            return /^image\//.test(arquivo.type);
        });

        if (!arquivos.length) {
            mostrarMensagem("Escolha arquivos de imagem (JPG, PNG, WebP…).", true);
            return;
        }

        let enviadas = 0;
        let falhas = 0;
        let ultimoMotivo = "";

        for (let i = 0; i < arquivos.length; i++) {
            mostrarMensagem("Enviando " + (i + 1) + " de " + arquivos.length + "…");

            try {
                const blob = await reduzirFoto(arquivos[i]);

                const dados = new FormData();
                dados.append("foto", blob, "foto.jpg");

                const resposta = await enviarAoServidor(URL_ENVIAR_FOTO, dados);

                fotos.unshift(resposta.foto);
                enviadas++;
            } catch (erro) {
                falhas++;
                ultimoMotivo = erro.message || "";
            }
        }

        desenharGaleria();

        if (falhas) {
            mostrarMensagem(
                enviadas + " enviada(s). " + falhas + " não puderam ser enviadas. " + ultimoMotivo,
                true
            );
        } else {
            mostrarMensagem(enviadas === 1 ? "1 foto enviada." : enviadas + " fotos enviadas.");
        }
    }

    // Escolher fotos pelo seletor de arquivos
    if (inputFotos) {
        inputFotos.addEventListener("change", function (evento) {
            adicionarFotos(evento.target.files);
            evento.target.value = "";
        });
    }

    // Arrastar e soltar fotos
    if (areaFotos) {
        ["dragenter", "dragover"].forEach(function (nome) {
            areaFotos.addEventListener(nome, function (evento) {
                evento.preventDefault();
                areaFotos.classList.add("arrastando");
            });
        });

        ["dragleave", "drop"].forEach(function (nome) {
            areaFotos.addEventListener(nome, function (evento) {
                evento.preventDefault();
                areaFotos.classList.remove("arrastando");
            });
        });

        areaFotos.addEventListener("drop", function (evento) {
            adicionarFotos(evento.dataTransfer.files);
        });
    }

    // Apagar todas as fotos
    if (botaoLimpar) {
        botaoLimpar.addEventListener("click", function () {
            if (!confirm("Apagar todas as fotos? Esta ação não pode ser desfeita.")) {
                return;
            }

            const dados = new FormData();
            dados.append("todas", "1");

            enviarAoServidor(URL_EXCLUIR_FOTO, dados).then(function () {
                fotos = [];

                desenharGaleria();
                mostrarMensagem("Todas as fotos foram apagadas.");
            }).catch(function (erro) {
                mostrarMensagem(erro.message || "Não foi possível apagar as fotos.", true);
            });
        });
    }

    // Foto ampliada (lightbox)
    function mostrarFotoAtual() {
        const foto = fotos[fotoAtual];

        if (!foto) {
            return;
        }

        fotoAmpliada.src = foto.url;
        fotoAmpliada.alt = "Foto " + (fotoAtual + 1);
        contadorFotos.textContent = (fotoAtual + 1) + " de " + fotos.length;
    }

    function abrirFoto(indice, botaoOrigem) {
        fotoAtual = indice;
        ultimoFoco = botaoOrigem || null;

        mostrarFotoAtual();
        lightbox.classList.add("aberto");
        botaoFechar.focus();
    }

    function fecharFoto() {
        lightbox.classList.remove("aberto");
        fotoAmpliada.removeAttribute("src");

        if (ultimoFoco && document.body.contains(ultimoFoco)) {
            ultimoFoco.focus();
        }
    }

    function irParaFoto(passo) {
        if (!fotos.length) {
            return;
        }

        fotoAtual = (fotoAtual + passo + fotos.length) % fotos.length;
        mostrarFotoAtual();
    }

    botaoFechar.addEventListener("click", fecharFoto);

    botaoAnterior.addEventListener("click", function () {
        irParaFoto(-1);
    });

    botaoProximo.addEventListener("click", function () {
        irParaFoto(1);
    });

    // Clicar fora da foto fecha o lightbox
    lightbox.addEventListener("click", function (evento) {
        if (evento.target === lightbox) {
            fecharFoto();
        }
    });

    // Teclado: Esc fecha, setas navegam
    document.addEventListener("keydown", function (evento) {
        if (!lightbox.classList.contains("aberto")) {
            return;
        }

        if (evento.key === "Escape") {
            fecharFoto();
        } else if (evento.key === "ArrowLeft") {
            irParaFoto(-1);
        } else if (evento.key === "ArrowRight") {
            irParaFoto(1);
        }
    });

    // Biografia (só o dono do perfil edita; salva sozinha)
    const campoBiografia = document.getElementById("biografia");
    const statusBiografia = document.getElementById("biografia-status");

    if (campoBiografia) {
        let ultimaSalva = campoBiografia.value.trim();
        let tempoBiografia = null;
        let tempoMensagemBio = null;

        const mostrarStatusBiografia = function (texto, erro) {
            statusBiografia.textContent = texto;
            statusBiografia.className = "biografia-status" + (erro ? " erro" : "");

            clearTimeout(tempoMensagemBio);

            if (!erro) {
                tempoMensagemBio = setTimeout(function () {
                    statusBiografia.textContent = "";
                }, 3000);
            }
        };

        const salvarBiografia = async function () {
            clearTimeout(tempoBiografia);

            const texto = campoBiografia.value.trim();

            // Nada mudou desde a última vez que foi salva
            if (texto === ultimaSalva) {
                return;
            }

            const dados = new FormData();
            dados.append("biografia", texto);

            try {
                await enviarAoServidor(URL_SALVAR_BIOGRAFIA, dados);
                ultimaSalva = texto;
                mostrarStatusBiografia("Biografia salva.", false);
            } catch (erro) {
                mostrarStatusBiografia(erro.message || "Não foi possível salvar a biografia.", true);
            }
        };

        // Salva um instante depois de a pessoa parar de digitar...
        campoBiografia.addEventListener("input", function () {
            clearTimeout(tempoBiografia);
            tempoBiografia = setTimeout(salvarBiografia, 1000);
        });

        // ...e na hora ao apertar Enter ou sair do campo
        campoBiografia.addEventListener("change", salvarBiografia);
    }

    // Mostra as fotos que vieram do servidor
    desenharGaleria();

});