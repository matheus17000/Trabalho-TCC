<?php
session_start();
require_once '../includes/acesso.php';
verificar_acesso('admin');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Minha Empresa';
$eid = $_SESSION['empresa_id'];

$empresa = $conn->query("SELECT * FROM empresas WHERE id = $eid")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include '../includes/head.php'; ?>
</head>

<body class="bg-gray-100">

    <?php include '../includes/sidebar.php'; ?>

    <div class="lg:ml-64 flex flex-col min-h-screen">

        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 p-6">

            <h3 class="text-lg font-semibold text-gray-700 mb-6">Minha Empresa</h3>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    Dados atualizados com sucesso!
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
                <form method="POST" action="/actions/salvar_minha_empresa.php"
                    onsubmit="return validarMinhaEmpresa()">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome da empresa *</label>
                        <input type="text" name="nome" required maxlength="150"
                            value="<?= htmlspecialchars($empresa['nome']) ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                        <input type="text" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj']) ?>"
                            placeholder="00.000.000/0001-00" maxlength="18" oninput="mascaraCNPJ(this)"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-cnpj" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>"
                            placeholder="(00) 00000-0000" maxlength="15" oninput="mascaraTelefone(this)"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-telefone" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input type="email" name="email" maxlength="120"
                            value="<?= htmlspecialchars($empresa['email'] ?? '') ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-email" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                        Salvar alterações
                    </button>

                </form>
            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <script src="/assets/js/validacoes.js"></script>
    <script>
        function validarMinhaEmpresa() {
            limparTodosErros(['nome', 'cnpj', 'telefone', 'email']);
            let valido = true;

            const nome = document.querySelector('input[name="nome"]').value.trim();
            if (nome.length < 2) {
                mostrarErro('nome', 'Nome deve ter pelo menos 2 caracteres.');
                valido = false;
            }

            const cnpj = document.querySelector('input[name="cnpj"]').value.trim();
            if (cnpj !== '' && !validarCNPJ(cnpj)) {
                mostrarErro('cnpj', 'CNPJ inválido.');
                valido = false;
            }

            const telefone = document.querySelector('input[name="telefone"]').value.trim();
            if (telefone !== '' && !validarTelefone(telefone)) {
                mostrarErro('telefone', 'Telefone inválido.');
                valido = false;
            }

            const email = document.querySelector('input[name="email"]').value.trim();
            if (email !== '' && !validarEmail(email)) {
                mostrarErro('email', 'E-mail inválido.');
                valido = false;
            }

            return valido;
        }
    </script>

</body>

</html>