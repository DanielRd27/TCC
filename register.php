<?php
require_once 'autenticacao.php';
require_once 'db.php';

// Buscar turmas
$pdo = conectar();
$sql_turmas = "SELECT id_turma, nome_turma FROM Turmas ORDER BY nome_turma";
$turmas = $pdo->query($sql_turmas);


// Se já estiver logado, manda para principal


$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';

// Verifica qual formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';

    // REGISTRO
    if ($acao === "register") {
        $nome = $_POST["nome"] ?? '';
        $email = $_POST["email"] ?? '';
        $turma_id = $_POST["turma_id"] ?? '';
        $senha = $_POST["senha"] ?? '';
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Email inválido.";
            header("Location: register.php?erro=" . urlencode($erro));
            exit();
        }

        // ✅ ADICIONAR: Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id_aluno FROM alunos WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Este email já está cadastrado.";
            header("Location: register.php?erro=" . urlencode($erro));
            exit();
        }

        try {
            if ($nome && $email && $turma_id && $senha) {
                // Insert do aluno
                $pdo = conectar();              
                $stmt = $pdo->prepare("
                    INSERT INTO alunos (nome, email, senha, telefone)
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $nome, $email, $senha_hash, ''
                ]);

                // ID do aluno cadastrado
                $id_aluno_novo = $pdo->lastInsertId();

                // Insert da relação de aluno e turma
                $stmt = $pdo->prepare("
                    INSERT INTO alunos_turmas (id_turma, id_aluno)
                    VALUES (?, ?)");
                $stmt->execute([
                    $turma_id, $id_aluno_novo
                ]);

                $msg = "Cadastro realizado! Agora faça login.";

                header("Location: index.php?msg=" . urlencode($msg));
                exit(); 

            } else {
                $erro = "Preencha todos os campos do cadastro.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack(); // Linha 57 (agora mais segura)
            }

            // ... (Tratamento de erro e redirecionamento de falha)
            $erro = "Erro ao cadastrar: " . $e->getMessage();
            header("Location: register.php?erro=" . urlencode($erro));
            exit();
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

    <!-- REGISTRO -->
    <div id="card-register" class="card-register screen ">
        <form method="post" action="" class="card-form form-register">
            <div class="input-group">
                <a href="index.php" class="back-button" aria-label="Voltar">X</a>
                <input type="hidden" name="acao" value="register">
                <!-- Input Nome -->
                <input type="text" name="nome" class="input-register" placeholder="Nome" required autocomplete="off">

                <!-- Input Email -->
                <input type="text" name="email" class="input-register" placeholder="E-Mail" required>

                <!-- Input Senha -->
                <input type="password" name="senha" class="input-register" placeholder="Senha" required autocomplete="off">

                <!-- Input Turma -->
                <select name="turma_id" class="input-register" required autocomplete="off">
                    <option value="">Selecione sua turma</option>
                    <?php foreach ($turmas as $t): ?>
                        <option value="<?= $t['id_turma'] ?>">
                            <?= $t['nome_turma'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Mensagens de erro -->
                <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
            </div>

            <!-- Botão de Register -->
            <button type="submit" class="btn-login-white btn-final-login">Cadastrar-se</button>
        </form>
    </div>

</main>

<script src="script.js"></script>
</body>
</html>
