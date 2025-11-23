<?php
require_once 'autenticacao.php';

// Se já estiver logado, redireciona para a página principal
if (isset($_SESSION['aluno_id'])) {
    header("Location: home.php");
    exit;
} elseif (isset($_SESSION['funcionario_id'])) {
    header("Location: EmployeeLayer/home.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === "login") {
        $login = trim($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        // Validação básica
        if (empty($login) || empty($senha)) {
            $erro = "Por favor, preencha todos os campos.";
        } else {
            if (autenticar($login, $senha)) {
                // Aguarda a função autenticar() definir as sessions
                if (isset($_SESSION['aluno_id'])) {
                    header("Location: UserLayer/home.php");
                    exit;
                } elseif (isset($_SESSION['funcionario_id'])) {
                    header("Location: EmployeeLayer/home.php");
                    exit;
                }
            } else {
                $erro = "Login ou senha incorretos.";
            }
        }
    }
}
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

    <!-- LOGIN -->
    <div id="card-login" class="card-login">
        <form method="POST" class="card-form form-login">
            <input type="hidden" name="acao" value="login">
            <div class="input-group">
                <a href="index.php" class="back-button" aria-label="Voltar">X</a>
                <input type="text" name="login" class="input-login white" placeholder="E-mail / Login" required>
                <input type="password" name="senha" class="input-login white" placeholder="Senha" required>
                <!-- Mensagem de Erro -->
                <?php if ($erro): ?>
                    <p class="erro-msg red"><?php echo $erro; ?></p>
                <?php endif; ?>
            </div>
            

            <!-- Botão de login -->
            <button type="submit" class="btn-login-dark white btn-final-login">Entrar</button>
        </form>
    </div>
</main>
</body>
</html>