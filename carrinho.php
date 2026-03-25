<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>Cafezin</title>

</head>
<body>
   <?php 
    include_once("Conexao.php");
    $nome=$_SESSION['nome']; 
    $dados = mysqli_query($conn,"SELECT * FROM tabela WHERE Nome='$nome' ");
    
    ?>
    <div class="caixa-video">
        <div class="mascara"></div>
              <img src="./img/images_1.jpg" alt="dadqa" >
    </div>
    <header class="header">
       <section>
            <a>

            </a>
            <nav class="navbar">
                <a href="index.html">Home</a>
                <a href="menu.php">Menu</a>
                <a href="#address" onclick ="mostrarModal()">Endereço</a>
             </nav> 

            <div class="icons"> 
                <img widht="30" hight="30" src="./img/shopping-cart--v1.png"
                alt="shopping-cart--v1"> 
                 <a href="Login.php">
                <img src="./img/icone-login1.png"alt="shopping-cart--v1" style="width: 30px; height: 30px;"></a>
                
            </div>  
       </section>   
    </header>

      <section class="menu">
            <div>
            <h3 class="titulo">SEU <Span>CARRINHO </Span><?php echo $nome ?></h3>
            </div>
        </section>
 


    <section class="menu" id="menu">
            <div class="menu-cardapio">
                <?php if (mysqli_num_rows($dados) > 0) { ?>
                <?php  while ($tabela = mysqli_fetch_assoc($dados))  { ?>
                   <div class="cardapio">
                    <img src="<?php echo $tabela['Imagem']; ?>" alt="item">
                    <h3><?php echo $tabela['Produto']; ?></h3>
                    <div class="preço">R$ <?php echo number_format($tabela['Valor'], 2, ',', '.'); ?></div>
                </div>
                
            <?php } ?>
             <?php } else { ?>
                <div class="cardapio">
            <h2>Nenhum item no carrinho 😾☕</h2> 
             </div><?php } ?>
                  </div>
                  <?php if(mysqli_num_rows($dados) > 0 || $nome=='Admin'){ ?>
                  <form action="Finalizar.php">
                    <button type="submit" class="botao-link">Finalizar pedido</button>
                    </form>
                    <form action="Delete.php" method="post">
                        <button type="submit" class="botao-link">Apagar carrinho</button>
                    </form>
                    <?php } ?>
    </section>
<?php
    if (isset($_SESSION['mensagem'])) {
    echo "<script>alert('{$_SESSION['mensagem']}');</script>";
    unset($_SESSION['mensagem']);
    }
?>
     <div class="modal">
        <h3 class="titulo"><span>Nosso</span> Endereço</h3>
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d79504.29052471898!2d-34.93370605136719!3d-7.158351299999987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x7acc29aa9ce26af%3A0xaf72a28cd6276079!2sUNIP%C3%8A%20-%20Centro%20Universit%C3%A1rio%20-%20Campus%20Jo%C3%A3o%20Pessoa!5e1!3m2!1spt-BR!2sbr!4v1762444279959!5m2!1spt-BR!2sbr" 
            width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="mascara-modal" onclick="esconderModal()"></div>
    <script src="./scripts.js"></script>
    
</body>
</html>
