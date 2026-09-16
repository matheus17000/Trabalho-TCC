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
    $cliente_id = (int) $_POST['cliente_id'];
    $pagamento = $_POST['pagamento'];
    $itens = json_decode($_POST['itens'], true);

    if (empty($itens) || !$cliente_id) {
        header('Location: /pages/vendas.php');
        exit;
    }

    // Calcula total
    $total = 0;
    foreach ($itens as $item) {
        $total += $item['subtotal'];
    }

    $conn->begin_transaction();

    try {
        // Verifica estoque de todos os itens antes de salvar
        foreach ($itens as $item) {
            $produto_id = (int) $item['produto_id'];
            $quantidade = (int) $item['quantidade'];

            $stmt = $conn->prepare("SELECT estoque FROM produtos WHERE id = ? AND empresa_id = ?");
            $stmt->bind_param('ii', $produto_id, $empresa_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row || $row['estoque'] < $quantidade) {
                throw new Exception('estoque');
            }
        }

        // Insere cabeçalho da venda
        $stmt = $conn->prepare("INSERT INTO vendas (empresa_id, cliente_id, data, pagamento, total) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iissd', $empresa_id, $cliente_id, $data, $pagamento, $total);
        $stmt->execute();
        $venda_id = $conn->insert_id;

        // Insere itens, baixa estoque e registra movimentação
        foreach ($itens as $item) {
            $produto_id = (int) $item['produto_id'];
            $quantidade = (int) $item['quantidade'];
            $valor_unitario = (float) $item['preco'];
            $subtotal = (float) $item['subtotal'];

            // Insere item
            $stmt = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, valor_unitario, subtotal) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iiidd', $venda_id, $produto_id, $quantidade, $valor_unitario, $subtotal);
            $stmt->execute();

            // Baixa estoque
            $stmt = $conn->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND empresa_id = ?");
            $stmt->bind_param('iii', $quantidade, $produto_id, $empresa_id);
            $stmt->execute();

            // Registra movimentação
            $referencia = "venda #$venda_id";
            $tipo = 'saida';
            $stmt = $conn->prepare("INSERT INTO movimentacoes (empresa_id, produto_id, tipo, quantidade, referencia) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iisis', $empresa_id, $produto_id, $tipo, $quantidade, $referencia);
            $stmt->execute();
        }

        registrar_log($conn, $empresa_id, $usuario_id, 'vendas', 'cadastro', 'Venda registrada #' . $venda_id . ' — R$ ' . number_format($total, 2, ',', '.'));
        $conn->commit();
        header('Location: /pages/vendas.php?sucesso=1');

    } catch (Exception $e) {
        $conn->rollback();
        $msg = $e->getMessage() === 'estoque' ? 'estoque' : 'geral';
        header("Location: /pages/vendas.php?erro=$msg");
    }
    exit;
}

header('Location: /pages/vendas.php');
exit;