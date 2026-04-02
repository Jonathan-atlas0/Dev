<?php

require_once __DIR__ . '/../models/EstoqueModel.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$model  = new EstoqueModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents("php://input"), true) ?? [];

// Rota: /api/estoque.php?produto=Capuccino
$produto = $_GET['produto'] ?? null;

switch ($method) {

    // GET /api/estoque.php           → lista todos
    // GET /api/estoque.php?produto=X → busca um produto
    case 'GET':
        if ($produto) {
            $item = $model->buscarPorNome($produto);
            if ($item) {
                echo json_encode($item);
            } else {
                http_response_code(404);
                echo json_encode(["erro" => "Produto não encontrado."]);
            }
        } else {
            echo json_encode($model->listarTodos());
        }
        break;

    // POST /api/estoque.php
    // Body: { "produto": "Capuccino", "valor": 15.99, "imagem": "./img/menu-1.png", "quantidade": 10 }
    case 'POST':
        $campos = ['produto', 'valor', 'imagem', 'quantidade'];
        foreach ($campos as $campo) {
            if (!isset($body[$campo])) {
                http_response_code(400);
                echo json_encode(["erro" => "Campo obrigatório ausente: $campo"]);
                exit;
            }
        }

        $ok = $model->inserir(
            $body['produto'],
            (float) $body['valor'],
            $body['imagem'],
            (int)   $body['quantidade']
        );

        http_response_code($ok ? 201 : 500);
        echo json_encode($ok
            ? ["mensagem" => "Produto adicionado ao estoque."]
            : ["erro"     => "Erro ao inserir produto."]
        );
        break;

    // PUT /api/estoque.php?produto=X
    // Body: { "quantidade": 20 }
    case 'PUT':
        if (!$produto) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe o produto na URL: ?produto=X"]);
            exit;
        }

        if (!isset($body['quantidade'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe a nova quantidade no body."]);
            exit;
        }

        $ok = $model->atualizarQuantidade($produto, (int) $body['quantidade']);
        echo json_encode($ok
            ? ["mensagem" => "Quantidade atualizada."]
            : ["erro"     => "Erro ao atualizar."]
        );
        break;

    // DELETE /api/estoque.php?produto=X
    case 'DELETE':
        if (!$produto) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe o produto na URL: ?produto=X"]);
            exit;
        }

        $ok = $model->deletar($produto);
        echo json_encode($ok
            ? ["mensagem" => "Produto removido do estoque."]
            : ["erro"     => "Erro ao remover produto."]
        );
        break;

    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
}