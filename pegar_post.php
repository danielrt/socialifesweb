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
 * O código retorna os dados detalhados de um produto.
 * Essa é uma requisição do tipo GET. Um produto é identificado 
 * pelo campo id.
 */

// Conexão com o banco de dados
require_once('conexao_db.php');

// Array de resposta
$resposta = array();

// Verifica se o parâmetro id foi enviado na requisição
if (isset($_GET["id"])) {
    
    // Aqui obtém-se o parâmetro 'id' e valida-se
    $id = $_GET['id'];

    // Validação para garantir que o 'id' é um número inteiro
    if (!is_numeric($id) || $id <= 0) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "ID inválido";
        $resposta["cod_erro"] = 3;
        echo json_encode($resposta);
        exit();
    }

    // Prepara a consulta SQL com placeholders para evitar SQL injection
    $query = "
        SELECT 
            p.id AS post_id,
            p.data_hora AS post_data_hora,
            p.texto AS post_texto,
            p.imagem AS post_imagem,
            u.login AS usuario_login,
            u.nome AS usuario_nome,
            u.foto AS usuario_foto
        FROM 
            public.post p
        JOIN 
            public.usuario u ON p.usuario_login = u.login
        WHERE 
            p.id = :id
    ";

    // Prepara a consulta no banco de dados
    $consulta = $db_con->prepare($query);
    $consulta->bindParam(':id', $id, PDO::PARAM_INT);

    // Executa a consulta
    if ($consulta->execute()) {
        if ($consulta->rowCount() > 0) {

            // Se o produto existir, os dados completos do produto 
            // são adicionados no array de resposta
            $linha = $consulta->fetch(PDO::FETCH_ASSOC);
            
            $resposta["id"] = $linha["post_id"];
            $resposta["data_hora"] = $linha["post_data_hora"];
            $resposta["texto"] = $linha["post_texto"];
            $resposta["imagem"] = $linha["post_imagem"];
            $resposta["usuario_login"] = $linha["usuario_login"];
            $resposta["usuario_nome"] = $linha["usuario_nome"];
            $resposta["usuario_foto"] = $linha["usuario_foto"];
            
            // Retorna sucesso
            $resposta["sucesso"] = 1;

        } else {
            // Caso o post não seja encontrado no banco de dados
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Post não encontrado";
            $resposta["cod_erro"] = 4;
        }
    } else {
        // Caso ocorra falha na execução da consulta no banco de dados
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro no BD: " . implode(" ", $consulta->errorInfo());
        $resposta["cod_erro"] = 2;
    }
} else {
    // Caso o parâmetro id não tenha sido enviado na requisição
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Campo requerido não preenchido";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);
?>
