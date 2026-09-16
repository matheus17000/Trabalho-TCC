<?php
$pagina_atual = basename($_SERVER['PHP_SELF']);
$admin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
?>

<!-- Overlay para fechar sidebar no mobile -->
<div id="sidebar-overlay" onclick="fecharSidebar()"
    class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<aside id="sidebar"
    class="w-64 min-h-screen bg-gray-900 text-white flex flex-col fixed top-0 left-0 z-40
           transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="px-6 py-5 border-b border-gray-700 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">ERP System</h1>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></p>
            <?php if ($admin): ?>
                <span class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full mt-1 inline-block">Admin</span>
            <?php else: ?>
                <span class="text-xs bg-gray-600 text-white px-2 py-0.5 rounded-full mt-1 inline-block">Operador</span>
            <?php endif; ?>
        </div>
        <!-- Botão fechar no mobile -->
        <button onclick="fecharSidebar()" class="lg:hidden text-gray-400 hover:text-white">
            ✕
        </button>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">

        <a href="/erp-tcc/pages/dashboard.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'dashboard.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            📊 Dashboard
        </a>

        <?php if ($admin): ?>
        <a href="/erp-tcc/pages/empresas.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'empresas.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            🏢 Empresas
        </a>
        <a href="/erp-tcc/pages/usuarios.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'usuarios.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            👤 Usuários
        </a>
        <?php endif; ?>

        <a href="/erp-tcc/pages/clientes.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'clientes.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            👥 Clientes
        </a>

        <a href="/erp-tcc/pages/produtos.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'produtos.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            📦 Produtos
        </a>

        <div class="pt-4 pb-1">
            <p class="text-xs text-gray-500 uppercase px-4">Movimentações</p>
        </div>

        <a href="/erp-tcc/pages/compras.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'compras.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            🛒 Compras
        </a>

        <a href="/erp-tcc/pages/vendas.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'vendas.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            💰 Vendas
        </a>

        <a href="/erp-tcc/pages/relatorios.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'relatorios.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            📄 Relatórios
        </a>

        <?php if ($admin): ?>
        <a href="/erp-tcc/pages/minha_empresa.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'minha_empresa.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            🏢 Minha Empresa
        </a>
        <?php endif; ?>

        <a href="/erp-tcc/pages/perfil.php"
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm
           <?= $pagina_atual === 'perfil.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
            👤 Meu Perfil
        </a>

    </nav>

    <div class="px-4 py-4 border-t border-gray-700">
        <button onclick="confirmarLogout()"
            class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-red-400 hover:bg-gray-700 w-full">
            🚪 Sair
        </button>
    </div>

</aside>

<!-- Modal logout -->
<div id="modal-logout" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
        <p class="text-2xl mb-2">🚪</p>
        <p class="text-gray-800 font-semibold mb-1">Sair do sistema</p>
        <p class="text-sm text-gray-500 mb-6">Tem certeza que deseja encerrar a sessão?</p>
        <div class="flex gap-3 justify-center">
            <button onclick="document.getElementById('modal-logout').classList.add('hidden')"
                class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                Cancelar
            </button>
            <a href="/erp-tcc/auth/logout.php"
                class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                Sim, sair
            </a>
        </div>
    </div>
</div>

<script>
    function confirmarLogout() {
        document.getElementById('modal-logout').classList.remove('hidden');
    }
    function abrirSidebar() {
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
    }
    function fecharSidebar() {
        document.getElementById('sidebar').classList.add('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.add('hidden');
    }
</script>