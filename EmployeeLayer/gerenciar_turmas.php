<?php
require_once '../autenticacao.php';
verifica_funcionario(); 
require_once '../db.php';

$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';
$em_edicao = false;

// Variável para armazenar dados da turma em edição, se houver
$turma_edicao = [];

// ***********************************************
// 🚨 NOVO SQL DE TABELA PARA TURMAS (CORRIGIDO)
// ***********************************************
// O SQL agrupa os intervalos associados na mesma linha
$sql_tabela = "
    SELECT
        T.id_turma,
        T.nome_turma,
        T.data_conclusao,
        -- Alias TI não é mais necessário aqui, mas mantido para clareza do JOIN
        GROUP_CONCAT(I.nome ORDER BY I.horario_inicio ASC SEPARATOR ', ') AS intervalos_associados
    FROM
        turmas T
    LEFT JOIN
        turma_intervalo TI ON T.id_turma = TI.id_turma
    LEFT JOIN
        intervalos I ON TI.id_intervalo = I.id_intervalo
    GROUP BY
        T.id_turma, T.nome_turma, T.data_conclusao, T.created_by -- GROUP BY completo para compatibilidade
    ORDER BY
        T.nome_turma ASC
";

// 2. Estabelece a conexão e busca dados
try {
    $pdo = conectar();
    $stmt = $pdo->query($sql_tabela); 
    $resultados = $stmt->fetchAll(); 
    
    // 🚨 Busca todos os intervalos disponíveis para os SELECTs do formulário
    $stmt_int = $pdo->query("SELECT id_intervalo, nome, CONCAT(horario_inicio, ' - ', horario_fim) AS horario FROM intervalos ORDER BY horario_inicio ASC");
    $intervalos_disponiveis = $stmt_int->fetchAll();
    
} catch (Exception $e) {
    // Se o erro de coluna desconhecida persistir, a Solução 1 (Adicionar 'ordem') ainda é necessária.
    die("Erro ao carregar dados: " . $e->getMessage());
}

// ***********************************************
// 🚨 NOVO BLOCO POST PARA TURMAS
// ***********************************************
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_turma'])) { // Adicionado check para diferenciar POSTs
    $id = $_POST['id'] ?? '';
    $nome_turma = trim($_POST['nome_turma']);
    $data_conclusao = trim($_POST['data_conclusao']);
    $int_1 = $_POST['intervalo_1'] ?? null; // Obrigatório
    $int_2 = $_POST['intervalo_2'] ?? null; // Opcional
    $int_3 = $_POST['intervalo_3'] ?? null; // Opcional
    $funcionario_id = $_SESSION['funcionario_id'];
    
    // Lista de IDs de Intervalo (apenas os preenchidos e únicos)
    $intervalos_ids = array_unique(array_filter([$int_1, $int_2, $int_3]));

    if (empty($nome_turma) || empty($data_conclusao) || empty($int_1)) {
        $erro_msg = "O Nome da Turma, a Data de Conclusão e o Intervalo 1 são obrigatórios.";
        header("Location: gerenciar_turmas.php?erro=" . urlencode($erro_msg));
        exit();
    }
    
    try {
        // Inicia a transação para garantir que Turma e Intervalos sejam salvos juntos
        $pdo->beginTransaction();

        if ($id) {
            // ATUALIZAÇÃO DA TABELA PRINCIPAL 'turmas'
            $stmt = $pdo->prepare("
                UPDATE turmas SET 
                    nome_turma = ?, 
                    data_conclusao = ?, 
                    update_by = ?
                WHERE id_turma = ?");
            $stmt->execute([$nome_turma, $data_conclusao, $funcionario_id, $id]);
            
            $id_turma = $id;
            $msg = "Turma atualizada com sucesso.";
            
            // 🚨 LIMPA AS ASSOCIAÇÕES EXISTENTES
            $pdo->prepare("DELETE FROM turma_intervalo WHERE id_turma = ?")->execute([$id_turma]);

        } else {
            // CADASTRO NA TABELA PRINCIPAL 'turmas'
            $stmt = $pdo->prepare("
                INSERT INTO turmas (nome_turma, data_conclusao, created_by)
                VALUES (?, ?, ?)");
            $stmt->execute([$nome_turma, $data_conclusao, $funcionario_id]);
            
            // Pega o ID da turma recém-criada
            $id_turma = $pdo->lastInsertId();
            $msg = "Turma cadastrada com sucesso.";
        }
        
        // 🚨 INSERE AS NOVAS ASSOCIAÇÕES DE INTERVALO
        // O SQL AGORA ESPERA A COLUNA 'ordem' (Se você corrigiu o BD)
        $sql_associacao = "INSERT INTO turma_intervalo (id_turma, id_intervalo, ordem) VALUES (?, ?, ?)";
        $stmt_associacao = $pdo->prepare($sql_associacao);
        
        $ordem = 1;
        foreach ($intervalos_ids as $id_intervalo) {
            // Garante que o ID não está vazio (embora array_filter já faça isso) e não é zero
            if (!empty($id_intervalo) && $id_intervalo != 0) { 
                $stmt_associacao->execute([$id_turma, $id_intervalo, $ordem]);
                $ordem++;
            }
        }
        
        // Finaliza a transação
        $pdo->commit();
        
        // REDIRECIONAMENTO DE SUCESSO
        header("Location: gerenciar_turmas.php?msg=" . urlencode($msg));
        exit(); 

    } catch (PDOException $e) {
        $pdo->rollBack(); // Em caso de erro, desfaz tudo
        $erro_msg = "Erro ao salvar Turma: " . $e->getMessage();
        header("Location: gerenciar_turmas.php?erro=" . urlencode($erro_msg));
        exit();
    }
}


// ***********************************************
// 🚨 NOVO BLOCO DE CARREGAR DADOS PARA EDIÇÃO (TURMAS)
// ***********************************************
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'editar') {
    $id = (int)$_GET['id'];
    
    // 1. Busca os dados da turma principal
    $stmt = $pdo->prepare('SELECT * FROM turmas WHERE id_turma = ?');
    $stmt->execute([$id]);
    $turma_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Busca os IDs dos intervalos associados (para preencher os selects)
    // Ordena pela coluna 'ordem' (que deve existir)
    $stmt_int = $pdo->prepare('SELECT id_intervalo FROM turma_intervalo WHERE id_turma = ? ORDER BY ordem ASC'); 
    $stmt_int->execute([$id]);
    $intervalos_associados = $stmt_int->fetchAll(PDO::FETCH_COLUMN);

    if ($turma_edicao) {
        $em_edicao = true;
        
        // Mapeia os IDs dos intervalos para o array principal para fácil acesso no HTML
        for ($i = 0; $i < 3; $i++) {
            $turma_edicao["intervalo_" . ($i + 1)] = $intervalos_associados[$i] ?? '';
        }
        
    } else {
        $erro_msg = "Turma não encontrada para edição.";
        header("Location: gerenciar_turmas.php?erro=" . urlencode($erro_msg));
        exit(); 
    }
}

// ***********************************************
// 🚨 NOVO BLOCO DE EXCLUSÃO (TURMAS)
// ***********************************************
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'excluir') {
    $id = (int)$_GET['id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Remove as associações na tabela M-N primeiro
        $pdo->prepare("DELETE FROM turma_intervalo WHERE id_turma = ?")->execute([$id]);
        
        // 2. Remove a turma
        $pdo->prepare("DELETE FROM turmas WHERE id_turma = ?")->execute([$id]);
        
        $pdo->commit();
        $msg = "Turma excluída com sucesso.";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro_msg = "Erro ao excluir Turma. Verifique se há alunos ou pedidos dependentes.";
    }
    
    header("Location: gerenciar_turmas.php?msg=" . urlencode($msg ?? '') . "&erro=" . urlencode($erro_msg ?? ''));
    exit();
}

// ... Resto do código PHP (não foi alterado)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel RCL - Gerenciar Turmas</title>
    <link rel="stylesheet" href="style.css">
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
        <form method="POST" action="gerenciar_turmas.php">
            <h1><?php echo $em_edicao ? 'Editar Turma' : 'Cadastrar Nova Turma'; ?></h1>
            <input type="hidden" name="id" value="<?php echo $turma_edicao['id_turma'] ?? ''; ?>" />

            <div class="inputs">
                <label for="nome_turma">Nome da Turma:</label>
                <input type="text" name="nome_turma" placeholder="Ex: ADS 2024.1" required value="<?php echo htmlspecialchars($turma_edicao['nome_turma'] ?? ''); ?>">

                <label for="data_conclusao">Data de Conclusão:</label>
                <input type="date" name="data_conclusao" required value="<?php echo htmlspecialchars($turma_edicao['data_conclusao'] ?? ''); ?>">

                <label>Intervalos Associados (Mínimo 1):</label>
                <div class="select-group">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <select name="intervalo_<?php echo $i; ?>" <?php if ($i == 1) echo 'required'; ?>>
                            <option value="">-- Intervalo <?php echo $i; ?> (<?php echo $i == 1 ? 'Obrigatório' : 'Opcional'; ?>) --</option>
                            <?php foreach ($intervalos_disponiveis as $intervalo): 
                                // O valor de comparação vem do array de edição mapeado: $turma_edicao['intervalo_1'], etc.
                                $selecionado = ($em_edicao && ($turma_edicao["intervalo_{$i}"] ?? '') == $intervalo['id_intervalo']) ? 'selected' : '';
                            ?>
                                <option value="<?= $intervalo['id_intervalo'] ?>" <?= $selecionado ?>>
                                    <?= htmlspecialchars($intervalo['nome']) . " (" . $intervalo['horario'] . ")" ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endfor; ?>
                </div>

                <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                
                <button type="submit"><?php echo $em_edicao ? 'Atualizar Turma' : 'Cadastrar Turma'; ?></button> 
                <?php if ($em_edicao): ?>
                    <a id="cancel_edit" href="gerenciar_turmas.php">Cancelar Edição</a>
                <?php endif; ?>
                
            </div>
        </form>

        <h2>Lista de Turmas</h2>
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nome</th>
                    <th>Conclusão</th>
                    <th>Intervalos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($linha['id_turma']) ?></td>
                    <td><?= htmlspecialchars($linha['nome_turma']) ?></td>
                    <td><?= htmlspecialchars($linha['data_conclusao']) ?></td>
                    <td>
                        <?php 
                            // O nome do campo agora é 'intervalos_associados'
                            if ($linha['intervalos_associados']):
                                echo htmlspecialchars($linha['intervalos_associados']);
                            else:
                                echo 'Nenhum';
                            endif;
                        ?>
                    </td>
                    <td class="actions-cell">
                        <div class="actions-buttons">
                            <a href="gerenciar_produtos.php?acao=editar&id=<?php echo (int)$p['id_produto']; ?>" class="action-btn editar">
                                <span>✏️</span> Editar
                            </a>
                            <a href="gerenciar_produtos.php?acao=excluir&id=<?php echo (int)$p['id_produto']; ?>" class="action-btn excluir" onclick="return confirm('Tem certeza que deseja excluir? Isso também removerá a imagem do servidor.');">
                                <span>🗑️</span> Excluir
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    
</body>
</html>