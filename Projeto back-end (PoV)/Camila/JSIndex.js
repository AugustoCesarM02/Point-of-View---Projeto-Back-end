const destaques = [

    // DESTAQUE 1
    {
        imagem: "Bergama. Turquia- Destaque 1.jpg",
        titulo: "Lindas imagens para<br>todos os momentos",
        descricao: "Explore uma coleção de fotografias e imagens selecionadas especialmente para você. Encontre inspiração, descubra novos estilos e compartilhe suas melhores descobertas.",
        link: "#"
    },

    // DESTAQUE 2
    {
        imagem: "Vietnam- Destaque 2.jpg",
        titulo: "Encontre novas<br>perspectivas",
        descricao: "Descubra fotografias únicas e diferentes pontos de vista para transformar a maneira como você enxerga cada momento.",
        link: "#"
    },

    // DESTAQUE 3
    {
        imagem: "Verona Itália- Destaque 3.jpg",
        titulo: "Inspire-se em novas<br>ideias",
        descricao: "Explore imagens selecionadas para trazer inspiração, criatividade e novas referências para seus projetos.",
        link: "#"
    },

    // DESTAQUE 4
    {
        imagem: "Capadócia- Destaque 4.jpg",
        titulo: "Descubra seu próximo<br>favorito",
        descricao: "Navegue pelo Point of View e encontre fotografias que combinam com seu estilo e sua forma de enxergar o mundo.",
        link: "#"
    }

];


let slideAtual = 0;


const imagemDestaque =
    document.getElementById("imagem-destaque");

const tituloDestaque =
    document.getElementById("titulo-destaque");

const descricaoDestaque =
    document.getElementById("descricao-destaque");

const botaoDestaque =
    document.getElementById("botao-destaque");

const abasDestaque =
    document.querySelectorAll(".aba-galeria");

const imagemContainer =
    document.querySelector(".imagem-galeria");

const botaoAnterior =
    document.getElementById("anterior");

const botaoProximo =
    document.getElementById("proximo");


function mostrarDestaque(numero) {

    slideAtual = numero;


    imagemContainer.classList.add("trocando");


    setTimeout(() => {

        imagemDestaque.src =
            destaques[slideAtual].imagem;

        tituloDestaque.innerHTML =
            destaques[slideAtual].titulo;

        descricaoDestaque.textContent =
            destaques[slideAtual].descricao;

        botaoDestaque.href =
            destaques[slideAtual].link;


        imagemContainer.classList.remove("trocando");

    }, 250);



    abasDestaque.forEach((aba, indice) => {

        aba.classList.toggle(
            "ativa",
            indice === slideAtual
        );

    });

}


function proximoDestaque() {

    slideAtual++;

    if (slideAtual >= destaques.length) {

        slideAtual = 0;

    }

    mostrarDestaque(slideAtual);
}


function anteriorDestaque() {

    slideAtual--;

    if (slideAtual < 0) {

        slideAtual = destaques.length - 1;

    }

    mostrarDestaque(slideAtual);
}


botaoProximo.addEventListener(
    "click",
    proximoDestaque
);


botaoAnterior.addEventListener(
    "click",
    anteriorDestaque
);


abasDestaque.forEach((aba, indice) => {

    aba.addEventListener("click", () => {

        mostrarDestaque(indice);

    });

});


const destaquesSemana = [

    {
        imagem: "img/destaque1.jpg",

        categoria: "FOTOGRAFIA",

        titulo: "Paisagens que<br>inspiram",

        descricao:
            "Uma das imagens mais vistas e curtidas pelos usuários do Point of View nesta semana.",

        visualizacoes: "2.548",

        curtidas: "1.245",

        pesquisas: "856",

        link: "#"
    },


    {
        imagem: "img/destaque2.jpg",

        categoria: "DECORAÇÃO",

        titulo: "Ambientes que<br>inspiram",

        descricao:
            "Uma das imagens de decoração mais pesquisadas da semana.",

        visualizacoes: "2.130",

        curtidas: "1.087",

        pesquisas: "924",

        link: "#"
    },


    {
        imagem: "img/destaque3.jpg",

        categoria: "MODA",

        titulo: "Seu estilo,<br>sua identidade",

        descricao:
            "Uma das fotografias de moda que mais chamou a atenção dos usuários esta semana.",

        visualizacoes: "1.987",

        curtidas: "963",

        pesquisas: "745",

        link: "#"
    },


    {
        imagem: "img/destaque4.jpg",

        categoria: "AESTHETIC",

        titulo: "Uma nova forma<br>de enxergar",

        descricao:
            "Imagem que conquistou espaço entre as mais populares do Point of View.",

        visualizacoes: "1.754",

        curtidas: "892",

        pesquisas: "631",

        link: "#"
    }

];


let destaqueSemanaAtual = 0;


const fotoSemana =
    document.getElementById("fotoSemana");

const categoriaSemana =
    document.getElementById("categoriaSemana");

const tituloSemana =
    document.getElementById("tituloSemana");

const descricaoSemana =
    document.getElementById("descricaoSemana");

const visualizacoesSemana =
    document.getElementById("visualizacoesSemana");

const curtidasSemana =
    document.getElementById("curtidasSemana");

const pesquisasSemana =
    document.getElementById("pesquisasSemana");

const botaoSemana =
    document.getElementById("botaoSemana");

const indicadoresSemana =
    document.querySelectorAll(".indicador-semana");


function mostrarDestaqueSemana(numero) {

    destaqueSemanaAtual = numero;

    const destaque =
        destaquesSemana[destaqueSemanaAtual];


    fotoSemana.style.opacity = "0";


    setTimeout(() => {

        fotoSemana.src =
            destaque.imagem;

        categoriaSemana.textContent =
            destaque.categoria;

        tituloSemana.innerHTML =
            destaque.titulo;

        descricaoSemana.textContent =
            destaque.descricao;

        visualizacoesSemana.textContent =
            destaque.visualizacoes;

        curtidasSemana.textContent =
            destaque.curtidas;

        pesquisasSemana.textContent =
            destaque.pesquisas;

        botaoSemana.href =
            destaque.link;

        fotoSemana.style.opacity = "1";

    }, 250);


    indicadoresSemana.forEach(
        (indicador, indice) => {

            indicador.classList.toggle(
                "ativo",
                indice === destaqueSemanaAtual
            );

        }
    );

}


indicadoresSemana.forEach(
    (indicador, indice) => {

        indicador.addEventListener(
            "click",
            () => {

                mostrarDestaqueSemana(indice);

            }
        );

    }
);


setInterval(() => {

    destaqueSemanaAtual++;

    if (
        destaqueSemanaAtual >=
        destaquesSemana.length
    ) {

        destaqueSemanaAtual = 0;

    }

    mostrarDestaqueSemana(
        destaqueSemanaAtual
    );

}, 6000);