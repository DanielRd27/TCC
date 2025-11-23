<?php
require_once '../autenticacao.php';
require_once '../db.php';

verifica_aluno();

$acao = $_GET['acao'] ?? '';
$filtro = $_GET['filtro'] ?? 'todos';
$busca = trim($_GET['busca'] ?? '');

$pdo = conectar();

/* =======================
   MONTAGEM DA CONSULTA
==========================*/
$sql = "
    SELECT id_produto, nome, categoria, descricao, preco_unitario, estoque, estoque_minimo, imagem
    FROM produtos
    WHERE 1 = 1
";

$params = [];

/* 🔍 BUSCA */
if ($acao === "buscar" && $busca !== "") {
    $sql .= " AND nome LIKE :busca";
    $params[':busca'] = "%$busca%";
}

/* 🧩 FILTRO */
if ($acao === "filtrar" && $filtro !== "todos") {
    $sql .= " AND categoria = :categoria";
    $params[':categoria'] = $filtro;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   CATEGORIAS
==========================*/
$stmt_categorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM produtos
    WHERE categoria <> 'Geral'
    ORDER BY categoria
");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);

/* =======================
   CARRINHO FLUTUANTE
==========================*/
$total_itens = 0;
$total_valor = 0;

if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $id_produto => $qtd) {

        $stmt_v = $pdo->prepare("SELECT preco_unitario FROM produtos WHERE id_produto = :id");
        $stmt_v->execute([':id' => $id_produto]);
        $preco = $stmt_v->fetchColumn();

        if ($preco !== false) {
            $total_itens += $qtd;
            $total_valor += $qtd * $preco;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="align-center">

    <div class="backgound-header-back-busca"></div>

    <header class="background-header header-busca mobile-content">
        <div class="nav-bar-header">
            <form style="width: 100%;" method="GET" action="busca.php">
                <input 
                    type="text"
                    name="busca"
                    id="busca"
                    value="<?= htmlspecialchars($busca) ?>"
                    placeholder="O que vai querer hoje?"
                    class="input-busca"
                    oninput="buscar()"
                >
                <input type="hidden" name="acao" value="buscar">
            </form>
        </div>
    </header>

    <main class="mobile-content align-center background-busca">

        <!-- FILTRO -->
        <section class="kanit-regular filtros-busca">
            <form method="GET" action="busca.php">
                <span>Filtro:</span>
                <div class="barra-cinza-vertical"></div>

                <label for="filtro">Categoria:</label>

                <select name="filtro" id="filtro" onchange="this.form.submit()">
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>
                        Todos
                    </option>

                    <?php foreach ($categorias as $c): ?>
                        <option 
                            value="<?= htmlspecialchars($c) ?>"
                            <?= $filtro === $c ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <input type="hidden" name="acao" value="filtrar">
            </form>
        </section>

        <!-- LISTA DE PRODUTOS -->
        <section class="cards-produtos-busca" id="lista-produtos">
            <?php foreach ($produtos as $p): ?>
                <a class="produto-card-busca" href="produto.php?id=<?= $p['id_produto'] ?>">
                    <div class="image-produto-busca">
                        <img src="<?= htmlspecialchars($p['imagem']) ?>">
                    </div>

                    <div class="info-produto-busca">
                        <span class="nome-produto-busca kanit-regular">
                            <?= htmlspecialchars($p['nome']) ?>
                        </span>

                        <span class="preco-produto-busca bold kanit-regular">
                            R$ <?= number_format($p['preco_unitario'], 2, ',', '.') ?>
                        </span>
                    </div>
                </a>

                <div class="barra-cinza-horizontal"></div>
            <?php endforeach ?>
        </section>

    </main>

    <footer class="mobile-content">

        <!-- CARRINHO FLUTUANTE -->
        <?php if ($total_itens > 0): ?>
        <section class="carrinho-flutuante-container">
            <div class="carrinho-flutuante">
                <div class="itens-carrinho-flutuante">
                    <span class="black bold kanit-regular">Total:</span>
                    <span class="black bold kanit-regular">
                        R$ <?= number_format($total_valor, 2, ',', '.') ?>
                    </span>

                    <div class="quantidade-carrinho kanit-regular">
                        <?= $total_itens ?> item<?= $total_itens > 1 ? 's' : '' ?> no carrinho
                    </div>
                </div>
                <a class="to-carrinho kanit-regular" href="carrinho.php">Ver Sacola</a>
            </div>
        </section>
        <?php endif; ?>

        <!-- NAV BAR -->
        <div class="nav-bar">
            <div class="nav-item">
                <a href="home.php"><img src="icons/home.png"></a>
                <a href="home.php">Home</a>
            </div>

            <div class="nav-item">
                <a href="busca.php" class="icon-busca"><img src="icons/procurar.png"></a>
                <a href="busca.php">Busca</a>
            </div>

            <div class="nav-item">
                <a href="pedidos.php" class="icon-pedidos"><img src="icons/pedidos.png"></a>
                <a href="pedidos.php">Pedidos</a>
            </div>

            <div class="nav-item">
                <a href="perfil.php" class="icon-perfil"><img src="icons/perfil.png"></a>
                <a href="perfil.php">Perfil</a>
            </div>
        </div>
    </footer>

    <script>
    function buscar() {
        const termo = document.getElementById('busca').value;

        fetch(`busca.php?acao=buscar&busca=${encodeURIComponent(termo)}`)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, "text/html");

                document.querySelector("#lista-produtos").innerHTML =
                    doc.querySelector("#lista-produtos").innerHTML;
            });
    }
    </script>

</body>
</html>
