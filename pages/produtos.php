<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/acesso.php';

$titulo = 'Produtos';

$busca = trim($_GET['busca'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$estoque = trim($_GET['estoque'] ?? '');

// Busca categorias para o select
$cats = $conn->query("SELECT DISTINCT categoria FROM produtos WHERE empresa_id = {$_SESSION['empresa_id']} AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");

// Categorias existentes para o combo box do modal
$cats_modal = $conn->query("
    SELECT DISTINCT categoria 
    FROM produtos 
    WHERE empresa_id = {$_SESSION['empresa_id']} 
    AND categoria IS NOT NULL 
    AND categoria != '' 
    ORDER BY categoria ASC
");

$categorias_existentes = [];
while ($c = $cats_modal->fetch_assoc()) {
    $categorias_existentes[] = $c['categoria'];
}

$where = "WHERE empresa_id = ?";
$params = [$_SESSION['empresa_id']];
$types = 'i';

if ($busca !== '') {
    $where .= " AND nome LIKE ?";
    $params[] = "%$busca%";
    $types .= 's';
}
if ($categoria !== '') {
    $where .= " AND categoria = ?";
    $params[] = $categoria;
    $types .= 's';
}
if ($estoque === 'baixo') {
    $where .= " AND estoque < 5";
} elseif ($estoque === 'zero') {
    $where .= " AND estoque = 0";
}

$stmt = $conn->prepare("SELECT * FROM produtos $where ORDER BY nome ASC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$produtos = $stmt->get_result();
$total_encontrados = $produtos->num_rows;
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
                <h3 class="text-lg font-semibold text-gray-700">Lista de Produtos</h3>
                <?php if (is_admin()): ?>
                    <button onclick="abrirModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                        + Novo Produto
                    </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                    $msgs = [
                        'criado' => 'Produto cadastrado com sucesso!',
                        'editado' => 'Produto atualizado com sucesso!',
                        'deletado' => 'Produto excluído com sucesso!',
                    ];
                    echo $msgs[$_GET['sucesso']] ?? 'Operação realizada!';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome..."
                    class="flex-1 min-w-40 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="categoria"
                    class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas as categorias</option>
                    <?php while ($c = $cats->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['categoria']) ?>" <?= $categoria === $c['categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['categoria']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="estoque"
                    class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos os estoques</option>
                    <option value="baixo" <?= $estoque === 'baixo' ? 'selected' : '' ?>>Estoque baixo (menos de 5)</option>
                    <option value="zero" <?= $estoque === 'zero' ? 'selected' : '' ?>>Sem estoque</option>
                </select>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition">
                    Filtrar
                </button>
                <?php if ($busca !== '' || $categoria !== '' || $estoque !== ''): ?>
                    <a href="/pages/produtos.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg transition">
                        Limpar
                    </a>
                <?php endif; ?>
            </form>

            <p class="text-sm text-gray-500 mb-3">
                <?= $total_encontrados ?> produto<?= $total_encontrados !== 1 ? 's' : '' ?>
                encontrado<?= $total_encontrados !== 1 ? 's' : '' ?>
            </p>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Nome</th>
                            <th class="px-6 py-3 text-left">Categoria</th>
                            <th class="px-6 py-3 text-right">Preço compra</th>
                            <th class="px-6 py-3 text-right">Preço venda</th>
                            <th class="px-6 py-3 text-center">Estoque</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($produtos->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Nenhum produto encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($p = $produtos->fetch_assoc()): ?>
                                <?php
                                if ($p['estoque'] == 0) {
                                    $badge = 'bg-red-100 text-red-700';
                                    $label = '⛔ Zerado';
                                } elseif ($p['estoque'] <= 4) {
                                    $badge = 'bg-red-100 text-red-600';
                                    $label = '🔴 Crítico';
                                } elseif ($p['estoque'] <= 10) {
                                    $badge = 'bg-yellow-100 text-yellow-700';
                                    $label = '🟡 Atenção';
                                } else {
                                    $badge = 'bg-green-100 text-green-700';
                                    $label = '🟢 Normal';
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($p['nome']) ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-right text-gray-500">R$
                                        <?= number_format($p['preco_compra'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-500">R$
                                        <?= number_format($p['preco_venda'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="text-xs px-2 py-1 rounded-full font-medium <?= $badge ?>">
                                            <?= $p['estoque'] ?> — <?= $label ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center space-x-2">
                                        <?php if (is_admin()): ?>
                                            <button
                                                onclick="abrirEdicao(<?= $p['id'] ?>,'<?= addslashes($p['nome']) ?>','<?= addslashes($p['categoria'] ?? '') ?>','<?= addslashes($p['descricao'] ?? '') ?>','<?= $p['preco_compra'] ?>','<?= $p['preco_venda'] ?>','<?= $p['estoque'] ?>')"
                                                class="text-blue-600 hover:underline text-xs">Editar</button>
                                        <?php endif; ?>
                                        <a href="/pages/historico_produto.php?id=<?= $p['id'] ?>"
                                            class="text-purple-600 hover:underline text-xs">Histórico</a>
                                        <?php if (is_admin()): ?>
                                            <button onclick="confirmarDelete(<?= $p['id'] ?>,'<?= addslashes($p['nome']) ?>')"
                                                class="text-red-500 hover:underline text-xs">Excluir</button>
                                        <?php endif; ?>
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

    <div id="modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
            <h2 id="modal-titulo" class="text-lg font-semibold text-gray-800 mb-4">Novo Produto</h2>
            <form method="POST" action="/actions/salvar_produto.php" onsubmit="return validarFormProduto()">
                <input type="hidden" name="id" id="campo-id">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" name="nome" id="campo-nome" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <div class="flex gap-2">
                            <select id="select-categoria" onchange="selecionarCategoria(this.value)"
                                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione ou digite uma nova...</option>
                                <?php foreach ($categorias_existentes as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                                <option value="__nova__">+ Nova categoria...</option>
                            </select>
                        </div>
                        <input type="text" name="categoria" id="campo-categoria" placeholder="Nome da categoria"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 hidden">
                        <p class="text-xs text-gray-400 mt-1">Selecione uma existente ou escolha "+ Nova categoria" para
                            criar.</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea name="descricao" id="campo-descricao" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço de compra *</label>
                        <input type="text" name="preco_compra" id="campo-preco-compra" required placeholder="0,00"
                            oninput="mascaraPreco(this)"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-preco-compra" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço de venda *</label>
                        <input type="text" name="preco_venda" id="campo-preco-venda" required placeholder="0,00"
                            oninput="mascaraPreco(this)"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p id="erro-preco-venda" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estoque inicial</label>
                        <input type="number" name="estoque" id="campo-estoque" min="0" value="0"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="fecharModal()"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-delete" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
            <p class="text-gray-700 mb-1 font-medium">Confirmar exclusão</p>
            <p id="msg-delete" class="text-sm text-gray-500 mb-6"></p>
            <div class="flex gap-3 justify-center">
                <button onclick="fecharDelete()"
                    class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancelar</button>
                <a id="link-delete" href="#"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">Excluir</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/validacoes.js"></script>
    <script>
        function abrirModal() {
            document.getElementById('modal-titulo').textContent = 'Novo Produto';
            document.getElementById('campo-id').value = '';
            document.getElementById('campo-nome').value = '';
            document.getElementById('campo-categoria').value = '';
            document.getElementById('campo-categoria').classList.add('hidden');
            document.getElementById('select-categoria').value = '';
            document.getElementById('campo-descricao').value = '';
            document.getElementById('campo-preco-compra').value = '0,00';
            document.getElementById('campo-preco-venda').value = '0,00';
            document.getElementById('campo-estoque').value = '0';
            limparTodosErros(['nome', 'preco-compra', 'preco-venda']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function abrirEdicao(id, nome, categoria, descricao, precoCompra, precoVenda, estoque) {
            document.getElementById('modal-titulo').textContent = 'Editar Produto';
            document.getElementById('campo-id').value = id;
            document.getElementById('campo-nome').value = nome;
            document.getElementById('campo-descricao').value = descricao;
            document.getElementById('campo-preco-compra').value = formatarPreco(precoCompra);
            document.getElementById('campo-preco-venda').value = formatarPreco(precoVenda);
            document.getElementById('campo-estoque').value = estoque;
            limparTodosErros(['nome', 'preco-compra', 'preco-venda']);

            // Seleciona categoria no combo
            const select = document.getElementById('select-categoria');
            const input = document.getElementById('campo-categoria');
            const opcaoExiste = Array.from(select.options).some(o => o.value === categoria);

            if (opcaoExiste && categoria !== '') {
                select.value = categoria;
                input.value = categoria;
                input.classList.add('hidden');
            } else if (categoria !== '') {
                select.value = '__nova__';
                input.value = categoria;
                input.classList.remove('hidden');
            } else {
                select.value = '';
                input.value = '';
                input.classList.add('hidden');
            }

            document.getElementById('modal').classList.remove('hidden');
        }

        function formatarPreco(valor) {
            let v = parseFloat(valor).toFixed(2);
            v = v.replace('.', ',');
            v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            return v;
        }
        function selecionarCategoria(valor) {
            const input = document.getElementById('campo-categoria');
            const select = document.getElementById('select-categoria');

            if (valor === '__nova__') {
                input.classList.remove('hidden');
                input.value = '';
                input.focus();
            } else if (valor === '') {
                input.classList.add('hidden');
                input.value = '';
            } else {
                input.classList.add('hidden');
                input.value = valor;
            }
        }
        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
        }
        function confirmarDelete(id, nome) {
            document.getElementById('msg-delete').textContent = 'Deseja excluir o produto "' + nome + '"?';
            document.getElementById('link-delete').href = '/actions/salvar_produto.php?deletar=' + id;
            document.getElementById('modal-delete').classList.remove('hidden');
        }
        function fecharDelete() {
            document.getElementById('modal-delete').classList.add('hidden');
        }
        function validarFormProduto() {
            limparTodosErros(['nome', 'preco-compra', 'preco-venda']);
            let valido = true;

            const nome = document.getElementById('campo-nome').value.trim();
            if (nome.length < 2) {
                mostrarErro('nome', 'Nome deve ter pelo menos 2 caracteres.');
                valido = false;
            }

            // Converte máscara para número
            const precoCompraStr = document.getElementById('campo-preco-compra').value
                .replace(/\./g, '').replace(',', '.');
            const precoCompra = parseFloat(precoCompraStr);
            if (isNaN(precoCompra) || precoCompra < 0) {
                mostrarErro('preco-compra', 'Preço de compra inválido.');
                valido = false;
            }

            const precoVendaStr = document.getElementById('campo-preco-venda').value
                .replace(/\./g, '').replace(',', '.');
            const precoVenda = parseFloat(precoVendaStr);
            if (isNaN(precoVenda) || precoVenda < 0) {
                mostrarErro('preco-venda', 'Preço de venda inválido.');
                valido = false;
            }

            if (valido && precoVenda > 0 && precoVenda < precoCompra) {
                mostrarErro('preco-venda', '⚠️ Preço de venda menor que o preço de compra. Confirma?');
            }

            return valido;
        }
    </script>

</body>

</html>