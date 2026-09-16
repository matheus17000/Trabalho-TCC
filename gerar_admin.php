<?php
require_once 'conexao.php';

$nome      = 'Administrador';
$email     = 'admin@erp.com';
$senha     = password_hash('admin123', PASSWORD_BCRYPT);
$empresa_id = 1;
$nivel     = 'admin';

$stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('issss', $empresa_id, $nome, $email, $senha, $nivel);

if ($stmt->execute()) {
    echo 'Usuário admin criado com sucesso!';
} else {
    echo 'Erro: ' . $conn->error;
}