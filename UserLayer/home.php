<?php
require_once '../autenticacao.php';
verifica_aluno(); // Garante que só usuários logados acessem


// Inicializa o carrinho se necessário
if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Determina o estado do carrinho
$carrinho_cheio = !empty($_SESSION['carrinho']);
$icone_carrinho = $carrinho_cheio ? 'carrinho-cheio.png' : 'carrinho-vazio.png';


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
                <a href="carrinho.php" class="icon-busca"><img src="icons/<?php echo $icone_carrinho?>" alt=""></a>
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
                    <a class="category-card salgado"
                        href="busca.php?acao=filtrar&filtro=Salgados">
                        <span class="white kanit-regular">Salgados</span>
                        <div class="image-category">
                            <img src="../img/Salgados.png" alt="">
                        </div>
                    </a>

                    <a class="category-card lanche"
                        href="busca.php?acao=filtrar&filtro=Lanches">
                        <span class="white kanit-regular">Lanches</span>
                        <div class="image-category">
                            <img src="../img/Lanches.png" alt="">
                        </div>
                    </a>
                </div>

                <div class="box">
                    <a class="category-card bebida"
                        href="busca.php?acao=filtrar&filtro=Bebidas">
                        <span class="white kanit-regular">Bebidas</span>
                        <div class="image-category">
                            <img src="../img/Bebidas.png" alt="">
                        </div>
                    </a>

                    <a class="category-card doce"
                        href="busca.php?acao=filtrar&filtro=Doces">
                        <span class="white kanit-regular">Doces</span>
                        <div class="image-category">
                            <img src="../img/Docees.png" alt="">
                        </div>
                    </a>
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