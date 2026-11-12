<?php
// Banco isolado para os testes: nunca toca em storage/database.sqlite.
$testDb = sys_get_temp_dir() . '/prestacao_test_' . getmypid() . '.sqlite';
putenv('PRESTACAO_DB=' . $testDb);
$testStorage = sys_get_temp_dir() . '/prestacao_storage_' . getmypid();
mkdir($testStorage, 0750, true);
putenv('PRESTACAO_STORAGE=' . $testStorage);
register_shutdown_function(static function () use ($testDb, $testStorage): void {
    if (is_dir($testStorage)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testStorage, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($testStorage);
    }
    foreach ([$testDb, $testDb . '-wal', $testDb . '-shm'] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
});

require dirname(__DIR__) . '/src/bootstrap.php';
prepare_storage();
