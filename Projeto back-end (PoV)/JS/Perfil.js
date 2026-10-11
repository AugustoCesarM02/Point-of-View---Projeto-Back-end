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

});