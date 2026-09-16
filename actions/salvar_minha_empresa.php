<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/log.php';

$eid        = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']);
    $cnpj     = trim($_POST['cnpj']);
    $telefone = trim($_POST['telefone']);
    $email    = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE empresas SET nome=?, cnpj=?, telefone=?, email=? WHERE id=?");
    $stmt->bind_param('ssssi', $nome, $cnpj, $telefone, $email, $eid);
    $stmt->execute();

    registrar_log($conn, $eid, $usuario_id, 'empresa', 'edicao', 'Dados da empresa atualizados');

    header('Location: /pages/minha_empresa.php?sucesso=1');
    exit;
}

header('Location: /pages/minha_empresa.php');
exit;