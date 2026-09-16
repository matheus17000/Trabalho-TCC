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
$data_ini = $_GET['data_ini'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$where = "WHERE v.empresa_id = ?";
$params = [$empresa_id];
$types = 'i';

if ($data_ini !== '') {
    $where .= " AND v.data >= ?";
    $params[] = $data_ini;
    $types .= 's';
}
if ($data_fim !== '') {
    $where .= " AND v.data <= ?";
    $params[] = $data_fim;
    $types .= 's';
}

$stmt = $conn->prepare("
    SELECT v.id, v.data, v.pagamento, v.total, c.nome AS cliente
    FROM vendas v
    JOIN clientes c ON c.id = v.cliente_id
    $where
    ORDER BY v.data DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$vendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$empresa = $conn->query("SELECT nome FROM empresas WHERE id = $empresa_id")->fetch_assoc();

$periodo = '';
if ($data_ini !== '' && $data_fim !== '') {
    $periodo = date('d/m/Y', strtotime($data_ini)) . ' até ' . date('d/m/Y', strtotime($data_fim));
} elseif ($data_ini !== '') {
    $periodo = 'A partir de ' . date('d/m/Y', strtotime($data_ini));
} elseif ($data_fim !== '') {
    $periodo = 'Até ' . date('d/m/Y', strtotime($data_fim));
} else {
    $periodo = 'Todo o período';
}

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1 { font-size: 18px; color: #1e40af; margin-bottom: 4px; }
    p.sub { font-size: 11px; color: #666; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #1e40af; color: white; padding: 8px; text-align: left; font-size: 11px; }
    td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .total-row td { font-weight: bold; background: #eff6ff; border-top: 2px solid #1e40af; }
    .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; }
</style></head><body>
<h1>Relatório de Vendas</h1>
<p class="sub">Empresa: ' . htmlspecialchars($empresa['nome']) . ' &nbsp;|&nbsp; Período: ' . $periodo . ' &nbsp;|&nbsp; Gerado em: ' . date('d/m/Y H:i') . '</p>
<table>
    <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Pagamento</th><th>Total</th></tr></thead>
    <tbody>';

$total_geral = 0;
foreach ($vendas as $v) {
    $total_geral += $v['total'];
    $html .= '<tr>
        <td>#' . $v['id'] . '</td>
        <td>' . date('d/m/Y', strtotime($v['data'])) . '</td>
        <td>' . htmlspecialchars($v['cliente']) . '</td>
        <td>' . ucfirst($v['pagamento']) . '</td>
        <td>R$ ' . number_format($v['total'], 2, ',', '.') . '</td>
    </tr>';
}

$html .= '<tr class="total-row"><td colspan="4">Total geral</td><td>R$ ' . number_format($total_geral, 2, ',', '.') . '</td></tr>';
$html .= '</tbody></table><p class="footer">ERP System &mdash; TCC</p></body></html>';

$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('relatorio_vendas.pdf', ['Attachment' => false]);