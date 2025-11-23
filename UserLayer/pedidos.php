<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rcl Home</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">
    <main class="mobile-content align-center background-pedidos">
        <!-- Mostra pedidos pendentes -->
        <section class="pedidos kanit-regular">
            <h1>Pendentes</h1>
            <div class="card-pedido">
                <div class="data-pedido"><p>Dia 12 de maio</p> <span class="horario">Para 9:30</span></div>
                <div class="itens-container">
                    <div class="item-pedido"><div class="item-quantidade">1</div><p>Hambúrguer</p></div>
                    <div class="item-pedido"><div class="item-quantidade">1</div><p>Coca-cola</p></div>
                </div>
                <div class="status-pedido"><p>Pedido: <span class="red">Preparando</span></p></div>
            </div>
        </section>
        
        <!-- Mostra pedidos já finalizados -->
        <section class="pedidos kanit-regular">
            <h1>Histórico</h1>
            <div class="history-list">
                <div class="card-pedido">
                    <div class="data-pedido"><p>Dia 11 de maio</p> <span class="horario">Para 9:30</span></div>
                    <div class="itens-container">
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Hambúrguer</p></div>
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Coca-cola</p></div>
                    </div>
                    <div class="status-pedido"><p>Pedido: <span class="green">Concluído</span></p></div>
                </div>
    
                <div class="card-pedido">
                    <div class="data-pedido"><p>Dia 11 de maio</p> <span class="horario">Para 9:30</span></div>
                    <div class="itens-container">
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Hambúrguer</p></div>
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Coca-cola</p></div>
                    </div>
                    <div class="status-pedido"><p>Pedido: <span class="green">Concluído</span></p></div>
                </div>
    
                <div class="card-pedido">
                    <div class="data-pedido"><p>Dia 11 de maio</p> <span class="horario">Para 9:30</span></div>
                    <div class="itens-container">
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Hambúrguer</p></div>
                        <div class="item-pedido"><div class="item-quantidade">1</div><p>Coca-cola</p></div>
                    </div>
                    <div class="status-pedido"><p>Pedido: <span class="green">Concluído</span></p></div>
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