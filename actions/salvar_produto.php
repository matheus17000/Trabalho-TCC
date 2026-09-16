<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/log.php';

$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['deletar'])) {
    $id = (int) $_GET['deletar'];
    $row = $conn->query("SELECT nome FROM produtos WHERE id = $id")->fetch_assoc();
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param('ii', $id, $empresa_id);
    $stmt->execute();
    registrar_log($conn, $empresa_id, $usuario_id, 'produtos', 'exclusao', 'Produto excluído: ' . ($row['nome'] ?? ''));
    header('Location: /pages/produtos.php?sucesso=deletado');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome']);
    $categoria = trim($_POST['categoria']);
    $descricao = trim($_POST['descricao']);
    $preco_compra = (float) str_replace(',', '.', str_replace('.', '', $_POST['preco_compra']));
    $preco_venda = (float) str_replace(',', '.', str_replace('.', '', $_POST['preco_venda']));
    $estoque = (int) $_POST['estoque'];

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE produtos SET nome=?, categoria=?, descricao=?, preco_compra=?, preco_venda=?, estoque=? WHERE id=? AND empresa_id=?");
        $stmt->bind_param('sssddiii', $nome, $categoria, $descricao, $preco_compra, $preco_venda, $estoque, $id, $empresa_id);
        $stmt->execute();
        registrar_log($conn, $empresa_id, $usuario_id, 'produtos', 'edicao', 'Produto editado: ' . $nome);
        header('Location: /pages/produtos.php?sucesso=editado');
    } else {
        $stmt = $conn->prepare("INSERT INTO produtos (empresa_id, nome, categoria, descricao, preco_compra, preco_venda, estoque) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('isssddi', $empresa_id, $nome, $categoria, $descricao, $preco_compra, $preco_venda, $estoque);
        $stmt->execute();
        registrar_log($conn, $empresa_id, $usuario_id, 'produtos', 'cadastro', 'Produto cadastrado: ' . $nome);
        header('Location: /pages/produtos.php?sucesso=criado');
    }
    exit;
}

header('Location: /pages/produtos.php');
exit;