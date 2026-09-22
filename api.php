<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$arquivo = __DIR__ . '/dados.json';

function carregarUsuarios($arquivo)
{
    if (!file_exists($arquivo)) {
        file_put_contents($arquivo, '[]');
    }

    $conteudo = file_get_contents($arquivo);
    return json_decode($conteudo, true) ?? [];
}

function salvarUsuarios($arquivo, $usuarios)
{
    file_put_contents(
        $arquivo,
        json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function responder($dados, $status = 200)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function lerJson()
{
    $conteudo = file_get_contents('php://input');
    return json_decode($conteudo, true) ?? [];
}

$usuarios = carregarUsuarios($arquivo);
$metodo = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($metodo === 'GET') {
    if ($id !== null) {
        foreach ($usuarios as $usuario) {
            if ($usuario['id'] === $id) {
                responder($usuario);
            }
        }

        responder(['erro' => 'Usuário não encontrado'], 404);
    }

    responder($usuarios);
}

if ($metodo === 'POST') {
    $dados = lerJson();

    if (empty($dados['nome']) || empty($dados['email'])) {
        responder(['erro' => 'Nome e e-mail são obrigatórios'], 400);
    }

    $novoId = 1;

    if (count($usuarios) > 0) {
        $ids = array_column($usuarios, 'id');
        $novoId = max($ids) + 1;
    }

    $novoUsuario = [
        'id' => $novoId,
        'nome' => trim($dados['nome']),
        'email' => trim($dados['email']),
        'idade' => $dados['idade'] ?? null
    ];

    $usuarios[] = $novoUsuario;
    salvarUsuarios($arquivo, $usuarios);

    responder($novoUsuario, 201);
}

if ($metodo === 'PUT') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID'], 400);
    }

    $dados = lerJson();

    if (empty($dados['nome']) || empty($dados['email']) || !isset($dados['idade'])) {
        responder(['erro' => 'PUT exige nome, email e idade'], 400);
    }

    foreach ($usuarios as $indice => $usuario) {
        if ($usuario['id'] === $id) {
            $usuarios[$indice] = [
                'id' => $id,
                'nome' => trim($dados['nome']),
                'email' => trim($dados['email']),
                'idade' => $dados['idade']
            ];

            salvarUsuarios($arquivo, $usuarios);
            responder($usuarios[$indice]);
        }
    }

    responder(['erro' => 'Usuário não encontrado'], 404);
}

if ($metodo === 'PATCH') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID'], 400);
    }

    $dados = lerJson();

    foreach ($usuarios as $indice => $usuario) {
        if ($usuario['id'] === $id) {
            if (array_key_exists('nome', $dados)) {
                $usuarios[$indice]['nome'] = trim($dados['nome']);
            }

            if (array_key_exists('email', $dados)) {
                $usuarios[$indice]['email'] = trim($dados['email']);
            }

            if (array_key_exists('idade', $dados)) {
                $usuarios[$indice]['idade'] = $dados['idade'];
            }

            salvarUsuarios($arquivo, $usuarios);
            responder($usuarios[$indice]);
        }
    }

    responder(['erro' => 'Usuário não encontrado'], 404);
}

if ($metodo === 'DELETE') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID'], 400);
    }

    foreach ($usuarios as $indice => $usuario) {
        if ($usuario['id'] === $id) {
            $usuarioExcluido = $usuario;
            array_splice($usuarios, $indice, 1);

            salvarUsuarios($arquivo, $usuarios);

            responder([
                'mensagem' => 'Usuário excluído',
                'usuario' => $usuarioExcluido
            ]);
        }
    }

    responder(['erro' => 'Usuário não encontrado'], 404);
}

responder(['erro' => 'Método não permitido'], 405);
