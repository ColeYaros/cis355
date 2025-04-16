<?php 
<<<<<<< HEAD
require "../database/database.php"; 
=======
require "database.php"; 
>>>>>>> dc1a4a93f9d04c9eb26bc2ed267c4e1ae43bcf21
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = "SELECT * FROM iss_persons where id = ? LIMIT 1";
$q = $pdo->prepare($sql);
$id = 1;
$q->execute(array($id));
$data = $q->fetch(PDO::FETCH_ASSOC);
print_r($data);
?>