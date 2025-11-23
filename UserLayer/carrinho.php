<?php
require_once '../autenticacao.php';
require_once '../db.php';

$pdo = conectar();

/* ============================
   PROCESSAR FINALIZAÇÃO DO PEDIDO
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_pedido'])) {
    
    // Verificar se há itens no carrinho
    if (empty($_SESSION['carrinho'])) {
        $_SESSION['erro'] = "Seu carrinho está vazio!";
        header("Location: carrinho.php");
        exit;
    }

    // Validar dados do formulário
    if (!isset($_POST['horario']) || !isset($_POST['forma_pagamento'])) {
        $_SESSION['erro'] = "Dados incompletos!";
        header("Location: carrinho.php");
        exit;
    }

    $id_aluno = $_SESSION['aluno_id'];
    $id_intervalo = intval($_POST['horario']);
    $forma_pagamento = $_POST['forma_pagamento'];

    try {
        $pdo->beginTransaction();

        // Gerar código de retirada único
        function gerarCodigoRetirada($pdo) {
            $codigo_unico = false;
            $codigo = '';
            
            while (!$codigo_unico) {
                $letra = chr(rand(65, 90)); // A-Z
                $numeros = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $codigo = $letra . $numeros;
                
                $stmt = $pdo->prepare("SELECT id_pedido FROM pedidos WHERE codigo_retirada = ?");
                $stmt->execute([$codigo]);
                
                if (!$stmt->fetch()) {
                    $codigo_unico = true;
                }
            }
            
            return $codigo;
        }

        $codigo_retirada = gerarCodigoRetirada($pdo);

        // Inserir o pedido
        $sql_pedido = "
            INSERT INTO pedidos 
            (id_aluno, status, codigo_retirada, intervalo, forma_pagamento, created_at) 
            VALUES 
            (:id_aluno, 'Pendente', :codigo_retirada, :intervalo, :forma_pagamento, NOW())
        ";
        
        $stmt_pedido = $pdo->prepare($sql_pedido);
        $stmt_pedido->execute([
            ':id_aluno' => $id_aluno,
            ':codigo_retirada' => $codigo_retirada,
            ':intervalo' => $id_intervalo,
            ':forma_pagamento' => $forma_pagamento
        ]);
        
        $id_pedido = $pdo->lastInsertId();

        // Inserir itens do pedido E ATUALIZAR ESTOQUE
        $sql_item = "
            INSERT INTO itens_pedido 
            (id_pedido, id_produto, quantidade) 
            VALUES 
            (:id_pedido, :id_produto, :quantidade)
        ";
        
        $stmt_item = $pdo->prepare($sql_item);
        
        foreach ($_SESSION['carrinho'] as $id_produto => $quantidade) {
            // Inserir item do pedido
            $stmt_item->execute([
                ':id_pedido' => $id_pedido,
                ':id_produto' => $id_produto,
                ':quantidade' => $quantidade
            ]);
            
            /* ============================
               ATUALIZAR ESTOQUE
            ============================ */
            $sql_estoque = "UPDATE produtos SET estoque = estoque - :quantidade WHERE id_produto = :id_produto";
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([
                ':quantidade' => $quantidade,
                ':id_produto' => $id_produto
            ]);
            
            /* ============================
               REGISTRAR MOVIMENTAÇÃO DE ESTOQUE
            ============================ */
            $sql_movimentacao = "
                INSERT INTO movimentacao_estoque 
                (id_produto, tipo_movimentacao, quantidade, data_movimentacao, observacao, movimentado_by) 
                VALUES 
                (:id_produto, 'saida', :quantidade, NOW(), :observacao, 1)
            ";
            $stmt_movimentacao = $pdo->prepare($sql_movimentacao);
            $stmt_movimentacao->execute([
                ':id_produto' => $id_produto,
                ':quantidade' => $quantidade,
                ':observacao' => 'Saída por pedido #' . $id_pedido  // CORREÇÃO AQUI
            ]);
        }

        $pdo->commit();

        // Limpar carrinho e mostrar sucesso
        $_SESSION['carrinho'] = [];
        $_SESSION['sucesso'] = "Pedido realizado com sucesso! Código: " . $codigo_retirada;
        
        header("Location: pedidos.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao finalizar pedido: " . $e->getMessage(); // Mostra o erro real
        header("Location: carrinho.php");
        exit;
    }
}

/* ============================
   1 — PEGAR TODAS AS TURMAS DO ALUNO
============================ */
$stmt = $pdo->prepare("
    SELECT id_turma
    FROM alunos_turmas
    WHERE id_aluno = :id
");
$stmt->execute([':id' => $_SESSION['aluno_id']]);
$turmas = $stmt->fetchAll(PDO::FETCH_COLUMN); // retorna só os IDs

/* ============================
   2 — PEGAR TODOS OS INTERVALOS DAS TURMAS
============================ */
$intervalos = [];

if (!empty($turmas)) {

    $listaTurmas = implode(',', array_map('intval', $turmas));

    $sql = "
        SELECT i.id_intervalo, i.horario_inicio
        FROM intervalos i
        INNER JOIN turma_intervalo ti ON ti.id_intervalo = i.id_intervalo
        WHERE ti.id_turma IN ($listaTurmas)
        ORDER BY i.horario_inicio
    ";

    $stmt = $pdo->query($sql);
    $intervalos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================
   3 — CARRINHO
============================ */
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Remover item
if (isset($_GET['remover'])) {
    $idRemover = intval($_GET['remover']);
    unset($_SESSION['carrinho'][$idRemover]);
    header("Location: carrinho.php");
    exit;
}

// Buscar produtos do carrinho
$itens = [];
$total = 0;

if (!empty($_SESSION['carrinho'])) {

    $ids = implode(',', array_map('intval', array_keys($_SESSION['carrinho'])));

    $sql = "SELECT id_produto, nome, preco_unitario FROM produtos WHERE id_produto IN ($ids)";
    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id_produto'];

        $row['quantidade'] = $_SESSION['carrinho'][$id];
        $row['subtotal'] = $row['preco_unitario'] * $row['quantidade'];
        $total += $row['subtotal'];

        $itens[] = $row;
    }
}

/* ============================
   4 — VALIDAÇÃO PARA FINALIZAR
============================ */
$podeFinalizar = !empty($itens) && !empty($intervalos);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">

<main class="mobile-content align-center background-carrinho">
    <section class="card-carrinho kanit-regular">

        <!-- MENSAGENS DE SUCESSO/ERRO -->
        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="mensagem-sucesso">
                <?= $_SESSION['sucesso'] ?>
                <?php unset($_SESSION['sucesso']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="mensagem-erro">
                <?= $_SESSION['erro'] ?>
                <?php unset($_SESSION['erro']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validarCarrinho()">
            <input type="hidden" name="finalizar_pedido" value="1">

            <div class="header-carrinho">
                <h2 class="kanit-regular">Carrinho</h2>

                <select name="horario" required>
                    <?php foreach ($intervalos as $i): ?>
                        <option value="<?= htmlspecialchars($i['id_intervalo']) ?>">
                            Para: <?= date("H:i", strtotime($i['horario_inicio'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="itens-carrinho kanit-regular">

                <?php if (empty($itens)): ?>
                    <p style="text-align:center; margin:20px 0;">Seu carrinho está vazio 😢</p>
                <?php else: ?>

                    <?php foreach ($itens as $item): ?>
                        <div class="item-pedido item-carrinho">

                            <div class="item-quantidade">
                                <?= $item['quantidade'] ?>
                            </div>

                            <p><?= htmlspecialchars($item['nome']) ?></p>

                            <span class="preco-item bold kanit-regular">
                                R$ <?= number_format($item['subtotal'], 2, ',', '.') ?>
                            </span>

                            <a class="btn-excluir"
                               href="carrinho.php?remover=<?= $item['id_produto'] ?>">
                                Remover
                            </a>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <div class="text-carrinho">
                <p>Forma de pagamento:</p>
                <span>Total</span>
            </div>

            <div class="payment-total">
                <select name="forma_pagamento" required>
                    <option value="pix">Pix</option>
                    <option value="cartao">Cartão de crédito</option>
                    <option value="debito">Cartão de débito</option>
                </select>

                <span class="bold kanit-regular">
                    R$ <?= number_format($total, 2, ',', '.') ?>
                </span>
            </div>

            <button class="payment-button" type="submit"
                <?= !$podeFinalizar ? 'disabled' : '' ?>>
                Finalizar compra
            </button>

        </form>

    </section>
</main>

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

<script>
function validarCarrinho() {
    const itens = <?= json_encode(!empty($itens)) ?>;
    const intervalos = <?= json_encode(!empty($intervalos)) ?>;
    
    if (!itens) {
        alert('Seu carrinho está vazio!');
        return false;
    }
    
    if (!intervalos) {
        alert('Nenhum intervalo disponível para suas turmas!');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>