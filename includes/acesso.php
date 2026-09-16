<?php
function verificar_acesso($nivel_minimo = 'admin') {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
    if ($nivel_minimo === 'admin' && $_SESSION['usuario_nivel'] !== 'admin') {
        header('Location: /pages/dashboard.php?erro=acesso');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}

function is_operador() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'operador';
}