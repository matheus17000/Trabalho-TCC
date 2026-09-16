<header class="lg:ml-64 bg-white border-b border-gray-200 px-4 py-4 flex items-center justify-between">

    <div class="flex items-center gap-3">
        <!-- Botão hamburguer mobile -->
        <button onclick="abrirSidebar()"
            class="lg:hidden text-gray-500 hover:text-gray-700 text-xl">
            ☰
        </button>
        <h2 class="text-lg font-semibold text-gray-700">
            <?= $titulo ?? 'Painel' ?>
        </h2>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="toggleDark()" id="btn-dark"
            class="text-gray-500 hover:text-gray-700 transition text-lg"
            title="Alternar modo escuro">
            🌙
        </button>
        <span class="text-sm text-gray-500 hidden sm:block">
            <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>
        </span>
        <a href="/erp-tcc/auth/logout.php"
           class="text-sm text-red-500 hover:underline">
            Sair
        </a>
    </div>

</header>

<script>
    function toggleDark() {
        const body = document.body;
        const btn  = document.getElementById('btn-dark');
        const isDark = body.classList.toggle('dark');
        localStorage.setItem('dark', isDark ? '1' : '0');
        btn.textContent = isDark ? '☀️' : '🌙';
    }
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('btn-dark');
        if (localStorage.getItem('dark') === '1') {
            document.body.classList.add('dark');
            if (btn) btn.textContent = '☀️';
        }
    });
</script>