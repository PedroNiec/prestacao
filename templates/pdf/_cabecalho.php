<?php
/** Cabeçalho comum dos relatórios (executor, concedente, processo, PP/NE/NL, objeto). Espera $projeto. */
$executor = 'MUNICÍPIO DE ' . mb_strtoupper((string) $projeto['municipio_nome']) . ' - ' . $projeto['municipio_uf'];
?>
<table class="grade cab">
    <tr><td class="rot" style="width:12%">Executor</td><td colspan="5"><?= e($executor) ?></td></tr>
    <tr><td class="rot">Concedente:</td><td colspan="5"><?= e($projeto['concedente']) ?></td></tr>
    <tr><td class="rot">Processo nº</td><td colspan="5"><?= e($projeto['processo']) ?></td></tr>
    <tr>
        <td class="rot">Nº PP</td><td style="width:22%"><?= e($projeto['nr_pp']) ?></td>
        <td class="rot" style="width:8%">Nº NE</td><td style="width:26%"><?= e($projeto['nr_ne']) ?></td>
        <td class="rot" style="width:8%">Nº NL</td><td><?= e($projeto['nr_nl']) ?></td>
    </tr>
    <tr><td class="rot">Objeto:</td><td colspan="5"><?= e($projeto['objeto']) ?></td></tr>
</table>
