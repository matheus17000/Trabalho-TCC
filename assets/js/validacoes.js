// ============================================================
// Validações e Máscaras reutilizáveis
// ============================================================

// CPF
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length !== 11) return false;
    if (/^(\d)\1+$/.test(cpf)) return false;
    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf[9])) return false;
    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    return resto === parseInt(cpf[10]);
}

// CNPJ
function validarCNPJ(cnpj) {
    cnpj = cnpj.replace(/[^\d]/g, '');
    if (cnpj.length !== 14) return false;
    if (/^(\d)\1+$/.test(cnpj)) return false;
    let tamanho = cnpj.length - 2;
    let numeros = cnpj.substring(0, tamanho);
    let digitos = cnpj.substring(tamanho);
    let soma = 0, pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
        if (pos < 2) pos = 9;
    }
    let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado !== parseInt(digitos.charAt(0))) return false;
    tamanho++;
    numeros = cnpj.substring(0, tamanho);
    soma = 0; pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    return resultado === parseInt(digitos.charAt(1));
}

// E-mail
function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Telefone
function validarTelefone(tel) {
    tel = tel.replace(/[^\d]/g, '');
    return tel.length >= 10 && tel.length <= 11;
}

// Senha forte
function validarSenha(senha) {
    const erros = [];
    if (senha.length < 8)            erros.push('Mínimo 8 caracteres');
    if (!/[A-Z]/.test(senha))        erros.push('Pelo menos uma maiúscula');
    if (!/[a-z]/.test(senha))        erros.push('Pelo menos uma minúscula');
    if (!/[0-9]/.test(senha))        erros.push('Pelo menos um número');
    if (!/[^A-Za-z0-9]/.test(senha)) erros.push('Pelo menos um especial (!@#$...)');
    return erros;
}

// ============================================================
// MÁSCARAS
// ============================================================

function mascaraCPF(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = v;
}

function mascaraCNPJ(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    input.value = v;
}

function mascaraTelefone(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 11);
    if (v.length <= 10) {
        v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    input.value = v;
}

function mascaraSenha(input) {
    // Remove espaços da senha
    input.value = input.value.replace(/\s/g, '');
}

function mascaraPreco(input) {
    let v = input.value.replace(/\D/g, '');
    v = (parseInt(v) / 100).toFixed(2);
    v = v.replace('.', ',');
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = v;
}

// ============================================================
// ERROS
// ============================================================

function mostrarErro(id, msg) {
    const el = document.getElementById('erro-' + id);
    if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    const campo = document.getElementById('campo-' + id);
    if (campo) campo.classList.add('border-red-500');
}

function limparErro(id) {
    const el = document.getElementById('erro-' + id);
    if (el) { el.textContent = ''; el.classList.add('hidden'); }
    const campo = document.getElementById('campo-' + id);
    if (campo) campo.classList.remove('border-red-500');
}

function limparTodosErros(ids) {
    ids.forEach(id => limparErro(id));
}