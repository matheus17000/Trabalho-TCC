<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$empresa_id = $_SESSION['empresa_id'];
$produto_id = (int) ($_GET['id'] ?? 0);

if (!$produto_id) {
    header('Location: /pages/produtos.php');
    exit;
}

// Busca dados do produto
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND empresa_id = ?");
$stmt->bind_param('ii', $produto_id, $empresa_id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) {
    header('Location: /pages/produtos.php');
    exit;
}

$titulo = 'Histórico — ' . $produto['nome'];

// Busca movimentações
$stmt = $conn->prepare("
    SELECT tipo, quantidade, referencia, data
    FROM movimentacoes
    WHERE produto_id = ? AND empresa_id = ?
    ORDER BY data DESC
");
$stmt->bind_param('ii', $produto_id, $empresa_id);
$stmt->execute();
$movimentacoes = $stmt->get_result();

// Totais
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantidade),0) FROM movimentacoes WHERE produto_id = ? AND empresa_id = ? AND tipo = 'entrada'");
$stmt->bind_param('ii', $produto_id, $empresa_id);
$stmt->execute();
$total_entradas = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COALESCE(SUM(quantidade),0) FROM movimentacoes WHERE produto_id = ? AND empresa_id = ? AND tipo = 'saida'");
$stmt->bind_param('ii', $produto_id, $empresa_id);
$stmt->execute();
$total_saidas = $stmt->get_result()->fetch_row()[0];
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

            <!-- Cabeçalho -->
            <div class="flex items-center gap-3 mb-6">
                <a href="/pages/produtos.php"
                    class="text-gray-400 hover:text-gray-600 text-sm">← Voltar</a>
                <h3 class="text-lg font-semibold text-gray-700">
                    Histórico — <?= htmlspecialchars($produto['nome']) ?>
                </h3>
            </div>

            <!-- Cards do produto -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Categoria</p>
                    <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($produto['categoria'] ?? '—') ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Estoque atual</p>
                    <p class="text-2xl font-bold <?= $produto['estoque'] < 5 ? 'text-red-600' : 'text-green-600' ?>">
                        <?= $produto['estoque'] ?>
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Total entradas</p>
                    <p class="text-2xl font-bold text-green-600">+<?= $total_entradas ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Total saídas</p>
                    <p class="text-2xl font-bold text-red-500">-<?= $total_saidas ?></p>
                </div>
            </div>

            <!-- Preços -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 mb-1">Preço de compra</p>
                    <p class="text-xl font-bold text-orange-500">R$ <?= number_format($produto['preco_compra'], 2, ',', '.') ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 mb-1">Preço de venda</p>
                    <p class="text-xl font-bold text-blue-600">R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></p>
                </div>
            </div>

            <!-- Movimentações -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="text-sm font-semibold text-gray-700">🔄 Movimentações</h4>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Data</th>
                            <th class="px-6 py-3 text-left">Tipo</th>
                            <th class="px-6 py-3 text-left">Referência</th>
                            <th class="px-6 py-3 text-right">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($movimentacoes->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Nenhuma movimentação registrada para este produto.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($m = $movimentacoes->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($m['data'])) ?>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full font-medium
                                        <?= $m['tipo'] === 'entrada'
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-600' ?>">
                                        <?= $m['tipo'] === 'entrada' ? '↑ Entrada' : '↓ Saída' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-500">
                                    <?= htmlspecialchars($m['referencia'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-3 text-right font-medium
                                    <?= $m['tipo'] === 'entrada' ? 'text-green-600' : 'text-red-500' ?>">
                                    <?= $m['tipo'] === 'entrada' ? '+' : '-' ?><?= $m['quantidade'] ?>
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

</body>
</html>