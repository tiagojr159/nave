<?php
require_once '../config.php';

// Conexão com o banco de dados
 $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Definir charset
 $conexao->set_charset("utf8");
?>