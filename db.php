<?php
/**
 * Função centralizada para conexão com o banco de dados via PDO.
 */
function conectar() {
    $host = 'localhost';
    $dbname = 'rcl_db'; // Nome do banco de dados 
    $user = 'root';
    $password = ''; // Senha do seu ambiente local
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    return new PDO($dsn, $user, $password, $options);
}