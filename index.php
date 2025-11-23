<?php

$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCL - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-background">

<main class="container-login login-background mobile-content">

    <h1 class="rcl title-loginPage white">RCL</h1>

    <!-- MENU PRINCIPAL -->
    <div id="first-card-login" class="card-form screen">
        <!-- Mensagem de Sucesso -->
        <?php if ($msg): ?>
        <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>


        <a class="btn-login-dark white" href="login.php">Já tenho uma conta</a>
        <a class="btn-login-white btn-final-login" href="register.php">Não tenho conta ainda</a>
    </div>
</main>

<script src="script.js"></script>
</body>
</html>
