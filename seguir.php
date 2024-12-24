<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

/*
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já segue
 * 2 : falha banco de dados
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

// Verifica se o usuário conseguiu autenticar
if (autenticar($db_con)) {

    // Verifica se o parâmetro 'usuario' foi enviado
    if (isset($_POST['usuario'])) {

        // Seta a data atual
        $date = date('Y-m-d H:i:s');
        $usuario = trim($_POST['usuario']);

        // Verifica se o usuário já segue o outro
        $consultaSeJaSegue = $db_con->prepare("SELECT id FROM public.seguindo WHERE usuario_login = :login AND usuario_login_seguindo = :usuario");
        $consultaSeJaSegue->bindParam(':login', $login, PDO::PARAM_STR);
        $consultaSeJaSegue->bindParam(':usuario', $usuario, PDO::PARAM_STR);

        if ($consultaSeJaSegue->execute()) {
            if ($consultaSeJaSegue->rowCount() == 0) {
                // Insere o novo relacionamento de seguir
                $consulta = $db_con->prepare("INSERT INTO seguindo(usuario_login, usuario_login_seguindo) VALUES(:login, :usuario)");
                $consulta->bindParam(':login', $login, PDO::PARAM_STR);
                $consulta->bindParam(':usuario', $usuario, PDO::PARAM_STR);

                if ($consulta->execute()) {
                    // Se o relacionamento foi criado, envia a notificação
                    $consultaNotificacao = $db_con->prepare("INSERT INTO public.notificacao (nova, data_hora, usuario_login, usuario_login_alvo, acao, post_id) 
                                        VALUES (true, :date, :login, :usuario, 2, NULL)");
                    $consultaNotificacao->bindParam(':date', $date, PDO::PARAM_STR);
                    $consultaNotificacao->bindParam(':login', $login, PDO::PARAM_STR);
                    $consultaNotificacao->bindParam(':usuario', $usuario, PDO::PARAM_STR);
                    $consultaNotificacao->execute();

                    $resposta["sucesso"] = 1;
                } else {
                    // Erro ao inserir no banco de dados
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro ao inserir no BD: " . implode(' ', $consulta->errorInfo());
                    $resposta["cod_erro"] = 2;
                }
            } else {
                // Se o usuário já segue, retorna sucesso sem necessidade de alteração
                $resposta["sucesso"] = 1;
            }
        } else {
            // Erro ao consultar se o usuário já segue
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao verificar seguimento: " . implode(' ', $consultaSeJaSegue->errorInfo());
            $resposta["cod_erro"] = 2;
        }

    } else {
        // Se o parâmetro 'usuario' não foi enviado
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo 'usuario' não preenchido";
        $resposta["cod_erro"] = 3;
    }

} else {
    // Falha de autenticação
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não confere";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);
?>
