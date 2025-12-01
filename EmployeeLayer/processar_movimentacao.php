<?php
require_once '../autenticacao.php'; 
require_once '../db.php';  
verifica_funcionario(); 

// HABILITA DEBUG AGRESSIVO
error_reporting(E_ALL);
ini_set('display_errors', 1);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['movimentos']) || !is_array($data['movimentos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados de movimento inválidos.']);
    exit();
}

$pdo = conectar();

// ⚠️ CONFIGURAÇÕES CRÍTICAS DO PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false); // ⬅️ DESATIVA AUTOCOMMIT

$movimentos = $data['movimentos'];
$funcionario_id = $data['funcionario_id'];
$observacao = $data['observacao'] ?? '';

try {
    // ⚠️ INICIA TRANSAÇÃO EXPLICITAMENTE
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // 1. Atualizar o Estoque
    $stmt_update = $pdo->prepare("
        UPDATE produtos 
        SET estoque = estoque + :quantidade 
        WHERE id_produto = :id_produto
    ");

    // 2. Inserir na movimentacao_estoque
    $stmt_insert = $pdo->prepare("
        INSERT INTO movimentacao_estoque (id_produto, tipo_movimentacao, quantidade, data_movimentacao, movimentado_by, observacao)
        VALUES (:id_produto, :tipo_movimentacao, :quantidade, NOW(), :movimentado_by, :observacao)
    ");

    $inserted_count = 0;

    foreach ($movimentos as $mov) {
        $id_produto = $mov['id_produto'];
        $quantidade = $mov['quantidade'];
        $tipo = ($quantidade > 0) ? 'entrada' : 'saida';

        // Atualiza o estoque
        $stmt_update->execute([
            'quantidade' => $quantidade,
            'id_produto' => $id_produto
        ]);

        // Insere o registro de movimentação
        $stmt_insert->execute([
            'id_produto' => $id_produto,
            'tipo_movimentacao' => $tipo,
            'quantidade' => $quantidade,
            'movimentado_by' => $funcionario_id,
            'observacao' => $observacao
        ]);

        // Verifica se realmente inseriu
        if ($stmt_insert->rowCount() > 0) {
            $inserted_count++;
        } else {
            throw new Exception("Falha silenciosa: Nenhuma linha inserida para produto $id_produto");
        }
    }

    // ⚠️ COMMIT EXPLÍCITO
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Movimentação registrada com sucesso!',
        'debug' => [
            'movimentos_processados' => count($movimentos),
            'registros_inseridos' => $inserted_count
        ]
    ]);
    exit();

} catch (PDOException $e) {
    // ⚠️ ROLLBACK EXPLÍCITO
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ERRO NO BANCO: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'error_info' => $e->errorInfo ?? null
        ]
    ]);
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
    exit();
}
?>