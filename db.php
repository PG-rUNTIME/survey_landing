<?php
declare(strict_types=1);

/**
 * Returns a configured PDO instance.
 * Replace the password below or read it from an environment variable for security.
 */
function getPdo(): PDO
{
    $host    = '10.169.39.99';
    $db      = 'AI_readiness_survey';
    $user    = 'litadmin';
    $pass    = 'Pa$$w0rd'; // e.g., getenv('DB_PASS')
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $user, $pass, $options);
}