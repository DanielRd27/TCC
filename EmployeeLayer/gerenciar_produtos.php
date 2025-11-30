<?php
require_once '../autenticacao.php';
verifica_funcionario();
require_once '../db.php';

// Define o diretório onde as imagens serão salvas.
// CRIE ESTA PASTA MANUALMENTE e garanta que tenha permissão de escrita.
$diretorio_destino = '../img/'; 

// Pega a mensagem da URL, se existir.
$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';    
$em_edicao = false;

$pdo = conectar();
// Adicionando 'imagem' na seleção para ser exibida na tabela
$sql_produtos = "SELECT id_produto, nome, categoria, descricao, preco_unitario, estoque, estoque_minimo, imagem FROM produtos ORDER BY nome";
$produtos = $pdo->query($sql_produtos);

// Processa o formulário de cadastro ou atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = trim($_POST['nome']);
    $categoria = trim($_POST['categoria']);
    // ✅ CAMPOS CORRIGIDOS PARA PRODUTOS
    $descricao = trim($_POST['descricao']); 
    // Garante que o preço seja um float (substitui vírgula por ponto, se houver)
    $preco_unitario = (float)str_replace(',', '.', trim($_POST['preco_unitario'])); 
    $estoque = (int)trim($_POST['estoque']);
    $estoque_minimo = (int)trim($_POST['estoque_minimo']);
    
    $caminho_imagem = null; // Variável para armazenar o caminho do novo arquivo
    $erro_upload = null;

    // 1. PROCESSAMENTO E VALIDAÇÃO DA IMAGEM
    if (isset($_FILES['imagem_file']) && $_FILES['imagem_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        $arquivo_info = $_FILES['imagem_file'];
        
        if ($arquivo_info['error'] === UPLOAD_ERR_OK) {
            $nome_original = basename($arquivo_info['name']);
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            $extensao_arquivo = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

            if (!in_array($extensao_arquivo, $extensoes_permitidas)) {
                $erro_upload = "Erro: Formato de arquivo não permitido (apenas JPG, JPEG, PNG, GIF).";
            } else {
                // Cria um nome único e move o arquivo
                $nome_unico = uniqid('prod_', true) . '.' . $extensao_arquivo;
                $caminho_final = $diretorio_destino . $nome_unico;

                if (move_uploaded_file($arquivo_info['tmp_name'], $caminho_final)) {
                    $caminho_imagem = $caminho_final;
                } else {
                    $erro_upload = "Erro ao salvar o arquivo no servidor.";
                }
            }
        } else {
            $erro_upload = "Erro no upload da imagem. Código: " . $arquivo_info['error'];
        }
    } 
    
    // Se houve erro no upload, redireciona antes de tentar salvar no banco
    if ($erro_upload) {
         header("Location: gerenciar_produtos.php?erro=" . urlencode($erro_upload));
         exit();
    }
    
    try {
        if ($id) {
            // --- ATUALIZAÇÃO ---
            $sql_imagem_update = '';
            // Parâmetros base sem a imagem
            $parametros = [
                $nome, $categoria, $descricao, $preco_unitario, $estoque_minimo, $_SESSION['funcionario_id']
            ];

            // Adiciona a imagem no SQL e nos parâmetros APENAS se um novo arquivo foi enviado
            if ($caminho_imagem) {
                $sql_imagem_update = ', imagem = ?';
                $parametros[] = $caminho_imagem; 
            }
            
            $sql = "
                UPDATE produtos SET 
                    nome = ?, categoria = ?, descricao = ?, preco_unitario = ?, estoque_minimo = ?, update_by = ? {$sql_imagem_update}
                WHERE id_produto = ?
            ";

            // O ID é o último parâmetro para o WHERE
            $parametros[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametros);

            $msg = "Produto atualizado com sucesso.";
            
        } else {
            // --- CADASTRO ---
              if ($estoque_minimo > $estoque) {
                $erro = "Estoque mínimo não pode ser maior que estoque atual";
                header("Location: gerenciar_produtos.php?erro=" . urlencode($erro));
                exit();
            }
                    
            // Validação: A imagem é obrigatória no cadastro? (O HTML já tem 'required', mas é bom validar aqui)
            if (!$caminho_imagem) {
                throw new Exception("A imagem é obrigatória para o cadastro de um novo produto.");
            }
            
            // SQL para INSERT com o novo campo 'imagem'
            $stmt = $pdo->prepare("
                INSERT INTO produtos (nome, categoria, descricao, preco_unitario, estoque, estoque_minimo, imagem, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nome, $categoria, $descricao, $preco_unitario, $estoque, $estoque_minimo, $caminho_imagem, $_SESSION['funcionario_id']
            ]);
            $msg = "Produto cadastrado com sucesso.";
        }

        header("Location: gerenciar_produtos.php?msg=" . urlencode($msg));
        exit(); 

    } catch (PDOException $e) {
        // Se a transação falhar, deleta o arquivo recém-salvo (se foi um cadastro)
        if ($caminho_imagem && !$id && file_exists($caminho_imagem)) {
             unlink($caminho_imagem); 
        }
        $erro = "Erro ao salvar produto: " . $e->getMessage();
        header("Location: gerenciar_produtos.php?erro=" . urlencode($erro));
        exit(); 
    } catch (Exception $e) {
        // Erro de validação (imagem obrigatória ou outros)
        header("Location: gerenciar_produtos.php?erro=" . urlencode($e->getMessage()));
        exit();
    }
}


// Processo de exclusão de item
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'excluir') {
    $id = (int)$_GET['id'];
    try {
        $stmt_select = $pdo->prepare('SELECT * FROM itens_pedido WHERE id_produto = ?');
        $stmt_select->execute([$id]);
        $lista_pedidos = $stmt_select->fetch();

        if (!$lista_pedidos) {
            $stmt_select = $pdo->prepare('SELECT imagem FROM produtos WHERE id_produto = ?');
            $stmt_select->execute([$id]);
            $produto_para_excluir = $stmt_select->fetch();

            // 3. Deleta o registro principal
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id_produto = ?');
            $stmt->execute([$id]);

            // 4. Deleta o arquivo físico
            if ($produto_para_excluir && !empty($produto_para_excluir['imagem']) && file_exists($produto_para_excluir['imagem'])) {
                unlink($produto_para_excluir['imagem']); 
            }
        } else {
            $erro = "Produto com registro em Pedidos, não pode ser excluido";
            header("Location: gerenciar_produtos.php?erro=" . urlencode($erro));
            exit(); 
        }

        $msg = "Produto Excluído com sucesso.";
        header("Location: gerenciar_produtos.php?msg=" . urlencode($msg));
        exit(); 
    
    } catch (PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
        header("Location: gerenciar_produtos.php?erro=" . urlencode($erro));
        exit(); 
    }
}

// Carrega dados do item para edição
if (isset($_GET['acao'], $_GET['id']) && $_GET['acao'] === 'editar') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id_produto = ?');
    $stmt->execute([$id]);
    $produto_edicao = $stmt->fetch();
    if ($produto_edicao) {
        $em_edicao = true;
    } else {
        $erro = "Produto não encontrada para edição.";
        header("Location: gerenciar_produtos.php?erro=" . urlencode($erro));
        exit(); 
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
        <form method="POST" action="gerenciar_produtos.php<?php if ($em_edicao) echo '?acao=editar&id=' . $produto_edicao['id_produto']; ?>" enctype="multipart/form-data">
            <h1><?php echo $em_edicao ? 'Editar Produto' : 'Cadastrar Novo Produto'; ?></h1>
            <input type="hidden" name="id" value="<?php echo $produto_edicao['id_produto'] ?? ''; ?>" />

            <div class="inputs">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" placeholder="Nome do produto" required value="<?php echo htmlspecialchars($produto_edicao['nome'] ?? ''); ?>">

                <label for="categoria">Categoria:</label>
                <input type="text" name="categoria" placeholder="Ex: Salgados" required value="<?php echo htmlspecialchars($produto_edicao['categoria'] ?? ''); ?>">

                <label>Descrição:</label>
                <input type="text" name="descricao" placeholder="Descrição do produto" required value="<?php echo htmlspecialchars($produto_edicao['descricao'] ?? ''); ?>">

                <label>Preço Unitário:</label>
                <input type="number" step="0.01" name="preco_unitario" placeholder="Ex: 12.00" required value="<?php echo htmlspecialchars($produto_edicao['preco_unitario'] ?? ''); ?>">

                <label><?php echo $em_edicao ? 'Estoque Atual (Não pode ser alterado por aqui)' : 'Estoque Inicial'; ?></label>
                <input <?php echo $em_edicao ? 'disabled' : ''; ?> type="number" name="estoque" placeholder="Estoque Inicial" required value="<?php echo htmlspecialchars($produto_edicao['estoque'] ?? ''); ?>">

                <label>Estoque Mínimo:</label>
                <input type="number" name="estoque_minimo" placeholder="Estoque minimo para o produto" required value="<?php echo htmlspecialchars($produto_edicao['estoque_minimo'] ?? ''); ?>">

                <label>Imagem:</label>
                <input 
                    type="file" 
                    name="imagem_file" 
                    id="imagem" 
                    accept="image/*" 
                    <?php if (!$em_edicao) echo 'required'; // Imagem só é obrigatória no cadastro ?>
                >
                <?php if ($em_edicao && !empty($produto_edicao['imagem'])): ?>
                    <small>Imagem atual: <a href="<?php echo htmlspecialchars($produto_edicao['imagem']); ?>" target="_blank">Ver</a>. Envie uma nova para substituir.</small>
                <?php endif; ?>

                <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                <button type="submit"><?php echo $em_edicao ? 'Atualizar Produto' : 'Cadastrar Produto'; ?></button> 
                <?php if ($em_edicao): ?>
                    <a id="cancel_edit" href="gerenciar_produtos.php">Cancelar Edição</a>
                <?php endif; ?>
                
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Descrição</th>
                    <th>Preço Unitário</th>
                    <th>Estoque</th>
                    <th>Estoque Minimo</th>
                    <th>Imagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['id_produto']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td><?= htmlspecialchars($p['descricao']) ?></td>
                    <td>R$ <?= number_format((float)$p['preco_unitario'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($p['estoque']) ?></td>
                    <td><?= htmlspecialchars($p['estoque_minimo']) ?></td>
                    <td>
                        <?php if (!empty($p['imagem']) && file_exists($p['imagem'])): ?>
                            <img src="<?= htmlspecialchars($p['imagem']) ?>" alt="Img Prod" style="max-width: 50px; height: auto;">
                        <?php else: ?>
                        [Image of an image placeholder]
                        <?php endif; ?>
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