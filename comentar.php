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

// Verifica se o usuário foi autenticado
if (autenticar($db_con)) {

    // Verifica se os parâmetros obrigatórios foram enviados
    if (isset($_POST['idpost']) && isset($_POST['comentario'])) {

        $date = date('Y-m-d H:i:s');
        $idpost = trim($_POST['idpost']);
        $comentario = $_POST['comentario'];
        $login = $GLOBALS['login']; // Obtém o login do usuário autenticado

        // Consulta para obter o login do autor do post
        $consultaUsuarioPost = $db_con->prepare("SELECT usuario_login FROM public.post WHERE id = :idpost");
        $consultaUsuarioPost->bindParam(':idpost', $idpost, PDO::PARAM_INT);
        
        if ($consultaUsuarioPost->execute()) {
            $autorPost = $consultaUsuarioPost->fetch(PDO::FETCH_ASSOC);
            
            if ($autorPost) {
                $usuarioAlvo = $autorPost['usuario_login'];

                // Inserção do comentário no banco de dados
                $consultaComentario = $db_con->prepare("INSERT INTO comentario (post_idpost, usuario_login, texto, data_hora)
                                                         VALUES (:idpost, :usuario_login, :comentario, :data_hora)");
                $consultaComentario->bindParam(':idpost', $idpost, PDO::PARAM_INT);
                $consultaComentario->bindParam(':usuario_login', $login);
                $consultaComentario->bindParam(':comentario', $comentario);
                $consultaComentario->bindParam(':data_hora', $date);

                if ($consultaComentario->execute()) {
                    $resposta["sucesso"] = 1;

                    // Adiciona uma notificação ao autor do post
                    $consultaNotificacao = $db_con->prepare("INSERT INTO public.notificacao (nova, data_hora, usuario_login, usuario_login_alvo, acao, post_id)
                                                             VALUES (true, NOW(), :usuario_login, :usuario_alvo, 1, :idpost)");
                    $consultaNotificacao->bindParam(':usuario_login', $login);
                    $consultaNotificacao->bindParam(':usuario_alvo', $usuarioAlvo);
                    $consultaNotificacao->bindParam(':idpost', $idpost);

                    $consultaNotificacao->execute();

                } else {
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro no BD ao adicionar comentário.";
                    $resposta["cod_erro"] = 2;
                }
            } else {
                // Se o post não for encontrado no banco
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Post não encontrado.";
                $resposta["cod_erro"] = 4;
            }
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro no BD ao consultar o post.";
            $resposta["cod_erro"] = 2;
        }

    } else {
        // Caso algum parâmetro obrigatório não tenha sido enviado
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo requerido não preenchido.";
        $resposta["cod_erro"] = 3;
    }
} else {
    // Caso o usuário não esteja autenticado
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não confere.";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Retorna a resposta em formato JSON
echo json_encode($resposta);

?>
