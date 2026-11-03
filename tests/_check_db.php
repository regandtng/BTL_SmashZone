<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "MySQL OK\n";
    foreach ($pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN) as $db) {
        echo $db . "\n";
    }
} catch (Throwable $e) {
    echo 'MySQL FAIL: ' . $e->getMessage() . "\n";
}
