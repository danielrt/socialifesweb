<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha no banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 */

date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Array de resposta
$resposta = array();

if (isset($_GET['limit']) && isset($_GET['offset'])) {

    $limit = (int)$_GET['limit'];
    $offset = (int)$_GET['offset'];

    // Obter posts de um usuário específico
    if (isset($_GET['usuario'])) {
        $usuario = $_GET['usuario'];

        // Consultar o número de posts do usuário
        $nPostsQuery = $db_con->prepare("
            SELECT COUNT(*) 
            FROM public.post p
            JOIN public.usuario u ON p.usuario_login = u.login
            WHERE u.login = :usuario
        ");
        $nPostsQuery->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $nPostsQuery->execute();
        $nPosts = $nPostsQuery->fetchColumn();

        $resposta["posts"] = array();
        $resposta["sucesso"] = 1;
        $resposta["n_posts"] = $nPosts;

        if ($nPosts > 0) {
            $consulta = $db_con->prepare("
                SELECT 
                    p.id AS post_id,
                    p.data_hora AS post_data_hora,
                    p.texto AS post_texto,
                    p.imagem AS post_imagem,
                    u.nome AS usuario_nome,
                    u.foto AS usuario_foto,
                    u.login AS usuario_login
                FROM public.post p
                JOIN public.usuario u ON p.usuario_login = u.login
                WHERE u.login = :usuario
                ORDER BY p.data_hora DESC
                LIMIT :limit OFFSET :offset
            ");
            $consulta->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
            $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);

            if ($consulta->execute()) {
                while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    $post = array();
                    $post["id"] = $linha["post_id"];
                    $post["data_hora"] = $linha["post_data_hora"];
                    $post["texto"] = $linha["post_texto"];
                    $post["imagem"] = $linha["post_imagem"];
                    $post["usuario_login"] = $linha["usuario_login"];
                    $post["usuario_nome"] = $linha["usuario_nome"];
                    $post["usuario_foto"] = $linha["usuario_foto"];
                    array_push($resposta["posts"], $post);
                }
            } else {
                // Erro na execução da consulta
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Erro no BD ao buscar posts do usuário";
                $resposta["cod_erro"] = 2;
            }
        }
    }
    // Obter a timeline do usuário autenticado
    else {
        if (autenticar($db_con)) {
            // Consultar o número de posts da timeline
            $nPostsQuery = $db_con->prepare("
                SELECT COUNT(*) 
                FROM public.post p
                JOIN public.usuario u ON p.usuario_login = u.login
                JOIN public.seguindo s ON s.usuario_login_seguindo = u.login
                WHERE s.usuario_login = :login
            ");
            $nPostsQuery->bindParam(':login', $login, PDO::PARAM_STR);
            $nPostsQuery->execute();
            $nPosts = $nPostsQuery->fetchColumn();

            $resposta["posts"] = array();
            $resposta["sucesso"] = 1;
            $resposta["n_posts"] = $nPosts;

            if ($nPosts > 0) {
                $consulta = $db_con->prepare("
                    SELECT 
                        p.id AS post_id,
                        p.data_hora AS post_data_hora,
                        p.texto AS post_texto,
                        p.imagem AS post_imagem,
                        u.nome AS usuario_nome,
                        u.foto AS usuario_foto,
                        u.login AS usuario_login
                    FROM public.post p
                    JOIN public.usuario u ON p.usuario_login = u.login
                    JOIN public.seguindo s ON s.usuario_login_seguindo = u.login
                    WHERE s.usuario_login = :login
                    ORDER BY p.data_hora DESC
                    LIMIT :limit OFFSET :offset
                ");
                $consulta->bindParam(':login', $login, PDO::PARAM_STR);
                $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
                $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);

                if ($consulta->execute()) {
                    while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                        $post = array();
                        $post["id"] = $linha["post_id"];
                        $post["data_hora"] = $linha["post_data_hora"];
                        $post["texto"] = $linha["post_texto"];
                        $post["imagem"] = $linha["post_imagem"];
                        $post["usuario_login"] = $linha["usuario_login"];
                        $post["usuario_nome"] = $linha["usuario_nome"];
                        $post["usuario_foto"] = $linha["usuario_foto"];
                        array_push($resposta["posts"], $post);
                    }
                } else {
                    // Erro na execução da consulta
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro no BD ao buscar posts da timeline";
                    $resposta["cod_erro"] = 2;
                }
            }
        } else {
            // Falha na autenticação
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Usuário ou senha não conferem";
            $resposta["cod_erro"] = 0;
        }
    }
} else {
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campos requeridos não preenchidos";
    $resposta["cod_erro"] = 3;
}

// Fecha conexão com o banco de dados
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);
?>
