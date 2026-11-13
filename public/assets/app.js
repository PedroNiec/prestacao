// Fecha as mensagens (botão ×). Sem JavaScript, a mensagem simplesmente permanece na tela.
document.addEventListener('click', function (e) {
    var botao = e.target.closest('.msg-fechar');
    if (!botao) return;
    var msg = botao.closest('.msg');
    var regiao = msg && msg.parentNode;
    if (msg) msg.remove();
    if (regiao && regiao.classList.contains('alertas') && !regiao.querySelector('.msg')) regiao.remove();
});

// Recolhe/expande o menu lateral; a escolha fica guardada neste navegador (localStorage).
(function () {
    var raiz = document.documentElement, botao = document.querySelector('.lateral-toggle');
    if (!botao) return;
    function aplicar(recolhida) {
        raiz.classList.toggle('lateral-recolhida', recolhida);
        botao.setAttribute('aria-expanded', recolhida ? 'false' : 'true');
        botao.setAttribute('aria-label', recolhida ? 'Expandir menu lateral' : 'Recolher menu lateral');
    }
    aplicar(raiz.classList.contains('lateral-recolhida'));
    botao.addEventListener('click', function () {
        var recolhida = !raiz.classList.contains('lateral-recolhida');
        aplicar(recolhida);
        try { localStorage.setItem('lateral', recolhida ? '1' : '0'); } catch (e) {}
    });
})();

// Menus ⋮ e painéis de envio (<details>): abrir um fecha os outros; clicar fora ou apertar Esc fecha. Sem JavaScript, continuam abrindo/fechando no próprio botão.
(function () {
    var SEL = 'details.menu-mais[open], details.doc-envio[open]';
    function fechar(exceto) {
        document.querySelectorAll(SEL).forEach(function (d) {
            if (d === exceto) return;
            d.removeAttribute('open');
            d.querySelectorAll('details[open]').forEach(function (n) { n.removeAttribute('open'); });
        });
    }
    document.addEventListener('click', function (e) {
        fechar(e.target.closest('details.menu-mais, details.doc-envio'));
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fechar(null); });
})();
