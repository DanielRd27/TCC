<?php
session_start();
require_once '../autenticacao.php';
verifica_funcionario(); // Garante que só usuários logados acessem

?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel RCL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        header {
            width: 100%;
            padding: 1rem;
            font-size: 3rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
        }

        header a {
            color: red;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 10px;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 3rem;
        }

        .btn {
            width: 100%;
            height: 10rem;
            padding: 25px 0;
            border: 2px solid #d1d1d1;
            border-radius: 10px;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .row {
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
    </style>
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