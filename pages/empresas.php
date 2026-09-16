<?php
session_start();
require_once '../includes/acesso.php';
verificar_acesso('admin');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Empresas';

$stmt = $conn->prepare("SELECT * FROM empresas ORDER BY nome ASC");
$stmt->execute();
$empresas = $stmt->get_result();
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
                <h3 class="text-lg font-semibold text-gray-700">Lista de Empresas</h3>
                <button onclick="abrirModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Nova Empresa
                </button>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                    $msgs = [
                        'criado' => 'Empresa cadastrada com sucesso!',
                        'editado' => 'Empresa atualizada com sucesso!',
                        'deletado' => 'Empresa excluída com sucesso!',
                    ];
                    echo $msgs[$_GET['sucesso']] ?? 'Operação realizada!';
                    ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Nome</th>
                            <th class="px-6 py-3 text-left">CNPJ</th>
                            <th class="px-6 py-3 text-left">Telefone</th>
                            <th class="px-6 py-3 text-left">E-mail</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($empresas->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Nenhuma empresa cadastrada ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($e = $empresas->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($e['nome']) ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($e['cnpj'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($e['telefone'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($e['email'] ?? '—') ?></td>
                                    <td class="px-6 py-3 text-center space-x-2">
                                        <button
                                            onclick="abrirEdicao(<?= $e['id'] ?>,'<?= addslashes($e['nome']) ?>','<?= addslashes($e['cnpj'] ?? '') ?>','<?= addslashes($e['telefone'] ?? '') ?>','<?= addslashes($e['email'] ?? '') ?>')"
                                            class="text-blue-600 hover:underline text-xs">Editar</button>
                                        <button onclick="confirmarDelete(<?= $e['id'] ?>,'<?= addslashes($e['nome']) ?>')"
                                            class="text-red-500 hover:underline text-xs">Excluir</button>
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

    <!-- Modal cadastro/edição -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 id="modal-titulo" class="text-lg font-semibold text-gray-800 mb-4">Nova Empresa</h2>
            <form method="POST" action="/actions/salvar_empresa.php" onsubmit="return validarFormEmpresa()">
                <input type="hidden" name="id" id="campo-id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="nome" id="campo-nome" required maxlength="150"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ *</label>
                    <input type="text" name="cnpj" id="campo-cnpj" required placeholder="00.000.000/0001-00"
                        maxlength="18" oninput="mascaraCNPJ(this)"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-cnpj" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" id="campo-telefone" placeholder="(00) 00000-0000" maxlength="15"
                        oninput="mascaraTelefone(this)"
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

    <!-- Modal exclusão -->
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
            document.getElementById('modal-titulo').textContent = 'Nova Empresa';
            document.getElementById('campo-id').value = '';
            document.getElementById('campo-nome').value = '';
            document.getElementById('campo-cnpj').value = '';
            document.getElementById('campo-telefone').value = '';
            document.getElementById('campo-email').value = '';
            limparTodosErros(['nome', 'cnpj', 'telefone', 'email']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function abrirEdicao(id, nome, cnpj, telefone, email) {
            document.getElementById('modal-titulo').textContent = 'Editar Empresa';
            document.getElementById('campo-id').value = id;
            document.getElementById('campo-nome').value = nome;
            document.getElementById('campo-cnpj').value = cnpj;
            document.getElementById('campo-telefone').value = telefone;
            document.getElementById('campo-email').value = email;
            limparTodosErros(['nome', 'cnpj', 'telefone', 'email']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
        }
        function confirmarDelete(id, nome) {
            document.getElementById('msg-delete').textContent = 'Deseja excluir a empresa "' + nome + '"?';
            document.getElementById('link-delete').href = '/actions/salvar_empresa.php?deletar=' + id;
            document.getElementById('modal-delete').classList.remove('hidden');
        }
        function fecharDelete() {
            document.getElementById('modal-delete').classList.add('hidden');
        }
        function validarFormEmpresa() {
            limparTodosErros(['nome', 'cnpj', 'telefone', 'email']);
            let valido = true;

            const nome = document.getElementById('campo-nome').value.trim();
            if (nome.length < 2) {
                mostrarErro('nome', 'Nome deve ter pelo menos 2 caracteres.');
                valido = false;
            }
            const cnpj = document.getElementById('campo-cnpj').value.trim();
            if (cnpj !== '' && !validarCNPJ(cnpj)) {
                mostrarErro('cnpj', 'CNPJ inválido.');
                valido = false;
            }
            const telefone = document.getElementById('campo-telefone').value.trim();
            if (telefone !== '' && !validarTelefone(telefone)) {
                mostrarErro('telefone', 'Telefone inválido.');
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

</html>