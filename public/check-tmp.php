<?php
echo "Temporary directory check:\n";
echo "sys_get_temp_dir(): " . sys_get_temp_dir() . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";

$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "\nUsing temp dir: $tmpDir\n";

// Проверим права
echo "Is writable: " . (is_writable($tmpDir) ? 'YES' : 'NO') . "\n";
echo "Is readable: " . (is_readable($tmpDir) ? 'YES' : 'NO') . "\n";

// Попробуем создать файл
$testFile = $tmpDir . '/php_test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Can write file: YES\n";
    echo "File created: $testFile\n";
    unlink($testFile);
} else {
    echo "Can write file: NO\n";
}

// Проверим свободное место
echo "\nDisk free space: " . round(disk_free_space($tmpDir) / 1024 / 1024, 2) . " MB\n";
?>