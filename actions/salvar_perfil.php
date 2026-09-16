<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/log.php';

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$acao       = $_POST['acao'] ?? '';

if ($acao === 'dados') {
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);

    // Verifica e-mail duplicado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND empresa_id = ? AND id != ?");
    $stmt->bind_param('sii', $email, $empresa_id, $usuario_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: /pages/perfil.php?erro=email_duplicado');
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=? WHERE id=? AND empresa_id=?");
    $stmt->bind_param('ssii', $nome, $email, $usuario_id, $empresa_id);
    $stmt->execute();

    // Atualiza sessão
    $_SESSION['usuario_nome'] = $nome;

    registrar_log($conn, $empresa_id, $usuario_id, 'perfil', 'edicao', 'Dados do perfil atualizados');
    header('Location: /pages/perfil.php?sucesso=1');
    exit;
}

if ($acao === 'senha') {
    $senha_atual    = $_POST['senha_atual'];
    $nova_senha     = $_POST['nova_senha'];
    $confirmar      = $_POST['confirmar_senha'];

    // Verifica senha atual
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param('ii', $usuario_id, $empresa_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($senha_atual, $row['senha'])) {
        header('Location: /pages/perfil.php?erro=senha_invalida');
        exit;
    }

    $hash = password_hash($nova_senha, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE usuarios SET senha=? WHERE id=? AND empresa_id=?");
    $stmt->bind_param('sii', $hash, $usuario_id, $empresa_id);
    $stmt->execute();

    registrar_log($conn, $empresa_id, $usuario_id, 'perfil', 'senha', 'Senha alterada');
    header('Location: /pages/perfil.php?sucesso=1');
    exit;
}

header('Location: /pages/perfil.php');
exit;