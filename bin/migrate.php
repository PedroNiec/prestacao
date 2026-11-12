<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$applied = prepare_storage();
echo $applied ? 'Aplicadas: ' . implode(', ', $applied) . PHP_EOL : "Banco já está atualizado.\n";
