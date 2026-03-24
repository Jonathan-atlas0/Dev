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
    <div class="caixa-video">
      <img src="./img/images_1.jpg" alt="dadqa" >
        <div class="mascara"></div>
    </div>
    <header class="header">
       <section>
            <a href="#"> 
            </a>
            <nav class="navbar">
                <a href="index.html">Home</a>
                <a href="menu.php">Menu ☕</a>
                <a href="#address" onclick ="mostrarModal()">Endereço</a>
             </nav> 

            <div class="icons"> 
                <img widht="30" hight="30" src="https://img.icons8.com/ios-glyphs/30/ffffff/shopping-cart--v1.png"
                alt="shopping-cart--v1"> 
            </div>  
       </section>   
    </header>
     </section>

     <section class="menu" id="menu">
            <h2 class="titulo">Nosso<span> MENU</span></h2>    
            <div class="menu-cardapio">
                 <div class="cardapio">
                    <img src="./img/menu-1.png" alt="item-1">
                    <h3> Capuccino </h3>
                    <div class="preço">R$ 15,99</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="Capuccino">
                     <input type="hidden" name="valor" value="15,99">
                     <input type="hidden" name="imagem" value="./img/menu-1.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
                                  <div class="cardapio">
                    <img src="./img/menu-2.png" alt="item-2">
                    <h3> Mocha </h3>
                    <div class="preço">R$ 12,59</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="Mocha">
                     <input type="hidden" name="valor" value="12,59">
                     <input type="hidden" name="imagem" value="./img/menu-2.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
                                  <div class="cardapio">
                    <img src="./img/menu-3.png" alt="item-3">
                    <h3> Macchiato</h3>
                    <div class="preço">R$ 16,99</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="Macchiato">
                     <input type="hidden" name="valor" value="16,99">
                     <input type="hidden" name="imagem" value="./img/menu-3.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
                                  <div class="cardapio">
                    <img src="./img/menu-4.png" alt="item-4">
                    <h3> Expresso </h3>
                    <div class="preço">R$ 10,00</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="café">
                     <input type="hidden" name="valor" value="10,00">
                     <input type="hidden" name="imagem" value="./img/menu-4.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
                                  <div class="cardapio">
                    <img src="./img/menu-5.png" alt="item-5">
                    <h3> Leite e caramelo </h3>
                    <div class="preço">R$ 14,99</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="café">
                     <input type="hidden" name="valor" value="14,99">
                     <input type="hidden" name="imagem" value="./img/menu-5.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
                                  <div class="cardapio">
                    <img src="./img/menu-6.png" alt="item-6">
                    <h3> Café preto</h3>
                    <div class="preço">R$ 18,99</div>
                   <form action="carrinho.php" method="post">
                     <input type="hidden" name="produto" value="café">
                     <input type="hidden" name="valor" value="18,99">
                     <input type="hidden" name="imagem" value="./img/menu-6.png">
                     <button type="submit" class="botao-link">Adiocinar ao carinho</button>
                   </form>
                 </div>
            </div>

     </section>
         

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
