<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$empresa_id = $_SESSION['empresa_id'];

if (isset($_GET['deletar'])) {
    $id = (int) $_GET['deletar'];
    // Proteção: não deixa excluir o próprio usuário logado
    if ($id === (int) $_SESSION['usuario_id']) {
        header('Location: /pages/usuarios.php');
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param('ii', $id, $empresa_id);
    $stmt->execute();
    header('Location: /pages/usuarios.php?sucesso=deletado');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int) ($_POST['id'] ?? 0);
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $nivel = $_POST['nivel'] === 'admin' ? 'admin' : 'operador';

    if ($id > 0) {
        // Editar — senha só atualiza se preenchida
        if (!empty($senha)) {
            $hash = password_hash($senha, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, senha=?, nivel=? WHERE id=? AND empresa_id=?");
            $stmt->bind_param('ssssii', $nome, $email, $hash, $nivel, $id, $empresa_id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, nivel=? WHERE id=? AND empresa_id=?");
            $stmt->bind_param('sssii', $nome, $email, $nivel, $id, $empresa_id);
        }
        $stmt->execute();
        header('Location: /pages/usuarios.php?sucesso=editado');
    } else {
        // Criar
        if (empty($senha)) {
            header('Location: /pages/usuarios.php?erro=senha_obrigatoria');
            exit;
        }
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, nivel) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $empresa_id, $nome, $email, $hash, $nivel);
        if (!$stmt->execute()) {
            header('Location: /pages/usuarios.php?erro=email_duplicado');
            exit;
        }
        header('Location: /pages/usuarios.php?sucesso=criado');
    }
    exit;
}

header('Location: /pages/usuarios.php');
exit;