<?php
/** Local, data por extenso e assinaturas. Espera $projeto. */
use App\Domain\Dates;

$data = Dates::extenso((string) $projeto['data_assinatura']);
$soPrefeito = $rodapeSoPrefeito ?? false;        // conciliação: só o Prefeito assina
$sep = $rodapeSeparador ?? ', ';
$crc = trim((string) $projeto['contador_crc']);
?>
<div class="assinaturas">
    <p class="local"><?= e($projeto['local']) ?><?= $data !== '' ? $sep . e($data) : '' ?></p>
    <table class="ass">
        <tr>
            <?php if (!$soPrefeito): ?>
            <td>
                <div class="linha-ass"></div>
                <strong><?= e($projeto['contador_nome']) ?></strong><br>
                Contador<?= $crc !== '' ? ' - ' . e($crc) : '' ?>
            </td>
            <?php endif; ?>
            <td>
                <div class="linha-ass"></div>
                <strong><?= e($projeto['prefeito_nome']) ?></strong><br>
                <?= $soPrefeito ? 'Prefeito Municipal' : 'Prefeito' ?>
            </td>
        </tr>
    </table>
</div>
