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
        
        // Verifica se o usuário já curtiu o post
        $consultaSeJaCurtiu = $db_con->prepare("SELECT id FROM public.curtida WHERE usuario_login = :usuario_login AND post_id = :post_id");
        $consultaSeJaCurtiu->bindParam(':usuario_login', $login);
        $consultaSeJaCurtiu->bindParam(':post_id', $postId);
        
        if ($consultaSeJaCurtiu->execute()) {
            if ($consultaSeJaCurtiu->rowCount() > 0) {
                // Se o usuário já curtiu o post, remove a curtida
                $linha = $consultaSeJaCurtiu->fetch(PDO::FETCH_ASSOC);
                $id = $linha['id'];
                
                // Deleta a curtida
                $consultaRemover = $db_con->prepare("DELETE FROM public.curtida WHERE id = :id");
                $consultaRemover->bindParam(':id', $id);
                
                if ($consultaRemover->execute()) {
                    $resposta["sucesso"] = 1;
                    $resposta["mensagem"] = "Curtida removida com sucesso.";
                } else {
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro ao remover a curtida no BD.";
                    $resposta["cod_erro"] = 2;
                }
            } else {
                // Se o usuário não curtiu o post, retorna que não há nada para remover
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Você não curtiu este post.";
                $resposta["cod_erro"] = 4;
            }
        } else {
            // Caso haja erro ao verificar a curtida no banco
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao verificar a curtida no BD.";
            $resposta["cod_erro"] = 2;
        }
    } else {
        // Se a requisição foi feita incorretamente
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo 'post_id' requerido não preenchido.";
        $resposta["cod_erro"] = 3;
    }
} else {
    // Caso falhe a autenticação
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não confere.";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Retorna a resposta em formato JSON
echo json_encode($resposta);

?>
