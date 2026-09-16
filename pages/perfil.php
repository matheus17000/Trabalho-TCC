<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
require_once '../conexao.php';
require_once '../includes/acesso.php';

$titulo = 'Meu Perfil';
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND empresa_id = ?");
$stmt->bind_param('ii', $usuario_id, $empresa_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
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

            <h3 class="text-lg font-semibold text-gray-700 mb-6">Meu Perfil</h3>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                    Perfil atualizado com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
                    <?php
                        $erros = [
                            'senha_invalida' => 'Senha atual incorreta.',
                            'email_duplicado' => 'Este e-mail já está em uso.',
                        ];
                        echo $erros[$_GET['erro']] ?? 'Erro ao atualizar perfil.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Dados pessoais -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">👤 Dados pessoais</h4>
                    <form method="POST" action="/actions/salvar_perfil.php" onsubmit="return validarDados()">
                        <input type="hidden" name="acao" value="dados">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                            <input type="text" name="nome" id="campo-nome" required maxlength="100"
                                value="<?= htmlspecialchars($usuario['nome']) ?>"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-nome" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                            <input type="email" name="email" id="campo-email" required maxlength="120"
                                value="<?= htmlspecialchars($usuario['email']) ?>"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-email" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                            <input type="text" disabled
                                value="<?= ucfirst($usuario['nivel']) ?>"
                                class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                        </div>

                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                            Salvar dados
                        </button>
                    </form>
                </div>

                <!-- Alterar senha -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">🔒 Alterar senha</h4>
                    <form method="POST" action="/actions/salvar_perfil.php" onsubmit="return validarSenhas()">
                        <input type="hidden" name="acao" value="senha">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha atual *</label>
                            <input type="password" name="senha_atual" id="campo-senha-atual" required maxlength="32"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-senha-atual" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha *</label>
                            <input type="password" name="nova_senha" id="campo-nova-senha" required maxlength="32"
                                oninput="mostrarForca(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-nova-senha" class="text-red-500 text-xs mt-1 hidden"></p>

                            <!-- Indicador força -->
                            <div id="forca-div" class="mt-2 hidden">
                                <div class="flex gap-1 mb-1">
                                    <div id="b1" class="h-1 flex-1 rounded bg-gray-200"></div>
                                    <div id="b2" class="h-1 flex-1 rounded bg-gray-200"></div>
                                    <div id="b3" class="h-1 flex-1 rounded bg-gray-200"></div>
                                    <div id="b4" class="h-1 flex-1 rounded bg-gray-200"></div>
                                </div>
                                <p id="texto-forca" class="text-xs text-gray-500"></p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha *</label>
                            <input type="password" name="confirmar_senha" id="campo-confirmar-senha" required maxlength="32"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="erro-confirmar-senha" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                            Alterar senha
                        </button>
                    </form>
                </div>

            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <script src="/assets/js/validacoes.js"></script>
    <script>
        function validarDados() {
            limparTodosErros(['nome', 'email']);
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
            return valido;
        }

        function mostrarForca(senha) {
            const div = document.getElementById('forca-div');
            if (senha.length === 0) { div.classList.add('hidden'); return; }
            div.classList.remove('hidden');
            const erros = validarSenha(senha);
            const forca = 5 - erros.length;
            const cores = ['bg-red-500','bg-orange-400','bg-yellow-400','bg-green-500'];
            const textos = ['','Fraca','Razoável','Boa','Forte'];
            ['b1','b2','b3','b4'].forEach((b, i) => {
                const el = document.getElementById(b);
                el.className = 'h-1 flex-1 rounded bg-gray-200';
                if (i < forca) el.classList.add(cores[Math.min(forca-1, 3)]);
            });
            document.getElementById('texto-forca').textContent = textos[Math.min(forca, 4)] || '';
        }

        function validarSenhas() {
            limparTodosErros(['senha-atual','nova-senha','confirmar-senha']);
            let valido = true;
            const senhaAtual = document.getElementById('campo-senha-atual').value;
            if (senhaAtual === '') {
                mostrarErro('senha-atual', 'Informe a senha atual.');
                valido = false;
            }
            const novaSenha = document.getElementById('campo-nova-senha').value;
            if (novaSenha !== '') {
                const erros = validarSenha(novaSenha);
                if (erros.length > 0) {
                    mostrarErro('nova-senha', erros.join(' | '));
                    valido = false;
                }
            }
            const confirmar = document.getElementById('campo-confirmar-senha').value;
            if (novaSenha !== confirmar) {
                mostrarErro('confirmar-senha', 'As senhas não coincidem.');
                valido = false;
            }
            return valido;
        }
    </script>

</body>
</html>