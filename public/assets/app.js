// Fecha as mensagens (botão ×). Sem JavaScript, a mensagem simplesmente permanece na tela.
document.addEventListener('click', function (e) {
    var botao = e.target.closest('.msg-fechar');
    if (!botao) return;
    var msg = botao.closest('.msg');
    var regiao = msg && msg.parentNode;
    if (msg) msg.remove();
    if (regiao && regiao.classList.contains('alertas') && !regiao.querySelector('.msg')) regiao.remove();
});
