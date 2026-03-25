<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login-style.css">
  
    <title>Document</title>
</head>
<body style="background-image: url(img/cafe-login2.jpg);">
    <main class="recepiente">
        <?php
        include_once("Conexao.php");
        session_unset();
        session_destroy();  
        ?>
        <h1>Login Cafetéria</h1>
        
      <form action="Login1.php" method="post">
      <div class="input-box">
            <input placeholder="Nome" type="text" name="nome">
        </div>
        <div class="input-box">
            <input placeholder="Senha" type="password">
        </div>
      <div class="remember-forgot">
        <label>
           <input type="checkbox">
           Lembrar senha
        </label>
            <a href="index.html">Voltar ao menu</a>
      </div>
      <button type="submit" class="login">Login</button>
      <div class="register-link">
        <p>Não tem uma conta?<a href="#">Registre-se</a></p>
      </div>
      </form>
    </main>
</body>
</html>