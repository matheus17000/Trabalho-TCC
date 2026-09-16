<?php
session_start();
require_once '../includes/acesso.php';
verificar_acesso('admin');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';

$titulo = 'Usuários';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE empresa_id = ? ORDER BY nome ASC");
$stmt->bind_param('i', $_SESSION['empresa_id']);
$stmt->execute();
$usuarios = $stmt->get_result();
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
                <h3 class="text-lg font-semibold text-gray-700">Lista de Usuários</h3>
                <button onclick="abrirModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Novo Usuário
                </button>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                    $msgs = [
                        'criado' => 'Usuário cadastrado com sucesso!',
                        'editado' => 'Usuário atualizado com sucesso!',
                        'deletado' => 'Usuário excluído com sucesso!',
                    ];
                    echo $msgs[$_GET['sucesso']] ?? 'Operação realizada!';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                    $erros = [
                        'email_duplicado' => 'Este e-mail já está cadastrado.',
                    ];
                    echo $erros[$_GET['erro']] ?? 'Erro ao realizar operação.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Nome</th>
                            <th class="px-6 py-3 text-left">E-mail</th>
                            <th class="px-6 py-3 text-center">Nível</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($usuarios->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Nenhum usuário cadastrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($u = $usuarios->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($u['nome']) ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="px-6 py-3 text-center">
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full
                                        <?= $u['nivel'] === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' ?>">
                                            <?= $u['nivel'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center space-x-2">
                                        <button
                                            onclick="abrirEdicao(<?= $u['id'] ?>,'<?= addslashes($u['nome']) ?>','<?= addslashes($u['email']) ?>','<?= $u['nivel'] ?>')"
                                            class="text-blue-600 hover:underline text-xs">Editar</button>
                                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                            <button onclick="confirmarDelete(<?= $u['id'] ?>,'<?= addslashes($u['nome']) ?>')"
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

    <!-- Modal cadastro/edição -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 id="modal-titulo" class="text-lg font-semibold text-gray-800 mb-4">Novo Usuário</h2>
            <form method="POST" action="/actions/salvar_usuario.php" onsubmit="return validarFormUsuario()">
                <input type="hidden" name="id" id="campo-id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="nome" id="campo-nome" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                    <input type="email" name="email" id="campo-email" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-email" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Senha <span id="label-senha">(obrigatória)</span>
                    </label>
                    <input type="password" name="senha" id="campo-senha" placeholder="Mínimo 8 caracteres"
                        maxlength="32" oninput="mascaraSenha(this); mostrarForcaSenha(this.value)"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-senha" class="text-red-500 text-xs mt-1 hidden"></p>

                    <div id="forca-senha" class="mt-2 hidden">
                        <div class="flex gap-1 mb-1">
                            <div id="barra-1" class="h-1 flex-1 rounded bg-gray-200"></div>
                            <div id="barra-2" class="h-1 flex-1 rounded bg-gray-200"></div>
                            <div id="barra-3" class="h-1 flex-1 rounded bg-gray-200"></div>
                            <div id="barra-4" class="h-1 flex-1 rounded bg-gray-200"></div>
                        </div>
                        <p id="texto-forca" class="text-xs text-gray-500"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
                    <input type="password" name="senha_confirm" id="campo-senha-confirm" maxlength="32"
                        oninput="mascaraSenha(this)"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="erro-senha-confirm" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nível *</label>
                    <select name="nivel" id="campo-nivel"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="operador">Operador</option>
                        <option value="admin">Admin</option>
                    </select>
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

        let modoEdicao = false;

        function abrirModal() {
            modoEdicao = false;
            document.getElementById('modal-titulo').textContent = 'Novo Usuário';
            document.getElementById('campo-id').value = '';
            document.getElementById('campo-nome').value = '';
            document.getElementById('campo-email').value = '';
            document.getElementById('campo-senha').value = '';
            document.getElementById('campo-senha-confirm').value = '';
            document.getElementById('campo-senha').required = true;
            document.getElementById('label-senha').textContent = '(obrigatória)';
            document.getElementById('campo-nivel').value = 'operador';
            document.getElementById('forca-senha').classList.add('hidden');
            limparTodosErros(['nome', 'email', 'senha', 'senha-confirm']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function abrirEdicao(id, nome, email, nivel) {
            modoEdicao = true;
            document.getElementById('modal-titulo').textContent = 'Editar Usuário';
            document.getElementById('campo-id').value = id;
            document.getElementById('campo-nome').value = nome;
            document.getElementById('campo-email').value = email;
            document.getElementById('campo-senha').value = '';
            document.getElementById('campo-senha-confirm').value = '';
            document.getElementById('campo-senha').required = false;
            document.getElementById('label-senha').textContent = '(deixe em branco para não alterar)';
            document.getElementById('campo-nivel').value = nivel;
            document.getElementById('forca-senha').classList.add('hidden');
            limparTodosErros(['nome', 'email', 'senha', 'senha-confirm']);
            document.getElementById('modal').classList.remove('hidden');
        }
        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
        }
        function confirmarDelete(id, nome) {
            document.getElementById('msg-delete').textContent = 'Deseja excluir o usuário "' + nome + '"?';
            document.getElementById('link-delete').href = '/actions/salvar_usuario.php?deletar=' + id;
            document.getElementById('modal-delete').classList.remove('hidden');
        }
        function fecharDelete() {
            document.getElementById('modal-delete').classList.add('hidden');
        }
        function mostrarForcaSenha(senha) {
            const div = document.getElementById('forca-senha');
            if (senha.length === 0) {div.classList.add('hidden'); return;}
            div.classList.remove('hidden');

            const erros = validarSenha(senha);
            const forca = 5 - erros.length;
            const cores = ['bg-red-500', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
            const textos = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];

            ['barra-1', 'barra-2', 'barra-3', 'barra-4'].forEach((b, i) => {
                const el = document.getElementById(b);
                el.className = 'h-1 flex-1 rounded bg-gray-200';
                if (i < forca) el.classList.add(cores[Math.min(forca - 1, 3)]);
            });
            document.getElementById('texto-forca').textContent = textos[Math.min(forca, 4)] || '';
        }
        function validarFormUsuario() {
            limparTodosErros(['nome', 'email', 'senha', 'senha-confirm']);
            let valido = true;

            const nome = document.getElementById('campo-nome').value.trim();
            if (nome.length < 2) {
                mostrarErro('nome', 'Nome deve ter pelo menos 2 caracteres.');
                valido = false;
            }
            const email = document.getElementById('campo-email').value.trim();
            if (!validarEmail(email)) {
                mostrarErro('email', 'E-mail inválido.');
                valido = false;
            }
            const senha = document.getElementById('campo-senha').value;
            const senhaConfirm = document.getElementById('campo-senha-confirm').value;

            if (!modoEdicao || senha !== '') {
                if (!modoEdicao && senha === '') {
                    mostrarErro('senha', 'Senha obrigatória.');
                    valido = false;
                } else if (senha !== '') {
                    const erros = validarSenha(senha);
                    if (erros.length > 0) {
                        mostrarErro('senha', erros.join(' | '));
                        valido = false;
                    } else if (senha !== senhaConfirm) {
                        mostrarErro('senha-confirm', 'As senhas não coincidem.');
                        valido = false;
                    }
                }
            }
            return valido;
        }
    </script>

</body>

</html>