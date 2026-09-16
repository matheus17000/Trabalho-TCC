<?php
function registrar_log($conn, $empresa_id, $usuario_id, $modulo, $acao, $descricao) {
    $stmt = $conn->prepare("INSERT INTO logs (empresa_id, usuario_id, modulo, acao, descricao) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iisss', $empresa_id, $usuario_id, $modulo, $acao, $descricao);
    $stmt->execute();
}