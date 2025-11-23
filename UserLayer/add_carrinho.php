<?php
session_start();
require_once '../autenticacao.php';

// garante que tem um carrinho
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$id = intval($_POST['id_produto'] ?? 0);
$quantidade = intval($_POST['quantidade'] ?? 1);

if ($id > 0) {

    // se o produto já está no carrinho, soma
    if (isset($_SESSION['carrinho'][$id])) {
        $_SESSION['carrinho'][$id] += $quantidade;
    } 
    else {
        $_SESSION['carrinho'][$id] = $quantidade;
    }
}

// redireciona de volta para lista de produtos
header("Location: busca.php");
exit;
