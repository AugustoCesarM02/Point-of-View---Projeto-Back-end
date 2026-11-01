/* Point of View - Painel administrativo (Usuários) */

(() => {
    'use strict';

    /* Se o JSIndex.js usa outras chaves no localStorage,
       troque aqui para o tema e a fonte valerem no site todo. */
    const CHAVE_TEMA = 'modoEscuro';
    const CHAVE_FONTE = 'tamanhoFonte';

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


    /* ---------- Busca e filtro (na própria página) ----------
       Quando passar a buscar pelo servidor, basta trocar por um
       formulário GET que leia $_GET['busca'] e $_GET['cargo']. */
    const campoBusca = document.getElementById('busca');
    const filtroCargo = document.getElementById('filtro-cargo');
    const linhas = [...document.querySelectorAll('#tabela-usuarios tbody tr[data-busca]')];
    const linhaVazia = document.getElementById('linha-vazia');
    const contagem = document.getElementById('contagem');

    const filtrar = () => {
        const termo = campoBusca.value.trim().toLowerCase();
        const cargo = filtroCargo.value;
        let visiveis = 0;

        linhas.forEach((linha) => {
            const combina = linha.dataset.busca.includes(termo) && (!cargo || linha.dataset.cargo === cargo);
            linha.hidden = !combina;
            if (combina) visiveis++;
        });

        if (linhas.length) linhaVazia.hidden = visiveis > 0;
        contagem.textContent = `Mostrando ${visiveis} de ${linhas.length} usuários`;
    };

    campoBusca.addEventListener('input', filtrar);
    filtroCargo.addEventListener('change', filtrar);


    /* ---------- Janela de confirmação ---------- */
    const modal = document.getElementById('modal-acao');
    const formModal = modal.querySelector('form');
    const campoId = document.getElementById('modal-id');
    const tituloModal = document.getElementById('modal-titulo');
    const textoModal = document.getElementById('modal-texto');
    const iconeModal = document.getElementById('modal-icone');
    const botaoConfirmar = document.getElementById('modal-confirmar');

    const acoes = {
        excluir: {
            titulo: 'Excluir usuário?',
            texto: (nome) => `${nome} será removido da plataforma. Essa ação não pode ser desfeita.`,
            botao: 'Excluir usuário',
            icone: 'bi-trash3'
        },
        promover: {
            titulo: 'Promover a administrador?',
            texto: (nome) => `${nome} passará a ter acesso a este painel e poderá gerenciar outros usuários.`,
            botao: 'Promover',
            icone: 'bi-arrow-up-circle'
        }
    };

    document.addEventListener('click', (evento) => {
        const botao = evento.target.closest('[data-acao]');
        if (!botao || botao.disabled) return;

        const config = acoes[botao.dataset.acao];
        if (!config) return;

        formModal.action = botao.dataset.url;
        campoId.value = botao.dataset.id;
        tituloModal.textContent = config.titulo;
        textoModal.textContent = config.texto(botao.dataset.nome);
        botaoConfirmar.textContent = config.botao;
        iconeModal.className = 'bi ' + config.icone;
        modal.className = 'modal modal--' + botao.dataset.acao;

        modal.showModal();
    });

    modal.querySelector('[data-fechar]').addEventListener('click', () => modal.close());
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) modal.close();   // clique fora da caixa
    });


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
