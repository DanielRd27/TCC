<?php
header('Content-Type: application/json');
require_once '../db.php';

$pdo = conectar();
const STATUS_PRONTO = 'Pronto'; // Busca apenas pedidos prontos para entrega

if (!isset($_POST['codigo_retirada']) || empty($_POST['codigo_retirada'])) {
    echo json_encode(['success' => false, 'message' => 'Código de retirada não fornecido.']);
    exit();
}

$codigo_retirada = $_POST['codigo_retirada'];

try {
    // 1. Buscar o pedido principal e o nome do aluno
    $sql_pedido = "
        SELECT 
            p.id_pedido, p.status, a.nome AS nome_aluno
        FROM 
            pedidos p
        JOIN 
            alunos a ON p.id_aluno = a.id_aluno
        WHERE 
            p.codigo_retirada = :codigo AND p.status = :status_pronto
    ";
    
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([
        'codigo' => $codigo_retirada,
        'status_pronto' => STATUS_PRONTO
    ]);
    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Nenhum pedido PRONTO encontrado com este código.']);
        exit();
    }

    $id_pedido = $pedido['id_pedido'];

    // 2. Buscar os itens do pedido
    $sql_itens = "
        SELECT 
            ip.quantidade, prod.nome AS nome_produto
        FROM 
            itens_pedido ip
        JOIN 
            produtos prod ON ip.id_produto = prod.id_produto
        WHERE 
            ip.id_pedido = :id_pedido
    ";
    
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute(['id_pedido' => $id_pedido]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

    // 3. Montar a resposta final
    $pedido['itens'] = $itens;

    echo json_encode(['success' => true, 'pedido' => $pedido]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}