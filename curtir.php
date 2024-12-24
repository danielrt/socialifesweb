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
        
        $date = date('Y-m-d H:i:s');
        $postId = trim($_POST['post_id']);
        $login = $GLOBALS['login']; // Obtém o login do usuário autenticado

        // Verifica se o usuário já curtiu o post
        $consultaSeJaCurtiu = $db_con->prepare("SELECT id FROM public.curtida WHERE usuario_login = :usuario_login AND post_id = :post_id");
        $consultaSeJaCurtiu->bindParam(':usuario_login', $login);
        $consultaSeJaCurtiu->bindParam(':post_id', $postId);
        
        if ($consultaSeJaCurtiu->execute()) {
            if ($consultaSeJaCurtiu->rowCount() == 0) {
                // Insere a curtida no banco
                $consulta = $db_con->prepare("INSERT INTO curtida(usuario_login, post_id) VALUES(:usuario_login, :post_id)");
                $consulta->bindParam(':usuario_login', $login);
                $consulta->bindParam(':post_id', $postId);
                
                if ($consulta->execute()) {
                    $resposta["sucesso"] = 1;

                    // Obtém o usuário alvo do post (o autor do post)
                    $consultaAutorPost = $db_con->prepare("SELECT usuario_login FROM public.post WHERE id = :post_id");
                    $consultaAutorPost->bindParam(':post_id', $postId);
                    
                    if ($consultaAutorPost->execute()) {
                        $autorPost = $consultaAutorPost->fetch(PDO::FETCH_ASSOC);
                        if ($autorPost) {
                            $usuarioAlvo = $autorPost['usuario_login'];

                            // Insere a notificação de curtida para o autor do post
                            $consultaNotificacao = $db_con->prepare("INSERT INTO public.notificacao (nova, data_hora, usuario_login, usuario_login_alvo, acao, post_id) 
                                                                     VALUES (true, :data_hora, :usuario_login, :usuario_login_alvo, 3, :post_id)");
                            $consultaNotificacao->bindParam(':data_hora', $date);
                            $consultaNotificacao->bindParam(':usuario_login', $login);
                            $consultaNotificacao->bindParam(':usuario_login_alvo', $usuarioAlvo);
                            $consultaNotificacao->bindParam(':post_id', $postId);
                            
                            $consultaNotificacao->execute();
                        }
                    }
                } else {
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro ao inserir a curtida no BD.";
                    $resposta["cod_erro"] = 2;
                }
            } else {
                // Se o usuário já curtiu o post, retornamos sucesso mas sem inserir novamente
                $resposta["sucesso"] = 1;
                $resposta["mensagem"] = "Você já curtiu este post.";
            }
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao verificar a curtida no BD.";
            $resposta["cod_erro"] = 2;
        }
    } else {
        // Caso algum parâmetro não tenha sido enviado
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
