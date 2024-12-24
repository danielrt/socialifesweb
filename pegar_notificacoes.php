<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");

date_default_timezone_set('America/Sao_Paulo');

/*
 * O código retorna uma lista de notificações para o cliente.
 * A resposta é em formato JSON.
 * 
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 */

// Conexão com o banco de dados
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Array para a resposta em JSON
$resposta = array();

// Verifica se o usuário foi autenticado
if (autenticar($db_con)) {

    // Inicia a consulta básica
    $query = "SELECT id, data_hora, usuario_login, acao, post_id 
              FROM public.notificacao 
              WHERE usuario_login_alvo = :usuario_login_alvo";

    // Se 'somente_novas' for passado, adicionar filtro
    if (isset($_GET['somente_novas']) && $_GET['somente_novas'] == 'true') {
        $query .= " AND nova = true";
    }

    // Adicionar a ordenação
    $query .= " ORDER BY data_hora ASC";

    // Adicionar paginacao com 'limit' e 'offset', se fornecidos
    if (isset($_GET['limit'])) {
        $query .= " LIMIT :limit";
    }
    if (isset($_GET['offset'])) {
        $query .= " OFFSET :offset";
    }

    // Preparar a consulta
    $consulta = $db_con->prepare($query);
    $consulta->bindParam(':usuario_login_alvo', $login, PDO::PARAM_STR);

    // Bind dos parâmetros limit e offset
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit']; // Garantir que 'limit' seja um inteiro
        $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
    }

    if (isset($_GET['offset'])) {
        $offset = (int) $_GET['offset']; // Garantir que 'offset' seja um inteiro
        $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);
    }

    // Executa a consulta
    if ($consulta->execute()) {

        // Armazenar as notificações na resposta
        $resposta["notificacoes"] = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $resposta["sucesso"] = 1;

        // Se 'somente_novas' foi passado, fazer o update das notificações
        if (isset($_GET['somente_novas']) && $_GET['somente_novas'] == 'true') {
            $ids = array_column($resposta["notificacoes"], 'id');
            
            // Se houver IDs para atualizar
            if (!empty($ids)) {
                // Gerar a consulta UPDATE com placeholders
                $updateQuery = "UPDATE public.notificacao SET nova = false WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
                $updateStmt = $db_con->prepare($updateQuery);

                // Vincula os IDs às consultas de atualização
                foreach ($ids as $index => $id) {
                    $updateStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
                }

                // Executa o UPDATE para marcar as notificações como "não novas"
                $updateStmt->execute();
            }
        }
        
    } else {
        // Caso ocorra falha no BD, a resposta retorna erro
        $resposta["sucesso"] = 0;
        $resposta["erro"] = "Erro no BD: " . implode(' ', $consulta->errorInfo());
        $resposta["cod_erro"] = 2;
    }

} else {
    // Caso a autenticação falhe
    $resposta["sucesso"] = 0;
    $resposta["erro"] = "Usuário ou senha não confere";
    $resposta["cod_erro"] = 0;
}

// Fecha a conexão com o banco de dados
$db_con = null;

// Converte a resposta para JSON e retorna
echo json_encode($resposta);
?>
