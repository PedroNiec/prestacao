// Ao mudar a origem, sugere a fonte (contrapartida = 2; demais = 1). O usuário ainda pode alterar.
(function () {
    var origem = document.getElementById('origem');
    var fonte = document.getElementById('fonte');
    if (!origem || !fonte) return;
    origem.addEventListener('change', function () {
        fonte.value = origem.value === 'contrapartida' ? '2' : '1';
    });
})();
