<?php
session_start();
require_once '../includes/acesso.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Clientes';

$busca = trim($_GET['busca'] ?? '');

if ($busca !== '') {
    $like = "%$busca%";
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE empresa_id = ? AND (nome LIKE ? OR telefone LIKE ? OR email LIKE ?) ORDER BY nome ASC");
    $stmt->bind_param('isss', $_SESSION['empresa_id'], $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE empresa_id = ? ORDER BY nome ASC");
    $stmt->bind_param('i', $_SESSION['empresa_id']);
}
$stmt->execute();
$clientes = $stmt->get_result();
$total_encontrados = $clientes->num_rows;
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

            <!-- Modal cadastro/edição -->
            <div id="modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h2 id="modal-titulo" class="text-lg font-semibold text-gray-800 mb-4">Novo Cliente</h2>
                    <form method="POST" action="/actions/salvar_cliente.php"
                        onsubmit="return validarFormCliente()">
                        <input type="hidden" name="id" id="campo-id">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                            <input type="text" name="nome" id="campo-nome" required maxlength="150"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" name="cpf" id="campo-cpf" placeholder="000.000.000-00" maxlength="14"
                                oninput="mascaraCPF(this)"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-cpf" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" id="campo-telefone" placeholder="(00) 00000-0000"
                                maxlength="15" oninput="mascaraTelefone(this)"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-telefone" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" id="campo-email" maxlength="120"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-email" class="text-red-500 text-xs mt-1 hidden"></p>
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

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                    $msgs = [
                        'criado' => 'Cliente cadastrado com sucesso!',
                        'editado' => 'Cliente atualizado com sucesso!',
                        'deletado' => 'Cliente excluído com sucesso!',
                    ];
                    echo $msgs[$_GET['sucesso']] ?? 'Operação realizada!';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Filtro -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-700">Lista de Clientes</h3>
                <button onclick="abrirModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Novo Cliente
                </button>
            </div>

            <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex gap-3">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                    placeholder="Buscar por nome, telefone ou e-mail..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition">
                    Buscar
                </button>
                <?php if ($busca !== ''): ?>
                    <a href="/pages/clientes.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg transition">
                        Limpar
                    </a>
                <?php endif; ?>
            </form>

            <p class="text-sm text-gray-500 mb-3">
                <?= $total_encontrados ?> cliente<?= $total_encontrados !== 1 ? 's' : '' ?>
                encontrado<?= $total_encontrados !== 1 ? 's' : '' ?>
            </p>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Nome</th>
                            <th class="px-6 py-3 text-left">CPF</th>
                            <th class="px-6 py-3 text-left">Telefone</th>
                            <th class="px-6 py-3 text-left">E-mail</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($clientes->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    <?= $busca !== '' ? 'Nenhum cliente encontrado para "' . htmlspecialchars($busca) . '".' : 'Nenhum cliente cadastrado ainda.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($c = $clientes->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($c['nome']) ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($c['cpf'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-center space-x-2">
                                        <button
                                            onclick="abrirEdicao(<?= $c['id'] ?>,'<?= addslashes($c['nome']) ?>','<?= addslashes($c['cpf'] ?? '') ?>','<?= addslashes($c['telefone'] ?? '') ?>','<?= addslashes($c['email'] ?? '') ?>')"
                                            class="text-blue-600 hover:underline text-xs">Editar</button>
                                        <?php if (is_admin()): ?>
                                            <button onclick="confirmarDelete(<?= $c['id'] ?>,'<?= addslashes($c['nome']) ?>')"
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
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 id="modal-titulo" class="text-lg font-semibold text-gray-800 mb-4">Novo Cliente</h2>
            <form method="POST" action="/actions/salvar_cliente.php">
                <input type="hidden" name="id" id="campo-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="nome" id="campo-nome" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                    <input type="text" name="cpf" id="campo-cpf"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" id="campo-telefone"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" id="campo-email"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            document.getElementById('modal-titulo').textContent = 'Novo Cliente';
            document.getElementById('campo-id').value = '';
            document.getElementById('campo-nome').value = '';
            document.getElementById('campo-cpf').value = '';
            document.getElementById('campo-telefone').value = '';
            document.getElementById('campo-email').value = '';
            limparTodosErros(['nome', 'cpf', 'telefone', 'email']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function abrirEdicao(id, nome, cpf, telefone, email) {
            document.getElementById('modal-titulo').textContent = 'Editar Cliente';
            document.getElementById('campo-id').value = id;
            document.getElementById('campo-nome').value = nome;
            document.getElementById('campo-cpf').value = cpf;
            document.getElementById('campo-telefone').value = telefone;
            document.getElementById('campo-email').value = email;
            limparTodosErros(['nome', 'cpf', 'telefone', 'email']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
        }
        function confirmarDelete(id, nome) {
            document.getElementById('msg-delete').textContent = 'Deseja excluir o cliente "' + nome + '"?';
            document.getElementById('link-delete').href = '/actions/salvar_cliente.php?deletar=' + id;
            document.getElementById('modal-delete').classList.remove('hidden');
        }
        function fecharDelete() {
            document.getElementById('modal-delete').classList.add('hidden');
        }

        function validarFormCliente() {
            limparTodosErros(['nome', 'cpf', 'telefone', 'email']);
            let valido = true;

            const nome = document.getElementById('campo-nome').value.trim();
            if (nome.length < 2) {
                mostrarErro('nome', 'Nome deve ter pelo menos 2 caracteres.');
                valido = false;
            }

            const cpf = document.getElementById('campo-cpf').value.trim();
            if (cpf !== '' && !validarCPF(cpf)) {
                mostrarErro('cpf', 'CPF inválido.');
                valido = false;
            }

            const telefone = document.getElementById('campo-telefone').value.trim();
            if (telefone !== '' && !validarTelefone(telefone)) {
                mostrarErro('telefone', 'Telefone inválido. Use formato (00) 00000-0000.');
                valido = false;
            }

            const email = document.getElementById('campo-email').value.trim();
            if (email !== '' && !validarEmail(email)) {
                mostrarErro('email', 'E-mail inválido.');
                valido = false;
            }

            return valido;
        }
    </script>

</body>

</html>F