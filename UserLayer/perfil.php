<?php
require_once '../autenticacao.php';
require_once '../db.php';

$pdo = conectar();

/* ============================
   BUSCAR DADOS DO ALUNO
============================ */
$sql_aluno = "
    SELECT nome, email, telefone 
    FROM alunos 
    WHERE id_aluno = :id_aluno
";
$stmt_aluno = $pdo->prepare($sql_aluno);
$stmt_aluno->execute([':id_aluno' => $_SESSION['aluno_id']]);
$aluno = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

/* ============================
   BUSCAR TURMAS DO ALUNO
============================ */
$sql_turmas = "
    SELECT t.nome_turma 
    FROM turmas t
    INNER JOIN alunos_turmas at ON t.id_turma = at.id_turma
    WHERE at.id_aluno = :id_aluno
";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->execute([':id_aluno' => $_SESSION['aluno_id']]);
$turmas = $stmt_turmas->fetchAll(PDO::FETCH_COLUMN);

/* ============================
   BUSCAR INTERVALOS DO ALUNO
============================ */
$sql_intervalos = "
    SELECT DISTINCT i.horario_inicio
    FROM intervalos i
    INNER JOIN turma_intervalo ti ON i.id_intervalo = ti.id_intervalo
    INNER JOIN alunos_turmas at ON ti.id_turma = at.id_turma
    WHERE at.id_aluno = :id_aluno
    ORDER BY i.horario_inicio
";
$stmt_intervalos = $pdo->prepare($sql_intervalos);
$stmt_intervalos->execute([':id_aluno' => $_SESSION['aluno_id']]);
$intervalos = $stmt_intervalos->fetchAll(PDO::FETCH_COLUMN);

// Formatar telefone para exibição
$telefone_exibicao = $aluno['telefone'] ? substr($aluno['telefone'], 0, -2) . 'xx' : 'Não informado';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - RCL</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center kanit-regular">
    <main class="mobile-content align-center background-perfil">
        <!-- Mostra nome do usuario -->
        <section class="user-name kanit-regular">
            <p><?= htmlspecialchars($aluno['nome'] ?? 'Usuário') ?></p>
            <div class="barra-cinza-horizontal"></div>
        </section>

        <!-- Dados do usuario -->
        <section class="dados-perfil">
            <div class="container-dados">
                <h1 class="kanit-regular">Dados:</h1>
                <div class="card-dados">
                    <div class="turma-telefone">
                        <label class="label-dado">Turma:</label>
                        <p>
                            <?php 
                            if (!empty($turmas)) {
                                echo htmlspecialchars(implode(', ', $turmas));
                            } else {
                                echo 'Sem turma';
                            }
                            ?>
                        </p>
                        <label class="label-dado">Telefone:</label>
                        <p><?= htmlspecialchars($telefone_exibicao) ?></p>
                    </div>
                    <div class="intervalos">
                        <label class="label-dado">Intervalos:</label>
                        <?php if (!empty($intervalos)): ?>
                            <?php foreach ($intervalos as $intervalo): ?>
                                <p><?= date("H:i", strtotime($intervalo)) ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Sem intervalos</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Config menu com, configurações, Editar dados, Favoritos, Ajuda, Sair da conta -->
        <section class="config-menu kanit-regular">
            <div class="barra-cinza-horizontal"></div>
            <div class="config-item">
                <a href="javascript:void(0)">Configurações</a>
                <small class="texto-ilustrativo">(Ilustrativo)</small>
            </div>
            <div class="barra-cinza-horizontal"></div>
            <div class="config-item">
                <a href="javascript:void(0)">Editar dados</a>
                <small class="texto-ilustrativo">(Ilustrativo)</small>
            </div>
            <div class="barra-cinza-horizontal"></div>
            <div class="config-item">
                <a href="javascript:void(0)">Favoritos</a>
                <small class="texto-ilustrativo">(Ilustrativo)</small>
            </div>
            <div class="barra-cinza-horizontal"></div>
            <div class="config-item">
                <a href="javascript:void(0)">Ajuda</a>
                <small class="texto-ilustrativo">(Ilustrativo)</small>
            </div>
            <div class="barra-cinza-horizontal"></div>
            <div class="config-item red">
                <a href="../logout.php">Sair da conta</a>
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
    
    <style>
    .texto-ilustrativo {
        color: #666;
        font-size: 0.8em;
        font-style: italic;
        margin-left: 10px;
    }
    </style>
</body>
</html>