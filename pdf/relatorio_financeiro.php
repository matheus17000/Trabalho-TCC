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

$where_v = "WHERE empresa_id = $empresa_id";
$where_c = "WHERE empresa_id = $empresa_id";

if ($data_ini !== '') {
    $where_v .= " AND data >= '$data_ini'";
    $where_c .= " AND data >= '$data_ini'";
}
if ($data_fim !== '') {
    $where_v .= " AND data <= '$data_fim'";
    $where_c .= " AND data <= '$data_fim'";
}

$total_vendas = $conn->query("SELECT COALESCE(SUM(total),0) FROM vendas $where_v")->fetch_row()[0];
$total_compras = $conn->query("SELECT COALESCE(SUM(total),0) FROM compras $where_c")->fetch_row()[0];
$qtd_vendas = $conn->query("SELECT COUNT(*) FROM vendas $where_v")->fetch_row()[0];
$qtd_compras = $conn->query("SELECT COUNT(*) FROM compras $where_c")->fetch_row()[0];
$lucro = $total_vendas - $total_compras;

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

$cor_lucro = $lucro >= 0 ? '#16a34a' : '#dc2626';
$lucro_fmt = 'R$ ' . number_format(abs($lucro), 2, ',', '.');
$lucro_label = $lucro >= 0 ? 'Lucro estimado' : 'Prejuízo estimado';

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1 { font-size: 18px; color: #1e40af; margin-bottom: 4px; }
    p.sub { font-size: 11px; color: #666; margin-top: 0; }
    .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
    .card .label { font-size: 11px; color: #666; margin-bottom: 4px; }
    .card .valor { font-size: 22px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #1e40af; color: white; padding: 8px; text-align: left; font-size: 11px; }
    td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; }
</style></head><body>
<h1>Relatório Financeiro</h1>
<p class="sub">Empresa: ' . htmlspecialchars($empresa['nome']) . ' &nbsp;|&nbsp; Período: ' . $periodo . ' &nbsp;|&nbsp; Gerado em: ' . date('d/m/Y H:i') . '</p>

<div class="card">
    <div class="label">Total de Compras (' . $qtd_compras . ' registros)</div>
    <div class="valor" style="color:#ea580c">R$ ' . number_format($total_compras, 2, ',', '.') . '</div>
</div>
<div class="card">
    <div class="label">Total de Vendas / Faturamento (' . $qtd_vendas . ' registros)</div>
    <div class="valor" style="color:#1e40af">R$ ' . number_format($total_vendas, 2, ',', '.') . '</div>
</div>
<div class="card">
    <div class="label">' . $lucro_label . '</div>
    <div class="valor" style="color:' . $cor_lucro . '">' . $lucro_fmt . '</div>
</div>

<table>
    <thead><tr><th>Indicador</th><th>Valor</th></tr></thead>
    <tbody>
        <tr><td>Total de compras realizadas</td><td>R$ ' . number_format($total_compras, 2, ',', '.') . '</td></tr>
        <tr><td>Total de vendas realizadas (faturamento)</td><td>R$ ' . number_format($total_vendas, 2, ',', '.') . '</td></tr>
        <tr><td>' . $lucro_label . '</td><td style="color:' . $cor_lucro . ';font-weight:bold">' . $lucro_fmt . '</td></tr>
    </tbody>
</table>

<p class="footer">ERP System &mdash; TCC</p>
</body></html>';

$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('relatorio_financeiro.pdf', ['Attachment' => false]);