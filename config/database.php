<?php
// config/database.php

$host    = 'localhost';
$port    = '5432';          // Default PostgreSQL port
$db      = 'peertutoring';  // The database name we will connect to
$user    = 'postgres';      // Your default Postgres username
$pass    = 'postgres'; // 💡 Change this to your actual password!

// The PDO DSN string specifically formatted for PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throws SQL errors as catchable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns arrays indexed by column name
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Uses native prepared statements for security
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("PostgreSQL Connection Breakdown: " . $e->getMessage());
}