<?php

/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parametros
 * 4 : entrada não encontrada no BD
 * 
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

// Conexão com bd
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Array de resposta
$resposta = array();

// Verifica se o usuário conseguiu autenticar
$isAuth = autenticar($db_con);

// Verifica se o parâmetro 'busca' foi enviado na requisição
if (isset($_GET["busca"])) {

    // Aqui são obtidos os parâmetros
    $busca = trim($_GET['busca']);

    // Evitar SQL Injection, usando placeholders
    $consulta = NULL;

    if ($isAuth) {
        // Obtém do BD os detalhes do usuário autenticado e dos usuários com base na busca
        $query = "
            SELECT 
                u.login AS usuario_login,
                u.nome AS usuario_nome,
                u.foto AS usuario_foto,
                u.cidade AS usuario_cidade,
                u.data_nascimento AS usuario_data_nascimento,
                CASE 
                    WHEN s.usuario_login IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS esta_sendo_seguido
            FROM 
                public.usuario u
            LEFT JOIN 
                public.seguindo s ON s.usuario_login = :login AND s.usuario_login_seguindo = u.login
            WHERE 
                u.nome ILIKE :busca
            ORDER BY 
                u.nome ASC;
        ";

        $consulta = $db_con->prepare($query);
        $consulta->bindValue(':login', $GLOBALS['login'], PDO::PARAM_STR);
        $consulta->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
    } else {
        // Caso o usuário não esteja autenticado, não será verificado se ele está sendo seguido
        $query = "
            SELECT 
                u.login AS usuario_login,
                u.nome AS usuario_nome,
                u.foto AS usuario_foto,
                u.cidade AS usuario_cidade,
                u.data_nascimento AS usuario_data_nascimento
            FROM 
                public.usuario u
            WHERE 
                u.nome ILIKE :busca
            ORDER BY 
                u.nome ASC;
        ";

        $consulta = $db_con->prepare($query);
        $consulta->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
    }

    if ($consulta->execute()) {
        $resposta["usuarios"] = array();
        $resposta["sucesso"] = 1;

        if ($consulta->rowCount() > 0) {
            while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                $usuario = array();
                $usuario["login"] = $linha["usuario_login"];
                $usuario["nome"] = $linha["usuario_nome"];
                $usuario["data_nascimento"] = $linha["usuario_data_nascimento"];
                $usuario["cidade"] = $linha["usuario_cidade"];
                $usuario["foto"] = $linha["usuario_foto"];

                // Se o usuário estiver autenticado, adiciona a informação de "seguindo"
                if ($isAuth) {
                    $usuario["esta_sendo_seguido"] = $linha["esta_sendo_seguido"];
                }

                array_push($resposta["usuarios"], $usuario);
            }
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Nenhum usuário encontrado com esse nome";
            $resposta["cod_erro"] = 4;
        }
    } else {
        // Caso ocorra falha no BD, o cliente recebe a chave "sucesso" com valor 0. 
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro no BD: " . $consulta->errorInfo()[2];
        $resposta["cod_erro"] = 2;
    }
} else {
    // Se a requisição foi feita incorretamente
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo 'busca' não preenchido corretamente";
    $resposta["cod_erro"] = 3;
}

// Fecha conexão com o BD
$db_con = null;

// Converte a resposta para o formato JSON.
echo json_encode($resposta);
?>
