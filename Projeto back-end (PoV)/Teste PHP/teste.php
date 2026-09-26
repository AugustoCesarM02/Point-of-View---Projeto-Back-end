<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="teste.css">
    <?php   require_once 'Servidor.php';?>
</head>
<body>
    <header>
        <div class="header">
            <a href="http://localhost/Teste%20PHP/teste.php" class="pov">Point of View</a>
            <div class="buttons">
                <button>menu</button>
                <a href="http://localhost/Teste%20PHP/Login.php" class="botão-login">Login</a>
            </div>
        </div>
        <hr class="linha-header">
    </header>
    <main>
        <div class="carrousel">
            <img src="img/1.jpg" alt="imgcarrousel">
        </div>
        <div class="cards">
            <div class="card">
                <img src="img/1.jpg" alt="imgcard">
            </div>
            <p>
                blabalblallbalblablablalbabllabal
            </p>
        </div>
        <div class="cards">
            <p>
                blabalblallbalblablablalbabllabal
            </p>
            <div class="card">
                <img src="img/1.jpg" alt="imgcard">
            </div>
        </div>
        <div class="cards">
            <div class="card">
                <img src="img/1.jpg" alt="imgcard">
            </div>
            <p>
                blabalblallbalblablablalbabllabal
            </p>
        </div>
        <div class="cards">
            <p>
                blabalblallbalblablablalbabllabal
            </p>
            <div class="card">
                <img src="img/1.jpg" alt="imgcard">
            </div>
        </div>
    </main>
    <footer>
        <hr class="linha-header">
        <div class="footer">
            <lu class="footer-links">
                <li><a href="#">teste</a></li>
                <li><a href="#">teste</a></li>
                <li><a href="#">teste</a></li>
            </lu>
            <div class="footer-text">
                <p>PoV</p>
                <img src="img/1.jpg" alt="imgfooter">
            </div>
            <div class="footer-social">
                <a href="#">teste</a>
                <a href="#">teste</a>
                <a href="#">teste</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>teste</p>
        </div>
    </footer>
</body>
</html>