<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

if (isset($_GET['deletar'])) {
    $id = (int) $_GET['deletar'];
    $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: /pages/empresas.php?sucesso=deletado');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $nome     = trim($_POST['nome']);
    $cnpj     = trim($_POST['cnpj']);
    $telefone = trim($_POST['telefone']);
    $email    = trim($_POST['email']);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE empresas SET nome=?, cnpj=?, telefone=?, email=? WHERE id=?");
        $stmt->bind_param('ssssi', $nome, $cnpj, $telefone, $email, $id);
        $stmt->execute();
        header('Location: /pages/empresas.php?sucesso=editado');
    } else {
        $stmt = $conn->prepare("INSERT INTO empresas (nome, cnpj, telefone, email) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $nome, $cnpj, $telefone, $email);
        $stmt->execute();
        header('Location: /pages/empresas.php?sucesso=criado');
    }
    exit;
}

header('Location: /pages/empresas.php');
exit;