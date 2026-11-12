// Reordenação por arraste e solte. Ao soltar, envia a nova ordem ao servidor.
(function () {
    var lista = document.getElementById('lista');
    var form = document.getElementById('form-ordem');
    if (!lista || !form) return;
    var arrastando = null;

    lista.addEventListener('dragstart', function (e) {
        arrastando = e.target.closest('li');
        if (!arrastando) return;
        arrastando.classList.add('arrastando');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', arrastando.dataset.id);
    });
    lista.addEventListener('dragover', function (e) {
        e.preventDefault();
        var alvo = e.target.closest('li');
        if (!arrastando || !alvo || alvo === arrastando) return;
        var r = alvo.getBoundingClientRect();
        var depois = (e.clientY - r.top) > r.height / 2;
        lista.insertBefore(arrastando, depois ? alvo.nextSibling : alvo);
    });
    lista.addEventListener('drop', function (e) { e.preventDefault(); });
    lista.addEventListener('dragend', function () {
        if (!arrastando) return;
        arrastando.classList.remove('arrastando');
        arrastando = null;
        form.submit();
    });
})();
