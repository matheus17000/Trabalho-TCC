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
    // Pega nome antes de deletar
    $row = $conn->query("SELECT nome FROM clientes WHERE id = $id")->fetch_assoc();
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param('ii', $id, $empresa_id);
    $stmt->execute();
    registrar_log($conn, $empresa_id, $usuario_id, 'clientes', 'exclusao', 'Cliente excluído: ' . ($row['nome'] ?? ''));
    header('Location: /pages/clientes.php?sucesso=deletado');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $nome     = trim($_POST['nome']);
    $cpf      = trim($_POST['cpf']);
    $telefone = trim($_POST['telefone']);
    $email    = trim($_POST['email']);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE clientes SET nome=?, cpf=?, telefone=?, email=? WHERE id=? AND empresa_id=?");
        $stmt->bind_param('ssssii', $nome, $cpf, $telefone, $email, $id, $empresa_id);
        $stmt->execute();
        registrar_log($conn, $empresa_id, $usuario_id, 'clientes', 'edicao', 'Cliente editado: ' . $nome);
        header('Location: /pages/clientes.php?sucesso=editado');
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (empresa_id, nome, cpf, telefone, email) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $empresa_id, $nome, $cpf, $telefone, $email);
        $stmt->execute();
        registrar_log($conn, $empresa_id, $usuario_id, 'clientes', 'cadastro', 'Cliente cadastrado: ' . $nome);
        header('Location: /pages/clientes.php?sucesso=criado');
    }
    exit;
}

header('Location: /pages/clientes.php');
exit;