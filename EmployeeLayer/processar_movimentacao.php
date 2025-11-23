<?php
session_start();
// O AJAX deve enviar JSON, não POST tradicional
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['movimentos']) || !is_array($data['movimentos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados de movimento inválidos.']);
    exit();
}

require_once '../db.php';
$pdo = conectar();

$movimentos = $data['movimentos'];
$funcionario_id = $data['funcionario_id'];
$observacao = $data['observacao'] ?? null;

try {
    $pdo->beginTransaction();

    // 1. Atualizar o Estoque na tabela 'produtos'
    $stmt_update = $pdo->prepare("
        UPDATE produtos 
        SET estoque = estoque + :quantidade 
        WHERE id_produto = :id_produto
    ");

    // 2. Inserir na tabela 'movimentacao_estoque'
    $stmt_insert = $pdo->prepare("
        INSERT INTO movimentacao_estoque (id_produto, tipo_movimentacao, quantidade, data_movimentacao, movimentado_by, observacao)
        VALUES (:id_produto, :tipo, :quantidade_abs, NOW(), :movimentado_by, :observacao)
    ");

    foreach ($movimentos as $mov) {
        $id_produto = $mov['id_produto'];
        $quantidade = $mov['quantidade'];
        $quantidade_abs = abs($quantidade);
        
        // Isto é o que define o TIPO
        $tipo = ($quantidade > 0) ? 'Entrada' : 'Saída'; 

        // Atualiza o estoque (mantido)
        $stmt_update->execute([
            'quantidade' => $quantidade,
            'id_produto' => $id_produto
        ]);

        // Insere o registro de movimentação, garantindo que 'tipo' seja Saída ou Entrada
        $stmt_insert->execute([
            'id_produto' => $id_produto,
            'tipo' => $tipo, // <<< AQUI REGISTRA SAÍDA OU ENTRADA
            'quantidade_abs' => $quantidade_abs,
            'movimentado_by' => $funcionario_id,
            'observacao' => $observacao
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Movimentação registrada com sucesso!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro desconhecido: ' . $e->getMessage()]);
}
?>