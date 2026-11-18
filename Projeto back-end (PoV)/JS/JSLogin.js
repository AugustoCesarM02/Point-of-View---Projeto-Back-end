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

// Mostra mensagem quando o PHP volta com ?erro=1
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get("erro") === "1") {
        const msg = document.getElementById("msgErro");
        if (msg) {
            msg.textContent = "Email ou senha incorretos. Tente novamente.";
            msg.style.display = "block";
        }
    }
});
