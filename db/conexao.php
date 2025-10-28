<?php
// db/conexao.php

//error_reporting(0);
    require_once __DIR__ . '/../config.php';


// Conexão com o banco de dados
 $conexao = new mysqli($db_host, $db_user, $db_pass, $db_name );

// Verificar conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Definir charset
 $conexao->set_charset("utf8");
?>