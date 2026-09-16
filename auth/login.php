<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../conexao.php';

    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmt = $conn->prepare("SELECT id, empresa_id, nome, senha, nivel FROM usuarios WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['empresa_id'] = $usuario['empresa_id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel'];
        header('Location: ../pages/dashboard.php');
        exit;
    }
} else {
    $erro = 'E-mail ou senha inválidos.';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="bg-white rounded-2xl shadow-md w-full max-w-md p-8">

        <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">ERP System</h1>
        <p class="text-gray-500 text-sm text-center mb-6">Acesse sua conta</p>

        <?php if ($erro): ?>
            <div class="bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="seu@email.com">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input type="password" name="senha" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="••••••••">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                Entrar
            </button>
        </form>
    </div>

</body>

</html>