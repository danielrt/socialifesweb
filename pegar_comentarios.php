<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

/*
 * O seguinte código retorna a lista de comentários de um post, com paginação.
 * A resposta é no formato JSON.
 */
 
/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 */

// Conexão com BD
require_once('conexao_db.php');

// Array de resposta
$resposta = array();

// Verifica se os parâmetros limit, offset e post_id foram recebidos
if (isset($_GET['limit']) && isset($_GET['offset']) && isset($_GET['post_id'])) {

    $limit = (int) $_GET['limit'];  // Garantir que limit seja um inteiro
    $offset = (int) $_GET['offset'];  // Garantir que offset seja um inteiro
    $post_id = (int) $_GET['post_id'];  // Garantir que post_id seja um inteiro

    // Verifica se os parâmetros são válidos
    if ($limit <= 0 || $offset < 0 || $post_id <= 0) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Parâmetros inválidos.";
        $resposta["cod_erro"] = 3;
    } else {
        // Realiza uma consulta ao BD para obter os comentários com paginação
        $consulta = $db_con->prepare("SELECT 
                                        c.id AS comentario_id,
                                        c.texto AS comentario_texto,
                                        c.data_hora AS comentario_data_hora,
                                        u.nome AS usuario_nome,
                                        u.foto AS usuario_foto,
                                        u.login AS usuario_login
                                    FROM 
                                        public.comentario c
                                    JOIN 
                                        public.usuario u ON c.usuario_login = u.login
                                    WHERE 
                                        c.post_id = :post_id
                                    ORDER BY 
                                        c.data_hora ASC
                                    LIMIT :limit OFFSET :offset");
        // Vincula os parâmetros de forma segura
        $consulta->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
        $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);

        if ($consulta->execute()) {
            // Consulta para obter o número total de comentários
            $nComentarios = $db_con->prepare("SELECT COUNT(*) FROM comentario WHERE post_id = :post_id");
            $nComentarios->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $nComentarios->execute();
            $totalComentarios = $nComentarios->fetchColumn();

            // Prepara a resposta
            $resposta["comentarios"] = array();
            $resposta["sucesso"] = 1;
            $resposta["n_comentarios"] = $totalComentarios;

            // Se houver comentários, adicione-os ao array de resposta
            if ($consulta->rowCount() > 0) {
                while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    $comentario = array();
                    $comentario["id"] = $linha["comentario_id"];
                    $comentario["texto"] = $linha["comentario_texto"];
                    $comentario["data_hora"] = $linha["comentario_data_hora"];
                    $comentario["usuario_login"] = $linha["usuario_login"];
                    $comentario["usuario_nome"] = $linha["usuario_nome"];
                    $comentario["usuario_foto"] = $linha["usuario_foto"];

                    // Adiciona o comentário ao array de resposta
                    array_push($resposta["comentarios"], $comentario);
                }
            }
        } else {
            // Caso ocorra falha na execução da consulta
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro no BD: " . $consulta->errorInfo()[2];
            $resposta["cod_erro"] = 2;
        }
    }
} else {
    // Se os parâmetros limit, offset ou post_id não foram enviados
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo requerido não preenchido";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o BD
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);
?>
