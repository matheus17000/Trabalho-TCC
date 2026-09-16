<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
$titulo = 'Relatórios';
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

            <h3 class="text-lg font-semibold text-gray-700 mb-6">Relatórios</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Relatório de Vendas -->
                <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col">
                    <div class="text-4xl mb-4 text-center">💰</div>
                    <h4 class="text-base font-semibold text-gray-800 mb-1 text-center">Relatório de Vendas</h4>
                    <p class="text-sm text-gray-500 mb-4 text-center">Lista vendas com cliente, data e total.</p>
                    <form method="GET" action="/pdf/relatorio_vendas.php" target="_blank" class="mt-auto">
                        <div class="mb-3">
                            <label class="block text-xs text-gray-500 mb-1">Data início</label>
                            <input type="date" name="data_ini"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs text-gray-500 mb-1">Data fim</label>
                            <input type="date" name="data_fim"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                            Gerar PDF
                        </button>
                    </form>
                </div>

                <!-- Relatório de Estoque -->
                <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col">
                    <div class="text-4xl mb-4 text-center">📦</div>
                    <h4 class="text-base font-semibold text-gray-800 mb-1 text-center">Relatório de Estoque</h4>
                    <p class="text-sm text-gray-500 mb-4 text-center">Lista produtos com quantidade em estoque.</p>
                    <form method="GET" action="/pdf/relatorio_estoque.php" target="_blank" class="mt-auto">
                        <div class="mb-3">
                            <label class="block text-xs text-gray-500 mb-1">Filtrar por categoria</label>
                            <input type="text" name="categoria" placeholder="Ex: Bebidas"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs text-gray-500 mb-1">Status do estoque</label>
                            <select name="estoque"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todos</option>
                                <option value="baixo">Estoque baixo (menos de 5)</option>
                                <option value="zero">Sem estoque</option>
                            </select>
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                            Gerar PDF
                        </button>
                    </form>
                </div>

                <!-- Relatório Financeiro -->
                <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col">
                    <div class="text-4xl mb-4 text-center">📊</div>
                    <h4 class="text-base font-semibold text-gray-800 mb-1 text-center">Relatório Financeiro</h4>
                    <p class="text-sm text-gray-500 mb-4 text-center">Resume compras, vendas e lucro estimado.</p>
                    <form method="GET" action="/pdf/relatorio_financeiro.php" target="_blank" class="mt-auto">
                        <div class="mb-3">
                            <label class="block text-xs text-gray-500 mb-1">Data início</label>
                            <input type="date" name="data_ini"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs text-gray-500 mb-1">Data fim</label>
                            <input type="date" name="data_fim"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                            Gerar PDF
                        </button>
                    </form>
                </div>

            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

</body>
</html>