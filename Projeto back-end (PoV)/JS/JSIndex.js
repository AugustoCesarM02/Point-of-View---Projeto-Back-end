document.addEventListener("DOMContentLoaded", function () {
const toggle = document.getElementById("toggle-dark");
const iconSun = toggle.querySelector(".sun");
const iconMoon = toggle.querySelector(".moon");
const logo = document.getElementById("img_logo");

//Função de tema
function applyDark(isDark) {
    document.body.classList.toggle("dark-mode", !!isDark);
    iconSun.style.display = isDark ? "none" : "inline-block";
    iconMoon.style.display = isDark ? "inline-block" : "none";
    toggle.setAttribute("aria-pressed", isDark ? "true" : "false");

    // Muda a logo conforme o tema
    const logo = document.getElementById("img_logo");
    if (logo) {
        logo.src = isDark ? "img/Logo_branca.jpg" : "img/Logo_preta.jpg";
    }
}

const savedTheme = localStorage.getItem("infomark-dark");
applyDark(savedTheme === "1");
toggle.addEventListener("click", function () {
    const nextDark = !document.body.classList.contains("dark-mode");
    applyDark(nextDark);
    localStorage.setItem("infomark-dark", nextDark ? "1" : "0");
});

//Controle de tema
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

//Carrinho hover
function mudarCarrinho(imagem) {
    document.getElementById("img-carrinho").src = imagem;
}

//Logo hover 
function mudarLogo(hover) {
    const logo = document.getElementById("img_logo");
    const isDark = document.body.classList.contains("dark-mode");

    if (hover) {
        // Quando passar o mouse
        if (isDark) {
            logo.src = "img/Logo_branca_colorida.jpg"; // Logo clara em hover
        } else {
            logo.src = "img/Logo_preta_colorida.jpg";  // Logo escura em hover
        }
    } else {
        // Quando tirar o mouse
        if (isDark) {
            logo.src = "img/Logo_branca.jpg";
        } else {
            logo.src = "img/Logo_preta.jpg";
        }
    }
}
