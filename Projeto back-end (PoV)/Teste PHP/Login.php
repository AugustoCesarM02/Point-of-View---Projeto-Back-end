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
        </div>
    </header>
    <main>
        <form>
            <div class="login">
                <label>Usuário</label><br>
                <input type="text" id="login" name="login" placeholder="Digite seu Usuário"><br>
                <label>Senha</label><br>
                <input type="text" id="senha" name="senha" placeholder="digite sua senha">
            </div>
        </form>
    </main>
</body>
</html>