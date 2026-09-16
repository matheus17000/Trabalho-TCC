<?php
date_default_timezone_set('America/Sao_Paulo');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$host    = $_ENV['HOST'];
$banco   = $_ENV['BANCO'];
$usuario = $_ENV['USUARIO'];
$senha   = $_ENV['SENHA'];

$conn = new mysqli($host, $usuario, $senha, $banco);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}