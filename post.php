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

// Conexão com o banco de dados
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Array de resposta
$resposta = array();

// Verifica se o usuário foi autenticado
if (autenticar($db_con)) {

    // Verifica se os parâmetros necessários foram enviados
    if (isset($_POST['texto']) || isset($_FILES['imagem'])) {
        
        // Definir data
        $date = date('Y-m-d H:i:s');
        
        // Atribui os parâmetros recebidos
        $texto = isset($_POST['texto']) ? $_POST['texto'] : "";
        $img_url = "";

        // Caso tenha imagem, faz o upload
        if (isset($_FILES['imagem'])) {
            // Faz o upload da imagem para o Imgur
            $filename = $_FILES['imagem']['tmp_name'];
            $client_id = "ce5d3a656e2aa51";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.imgur.com/3/image',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => array('image' => new CURLFile($filename), 'type' => 'file'),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Client-ID ' . $client_id
                ),
            ));

            $imgur_response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($http_code == 200) {
                // Extrai a URL da imagem carregada
                $imgur_response_json = json_decode($imgur_response, true);
                $img_url = $imgur_response_json['data']['link'];
            } else {
                // Erro ao enviar imagem para o Imgur
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Erro ao enviar a imagem para o IMGUR. HTTP CODE: " . $http_code;
                $resposta["cod_erro"] = 2;
                echo json_encode($resposta);
                exit;
            }
        }

        // Prepara a consulta SQL com placeholders para evitar SQL Injection
        $consulta = $db_con->prepare("INSERT INTO post(texto, imagem, data_hora, usuarios_login) 
                                      VALUES(:descricao, :img_url, :date, :login)");

        // Vincula os parâmetros de forma segura
        $consulta->bindParam(':descricao', $texto, PDO::PARAM_STR);
        $consulta->bindParam(':img_url', $img_url, PDO::PARAM_STR);
        $consulta->bindParam(':login', $login, PDO::PARAM_STR);
        $consulta->bindParam(':date', $date, PDO::PARAM_STR);

        // Executa a consulta de inserção
        if ($consulta->execute()) {
            $resposta["sucesso"] = 1;
        } else {
            // Se ocorrer erro na inserção
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao criar produto no BD: " . $consulta->errorInfo()[2];
            $resposta["cod_erro"] = 2;
        }
    } else {
        // Se faltarem parâmetros na requisição (nenhum texto ou imagem enviado)
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Campo requerido não preenchido";
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
