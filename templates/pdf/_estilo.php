<?php $margem ??= '28pt 14pt 28pt 28pt'; $fonte ??= '8pt'; ?>
<style>
    @page { margin: <?= $margem ?>; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: <?= $fonte ?>; color: #000; }
    table { border-collapse: collapse; width: 100%; }
    table.grade td, table.grade th { border: 0.7pt solid #000; padding: 3pt 3pt; vertical-align: middle; }
    .cab { margin-bottom: 0; }
    .rot { font-weight: bold; }
    .titulo { border: 0.7pt solid #000; border-top: 0; text-align: center; font-weight: bold; font-size: 10pt; padding: 5pt 0; }
    th { font-weight: bold; text-align: center; }
    td.c { text-align: center; }
    td.v { text-align: center; white-space: nowrap; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .total td { font-weight: bold; }
    .semborda td, .semborda th { border: 0; }
    .assinaturas { margin-top: 28pt; page-break-inside: avoid; }
    .local { text-align: center; font-size: 9pt; margin: 0 0 34pt; }
    table.ass td { width: 50%; text-align: center; font-size: 9pt; padding: 0 20pt; }
    .linha-ass { border-top: 0.7pt solid #000; margin: 0 30pt 3pt; }
    .vazio { text-align: center; padding: 10pt; font-style: italic; }
</style>
