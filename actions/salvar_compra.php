<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/log.php';
$usuario_id = $_SESSION['usuario_id'];

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];
    $pagamento = $_POST['pagamento'];
    $itens = json_decode($_POST['itens'], true);

    if (empty($itens)) {
        header('Location: /pages/compras.php');
        exit;
    }

    // Calcula total
    $total = 0;
    foreach ($itens as $item) {
        $total += $item['subtotal'];
    }

    // Inicia transação
    $conn->begin_transaction();

    try {
        // Insere cabeçalho da compra
        $stmt = $conn->prepare("INSERT INTO compras (empresa_id, data, pagamento, total) VALUES (?,?,?,?)");
        $stmt->bind_param('issd', $empresa_id, $data, $pagamento, $total);
        $stmt->execute();
        $compra_id = $conn->insert_id;

        // Insere itens e atualiza estoque
        foreach ($itens as $item) {
            $produto_id = (int) $item['produto_id'];
            $quantidade = (int) $item['quantidade'];
            $valor_unitario = (float) $item['preco'];
            $subtotal = (float) $item['subtotal'];

            // Insere item
            $stmt = $conn->prepare("INSERT INTO itens_compra (compra_id, produto_id, quantidade, valor_unitario, subtotal) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iiidd', $compra_id, $produto_id, $quantidade, $valor_unitario, $subtotal);
            $stmt->execute();

            // Atualiza estoque
            $stmt = $conn->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ? AND empresa_id = ?");
            $stmt->bind_param('iii', $quantidade, $produto_id, $empresa_id);
            $stmt->execute();

            // Registra movimentação
            $referencia = "compra #$compra_id";
            $tipo = 'entrada';
            $stmt = $conn->prepare("INSERT INTO movimentacoes (empresa_id, produto_id, tipo, quantidade, referencia) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iisis', $empresa_id, $produto_id, $tipo, $quantidade, $referencia);
            $stmt->execute();
        }

        registrar_log($conn, $empresa_id, $usuario_id, 'compras', 'cadastro', 'Compra registrada #' . $compra_id . ' — R$ ' . number_format($total, 2, ',', '.'));
        $conn->commit();
        header('Location: /pages/compras.php?sucesso=1');
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: /pages/compras.php?erro=1');
    }
    exit;
}

header('Location: /pages/compras.php');
exit;