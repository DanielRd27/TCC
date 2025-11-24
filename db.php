<?php
/**
 * Função centralizada para conexão com o banco de dados via PDO.
 */
function conectar() {
    $host = 'localhost';
    $dbname = 'rcl_db';
    $user = 'root';
    $password = 'Senai@118';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        return new PDO($dsn, $user, $password, $options);
    } catch (PDOException $e) {
        // Em produção, logue o erro em vez de exibir
        die("Erro de conexão: " . $e->getMessage());
    }
}