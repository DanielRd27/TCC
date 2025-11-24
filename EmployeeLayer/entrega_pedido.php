<?php
require_once '../autenticacao.php';
verifica_funcionario();
require_once '../db.php';

$pdo = conectar();
// Utilize as constantes que você definiu para os status
const STATUS_PRONTO = 'Pronto'; 
const STATUS_CONCLUIDO = 'Concluído'; 

// Certifique-se de que a ID do funcionário esteja na sessão
// Usaremos um valor padrão (1) se a sessão estiver vazia, mas ajuste conforme sua autenticação
$funcionario_id_logado = $_SESSION['funcionario_id'] ?? 1; 

$msg = '';
$erro = '';

// =======================================================
// 1. Processamento da Finalização (quando o botão é clicado)
// =======================================================

if (isset($_POST['finalizar_pedido'], $_POST['id_pedido_concluir'])) {
    $id_pedido = (int)$_POST['id_pedido_concluir'];

    try {
        $pdo->beginTransaction();

        // ----------------------------------------------------
        // 1. OBTÉM OS ITENS DO PEDIDO (APENAS PARA VALIDAÇÃO)
        // ----------------------------------------------------
        $stmt_itens = $pdo->prepare("
            SELECT id_produto, quantidade 
            FROM itens_pedido 
            WHERE id_pedido = ?
        ");
        $stmt_itens->execute([$id_pedido]);
        $itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

        if (empty($itens_pedido)) {
            // Lança exceção para garantir o rollback
            throw new Exception("Pedido #{$id_pedido} não possui itens. Não pode ser finalizado.");
        }

        // ----------------------------------------------------
        // 2. ATUALIZA O STATUS DO PEDIDO PARA CONCLUÍDO
        // (REMOVIDA A PARTE DE BAIXA NO ESTOQUE)
        // ----------------------------------------------------
        $stmt_status = $pdo->prepare("
            UPDATE pedidos 
            SET status = ?, updated_at = NOW(), update_by = ? 
            WHERE id_pedido = ? AND status = ?
        ");
        
        $stmt_status->execute([
            STATUS_CONCLUIDO, 
            $funcionario_id_logado,
            $id_pedido, 
            STATUS_PRONTO
        ]);

        if ($stmt_status->rowCount() > 0) {
            $pdo->commit();
            $msg = "Pedido #{$id_pedido} entregue com sucesso!";
        } else {
            $pdo->rollBack();
            $erro = "Erro: O pedido #{$id_pedido} não está mais no status 'Pronto' ou já foi concluído.";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $erro = "Erro ao finalizar pedido: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrega de Pedido - RCL</title>
</head>
<style>
        /* CSS BÁSICO PARA O LAYOUT DE DUAS COLUNAS */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; border-bottom: 2px solid #000; background: white; font-size: 1.5rem; font-weight: bold; }
        .voltar { color: red; text-decoration: none; font-size: 1rem; }
        
        main { display: flex; height: calc(100vh - 60px); }

        /* PAINEL ESQUERDO: CÓDIGO */
        .left-panel { flex: 2; padding: 4rem; background: white; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .code-container { border: 2px solid #ccc; border-radius: 10px; padding: 40px; width: 100%; max-width: 500px; text-align: center; }
        .code-container h1 { color: #555; font-size: 2.5em; margin-bottom: 20px; }
        #codigo-retirada {padding: 15px; font-size: 1.5em; text-align: center; border: 1px solid #aaa; border-radius: 5px; }

        /* PAINEL DIREITO: DETALHES DO PEDIDO */
        .right-panel { flex: 1; min-width: 350px; padding: 2rem; background-color: #eee; border-left: 2px solid #ccc; display: flex; flex-direction: column; }
        .pedido-header { font-size: 1.8rem; font-weight: bold; margin-bottom: 20px; }
        .pedido-details { flex-grow: 1; margin-bottom: 20px; }
        
        #pedido-info { min-height: 200px; }
        
        .item-list { list-style: none; padding: 0; margin: 15px 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dotted #ccc; }
        .item-qty { font-weight: bold; margin-right: 10px; }
        
        .pedido-footer { border-top: 1px solid #ccc; padding-top: 15px; }
        .footer-row { display: flex; justify-content: flex-end; font-weight: bold; margin-bottom: 5px; }

        #finalizar-registro { width: 100%; padding: 15px; background: red; color: white; border: none; font-size: 1.1em; cursor: pointer; margin-top: 10px; }
        #finalizar-registro:disabled { background: #ccc; cursor: not-allowed; }
        
        .alert, .erro { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert { background-color: #d4edda; color: #155724; }
        .erro { background-color: #f8d7da; color: #721c24; }
    </style>
<body>
    <header>
        RCL
        <a href="home.php" class="voltar">Voltar</a>
    </header>

    <main>
        <div class="left-panel">
            <div class="code-container">
                <h1>Código de Retirada</h1>
                <input type="text" id="codigo-retirada" placeholder="Digite o código" onkeyup="buscarPedido()">
                <div id="status-busca" style="margin-top: 15px; color: blue;"></div>
            </div>
        </div>

        <div class="right-panel">
            <div class="pedido-header">Pedido</div>
            
            <?php if ($msg): ?><div class="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($erro): ?><div class="erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

            <div class="pedido-details" id="pedido-info">
                <p>Aguardando o Código de Retirada...</p>
            </div>

            <div class="pedido-footer">
                <div class="footer-row">
                    <span id="total-itens-exibicao">Total: 0 itens</span>
                </div>
                
                <form method="POST" action="entrega_pedido.php" id="form-finalizar">
                    <input type="hidden" name="id_pedido_concluir" id="id-pedido-concluir" value="">
                    <button type="submit" name="finalizar_pedido" id="finalizar-registro" disabled>
                        Finalizar Pedido
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <script src="js/entrega_pedido.js"></script> 
</body>
</html>