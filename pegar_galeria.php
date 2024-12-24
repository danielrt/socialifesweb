<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

/*
 * O seguinte código retorna a lista de fotos associadas a um usuário, com paginação.
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

// Verifica se os parâmetros limit, offset e usuario_login foram enviados
if (isset($_GET['limit']) && isset($_GET['offset']) && isset($_GET['usuario_login'])) {

    $limit = (int) $_GET['limit'];  // Garantir que limit seja um inteiro
    $offset = (int) $_GET['offset'];  // Garantir que offset seja um inteiro
    $usuario_login = $_GET['usuario_login'];  // usuario_login é uma string (não precisa de cast)

    // Verifica se os parâmetros são válidos
    if ($limit <= 0 || $offset < 0 || empty($usuario_login)) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Parâmetros inválidos.";
        $resposta["cod_erro"] = 3;
    } else {
        // Realiza a consulta ao BD com consulta preparada (prevenção de SQL Injection)
        $consulta = $db_con->prepare("SELECT 
                                        p.id AS post_id,
                                        p.imagem AS post_imagem,
                                        p.data_hora AS post_data_hora
                                    FROM 
                                        public.post p
                                    WHERE 
                                        p.usuario_login = :usuario_login
                                    AND 
                                        p.imagem IS NOT NULL
                                    ORDER BY 
                                        p.data_hora DESC
                                    LIMIT :limit OFFSET :offset");

        // Vincula os parâmetros de forma segura
        $consulta->bindParam(':usuario_login', $usuario_login, PDO::PARAM_STR);
        $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
        $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);

        // Executa a consulta
        if ($consulta->execute()) {
            // Consulta para obter o número total de fotos
            $nFotos = $db_con->prepare("SELECT 
                                            COUNT(*)
                                        FROM 
                                            public.post p
                                        WHERE 
                                            p.usuario_login = :usuario_login
                                        AND 
                                            p.imagem IS NOT NULL");
            $nFotos->bindParam(':usuario_login', $usuario_login, PDO::PARAM_STR);
            $nFotos->execute();
            $totalFotos = $nFotos->fetchColumn();

            // Prepara a resposta
            $resposta["fotos"] = array();
            $resposta["sucesso"] = 1;
            $resposta["n_fotos"] = $totalFotos;

            // Se houver fotos, adiciona-as ao array de resposta
            if ($consulta->rowCount() > 0) {
                while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    $foto = array();
                    $foto["post_id"] = $linha["post_id"];
                    $foto["foto"] = $linha["post_imagem"];
                    $foto["data_hora"] = $linha["post_data_hora"];

                    // Adiciona a foto ao array de fotos
                    array_push($resposta["fotos"], $foto);
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
    // Se os parâmetros limit, offset ou usuario_login não foram enviados
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo requerido não preenchido";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o BD
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);
?>
