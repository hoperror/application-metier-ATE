<?php
// config.php
$host = "localhost";
$db   = "td7_avions";      // <-- adapte si ton nom de base est différent
$user = "postgres";    // <-- adapte
$pass = "postgres";     // <-- adapte
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db;options='--client_encoding=UTF8'";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion BDD : " . $e->getMessage());
}