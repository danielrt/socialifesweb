<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 */

date_default_timezone_set('America/Sao_Paulo');

// Conexão com banco de dados
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Array de resposta
$resposta = array();

// Verifica se o usuário conseguiu autenticar
if (autenticar($db_con)) {

    if (isset($_POST['post_id'])) {

        $postId = trim($_POST['post_id']);

        // Valida se o post_id é um valor numérico (pode ser ajustado conforme necessidade)
        if (!is_numeric($postId)) {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "post_id inválido.";
            $resposta["cod_erro"] = 3;
        } else {
            try {
                // Verifica se o post existe e se pertence ao usuário
                $consultaSePostExiste = $db_con->prepare("SELECT id FROM public.post WHERE post_id = :post_id AND usuario_login = :usuario_login");
                $consultaSePostExiste->bindParam(':post_id', $postId);
                $consultaSePostExiste->bindParam(':usuario_login', $login);

                if ($consultaSePostExiste->execute()) {
                    if ($consultaSePostExiste->rowCount() > 0) {
                        // Se o post existe e pertence ao usuário, realiza a exclusão
                        $consulta = $db_con->prepare("DELETE FROM post WHERE post_id = :post_id");
                        $consulta->bindParam(':post_id', $postId);

                        if ($consulta->execute()) {
                            $resposta["sucesso"] = 1;
                            $resposta["mensagem"] = "Post excluído com sucesso.";
                        } else {
                            $resposta["sucesso"] = 0;
                            $resposta["erro"] = "Erro ao excluir post no BD.";
                            $resposta["cod_erro"] = 2;
                        }
                    } else {
                        // O post não existe ou não pertence ao usuário
                        $resposta["sucesso"] = 0;
                        $resposta["erro"] = "Post não encontrado ou você não tem permissão para excluí-lo.";
                        $resposta["cod_erro"] = 4;
                    }
                } else {
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro ao verificar a existência do post no BD.";
                    $resposta["cod_erro"] = 2;
                }
            } catch (Exception $e) {
                // Captura qualquer exceção e retorna uma mensagem de erro
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Erro inesperado: " . $e->getMessage();
                $resposta["cod_erro"] = 2;
            }
        }
    } else {
        // Se o parâmetro 'post_id' não foi enviado
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo 'post_id' não preenchido.";
        $resposta["cod_erro"] = 3;
    }
} else {
    // Falha de autenticação
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não confere.";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Retorna a resposta em formato JSON
echo json_encode($resposta);

?>
