<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$empresa_id = $_SESSION['empresa_id'];
$categoria = trim($_GET['categoria'] ?? '');
$estoque = trim($_GET['estoque'] ?? '');

$where = "WHERE empresa_id = ?";
$params = [$empresa_id];
$types = 'i';

if ($categoria !== '') {
    $where .= " AND categoria LIKE ?";
    $params[] = "%$categoria%";
    $types .= 's';
}
if ($estoque === 'baixo') {
    $where .= " AND estoque < 5 AND estoque > 0";
} elseif ($estoque === 'zero') {
    $where .= " AND estoque = 0";
}

$stmt = $conn->prepare("SELECT nome, categoria, preco_venda, estoque FROM produtos $where ORDER BY categoria, nome ASC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$empresa = $conn->query("SELECT nome FROM empresas WHERE id = $empresa_id")->fetch_assoc();

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1 { font-size: 18px; color: #1e40af; margin-bottom: 4px; }
    p.sub { font-size: 11px; color: #666; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #1e40af; color: white; padding: 8px; text-align: left; font-size: 11px; }
    td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .baixo { color: #dc2626; font-weight: bold; }
    .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; }
</style></head><body>
<h1>Relatório de Estoque</h1>
<p class="sub">Empresa: ' . htmlspecialchars($empresa['nome']) . ' &nbsp;|&nbsp; Gerado em: ' . date('d/m/Y H:i') . '</p>
<table>
    <thead><tr><th>Produto</th><th>Categoria</th><th>Preço Venda</th><th>Estoque</th><th>Status</th></tr></thead>
    <tbody>';

foreach ($produtos as $p) {
    $status = $p['estoque'] == 0 ? 'Zerado' : ($p['estoque'] < 5 ? 'Crítico' : 'Normal');
    $cls = $p['estoque'] < 5 ? ' class="baixo"' : '';
    $html .= '<tr>
        <td>' . htmlspecialchars($p['nome']) . '</td>
        <td>' . htmlspecialchars($p['categoria'] ?? '—') . '</td>
        <td>R$ ' . number_format($p['preco_venda'], 2, ',', '.') . '</td>
        <td' . $cls . '>' . $p['estoque'] . '</td>
        <td' . $cls . '>' . $status . '</td>
    </tr>';
}

$html .= '</tbody></table><p class="footer">ERP System &mdash; TCC</p></body></html>';

$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('relatorio_estoque.pdf', ['Attachment' => false]);