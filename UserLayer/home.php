<?php
session_start();
require_once '../autenticacao.php';
verifica_aluno(); // Garante que só usuários logados acessem

?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rcl Home</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">
    <div class="backgound-header-back"></div>
    <header class="background-header mobile-content">
        <div class="nav-bar-header">
            <div class="logo rcl white">RCL</div>
            <div class="nav-item cart img-nav">
                <a href="carrinho.html" class="icon-busca"><img src="icons/carrinho-cheio.png" alt=""></a>
            </div>
        </div>
    </header>

    <main class="mobile-content align-center background-home">
        <section class="self-service-preview">
            <div class="text">
                <p class="kanit-regular">Self-service / Almoço</p>
                <span class="bold kanit-regular">R$ 39,99/Kg</span>
            </div>

            <div class="self-service-inputs">
                <div class="input-self-service">
                    <label for="peso" class="kanit-regular">Peso:</label>
                    <input id="inputSSPreview" type="number" placeholder="Coloque o quanto costuma comer em Gramas">
                </div>
                <div class="result-self-service">
                    <label for="result" class="kanit-regular">Preço do almoço:</label>
                    <div class="result-content"><p id="resultSSPreview">R$ 00,00</p></div>
                </div>
            </div>

        </section>
        <section class="categories">
            <p class="kanit-regular">Categorias</p>
            <div class="caregory-card-content">
                <div class="box">
                    <div class="category-card salgado">
                        <span class="white kanit-regular">Salgados</span>
                        <div class="image-category"><img src="img/Salgados.png" alt=""></div>
                    </div>
                    <div class="category-card lanche ">
                        <span class="white kanit-regular">Lanches</span>
                        <div class="image-category"><img src="img/Lanches.png" alt=""></div>
                    </div>
                </div>
                <div class="box">
                    <div class="category-card bebida">
                        <span class="white kanit-regular">Bebidas</span>
                        <div class="image-category"><img src="img/Bebidas.png" alt=""></div>
                    </div>
                    <div class="category-card doce">
                        <span class="white kanit-regular">Doce</span>
                        <div class="image-category"><img src="img/Docees.png" alt=""></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer permanente como navBar -->
    <footer class="mobile-content kanit-regular">
        <div class="nav-bar">
            <div class="nav-item">
                <a href="home.php"><img src="icons/home.png" alt=""></a>
                <a href="home.php">Home</a>
            </div>
            <div class="nav-item">
                <a href="busca.php" class="icon-busca"><img src="icons/procurar.png" alt=""></a>
                <a href="busca.php">Busca</a>
            </div>
            <div class="nav-item">
                <a href="pedidos.php" class="icon-pedidos"><img src="icons/pedidos.png" alt=""></a>
                <a href="pedidos.php">Pedidos</a>
            </div>
            <div class="nav-item">
                <a class="icon-perfil" href="perfil.php"><img src="icons/perfil.png" alt=""></a>
                <a href="perfil.php">Perfil</a>
            </div>
        </div>
    </footer> 

    <script src="script.js"></script>
</body>
</html>