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

    if (isset($_POST['usuario'])) {

        $usuario = trim($_POST['usuario']);

        // Verifica se o usuário já segue o usuário alvo
        $consultaSeJaSegue = $db_con->prepare("SELECT id FROM public.seguindo WHERE usuario_login = :usuario_login AND usuario_login_seguindo = :usuario_seguindo");
        $consultaSeJaSegue->bindParam(':usuario_login', $login);
        $consultaSeJaSegue->bindParam(':usuario_seguindo', $usuario);

        if ($consultaSeJaSegue->execute()) {
            if ($consultaSeJaSegue->rowCount() > 0) {
                // Se o usuário já segue, então remove o seguimento
                $linha = $consultaSeJaSegue->fetch(PDO::FETCH_ASSOC);
                $id = $linha['id'];

                // Deleta o seguimento
                $consultaRemover = $db_con->prepare("DELETE FROM public.seguindo WHERE id = :id");
                $consultaRemover->bindParam(':id', $id);

                if ($consultaRemover->execute()) {
                    $resposta["sucesso"] = 1;
                    $resposta["mensagem"] = "Seguindo removido com sucesso.";
                } else {
                    $resposta["sucesso"] = 0;
                    $resposta["erro"] = "Erro ao remover seguimento no BD.";
                    $resposta["cod_erro"] = 2;
                }
            } else {
                // Se o usuário não está seguindo o outro
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Você não está seguindo este usuário.";
                $resposta["cod_erro"] = 4;
            }
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao verificar o seguimento no BD.";
            $resposta["cod_erro"] = 2;
        }
    } else {
        // Se o parâmetro 'usuario' não foi enviado
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo 'usuario' não preenchido.";
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
