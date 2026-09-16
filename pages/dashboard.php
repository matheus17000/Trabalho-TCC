<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Dashboard';
$eid = $_SESSION['empresa_id'];

// KPIs
$total_clientes = $conn->query("SELECT COUNT(*) FROM clientes WHERE empresa_id = $eid")->fetch_row()[0];
$total_produtos = $conn->query("SELECT COUNT(*) FROM produtos WHERE empresa_id = $eid")->fetch_row()[0];
$total_vendas = $conn->query("SELECT COUNT(*) FROM vendas WHERE empresa_id = $eid")->fetch_row()[0];
$total_compras = $conn->query("SELECT COUNT(*) FROM compras WHERE empresa_id = $eid")->fetch_row()[0];
$faturamento = $conn->query("SELECT COALESCE(SUM(total),0) FROM vendas WHERE empresa_id = $eid")->fetch_row()[0];
$custo = $conn->query("SELECT COALESCE(SUM(total),0) FROM compras WHERE empresa_id = $eid")->fetch_row()[0];
$lucro = $faturamento - $custo;

// Estoque baixo
$estoque_baixo = $conn->query("SELECT nome, estoque FROM produtos WHERE empresa_id = $eid AND estoque < 5 ORDER BY estoque ASC LIMIT 5");

// Últimas movimentações
$movimentacoes = $conn->query("
    SELECT m.tipo, m.quantidade, m.referencia, m.data, p.nome AS produto
    FROM movimentacoes m
    JOIN produtos p ON p.id = m.produto_id
    WHERE m.empresa_id = $eid
    ORDER BY m.data DESC LIMIT 8
");

// Gráfico — vendas por mês
$vendas_mes = $conn->query("
    SELECT DATE_FORMAT(data, '%m/%Y') AS mes, SUM(total) AS total
    FROM vendas
    WHERE empresa_id = $eid
      AND data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY DATE_FORMAT(data, '%Y-%m') ASC
");
$labels_vendas = [];
$dados_vendas = [];
while ($r = $vendas_mes->fetch_assoc()) {
    $labels_vendas[] = $r['mes'];
    $dados_vendas[] = (float) $r['total'];
}

// Gráfico — compras por mês
$compras_mes = $conn->query("
    SELECT DATE_FORMAT(data, '%m/%Y') AS mes, SUM(total) AS total
    FROM compras
    WHERE empresa_id = $eid
      AND data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY DATE_FORMAT(data, '%Y-%m') ASC
");
$labels_compras = [];
$dados_compras = [];
while ($r = $compras_mes->fetch_assoc()) {
    $labels_compras[] = $r['mes'];
    $dados_compras[] = (float) $r['total'];
}

// Gráfico — produtos por categoria
$categorias = $conn->query("
    SELECT categoria, COUNT(*) AS total
    FROM produtos
    WHERE empresa_id = $eid AND categoria IS NOT NULL AND categoria != ''
    GROUP BY categoria
    ORDER BY total DESC
");
$labels_cat = [];
$dados_cat = [];
while ($r = $categorias->fetch_assoc()) {
    $labels_cat[] = $r['categoria'];
    $dados_cat[] = (int) $r['total'];
}

// Gráfico — lucro por mês
$lucro_vendas = $conn->query("
    SELECT DATE_FORMAT(data, '%m/%Y') AS mes,
           DATE_FORMAT(data, '%Y-%m') AS mes_ordem,
           SUM(total) AS total
    FROM vendas
    WHERE empresa_id = $eid
      AND data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes_ordem ASC
");
$lucro_compras = $conn->query("
    SELECT DATE_FORMAT(data, '%m/%Y') AS mes,
           DATE_FORMAT(data, '%Y-%m') AS mes_ordem,
           SUM(total) AS total
    FROM compras
    WHERE empresa_id = $eid
      AND data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes_ordem ASC
");

$vendas_por_mes = [];
while ($r = $lucro_vendas->fetch_assoc()) {
    $vendas_por_mes[$r['mes']] = (float) $r['total'];
}
$compras_por_mes = [];
while ($r = $lucro_compras->fetch_assoc()) {
    $compras_por_mes[$r['mes']] = (float) $r['total'];
}
$todos_meses = array_unique(array_merge(array_keys($vendas_por_mes), array_keys($compras_por_mes)));
sort($todos_meses);
$labels_lucro = [];
$dados_lucro = [];
foreach ($todos_meses as $mes) {
    $labels_lucro[] = $mes;
    $v = $vendas_por_mes[$mes] ?? 0;
    $c = $compras_por_mes[$mes] ?? 0;
    $dados_lucro[] = round($v - $c, 2);
}

// Últimos logs
$ultimos_logs = $conn->query("
    SELECT l.modulo, l.acao, l.descricao, l.data, u.nome AS usuario
    FROM logs l
    JOIN usuarios u ON u.id = l.usuario_id
    WHERE l.empresa_id = $eid
    ORDER BY l.data DESC
    LIMIT 6
");

// Saudação
$hora = (int) date('H');
if ($hora >= 5 && $hora < 12)
    $saudacao = 'Bom dia';
elseif ($hora >= 12 && $hora < 18)
    $saudacao = 'Boa tarde';
else
    $saudacao = 'Boa noite';

$dias = [
    'Sunday' => 'domingo',
    'Monday' => 'segunda-feira',
    'Tuesday' => 'terça-feira',
    'Wednesday' => 'quarta-feira',
    'Thursday' => 'quinta-feira',
    'Friday' => 'sexta-feira',
    'Saturday' => 'sábado'
];
$meses_nome = [
    'January' => 'janeiro',
    'February' => 'fevereiro',
    'March' => 'março',
    'April' => 'abril',
    'May' => 'maio',
    'June' => 'junho',
    'July' => 'julho',
    'August' => 'agosto',
    'September' => 'setembro',
    'October' => 'outubro',
    'November' => 'novembro',
    'December' => 'dezembro'
];

$dia_semana = $dias[date('l')];
$dia = date('d');
$mes_atual = $meses_nome[date('F')];
$ano = date('Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">

    <?php include '../includes/sidebar.php'; ?>

    <div class="lg:ml-64 flex flex-col min-h-screen">

        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 p-6">

            <!-- Boas-vindas -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-5 mb-6 text-white">
                <p class="text-lg font-semibold">
                    <?= $saudacao ?>, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>! 👋
                </p>
                <p class="text-sm text-blue-100 mt-1">
                    Hoje é <?= $dia_semana ?>, <?= $dia ?> de <?= $mes_atual ?> de <?= $ano ?>.
                </p>
            </div>

            <?php if (isset($_GET['erro']) && $_GET['erro'] === 'acesso'): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                    ⛔ Você não tem permissão para acessar essa página.
                </div>
            <?php endif; ?>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Clientes</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $total_clientes ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Produtos</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $total_produtos ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Vendas</p>
                    <p class="text-2xl font-bold text-green-600"><?= $total_vendas ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Compras</p>
                    <p class="text-2xl font-bold text-orange-500"><?= $total_compras ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Faturamento</p>
                    <p class="text-2xl font-bold text-green-600">R$ <?= number_format($faturamento, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Lucro estimado</p>
                    <p class="text-2xl font-bold <?= $lucro >= 0 ? 'text-green-600' : 'text-red-500' ?>">
                        R$ <?= number_format($lucro, 2, ',', '.') ?>
                    </p>
                </div>
            </div>

            <!-- Gráficos linha 1 — vendas, compras, lucro -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">📈 Vendas por mês</h3>
                    <canvas id="graficoVendas" height="120"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">🛒 Compras por mês</h3>
                    <canvas id="graficoCompras" height="120"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">💰 Lucro estimado por mês</h3>
                    <canvas id="graficoLucro" height="120"></canvas>
                </div>
            </div>

            <!-- Gráficos linha 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">📦 Produtos por categoria</h3>
                    <canvas id="graficoCategorias" height="200"></canvas>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">⚠️ Estoque baixo</h3>
                    <?php if ($estoque_baixo->num_rows === 0): ?>
                        <p class="text-sm text-gray-400">Nenhum produto com estoque baixo.</p>
                    <?php else: ?>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-400 border-b">
                                    <th class="pb-2">Produto</th>
                                    <th class="pb-2 text-right">Estoque</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = $estoque_baixo->fetch_assoc()): ?>
                                    <tr class="border-b last:border-0">
                                        <td class="py-2"><?= htmlspecialchars($p['nome']) ?></td>
                                        <td class="py-2 text-right">
                                            <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">
                                                <?= $p['estoque'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">🔄 Últimas movimentações</h3>
                    <?php if ($movimentacoes->num_rows === 0): ?>
                        <p class="text-sm text-gray-400">Nenhuma movimentação registrada.</p>
                    <?php else: ?>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-400 border-b">
                                    <th class="pb-2">Produto</th>
                                    <th class="pb-2">Tipo</th>
                                    <th class="pb-2 text-right">Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($m = $movimentacoes->fetch_assoc()): ?>
                                    <tr class="border-b last:border-0">
                                        <td class="py-2 text-xs"><?= htmlspecialchars($m['produto']) ?></td>
                                        <td class="py-2">
                                            <span
                                                class="text-xs px-2 py-0.5 rounded-full
                                            <?= $m['tipo'] === 'entrada' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                                <?= $m['tipo'] ?>
                                            </span>
                                        </td>
                                        <td class="py-2 text-right text-xs"><?= $m['quantidade'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimas atividades -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">📋 Últimas atividades</h3>
                <?php if ($ultimos_logs->num_rows === 0): ?>
                    <p class="text-sm text-gray-400">Nenhuma atividade registrada ainda.</p>
                <?php else: ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b text-xs uppercase">
                                <th class="pb-2">Usuário</th>
                                <th class="pb-2">Módulo</th>
                                <th class="pb-2">Descrição</th>
                                <th class="pb-2 text-right">Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($l = $ultimos_logs->fetch_assoc()): ?>
                                <tr class="border-b last:border-0">
                                    <td class="py-2 font-medium text-gray-700"><?= htmlspecialchars($l['usuario']) ?></td>
                                    <td class="py-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                            <?= ucfirst($l['modulo']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2 text-gray-500"><?= htmlspecialchars($l['descricao']) ?></td>
                                    <td class="py-2 text-right text-gray-400 text-xs">
                                        <?= date('d/m/Y H:i', strtotime($l['data'])) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <script>
        new Chart(document.getElementById('graficoVendas'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_vendas) ?>,
                datasets: [{
                    label: 'Vendas (R$)',
                    data: <?= json_encode($dados_vendas) ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true}}
            }
        });

        new Chart(document.getElementById('graficoCompras'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_compras) ?>,
                datasets: [{
                    label: 'Compras (R$)',
                    data: <?= json_encode($dados_compras) ?>,
                    backgroundColor: 'rgba(234, 88, 12, 0.7)',
                    borderColor: 'rgba(234, 88, 12, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true}}
            }
        });

        new Chart(document.getElementById('graficoLucro'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_lucro) ?>,
                datasets: [{
                    label: 'Lucro (R$)',
                    data: <?= json_encode($dados_lucro) ?>,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('graficoCategorias'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_cat) ?>,
                datasets: [{
                    data: <?= json_encode($dados_cat) ?>,
                    backgroundColor: [
                        'rgba(37,99,235,0.8)',
                        'rgba(16,185,129,0.8)',
                        'rgba(234,88,12,0.8)',
                        'rgba(139,92,246,0.8)',
                        'rgba(236,72,153,0.8)',
                        'rgba(245,158,11,0.8)',
                        'rgba(20,184,166,0.8)',
                        'rgba(99,102,241,0.8)',
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {position: 'bottom', labels: {font: {size: 10}}}
                }
            }
        });
    </script>

</body>

</html>