<?php
require_once '../autenticacao.php';
verifica_funcionario();
require_once '../db.php';

// Mapeamento dos STATUS que serão exibidos (usando os valores do banco)
const STATUS_PENDENTE = 'Pendente';
const STATUS_PREPARANDO = 'Preparando';
const STATUS_PRONTO = 'Pronto';
const STATUS_CONCLUIDO = 'Concluído';

$pdo = conectar();
$msg = $_GET['msg'] ?? ''; 
$erro = $_GET['erro'] ?? '';

// Processamento da Alteração de Status
if (isset($_GET['acao'], $_GET['id_pedido'], $_GET['status'])) {
    $id_pedido = (int)$_GET['id_pedido'];
    $novo_status = $_GET['status'];

    if (in_array($novo_status, [STATUS_PREPARANDO, STATUS_PRONTO, STATUS_CONCLUIDO])) {
        
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE pedidos SET 
                    status = ?, update_by = ?, updated_at = NOW() 
                WHERE id_pedido = ?
            ");
            
            $stmt->execute([
                $novo_status, 
                $_SESSION['funcionario_id'], 
                $id_pedido
            ]);

            $pdo->commit();
            $msg = "Status do Pedido #{$id_pedido} alterado para '" . formatarStatus($novo_status) . "' com sucesso.";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $erro = "Erro ao atualizar status: " . $e->getMessage();
        }
        
        header("Location: gerenciar_pedidos.php?msg=" . urlencode($msg) . ($erro ? "&erro=" . urlencode($erro) : ""));
        exit();
    } else {
        $erro = "Status inválido fornecido.";
    }
}

// Função para formatar status para exibição
function formatarStatus($status) {
    $status_map = [
        'pendente' => 'Pendente',
        'preparando' => 'Preparando',
        'pronto' => 'Pronto',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

// Obtenção dos Pedidos e Itens
$sql_dados = "
    SELECT 
        p.id_pedido, p.status, p.intervalo, p.forma_pagamento, p.created_at,
        ip.quantidade, 
        prod.nome AS nome_produto, 
        a.nome AS nome_aluno,
        i.horario_inicio
    FROM 
        pedidos p
    JOIN 
        itens_pedido ip ON p.id_pedido = ip.id_pedido
    JOIN 
        produtos prod ON ip.id_produto = prod.id_produto
    JOIN
        alunos a ON p.id_aluno = a.id_aluno
    JOIN 
        intervalos i ON p.intervalo = i.id_intervalo
    WHERE 
        p.status IN ('" . STATUS_PENDENTE . "', '" . STATUS_PREPARANDO . "', '" . STATUS_PRONTO . "')
    ORDER BY 
        i.horario_inicio ASC, p.id_pedido ASC
";

$stmt_dados = $pdo->query($sql_dados);
$dados_raw = $stmt_dados->fetchAll(PDO::FETCH_ASSOC);

// Estrutura de Agrupamento: [Intervalo] => [Status] => [ID_Pedido] => Detalhes
$pedidos_agrupados = [];

$status_para_exibir = [
    STATUS_PENDENTE, 
    STATUS_PREPARANDO, 
    STATUS_PRONTO
];

foreach ($dados_raw as $row) {
    $horario_agrupamento = $row['horario_inicio']; 
    $status = $row['status'];
    $id_pedido = $row['id_pedido'];
    
    if (!isset($pedidos_agrupados[$horario_agrupamento])) {
        $pedidos_agrupados[$horario_agrupamento] = [];
        foreach ($status_para_exibir as $s) {
             $pedidos_agrupados[$horario_agrupamento][$s] = [];
        }
    }
    
    if (!isset($pedidos_agrupados[$horario_agrupamento][$status][$id_pedido])) {
         $pedidos_agrupados[$horario_agrupamento][$status][$id_pedido] = [
             'id_pedido' => $id_pedido,
             'forma_pagamento' => $row['forma_pagamento'],
             'aluno' => $row['nome_aluno'],
             'horario_inicio' => $row['horario_inicio'],
             'itens' => []
         ];
    }
    
    $pedidos_agrupados[$horario_agrupamento][$status][$id_pedido]['itens'][] = [
        'quantidade' => $row['quantidade'],
        'nome_produto' => $row['nome_produto']
    ];
}

// Ordena por horário
uksort($pedidos_agrupados, function($a, $b) {
    return strtotime($a) <=> strtotime($b);
});
?>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gerenciamento de Pedidos - Kanban</title>
    <style>
        /* =======================================
        1. ESTILOS GERAIS E LAYOUT PRINCIPAL
        ======================================= */

        body { 
            margin: 0; 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f4; 
        }

        main { 
            padding: 2rem; 
        }

        /* --- Cabeçalho (Header) --- */
        header {
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            background: white;
        }

        header div {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        header a {
            color: red;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            margin-right: 10px;
        }

        /* --- Alertas e Erros --- */
        .alert, .erro { 
            padding: 10px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
        }

        .alert { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }

        .erro { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        /* =======================================
        2. ESTILOS KANBAN (Intervalos e Colunas)
        ======================================= */

        .intervalo-section { 
            margin-bottom: 30px; 
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .intervalo-header { 
            font-size: 1.5rem; 
            font-weight: bold; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #ccc; 
            padding-bottom: 10px;
            color: #333;
        }

        .kanban-board { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr);
            gap: 20px; 
        }

        .kanban-col { 
            background-color: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            border: 2px solid #dee2e6;
        }

        .col-title { 
            font-size: 1.1rem; 
            font-weight: bold; 
            margin-bottom: 15px; 
            text-align: center; 
            padding: 10px;
            border-radius: 4px;
        }

        /* =======================================
        3. ESTILOS DO CARD (Pedido)
        ======================================= */

        .card {
            background: white; 
            border-radius: 6px; 
            padding: 15px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            margin-bottom: 15px;
            border-left: 4px solid #ddd;
        }

        .card-header { 
            font-size: 1.1rem; 
            font-weight: bold; 
            margin-bottom: 10px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-aluno { 
            font-size: 0.9rem; 
            color: #555; 
            margin-bottom: 10px; 
            font-style: italic;
        }

        .card-codigo {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 10px;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            border-left: 3px solid #28a745;
        }

        /* --- Lista de Itens (Produtos) --- */
        .item-list { 
            list-style: none; 
            padding: 0; 
            margin: 0 0 10px 0; 
            border-top: 1px solid #eee; 
            padding-top: 10px; 
        }

        .item-list li { 
            display: flex; 
            justify-content: space-between; 
            padding: 4px 0; 
            font-size: 0.9rem;
            border-bottom: 1px solid #f8f9fa;
        }

        .item-list li:last-child {
            border-bottom: none;
        }

        .item-qty { 
            font-weight: bold; 
            width: 30px; 
            text-align: left; 
        }

        /* =======================================
        4. CORES DE STATUS
        ======================================= */

        .status-Pendente { 
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .status-Preparando { 
            background-color: #cce7ff;
            border-color: #b3d7ff;
            color: #004085;
        }

        .status-Pronto { 
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .text-pendente { color: #dc3545; }
        .text-preparando { color: #ffc107; }
        .text-pronto { color: #28a745; }

        /* =======================================
        5. AÇÕES (Rodapé do Card)
        ======================================= */

        .card-footer { 
            border-top: 1px solid #ddd; 
            text-align: center; 
            padding: 10px 0 0 0;
            margin-top: 10px;
        }

        .status-btn { 
            display: block; 
            padding: 10px 15px; 
            border: none; 
            font-weight: bold; 
            cursor: pointer; 
            text-decoration: none;
            color: white; 
            background: #007bff; 
            transition: background-color 0.2s;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .status-btn:hover { 
            background-color: #0056b3; 
            text-decoration: none;
            color: white;
        }

        .btn-preparando { background: #ffc107; color: #000; }
        .btn-preparando:hover { background: #e0a800; color: #000; }

        .btn-pronto { background: #28a745; }
        .btn-pronto:hover { background: #218838; }

        .current-status { 
            color: #6c757d; 
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <div>RCL - Olá, <?php echo htmlspecialchars($_SESSION['funcionario_nome']) ?></div>
        <div>
            <a href="home.php">Voltar</a>
        </div>
    </header>

    <main>
        <h1>Gerenciar Pedidos</h1>
        
        <?php if ($msg): ?>
            <div class="alert"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (empty($pedidos_agrupados)): ?>
            <p>Nenhum pedido pendente, em preparo ou pronto no momento.</p>
        <?php endif; ?>

        <?php foreach ($pedidos_agrupados as $horario_agrupamento => $colunas): ?>
            <div class="intervalo-section">
                <div class="intervalo-header">⏰ Para <?= date('H:i', strtotime($horario_agrupamento)) ?></div>
                
                <div class="kanban-board">
                    
                    <?php 
                    $status_map = [
                        STATUS_PENDENTE => [
                            'title' => 'Pedidos Pendentes', 
                            'class' => 'status-Pendente',
                            'text_class' => 'text-pendente'
                        ],
                        STATUS_PREPARANDO => [
                            'title' => 'Em Preparo', 
                            'class' => 'status-Preparando',
                            'text_class' => 'text-preparando'
                        ],
                        STATUS_PRONTO => [
                            'title' => 'Pronto para Entrega', 
                            'class' => 'status-Pronto',
                            'text_class' => 'text-pronto'
                        ],
                    ];
                    ?>
                    
                    <?php foreach ($status_map as $status_key => $col_data): ?>
                        <div class="kanban-col">
                            <div class="col-title <?= $col_data['class'] ?>">
                                <?= $col_data['title'] ?>
                            </div>
                            
                            <?php $pedidos_coluna = $colunas[$status_key] ?? []; ?>

                            <?php foreach ($pedidos_coluna as $id_pedido => $pedido): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <span>#<?= $id_pedido ?></span>
                                        <span class="<?= $col_data['text_class'] ?>">
                                            <?= formatarStatus($status_key) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="card-aluno">👤 <?= htmlspecialchars($pedido['aluno']) ?></div>
                                    
                                    <?php if ($status_key === STATUS_PRONTO): ?>
                                        <div class="card-codigo">
                                            🔑 Código: <strong>*****</strong>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <ul class="item-list">
                                        <?php foreach ($pedido['itens'] as $item): ?>
                                            <li>
                                                <span class="item-qty"><?= $item['quantidade'] ?>x</span> 
                                                <span><?= htmlspecialchars($item['nome_produto']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <div class="card-footer">
                                        <?php if ($status_key === STATUS_PENDENTE): ?>
                                            <a 
                                                class="status-btn btn-preparando" 
                                                href="gerenciar_pedidos.php?acao=status&id_pedido=<?= $id_pedido ?>&status=<?= STATUS_PREPARANDO ?>"
                                                onclick="return confirm('Mover Pedido #<?= $id_pedido ?> para PREPARANDO?');"
                                            >
                                                ▶️ Iniciar Preparo
                                            </a>
                                        <?php elseif ($status_key === STATUS_PREPARANDO): ?>
                                            <a 
                                                class="status-btn btn-pronto" 
                                                href="gerenciar_pedidos.php?acao=status&id_pedido=<?= $id_pedido ?>&status=<?= STATUS_PRONTO ?>"
                                                onclick="return confirm('Mover Pedido #<?= $id_pedido ?> para PRONTO?');"
                                            >
                                                ✅ Marcar como Pronto
                                            </a>
                                        <?php elseif ($status_key === STATUS_PRONTO): ?>
                                            <div class="current-status">
                                                ⏳ Aguardando retirada
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($pedidos_coluna)): ?>
                                <div style="text-align: center; color: #6c757d; padding: 20px;">
                                    Nenhum pedido
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>