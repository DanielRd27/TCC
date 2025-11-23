<?php
require_once '../autenticacao.php';
require_once '../db.php';

$pdo = conectar();

/* ============================
   BUSCAR PEDIDOS DO ALUNO
============================ */
$sql = "
    SELECT 
        p.id_pedido,
        p.status,
        p.codigo_retirada,
        p.forma_pagamento,
        p.created_at,
        i.horario_inicio,
        GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.nome) SEPARATOR '||') as itens
    FROM pedidos p
    INNER JOIN intervalos i ON p.intervalo = i.id_intervalo
    INNER JOIN itens_pedido ip ON p.id_pedido = ip.id_pedido
    INNER JOIN produtos pr ON ip.id_produto = pr.id_produto
    WHERE p.id_aluno = :id_aluno
    GROUP BY p.id_pedido
    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_aluno' => $_SESSION['aluno_id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   FUNÇÃO PARA FORMATAR STATUS
============================ */
function formatarStatus($status) {
    // Agora mantemos "Concluído" como está, já que é o valor do banco
    $status_map = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'preparando' => 'Preparando',
        'pronto' => 'Pronto',
        'Concluído' => 'Concluído', // Mantém "Concluído" igual
        'cancelado' => 'Cancelado'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

/* ============================
   FUNÇÃO PARA COR DO STATUS
============================ */
function corStatus($status) {
    switch($status) {
        case 'pendente': return 'orange';
        case 'confirmado': return 'blue';
        case 'preparando': return 'yellow';
        case 'pronto': return 'green';
        case 'Concluído': return 'green'; // Agora é "Concluído"
        case 'cancelado': return 'red';
        default: return 'gray';
    }
}

/* ============================
   SEPARAR PEDIDOS ATIVOS E HISTÓRICO - CORRIGIDO
============================ */
$pedidos_ativos = [];
$pedidos_historico = [];

foreach ($pedidos as $pedido) {
    // ATIVOS: tudo que NÃO é "Concluído" ou "cancelado"
    // HISTÓRICO: apenas "Concluído" e "cancelado"
    if (in_array($pedido['status'], ['Concluído', 'cancelado'])) {
        $pedidos_historico[] = $pedido;
    } else {
        $pedidos_ativos[] = $pedido;
    }
}

/* ============================
   FUNÇÃO PARA FORMATAR DATA
============================ */
function formatarData($data) {
    $data_obj = new DateTime($data);
    $hoje = new DateTime();
    $ontem = clone $hoje;
    $ontem->modify('-1 day');
    
    if ($data_obj->format('Y-m-d') === $hoje->format('Y-m-d')) {
        return 'Hoje';
    } elseif ($data_obj->format('Y-m-d') === $ontem->format('Y-m-d')) {
        return 'Ontem';
    } else {
        return 'Dia ' . $data_obj->format('d/m');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">
    <main class="mobile-content align-center background-pedidos">
        
        <!-- Mostra pedidos ativos (tudo que NÃO é "Concluído" ou "cancelado") -->
        <section class="pedidos kanit-regular">
            <h1>Pedidos Ativos</h1>
            
            <?php if (empty($pedidos_ativos)): ?>
                <div class="card-pedido">
                    <p style="text-align:center; padding:20px;">Nenhum pedido ativo</p>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos_ativos as $pedido): ?>
                    <div class="card-pedido">
                        <div class="data-pedido">
                            <p><?= formatarData($pedido['created_at']) ?></p> 
                            <span class="horario">Para <?= date("H:i", strtotime($pedido['horario_inicio'])) ?></span>
                        </div>
                        
                        <div class="itens-container">
                            <?php 
                            $itens = explode('||', $pedido['itens']);
                            foreach ($itens as $item): 
                                list($quantidade, $nome) = explode('x ', $item, 2);
                            ?>
                                <div class="item-pedido">
                                    <div class="item-quantidade"><?= $quantidade ?></div>
                                    <p><?= htmlspecialchars($nome) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="status-pedido">
                            <p>Pedido: <span class="<?= corStatus($pedido['status']) ?>">
                                <?= formatarStatus($pedido['status']) ?>
                            </span></p>
                            
                            <?php if ($pedido['status'] === 'pronto'): ?>
                                <div class="codigo-retirada">
                                    <strong>Código: <?= $pedido['codigo_retirada'] ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array($pedido['status'], ['pendente', 'confirmado', 'preparando'])): ?>
                                <div class="info-aguarde">
                                    <small>Aguarde o preparo para ver o código de retirada</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        
        <!-- Mostra pedidos já finalizados (apenas "Concluído" e "cancelado") -->
        <section class="pedidos kanit-regular">
            <h1>Histórico</h1>
            
            <?php if (empty($pedidos_historico)): ?>
                <div class="card-pedido">
                    <p style="text-align:center; padding:20px;">Nenhum pedido no histórico</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($pedidos_historico as $pedido): ?>
                        <div class="card-pedido">
                            <div class="data-pedido">
                                <p><?= formatarData($pedido['created_at']) ?></p> 
                                <span class="horario">Para <?= date("H:i", strtotime($pedido['horario_inicio'])) ?></span>
                            </div>
                            
                            <div class="itens-container">
                                <?php 
                                $itens = explode('||', $pedido['itens']);
                                foreach ($itens as $item): 
                                    list($quantidade, $nome) = explode('x ', $item, 2);
                                ?>
                                    <div class="item-pedido">
                                        <div class="item-quantidade"><?= $quantidade ?></div>
                                        <p><?= htmlspecialchars($nome) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="status-pedido">
                                <p>Pedido: <span class="<?= corStatus($pedido['status']) ?>">
                                    <?= formatarStatus($pedido['status']) ?>
                                </span></p>
                                
                                <?php if ($pedido['status'] === 'Concluído'): ?>
                                    <div class="codigo-retirada">
                                        <small>Código usado: <?= $pedido['codigo_retirada'] ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

     <!-- Footer permanente como navBar -->
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

    <script src="script.js"></script>
</body>
</html>