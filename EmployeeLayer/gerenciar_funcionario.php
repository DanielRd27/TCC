<?php
require_once '../autenticacao.php';
verifica_funcionario();
require_once '../db.php';

// Pega a mensagem da URL, se existir.
$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';
$em_edicao = false;

$pdo = conectar();
$sql_funcionarios = "SELECT id_funcionario, nome, login, telefone, cargo, created_at FROM funcionarios ORDER BY created_at";
$funcionarios = $pdo->query($sql_funcionarios);

// Processa o formulário de cadastro ou atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = trim($_POST['nome']);
    $login = trim($_POST['login']);
    // 🔑 Senha capturada do formulário (pode estar vazia na edição)
    $nova_senha = trim($_POST['senha']); 
    
    // Variáveis para montagem dinâmica do SQL
    $sql_senha_update = '';
    $parametros_senha = [];
    
    $telefone = trim($_POST['telefone']);
    $cargo = trim($_POST['cargo']);
    
    try {
        if ($id) {
            // --- ATUALIZAÇÃO ---
            
            // Parâmetros base: nome, login
            $parametros_base = [$nome, $login]; 
            
            // 1. Verifica se a senha foi preenchida (só na EDIÇÃO)
            if (!empty($nova_senha)) {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql_senha_update = ', senha = ?';
                // Adiciona o hash ao array de parâmetros de senha
                $parametros_senha[] = $senha_hash; 
            }

            // 2. Monta o SQL dinamicamente
            $sql = "
                UPDATE funcionarios SET 
                    nome = ?, login = ? {$sql_senha_update}, telefone = ?, cargo = ?
                WHERE id_funcionario = ?
            ";

            // 3. Monta os parâmetros finais: (nome, login) + (senha opcional) + (telefone, cargo, id)
            // Parâmetros finais para WHERE e campos não-senha
            $parametros_final = [$telefone, $cargo, $id];
            $parametros_execucao = array_merge($parametros_base, $parametros_senha, $parametros_final);
            
            // Executa o UPDATE
            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametros_execucao);
            $msg = "Funcionário atualizado com sucesso.";

        } else {
            // --- CADASTRO ---
            if (empty($nova_senha)) {
                throw new Exception("A senha é obrigatória para o cadastro de um novo funcionário.");
            }
            // 🔑 Geração do hash (só aqui é obrigatória)
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO funcionarios (nome, login, senha, telefone, cargo)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $nome, $login, $senha_hash, $telefone, $cargo
            ]);
            $msg = "Funcionário cadastrado com sucesso.";
        }

        // REDIRECIONAMENTO CORRIGIDO: Sempre para gerenciar_funcionario.php
        header("Location: gerenciar_funcionario.php?msg=" . urlencode($msg));
        exit(); 

    } catch (PDOException $e) {
        $erro = "Erro ao salvar Funcionário: " . $e->getMessage();
        // REDIRECIONAMENTO CORRIGIDO
        header("Location: gerenciar_funcionario.php?erro=" . urlencode($erro));
        exit(); 
    } 
}

// Processo de exlusão de item
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'excluir') {
    $id = (int)$_GET['id'];
    try {
        // ❌ CORREÇÃO: Tabela 'funcionarios' (no plural)
        $stmt = $pdo->prepare('DELETE FROM funcionarios WHERE id_funcionario = ?');
        $stmt->execute([$id]);

        $msg = "Funcionário Excluído com sucesso.";
        header("Location: gerenciar_funcionario.php?msg=" . urlencode($msg));
        exit(); 
    
    } catch (PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
        header("Location: gerenciar_funcionario.php?erro=" . urlencode($erro));
        exit(); 
    }
}

// Carrega dados do item para edição
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'editar') {
    $id = (int)$_GET['id'];
    // ❌ CORREÇÃO: Tabela 'funcionarios' (já estava certo aqui)
    $stmt = $pdo->prepare('SELECT * FROM funcionarios WHERE id_funcionario = ?');
    $stmt->execute([$id]);
    $funcionario_edicao = $stmt->fetch();
    if ($funcionario_edicao) {
        $em_edicao = true;
    } else {
        // ❌ CORREÇÃO: Mensagem de erro e redirecionamento
        $erro = "Funcionário não encontrado para edição.";
        header("Location: gerenciar_funcionario.php?erro=" . urlencode($erro));
        exit(); 
    }
}

function formatar_telefone($numero) {
    // Remove qualquer coisa que não seja dígito
    $numero = preg_replace('/[^0-9]/', '', $numero);
    $tamanho = strlen($numero);

    switch ($tamanho) {
        case 10:
            // Fixo: (XX) XXXX-XXXX
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $numero);
        
        case 11:
            // Celular: (XX) XXXXX-XXXX
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $numero);
        
        case 8:
            // Telefone sem DDD: XXXX-XXXX
            return preg_replace('/(\d{4})(\d{4})/', '$1-$2', $numero);
        
        case 9:
            // Celular sem DDD: XXXXX-XXXX
            return preg_replace('/(\d{5})(\d{4})/', '$1-$2', $numero);
        
        default:
            // Retorna o número original se não corresponder aos padrões
            return $numero;
    }
}


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
        <div>
            <a href="../logout.php">Sair da conta</a>
            <a href="home.php">Voltar</a>
        </div>
    </header>

    <main>
        <form method="POST" action="gerenciar_funcionario.php<?php if ($em_edicao) echo '?acao=editar&id=' . $funcionario_edicao['id_funcionario']; ?>">
            <h1><?php echo $em_edicao ? 'Editar Funcionario' : 'Cadastrar Novo funcionario'; ?></h1>
            <input type="hidden" name="id" value="<?php echo $funcionario_edicao['id_funcionario'] ?? ''; ?>" />

            <div class="inputs">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" placeholder="Nome" required value="<?php echo htmlspecialchars($funcionario_edicao['nome'] ?? ''); ?>">

                <label for="login">Login:</label>
                <input type="text" name="login" placeholder="Login / Usuario" required value="<?php echo htmlspecialchars($funcionario_edicao['login'] ?? ''); ?>">

                <label>Senha</label>
                <input 
                    type="password" 
                    name="senha" 
                    placeholder="<?php echo $em_edicao ? 'Preencha para alterar a senha' : 'Senha'; ?>" 
                    <?php if (!$em_edicao) echo 'required'; // Senha só é obrigatória no cadastro ?>
                    value="" 
                >
                <label>Telefone</label>
                <input type="tel" name="telefone" placeholder="Telefone" maxlength="11" required value="<?php echo htmlspecialchars($funcionario_edicao['telefone'] ?? ''); ?>">

                <label>Cargo</label>
                <input type="text" name="cargo" placeholder="Administrador, Estoquista, Atendente... etc" required value="<?php echo htmlspecialchars($funcionario_edicao['cargo'] ?? ''); ?>">

                <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                <button type="submit"><?php echo $em_edicao ? 'Atualizar Funcionário' : 'Cadastrar Funcionário'; ?></button> 
                <?php if ($em_edicao): ?>
                    <a id="cancel_edit" href="gerenciar_funcionario.php">Cancelar Edição</a>
                <?php endif; ?>
                
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Senha</th>
                    <th>Telefone</th>
                    <th>Cargo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funcionarios as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['id_funcionario']) ?></td>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['login']) ?></td>
                    <td>********</td>
                    <td><?= htmlspecialchars(formatar_telefone($f['telefone'])) ?></td>
                    <td><?= htmlspecialchars($f['cargo']) ?></td>
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



