<?php
session_start();
$titulo = 'Página não encontrada';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>404 — ERP System</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="text-center">
        <p class="text-8xl font-bold text-blue-600 mb-4">404</p>
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Página não encontrada</h1>
        <p class="text-gray-500 text-sm mb-8">A página que você tentou acessar não existe ou foi removida.</p>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="/pages/dashboard.php"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-3 rounded-lg transition">
                Voltar ao Dashboard
            </a>
        <?php else: ?>
            <a href="/auth/login.php"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-3 rounded-lg transition">
                Ir para o Login
            </a>
        <?php endif; ?>
    </div>

</body>
</html>