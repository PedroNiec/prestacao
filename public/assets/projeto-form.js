// Ao escolher o município, preenche local e assinaturas que ainda estiverem vazios.
(function () {
    var sel = document.getElementById('municipio_id');
    if (!sel) return;
    var map = { local: 'local', prefeito_nome: 'prefeito', contador_nome: 'contador', contador_crc: 'crc' };
    sel.addEventListener('change', function () {
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;
        Object.keys(map).forEach(function (id) {
            var input = document.getElementById(id);
            if (input && input.value.trim() === '') input.value = opt.dataset[map[id]] || '';
        });
    });
})();
