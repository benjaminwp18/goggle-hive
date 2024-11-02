<?php
    $pdo_location = 'localhost';
    $pdo_username = 'your username';
    $pdo_password = 'your password';
    $pdo_db = 'your database name';

    $startTrans = 'START TRANSACTION';
    $commitTrans = 'COMMIT';

    try {
        $link = new PDO("mysql:host=$pdo_location;dbname=$pdo_db", $pdo_username, $pdo_password);
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }