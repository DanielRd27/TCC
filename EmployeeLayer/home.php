<?php
require_once '../autenticacao.php';
verifica_funcionario(); // Garante que só usuários logados acessem

?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel RCL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div>RCL - Olá, <?php echo $_SESSION['funcionario_nome']?></div>
        <a href="../logout.php">Sair da conta</a>
    </header>

    <?php if ($_SESSION['funcionario_cargo'] === 'Administrador'): ?>
        <div class="container">
            <div class="row">
                <a class="btn" href="gerenciar_intervalos.php">Gerenciar intervalos</a>
                <a class="btn" href="gerenciar_turmas.php">Gerenciar turmas</a>
            </div>
            <div class="row">
                <a class="btn" href="gerenciar_funcionario.php">Novo funcionário</a>
                <a class="btn" href="gerenciar_produtos.php">Gerenciar produtos</a>
            </div>
        </div>
    <?php endif; ?>


    <?php if ($_SESSION['funcionario_cargo'] !== 'Administrador'): ?>
        <div class="container">
            <a class="btn" href="gerenciar_pedidos.php">Pedidos</a>

            <div class="row">
                <a class="btn" href="gerenciar_estoque.php">Gestão de Estoque</a>
                <a class="btn" href="entrega_pedido.php">Retirada de pedidos</a>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>