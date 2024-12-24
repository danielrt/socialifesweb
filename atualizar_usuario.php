<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados
require_once('conexao_db.php');

// Inclusão do script de autenticação
require_once('autenticacao.php');

// Array de resposta
$resposta = array();

// Verifica se o usuário conseguiu autenticar
if (autenticar($db_con)) {
    $login = $GLOBALS['login']; // Obtém o login do usuário autenticado

    // Campos permitidos para atualização
    $fields = [
        'nome' => $_POST['nome'] ?? null,
        'data_nascimento' => isset($_POST['data_nascimento']) ? date("Y-m-d", strtotime($_POST['data_nascimento'])) : null,
        'cidade' => $_POST['cidade'] ?? null,
        'senha' => $_POST['senha'] ?? null,
        'foto' => $_FILES['foto'] ?? null // Recebe o arquivo de foto, se enviado
    ];

    // Remove campos nulos
    $fieldsToUpdate = array_filter($fields, fn($value) => !is_null($value));

    // Valida a senha
    if (isset($fieldsToUpdate['senha'])) {
        if (strlen($fieldsToUpdate['senha']) < 6) {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "A senha deve ter pelo menos 6 caracteres";
            $resposta["cod_erro"] = 4;
            echo json_encode($resposta);
            exit();
        }
        $fieldsToUpdate['token'] = password_hash($fieldsToUpdate['senha'], PASSWORD_DEFAULT);
        unset($fieldsToUpdate['senha']);
    }

    // Processa a foto, se enviada
    if (isset($fieldsToUpdate['foto'])) {
        // Verifica se o arquivo é uma imagem válida
        $foto = $fieldsToUpdate['foto'];
        if ($foto['error'] == UPLOAD_ERR_OK) {
            // Envia a imagem para o Imgur
            $filename = $foto['tmp_name'];
            $client_id = 'ce5d3a656e2aa51'; // Insira seu Client-ID do Imgur aqui

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.imgur.com/3/image',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => array(
                    'image' => new CURLFile($filename),
                    'type' => 'file'
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Client-ID ' . $client_id
                )
            ));

            $imgur_response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($http_code !== 200) {
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "Erro ao enviar a imagem para o Imgur";
                $resposta["cod_erro"] = 2;
                echo json_encode($resposta);
                exit();
            }

            $imgur_response_json = json_decode($imgur_response, true);
            $img_url = $imgur_response_json['data']['link'] ?? null;

            if (!$img_url) {
                $resposta["sucesso"] = 0;
                $resposta["erro"] = "URL da imagem não retornada pelo Imgur";
                $resposta["cod_erro"] = 2;
                echo json_encode($resposta);
                exit();
            }

            // Atualiza a foto no banco de dados
            $fieldsToUpdate['foto'] = $img_url;
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro ao enviar o arquivo de imagem";
            $resposta["cod_erro"] = 2;
            echo json_encode($resposta);
            exit();
        }
    }

    if (empty($fieldsToUpdate)) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Nenhum campo enviado para atualização";
        $resposta["cod_erro"] = 3;
    } else {
        // Construção dinâmica do SQL
        $setClause = implode(", ", array_map(fn($key) => "$key = :$key", array_keys($fieldsToUpdate)));

        $query = "UPDATE usuario SET $setClause WHERE login = :login";
        $stmt = $db_con->prepare($query);

        // Associa os valores dos parâmetros
        foreach ($fieldsToUpdate as $key => $value) {
            $stmt->bindParam(":$key", $fieldsToUpdate[$key]);
        }
        $stmt->bindParam(":login", $login);

        // Executa a atualização
        if ($stmt->execute()) {
            $resposta["sucesso"] = 1;
            $resposta["erro"] = "Usuário atualizado com sucesso";
            $resposta["cod_erro"] = 1;
        } else {
            $resposta["sucesso"] = 0;
            $resposta["erro"] = "Erro no banco de dados: " . $stmt->errorInfo()[2];
            $resposta["cod_erro"] = 2;
        }
    }
} else {
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não conferem";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o BD
$db_con = null;

// Converte a resposta para o formato JSON
echo json_encode($resposta);

?>
