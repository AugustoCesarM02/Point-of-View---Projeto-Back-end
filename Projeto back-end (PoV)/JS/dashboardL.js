/* Point of View - Painel administrativo (Log) */

(() => {
    'use strict';

    /* Mesmas chaves do JSDashboardU.js: o tema e a fonte valem nas duas páginas. */
    const CHAVE_TEMA = 'modoEscuro';
    const CHAVE_FONTE = 'tamanhoFonte';
    const CHAVE_AUTO = 'logAtualizarSozinho';
    const INTERVALO = 30000; // 30 segundos

    const guardar = (chave, valor) => {
        try { localStorage.setItem(chave, valor); } catch (e) { /* ignora */ }
    };
    const ler = (chave) => {
        try { return localStorage.getItem(chave); } catch (e) { return null; }
    };


    /* ---------- Modo escuro ---------- */
    const botaoTema = document.getElementById('toggle-dark');
    let escuro = ler(CHAVE_TEMA) === '1';

    const aplicarTema = () => {
        document.body.classList.toggle('dark-mode', escuro);
        botaoTema.setAttribute('aria-pressed', String(escuro));
    };

    aplicarTema();
    botaoTema.addEventListener('click', () => {
        escuro = !escuro;
        aplicarTema();
        guardar(CHAVE_TEMA, escuro ? '1' : '0');
    });


    /* ---------- Tamanho da fonte ---------- */
    const MIN = 80, MAX = 140, PASSO = 10;
    let escala = Number(ler(CHAVE_FONTE)) || 100;

    const aplicarFonte = () => {
        escala = Math.min(MAX, Math.max(MIN, escala));
        document.documentElement.style.fontSize = escala + '%';
        guardar(CHAVE_FONTE, String(escala));
    };

    aplicarFonte();
    document.getElementById('font-inc').addEventListener('click', () => { escala += PASSO; aplicarFonte(); });
    document.getElementById('font-dec').addEventListener('click', () => { escala -= PASSO; aplicarFonte(); });


    /* ---------- Filtros (o servidor filtra e pagina) ---------- */
    const formFiltros = document.getElementById('form-filtros');
    const campoBusca = document.getElementById('busca');

    formFiltros.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => formFiltros.submit());
    });

    // Busca enquanto digita (espera 450 ms de pausa) e devolve o foco ao campo
    let espera;
    campoBusca.addEventListener('input', () => {
        clearTimeout(espera);
        espera = setTimeout(() => formFiltros.submit(), 450);
    });

    if (new URLSearchParams(location.search).has('busca') && campoBusca.value) {
        campoBusca.focus();
        campoBusca.setSelectionRange(campoBusca.value.length, campoBusca.value.length);
    }


    /* ---------- Atualizar sozinho (recarrega mantendo os filtros) ---------- */
    const alternar = document.getElementById('auto-atualizar');
    let relogio = null;

    const desligar = () => {
        if (relogio) clearInterval(relogio);
        relogio = null;
    };

    const ligar = () => {
        desligar();
        relogio = setInterval(() => {
            // Não recarrega com a aba escondida nem enquanto a pessoa digita
            if (document.hidden || document.activeElement === campoBusca) return;
            location.reload();
        }, INTERVALO);
    };

    alternar.checked = ler(CHAVE_AUTO) === '1';
    if (alternar.checked) ligar();

    alternar.addEventListener('change', () => {
        guardar(CHAVE_AUTO, alternar.checked ? '1' : '0');
        if (alternar.checked) ligar(); else desligar();
    });


    /* ---------- Descrições longas: clique para abrir e fechar ---------- */
    const dica = (el) => {
        el.title = el.classList.contains('aberto') ? 'Clique para recolher' : 'Clique para ver tudo';
    };

    const marcarExpansiveis = () => {
        document.querySelectorAll('.descricao-log').forEach((el) => {
            if (el.classList.contains('aberto') || el.scrollHeight > el.clientHeight + 1) {
                el.classList.add('expansivel');
                dica(el);
            } else {
                el.classList.remove('expansivel');
                el.removeAttribute('title');
            }
        });
    };

    document.addEventListener('click', (evento) => {
        const el = evento.target.closest('.descricao-log.expansivel');
        if (!el) return;
        el.classList.toggle('aberto');
        dica(el);
    });

    marcarExpansiveis();
    window.addEventListener('resize', marcarExpansiveis);


    /* ---------- Números da faixa: contagem ao abrir a página ---------- */
    const reduzir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduzir) {
        document.querySelectorAll('[data-contar]').forEach((el) => {
            const alvo = Number(el.dataset.contar);
            if (!alvo) return;

            const duracao = 900;
            const inicio = performance.now();

            const passo = (agora) => {
                const t = Math.min(1, (agora - inicio) / duracao);
                const suave = 1 - Math.pow(1 - t, 3);
                el.textContent = Math.round(alvo * suave).toLocaleString('pt-BR');
                if (t < 1) requestAnimationFrame(passo);
            };

            el.textContent = '0';
            requestAnimationFrame(passo);
        });
    }
})();
