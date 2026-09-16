<?php
require_once 'conexao.php';

$usuarios = [
    [
        'empresa_id' => 3,
        'nome'       => 'Admin Barbearia',
        'email'      => 'admin@barbearipremium.com.br',
        'senha'      => 'Barber@123',
        'nivel'      => 'admin'
    ],
    [
        'empresa_id' => 4,
        'nome'       => 'Admin Burger',
        'email'      => 'admin@burgerhouse.com.br',
        'senha'      => 'Burger@123',
        'nivel'      => 'admin'
    ],
];

foreach ($usuarios as $u) {
    $hash = password_hash($u['senha'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, nivel) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $u['empresa_id'], $u['nome'], $u['email'], $hash, $u['nivel']);
    if ($stmt->execute()) {
        echo "Usuário {$u['email']} criado com sucesso!<br>";
    } else {
        echo "Erro ao criar {$u['email']}: " . $conn->error . "<br>";
    }
}