<?php

/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

/*
 * O código seguinte retorna os dados detalhados de um usuário.
 * Essa é uma requisição do tipo GET. O usuário é identificado
 * pelo campo "login".
 */

// Conexão com o banco de dados
require_once('conexao_db.php');

// Array de resposta
$resposta = array();

// Verifica se o parâmetro "login" foi enviado na requisição
if (isset($_GET["login"])) {
    
    // Obtém o parâmetro
    $login = $_GET['login'];

    // Prepara a consulta para buscar os detalhes do usuário
    $consulta = $db_con->prepare("SELECT 
                                    u.login AS usuario_login,
                                    u.nome AS usuario_nome,
                                    u.foto AS usuario_foto,
                                    u.cidade AS usuario_cidade,
                                    u.data_nascimento AS usuario_data_nascimento
                                  FROM 
                                    public.usuario u
                                  WHERE 
                                    u.login = :login");

    // Vincula o parâmetro à consulta para evitar SQL Injection
    $consulta->bindParam(':login', $login, PDO::PARAM_STR);

    // Executa a consulta
    if ($consulta->execute()) {
        // Verifica se o usuário foi encontrado
        if ($consulta->rowCount() > 0) {

            // Se o usuário existe, pega os dados
            $linha = $consulta->fetch(PDO::FETCH_ASSOC);
            
            $resposta["login"] = $linha["usuario_login"];
            $resposta["data_nascimento"] = $linha["usuario_data_nascimento"];
            $resposta["nome"] = $linha["usuario_nome"];
            $resposta["foto"] = $linha["usuario_foto"];
            $resposta["cidade"] = $linha["usuario_cidade"];
            
            // Retorna sucesso
            $resposta["sucesso"] = 1;
            
        } else {
            // Caso o usuário não seja encontrado
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Usuário não encontrado";
            $resposta["cod_erro"] = 4;
        }
    } else {
        // Caso ocorra falha na execução da consulta
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro no BD ao buscar o usuário";
        $resposta["cod_erro"] = 2;
    }
} else {
    // Caso o parâmetro "login" não tenha sido enviado
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo requerido não preenchido";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);

?>
