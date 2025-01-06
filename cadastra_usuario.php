<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

// conexão com bd
require_once('conexao_db.php');

// array de resposta
$resposta = array();

// Validação de entrada
if (isset($_POST['login'], $_POST['senha'], $_POST['nome'], $_POST['cidade'], $_POST['data_nascimento'], $_FILES['foto'])) {
    
    $login = trim($_POST['login']);
    $senha = trim($_POST['senha']);
    $nome = trim($_POST['nome']);
    $cidade = trim($_POST['cidade']);
    $data_nascimento = trim($_POST['data_nascimento']);
    $foto = $_FILES['foto'];

    if (strlen($senha) < 6) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Senha deve ter pelo menos 6 caracteres";
        $resposta["cod_erro"] = 3;
        echo json_encode($resposta);
        exit();
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Data de nascimento inválida";
        $resposta["cod_erro"] = 3;
        echo json_encode($resposta);
        exit();
    }

    // Verifica se o usuário já existe
    $consulta_usuario_existe = $db_con->prepare("SELECT login FROM usuario WHERE login = :login");
    $consulta_usuario_existe->bindParam(':login', $login, PDO::PARAM_STR);
    $consulta_usuario_existe->execute();

    if ($consulta_usuario_existe->rowCount() > 0) {
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Usuário já cadastrado";
        $resposta["cod_erro"] = 1;
        echo json_encode($resposta);
        exit();
    }

    // Criação do token (hash da senha)
    $token = password_hash($senha, PASSWORD_DEFAULT);

    // Upload da imagem no Imgur
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

    // Inserção no banco de dados
    $consulta = $db_con->prepare("INSERT INTO usuario (login, token, nome, data_nascimento, cidade, foto) VALUES (:login, :token, :nome, :data_nascimento, :cidade, :foto)");
    $consulta->bindParam(':login', $login, PDO::PARAM_STR);
    $consulta->bindParam(':token', $token, PDO::PARAM_STR);
    $consulta->bindParam(':nome', $nome, PDO::PARAM_STR);
    $consulta->bindParam(':data_nascimento', $data_nascimento, PDO::PARAM_STR);
    $consulta->bindParam(':cidade', $cidade, PDO::PARAM_STR);
    $consulta->bindParam(':foto', $img_url, PDO::PARAM_STR);

    if ($consulta->execute()) {
        $resposta["sucesso"] = 1;
    } else {
        error_log("Erro ao inserir no banco de dados: " . implode(" | ", $consulta->errorInfo()));
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro ao inserir no banco de dados";
        $resposta["cod_erro"] = 2;
    }
} else {
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Parâmetros obrigatórios não fornecidos";
    $resposta["cod_erro"] = 3;
}

// Fecha a conexão com o BD
$db_con = null;

// Retorna a resposta em JSON
echo json_encode($resposta);

?>
