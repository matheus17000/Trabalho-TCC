# ERP System — TCC

Sistema ERP Web para pequenas empresas desenvolvido como Trabalho de Conclusão de Curso.

## Tecnologias

- PHP (procedural)
- MySQL com MySQLi
- HTML5 + Tailwind CSS
- JavaScript
- Dompdf (relatórios PDF)

## Funcionalidades

- Autenticação com controle de nível (admin/operador)
- Dashboard com KPIs e gráficos
- CRUD de empresas, usuários, clientes e produtos
- Registro de compras com entrada automática de estoque
- Registro de vendas com baixa automática de estoque
- Controle de estoque com histórico por produto
- Relatórios PDF com filtros (vendas, estoque, financeiro)
- Logs de atividade
- Modo escuro
- Validações de CPF, CNPJ, e-mail e senha forte
- Sistema multiempresa (SaaS simplificado)

## Instalação

1. Instalar XAMPP
2. Clonar o projeto em `C:\xampp\htdocs\erp-tcc`
3. Iniciar Apache e MySQL no XAMPP
4. Acessar `http://localhost/phpmyadmin`
5. Criar banco `erp_tcc` com collation `utf8mb4_unicode_ci`
6. Importar o arquivo `database/banco_erp.sql`
7. Instalar dependências: `composer install`
8. Acessar `http://localhost/erp-tcc`

## Estrutura de pastas

erp-tcc/
├── actions/ — processamento de formulários
├── assets/ — CSS, JS e imagens
├── auth/ — login e logout
├── database/ — script SQL
├── includes/ — componentes reutilizáveis
├── pages/ — páginas do sistema
├── pdf/ — geração de relatórios
└── vendor/ — dependências (Dompdf)

## Empresas de demonstração

**Empresa 1 — Barbearia Premium**
Revenda de produtos masculinos (pomadas, kits, acessórios)

**Empresa 2 — Burger House**
Controle de insumos e estoque operacional
