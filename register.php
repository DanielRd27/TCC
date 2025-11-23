<?php
session_start();
require_once 'autenticacao.php';
require_once 'db.php';

// Buscar turmas
$pdo = conectar();
$sql_turmas = "SELECT id_turma, nome FROM Turmas ORDER BY nome_turma";
$turmas = $pdo->query($sql_turmas);


// Se já estiver logado, manda para principal


$erro = "";

// Verifica qual formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';

    // REGISTRO
    if ($acao === "register") {
        $nome = $_POST["nome"] ?? '';
        $email = $_POST["email"] ?? '';
        $turma = $_POST["turma_id"] ?? '';
        $senha = $_POST["senha"] ?? '';

        if ($nome && $email && $turma && $senha) {
            $pdo = conectar()
            $sql = "INSERT INTO alunos (nome, email, senha, telefone, created_at)
                    VALUES (?, ?, ?, '', NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->bind_param("sss", $nome, $email, $senha);

            if ($stmt->execute()) {
                $erro = "Cadastro realizado! Agora faça login.";
            } else {
                $erro = "Erro ao cadastrar. Email já existe?";
            }
        } else {
            $erro = "Preencha todos os campos do cadastro.";
        }
    }
}
?>

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

    <!-- Mensagem de Erro -->
    <?php if ($erro): ?>
        <p class="erro-msg"><?php echo $erro; ?></p>
    <?php endif; ?>

    <!-- REGISTRO -->
    <div id="card-register" class="card-register screen ">
        <form method="post" action="" class="card-form form-register">
            <div class="input-group">
                <a href="index.php" class="back-button" aria-label="Voltar">X</a>
                <input type="hidden" name="acao" value="register">
                <!-- Input Nome -->
                <input type="text" name="nome" class="input-register" placeholder="Nome" required>

                <!-- Input Senha -->
                <input type="password" name="senha" class="input-register" placeholder="Senha" required>

                <!-- Input Email -->
                <input type="text" name="email" class="input-register" placeholder="E-Mail" required>

                <!-- Input Turma -->
                <select name="turma_id" class="input-register" required>
                    <option value="">Selecione sua turma</option>
                    <?php while ($t = $turmas->fetch_assoc()): ?>
                        <option value="<?= $t['id_turma'] ?>">
                            <?= $t['nome'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Botão de Register -->
            <button type="submit" class="btn-login-white btn-final-login">Cadastrar-se</button>
        </form>
    </div>

</main>

<script src="script.js"></script>
</body>
</html>
