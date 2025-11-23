<?php
session_start();
require_once '../autenticacao.php';
verifica_funcionario(); 
require_once '../db.php';

$pdo = conectar();
$funcionario_id = $_SESSION['funcionario_id']; // ID do funcionário logado
$nome_funcionario = $_SESSION['funcionario_nome']; // Nome para exibir na lista (dropdown)

// Constantes de Limite de Estoque (pode ser ajustado ou lido de uma tabela)
const ESTOQUE_MINIMO_PADRAO = 10; 

// -----------------------------------------------------------
// 1. Busca de Produtos (com dados de Estoque)
// -----------------------------------------------------------

try {
    // Busca id, nome, estoque e estoque_minimo (assumindo que estão na tabela 'produtos')
    $stmt = $pdo->query("SELECT id_produto, nome, estoque, estoque_minimo, imagem FROM produtos ORDER BY nome ASC");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estoque - RCL</title>
    <style>
        /* (Seu CSS base, adaptado para o layout de duas colunas) */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; border-bottom: 2px solid #000; background: white; font-size: 1.5rem; font-weight: bold; }
        .voltar { color: red; text-decoration: none; font-size: 1rem; }
        
        main { display: flex; height: calc(100vh - 56px); } /* Calcula altura da tela - altura do header */

        /* --- Coluna da Esquerda: Produtos (Estoque) --- */
        .left-panel { flex: 3; padding: 2rem; background: white; overflow-y: auto; }
        .search-container { margin-bottom: 20px; }
        #pesquisar-produto { width: 100%; padding: 10px; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
        .product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }

        .product-card {
            border: 1px solid #ddd; 
            border-radius: 6px; 
            padding: 10px; 
            text-align: center; 
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
            position: relative;
        }

        .product-card:hover { background-color: #f0f0f0; }

        .product-name { font-weight: bold; margin-bottom: 5px; }
        .product-stock { font-size: 0.9em; }
        .stock-alert { color: red; font-weight: bold; }
        
        /* Imagem (Placeholder) */
        .product-image {
            width: 100%;
            height: 200px; /* Aumentado */
            background-color: #eee;
            border-radius: 4px;
            margin-bottom: 5px;
            
            /* Adicione estas propriedades para lidar com a imagem interna */
            overflow: hidden; 
            display: flex; /* Para centralizar se necessário */
            justify-content: center;
            align-items: center;
        }
        .product-image img {
            max-height: 180px;
            width: auto;  
            object-fit: contain;
        }
        
        /* --- Coluna da Direita: Lista de Movimentação --- */
        .right-panel { 
            flex: 1; 
            padding: 1rem; 
            background-color: #eee; 
            border-left: 2px solid #ccc; 
            display: flex; 
            flex-direction: column;
            min-width: 300px;
        }

        .list-header { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; }
        #movimentacao-list { flex-grow: 1; overflow-y: auto; list-style: none; padding: 0; }
        #movimentacao-list li { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 8px 0; 
            border-bottom: 1px dashed #ccc;
        }
        
        .item-controls { display: flex; align-items: center; }
        .qty-button { 
            width: 30px; 
            height: 30px; 
            background: red; 
            color: white; 
            border: none; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1.2em;
            line-height: 1;
        }
        
        .qty-input { width: 40px; text-align: center; border: 1px solid #ccc; height: 30px; margin: 0 5px; }

        /* --- Rodapé da Lista --- */
        .list-footer { border-top: 1px solid #ccc; padding-top: 15px; }
        .footer-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .footer-row label { font-weight: bold; }
        
        #finalizar-registro { width: 100%; padding: 15px; background: red; color: white; border: none; font-size: 1.1em; cursor: pointer; margin-top: 10px; }
        
    </style>
</head>
<body>
    <header>
        RCL
        <a href="home.php" class="voltar">Voltar</a>
    </header>

    <main>
        <div class="left-panel">
            <h1>Estoque Atual</h1>
            <div class="search-container">
                <input type="text" id="pesquisar-produto" placeholder="Pesquisar produto..." onkeyup="filterProducts()">
            </div>

            <div class="product-grid" id="product-grid">
                <?php foreach ($produtos as $produto): 
                    $estoque_minimo = $produto['estoque_minimo'] ?? ESTOQUE_MINIMO_PADRAO;
                    $em_alerta = $produto['estoque'] < $estoque_minimo;
                ?>
                    <div 
                        class="product-card" 
                        data-id="<?= $produto['id_produto'] ?>" 
                        data-name="<?= htmlspecialchars($produto['nome']) ?>"
                        onclick="addItemToMovement(<?= $produto['id_produto'] ?>, '<?= htmlspecialchars($produto['nome']) ?>')"
                    >
                        <div class="product-image">
                            <?php if (!empty($produto['imagem']) && file_exists($produto['imagem'])): ?>
                                <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="Img Prod">
                            <?php else: ?>
                            [Image of an image placeholder]
                            <?php endif; ?>
                        </div>
                        <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                        <div class="product-stock">
                            Estoque: <span class="<?= $em_alerta ? 'stock-alert' : '' ?>">
                                <?= $produto['estoque'] ?>
                            </span>
                            <?php if ($em_alerta): ?>
                                <br>(Mínimo: <?= $estoque_minimo ?>)
                                <span class="stock-alert">⚠️ ALERTA</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="right-panel">
            <div class="list-header">Movimentação</div>
            <ul id="movimentacao-list">
                </ul>

            <div class="list-footer">
                <div class="footer-row">
                    <label>Funcionário:</label>
                    <span><?= htmlspecialchars($nome_funcionario) ?></span>
                </div>
                <div class="footer-row">
                    <label>Total de Itens:</label>
                    <span id="total-itens-movimentados">0</span>
                </div>
                
                <input type="hidden" id="funcionario-id" value="<?= $funcionario_id ?>">
                
                <textarea id="observacao" placeholder="Observações (opcional)" style="width: 100%; margin-bottom: 10px;"></textarea>

                <button id="finalizar-registro" onclick="finalizeMovement()">
                    Finalizar Registro
                </button>
            </div>
        </div>
    </main>
    
    <script src="js/estoque_movimentacao.js"></script>
</body>
</html>