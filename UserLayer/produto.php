<?php
require_once '../autenticacao.php';
require_once '../db.php';

verifica_aluno();

$pdo = conectar();

// RECEBE O ID
$id = $_GET['id'] ?? '';

if ($id === '' || !is_numeric($id)) {
    echo "Produto inválido!";
    exit;
}

// CONSULTA O PRODUTO COMPLETO
$stmt = $pdo->prepare("
    SELECT id_produto, nome, categoria, descricao, preco_unitario, imagem
    FROM produtos
    WHERE id_produto = :id
");
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['nome']) ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">

<main class="mobile-content align-center background-produto">

    <a href="busca.php" class="red"><</a>

    <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="" style="max-width: 450px; border-radius: 12px;">

    <div class="produto-detelhado">

        <div class="name-and-price kanit-regular">
            <p><?= htmlspecialchars($produto['nome']) ?></p>
            <span>R$ <?= number_format($produto['preco_unitario'], 2, ',', '.') ?></span>
        </div>

        <div class="ingredientes kanit-regular">
            <p>Descricao:</p>
            <ul>
                <?php 
                // ingredientes no banco: "Pão;Hambúrguer;Queijo"
                $lista = explode(";", $produto['descricao']);
                foreach ($lista as $ing):
                ?>
                <li><?= htmlspecialchars($ing) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="options-produto">
            <div class="barra-cinza-horizontal"></div>

            <form method="POST" action="add_carrinho.php">
                <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                <input type="hidden" id="quantidade-produto-form" name="quantidade" value="1">

                <div class="quantidade">
                    <p class="menos" onclick="increaseQuantity(-1)">-</p>
                    <span id="quantidade-produto" class="quantidade-produto kanit-regular">1</span>
                    <p class="mais" onclick="increaseQuantity(1)">+</p>
                </div>

                <button class="add-to-carrinho kanit-regular">
                    <p>Adicionar</p><span id="result">R$ <?= $produto['preco_unitario'] ?></span>
                </button>
            </form>
        </div>

    </div>
</main>

<script>
    let preco = <?= $produto['preco_unitario'] ?>;

    function increaseQuantity(valor) {
        let q = document.getElementById('quantidade-produto');
        let n = parseInt(q.innerText);

        n += valor;
        if (n < 1) n = 1;

        q.innerText = n;

        // 🔥 ATUALIZA O VALOR DO INPUT HIDDEN
        document.getElementById('quantidade-produto-form').value = n;

        document.getElementById('result').innerText =
            "R$ " + (n * preco).toFixed(2).replace('.', ',');
    }

</script>

</body>
</html>
