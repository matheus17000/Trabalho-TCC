<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Compras';

$data_ini = $_GET['data_ini'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$stmt_produtos = $conn->prepare("SELECT id, nome, preco_compra FROM produtos WHERE empresa_id = ? ORDER BY nome ASC");
$stmt_produtos->bind_param('i', $_SESSION['empresa_id']);
$stmt_produtos->execute();
$produtos = $stmt_produtos->get_result()->fetch_all(MYSQLI_ASSOC);

$where = "WHERE c.empresa_id = ?";
$params = [$_SESSION['empresa_id']];
$types = 'i';

if ($data_ini !== '') {
    $where .= " AND c.data >= ?";
    $params[] = $data_ini;
    $types .= 's';
}
if ($data_fim !== '') {
    $where .= " AND c.data <= ?";
    $params[] = $data_fim;
    $types .= 's';
}

$stmt = $conn->prepare("
    SELECT c.id, c.data, c.pagamento, c.total,
           COUNT(ic.id) AS qtd_itens
    FROM compras c
    LEFT JOIN itens_compra ic ON ic.compra_id = c.id
    $where
    GROUP BY c.id
    ORDER BY c.data DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$compras = $stmt->get_result();
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

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-700">Compras</h3>
                <button onclick="abrirModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Nova Compra
                </button>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    Compra registrada com sucesso! Estoque atualizado.
                </div>
            <?php endif; ?>

            <!-- Filtro período -->
            <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end"
                onsubmit="return validarDatas()">

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data início</label>
                    <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>"
                        class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data fim</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>"
                        class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition">
                    Filtrar
                </button>
                <?php if ($data_ini !== '' || $data_fim !== ''): ?>
                    <a href="/pages/compras.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg transition">
                        Limpar
                    </a>
                <?php endif; ?>
            </form>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">#</th>
                            <th class="px-6 py-3 text-left">Data</th>
                            <th class="px-6 py-3 text-left">Pagamento</th>
                            <th class="px-6 py-3 text-center">Itens</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($compras->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Nenhuma compra encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($c = $compras->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-400">#<?= $c['id'] ?></td>
                                    <td class="px-6 py-3 text-gray-700"><?= date('d/m/Y', strtotime($c['data'])) ?></td>
                                    <td class="px-6 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                            <?= ucfirst($c['pagamento']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-500"><?= $c['qtd_itens'] ?></td>
                                    <td class="px-6 py-3 text-right font-medium text-gray-800">
                                        R$ <?= number_format($c['total'], 2, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <!-- Modal Nova Compra -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Nova Compra</h2>
            <form method="POST" action="/actions/salvar_compra.php" id="form-compra">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                        <input type="date" name="data" required value="<?= date('Y-m-d') ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pagamento *</label>
                        <select name="pagamento"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao">Cartão</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">Adicionar produto</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <select id="select-produto"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione um produto...</option>
                                <?php foreach ($produtos as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>"
                                        data-preco="<?= $p['preco_compra'] ?>">
                                        <?= htmlspecialchars($p['nome']) ?> — R$
                                        <?= number_format($p['preco_compra'], 2, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <input type="number" id="input-quantidade" min="1" value="1"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <button type="button" onclick="adicionarItem()"
                        class="mt-3 bg-gray-700 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded-lg transition">
                        + Adicionar
                    </button>
                </div>
                <div class="mb-4">
                    <table class="w-full text-sm">
                        <thead class="text-gray-400 text-xs uppercase border-b">
                            <tr>
                                <th class="pb-2 text-left">Produto</th>
                                <th class="pb-2 text-center">Qtd</th>
                                <th class="pb-2 text-right">Unit.</th>
                                <th class="pb-2 text-right">Subtotal</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody id="itens-body">
                            <tr id="linha-vazia">
                                <td colspan="5" class="py-4 text-center text-gray-400 text-xs">Nenhum produto
                                    adicionado.</td>
                            </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="itens" id="campo-itens">
                </div>
                <div class="flex justify-end mb-4">
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Total da compra</p>
                        <p class="text-2xl font-bold text-gray-800">R$ <span id="total">0,00</span></p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="fecharModal()"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancelar</button>
                    <button type="submit" onclick="return prepararEnvio()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Salvar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itens = [];
        function abrirModal() {itens = []; renderItens(); document.getElementById('modal').classList.remove('hidden');}
        function fecharModal() {document.getElementById('modal').classList.add('hidden');}
        function adicionarItem() {
            const select = document.getElementById('select-produto');
            const qtd = parseInt(document.getElementById('input-quantidade').value);
            const option = select.options[select.selectedIndex];
            if (!select.value || qtd < 1) {alert('Selecione um produto e informe a quantidade.'); return;}
            const id = select.value, nome = option.dataset.nome, preco = parseFloat(option.dataset.preco);
            const existente = itens.find(i => i.produto_id === id);
            if (existente) {existente.quantidade += qtd; existente.subtotal = existente.quantidade * existente.preco;}
            else {itens.push({produto_id: id, nome, quantidade: qtd, preco, subtotal: qtd * preco});}
            select.value = ''; document.getElementById('input-quantidade').value = 1; renderItens();
        }
        function removerItem(index) {itens.splice(index, 1); renderItens();}
        function renderItens() {
            const tbody = document.getElementById('itens-body');
            if (itens.length === 0) {tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-gray-400 text-xs">Nenhum produto adicionado.</td></tr>'; document.getElementById('total').textContent = '0,00'; return;}
            let html = '', total = 0;
            itens.forEach((item, index) => {
                total += item.subtotal;
                html += `<tr class="border-b"><td class="py-2">${item.nome}</td><td class="py-2 text-center">${item.quantidade}</td><td class="py-2 text-right">R$ ${item.preco.toFixed(2).replace('.', ',')}</td><td class="py-2 text-right">R$ ${item.subtotal.toFixed(2).replace('.', ',')}</td><td class="py-2 text-right"><button type="button" onclick="removerItem(${index})" class="text-red-400 hover:text-red-600 text-xs">✕</button></td></tr>`;
            });
            tbody.innerHTML = html;
            document.getElementById('total').textContent = total.toFixed(2).replace('.', ',');
        }
        function prepararEnvio() {
            if (itens.length === 0) {alert('Adicione ao menos um produto.'); return false;}
            document.getElementById('campo-itens').value = JSON.stringify(itens); return true;
        }
    </script>

    <script>
        function validarDatas() {
            const ini = document.querySelector('input[name="data_ini"]').value;
            const fim = document.querySelector('input[name="data_fim"]').value;

            if (ini && fim && ini > fim) {
                alert('A data de início não pode ser maior que a data fim!');
                return false;
            }
            return true;
        }
    </script>

</body>

</html>