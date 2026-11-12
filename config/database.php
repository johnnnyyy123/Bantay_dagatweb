<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('BDM_DB_HOST') ?: '127.0.0.1';
    $name = getenv('BDM_DB_NAME') ?: 'bantay_dagat_marinduque';
    $user = getenv('BDM_DB_USER') ?: 'root';
    $password = getenv('BDM_DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
