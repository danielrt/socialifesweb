<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados
require_once('conexao_db.php');

// Array para a resposta JSON
$resposta = array();

// Verifica se todos os parâmetros foram enviados corretamente
if (isset($_GET['limit']) && isset($_GET['offset']) && isset($_GET['usuario_login'])) {
 
    $limit = (int)$_GET['limit'];  // Garantir que o limite seja um número inteiro
    $offset = (int)$_GET['offset'];  // Garantir que o offset seja um número inteiro
    $usuario_login = $_GET['usuario_login'];  // Não é necessário fazer cast, pois é uma string

    // Prepara a consulta para pegar os usuários seguidos
    $consulta = $db_con->prepare("
        SELECT 
            u.login AS usuario_login,
            u.nome AS usuario_nome,
            u.foto AS usuario_foto
        FROM 
            public.seguindo s
        JOIN 
            public.usuario u ON s.usuario_login_seguindo = u.login
        WHERE 
            s.usuario_login = :usuario_login
        ORDER BY 
            u.nome ASC
        LIMIT :limit OFFSET :offset
    ");
    $consulta->bindParam(':usuario_login', $usuario_login, PDO::PARAM_STR);
    $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
    $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);

    if ($consulta->execute()) {
        // Consulta para contar o número de usuários seguidos
        $nUsuariosQuery = $db_con->prepare("
            SELECT COUNT(*) 
            FROM public.seguindo s
            JOIN public.usuario u ON s.usuario_login_seguindo = u.login
            WHERE s.usuario_login = :usuario_login
        ");
        $nUsuariosQuery->bindParam(':usuario_login', $usuario_login, PDO::PARAM_STR);
        $nUsuariosQuery->execute();
        $nUsuarios = $nUsuariosQuery->fetchColumn();

        // Prepara a resposta
        $resposta["usuarios"] = array();
        $resposta["sucesso"] = 1;
        $resposta["n_usuarios"] = $nUsuarios;

        if ($consulta->rowCount() > 0) {
            // Processa os resultados da consulta
            while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                $usuario = array();
                $usuario["login"] = $linha["usuario_login"];
                $usuario["nome"] = $linha["usuario_nome"];
                $usuario["foto"] = $linha["usuario_foto"];
                array_push($resposta["usuarios"], $usuario);
            }
        } else {
            // Caso não haja resultados, define sucesso como 0
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Nenhum usuário encontrado";
            $resposta["cod_erro"] = 4;
        }
    } else {
        // Caso haja erro na execução da consulta
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro no BD ao recuperar os dados";
        $resposta["cod_erro"] = 2;
    }
} else {
    // Se os parâmetros não forem enviados corretamente
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo requerido não preenchido";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);

?>
