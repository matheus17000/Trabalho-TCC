<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/dark.css">
<title>ERP — <?= $titulo ?? 'Sistema' ?></title>
<script>
    // Aplica modo escuro antes de renderizar para evitar flash
    if (localStorage.getItem('dark') === '1') {
        document.documentElement.classList.add('dark-loading');
    }
</script>