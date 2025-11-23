<?php
require_once '../autenticacao.php';
verifica_funcionario();
require_once '../db.php';

// Pega a mensagem da URL, se existir.
$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';
$em_edicao = false;

// Monta o query que associa as turmas com seus intervalos pertencentes 
$sql_tabela = "
    SELECT
        I.id_intervalo,
        I.nome,
        I.horario_inicio,
        I.horario_fim,
        GROUP_CONCAT(T.nome_turma SEPARATOR ', ') AS turmas_associadas
    FROM
        intervalos I
    LEFT JOIN
        turma_intervalo TI ON I.id_intervalo = TI.id_intervalo
    LEFT JOIN
        turmas T ON TI.id_turma = T.id_turma
    GROUP BY
        I.id_intervalo, I.horario_inicio, I.horario_fim
    ORDER BY
        I.horario_inicio ASC
";

// 2. Estabelece a conexão
try {
    $pdo = conectar(); // Usa sua função conectar()
    
    // 3. Prepara e executa a consulta
    $stmt = $pdo->query($sql_tabela); 
    
    // 4. Busca todos os resultados
    $resultados = $stmt->fetchAll(); 

} catch (Exception $e) {
    // Trata erros de conexão ou SQL
    die("Erro ao carregar dados: " . $e->getMessage());
}

// Processa o formulário de cadastro ou atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = trim($_POST['nome']);
    $horario_inicio = trim($_POST['horario_inicio']);
    $horario_fim = trim($_POST['horario_fim']);

    // Verifica se o tamanho do intervalo é valido
    $limite_minutos = 90;
    $inicio = new DateTime($horario_inicio);
    $fim = new DateTime($horario_fim);

    // Calcula a diferença
    $intervalo = $inicio->diff($fim);

    // 1. Converte o DateInterval para o total de minutos
    // Note que $intervalo->i são apenas os minutos restantes (0-59),
    // enquanto $intervalo->h são as horas.
    $duracao_total_minutos = ($intervalo->h * 60) + $intervalo->i;

    // 2. Realiza a comparação
    if ($duracao_total_minutos > $limite_minutos) {
        $erro = "O intervalo não pode ter mais de 1 hora e meia.";
    } else {
        try {
            if ($id) {
                // ATUALIZAÇÃO
                $stmt = $pdo->prepare("
                    UPDATE intervalos SET 
                        nome = ?, horario_inicio = ?, horario_fim = ?, update_by = ?
                    WHERE id_intervalo = ?");
                $stmt->execute([
                    $nome, $horario_inicio, $horario_fim, $_SESSION['funcionario_id'], $id
                ]);
                $msg = "Intervalo atualizado com sucesso.";
            } else {
                // CADASTRO
                $stmt = $pdo->prepare("
                    INSERT INTO intervalos (nome, horario_inicio, horario_fim, created_by)
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $nome, $horario_inicio, $horario_fim, $_SESSION['funcionario_id']
                ]);
                $msg = "Intervalo cadastrado com sucesso.";
            }

            // REDIRECIONAMENTO
            // O redirecionamento recarrega a página, limpando o POST e os inputs.
            header("Location: gerenciar_intervalos.php?msg=" . urlencode($msg));
            exit(); // Garante que o script pare de executar imediatamente

        } catch (PDOException $e) {
            $erro = "Erro ao salvar Intervalo: " . $e->getMessage();
            // REDIRECIONAMENTO
            // O redirecionamento recarrega a página, limpando o POST e os inputs.
            header("Location: gerenciar_intervalos.php?erro=" . urlencode($erro));
            exit(); // Garante que o script pare de executar imediatamente
        }
    }
}

// Processo de exlusão de item
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'excluir') {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM intervalos WHERE id_intervalo = ?');
        $stmt->execute([$id]);

        $stmt = $pdo->prepare('DELETE FROM turma_intervalo WHERE id_intervalo = ?');
        $stmt->execute([$id]);

        $msg = "Intervalo Excluido com sucesso.";

        // REDIRECIONAMENTO
        // O redirecionamento recarrega a página, limpando o POST e os inputs.
        header("Location: gerenciar_intervalos.php?msg=" . urlencode($msg));
        exit(); // Garante que o script pare de executar imediatamente
    
    } catch (PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
        
        // REDIRECIONAMENTO
        // O redirecionamento recarrega a página, limpando o POST e os inputs.
        header("Location: gerenciar_intervalos.php?erro=" . urlencode($erro));
        exit(); // Garante que o script pare de executar imediatamente
    }
}

// Carrega dados do item para edição
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'editar') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM intervalos WHERE id_intervalo = ?');
    $stmt->execute([$id]);
    $intervalo_edicao = $stmt->fetch();
    if ($intervalo_edicao) {
        $em_edicao = true;
    } else {
        $erro = "Intervalo não encontrada para edição.";
        // REDIRECIONAMENTO
        // O redirecionamento recarrega a página, limpando o POST e os inputs.
        header("Location: gerenciar_intervalos.php?erro=" . urlencode($erro));
        exit(); // Garante que o script pare de executar imediatamente
    }
}



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
        
        main {
            padding: 2rem;
        }

        .alert {
            margin-top:1rem;
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }

        .erro {
            margin-top:1rem;
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
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

        header div {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        header a {
            color: red;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 10px;
        }

        .inputs {
            display: flex;
            flex-direction: column;
        }

        .inputs label {
            font-size: 1.5rem;
            margin: 1rem 0;
        }

        .inputs button {
            border-radius: 10px;
            border: 1px solid black;
            padding: 1rem;
            margin: 1rem 0;
            cursor: pointer;
            height: 4rem;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .inputs button:hover {
            background-color: #29292933;
        }

        input[type="text"], 
        input[type="time"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }

        button[type="submit"] {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        #cancel_edit {
            color: #ffffffff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 0;
            background: red;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            text-align: center;
        }

        /* --- ESTILOS DA TABELA --- */

        table {
            width: 100%;
            border-collapse: collapse; /* Remove espaços duplos entre as bordas */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
            background-color: white;
        }

        /* Cabeçalho da Tabela */
        thead tr {
            background-color: #343a40; /* Cinza escuro */
            color: white;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #444;
            font-weight: 600;
        }

        /* Corpo da Tabela */
        tbody tr {
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s;
        }

        tbody tr:nth-child(even) {
            background-color: #f4f4f4; /* Fundo mais claro para linhas pares (zebra striping) */
        }

        tbody tr:hover {
            background-color: #e9ecef; /* Efeito hover suave */
        }

        td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        /* Estilo para links de Ações */
        td a {
            color: #007bff;
            text-decoration: none;
            margin-right: 5px;
        }

        td a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .red {
            color: red;
        }

        
    </style>
</head>
<body>
    <header>
        <div>RCL - Olá, <?php echo $_SESSION['funcionario_nome']?></div>
        <div>
            <a href="../logout.php">Sair da conta</a>
            <a href="home.php">Voltar</a>
        </div>
    </header>

    <main>
        <form method="POST" action="gerenciar_intervalos.php<?php if ($em_edicao) echo '?acao=editar&id=' . $intervalo_edicao['id_intervalo']; ?>">
            <h1><?php echo $em_edicao ? 'Editar Intervalo' : 'Cadastrar Novo Intervalo'; ?></h1>
            <input type="hidden" name="id" value="<?php echo $intervalo_edicao['id_intervalo'] ?? ''; ?>" />

            <div class="inputs">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" placeholder="Ex: Almoço" required value="<?php echo htmlspecialchars($intervalo_edicao['nome'] ?? ''); ?>">

                <label>Horario de Inicio:</label>
                <input type="time" name="horario_inicio" required value="<?php echo htmlspecialchars($intervalo_edicao['horario_inicio'] ?? ''); ?>">

                <label>Horario de Fim:</label>
                <input type="time" name="horario_fim" required value="<?php echo htmlspecialchars($intervalo_edicao['horario_fim'] ?? ''); ?>">
                <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                <button type="submit"><?php echo $em_edicao ? 'Atualizar Intervalo' : 'Cadastrar Intervalo'; ?></button> 
                <?php if ($em_edicao): ?>
                    <a id="cancel_edit" href="gerenciar_intervalos.php">Cancelar Edição</a>
                <?php endif; ?>
                
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nome</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Turmas Associadas</th>
                    <th>ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($linha['id_intervalo']) ?></td>
                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                    <td><?= htmlspecialchars(substr($linha['horario_inicio'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars(substr($linha['horario_fim'], 0, 5)) ?></td>
                    <td>
                        <?php 
                            // Verifica se há turmas (o campo será NULL se não houver LEFT JOIN)
                            if ($linha['turmas_associadas']):
                                echo htmlspecialchars($linha['turmas_associadas']);
                            else:
                                echo 'Nenhuma';
                            endif;
                        ?>
                    </td>
                    <td>
                        <a href="gerenciar_intervalos.php?acao=editar&id=<?php echo (int)$linha['id_intervalo']; ?>">Editar</a> |
                        <a class="red" href="gerenciar_intervalos.php?acao=excluir&id=<?php echo (int)$linha['id_intervalo']; ?>" onclick="return confirm('Tem certeza que deseja excluir?');">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    
</body>
</html>



