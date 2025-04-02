<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbName = "tp_session1";

$conn = mysqli_connect($host, $user, $pass, $dbName);

if (!$conn) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}
?>