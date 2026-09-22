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
        json_encode(array_values($usuarios), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function responder($dados, $status = 200)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function erroValidacao($erros)
{
    responder([
        'error' => true,
        'status' => 422,
        'message' => 'Dados inválidos',
        'errors' => $erros
    ], 422);
}

function lerJson()
{
    $conteudo = file_get_contents('php://input');
    $dados = json_decode($conteudo, true);

    if ($conteudo !== '' && json_last_error() !== JSON_ERROR_NONE) {
        responder([
            'error' => true,
            'status' => 400,
            'message' => 'JSON inválido'
        ], 400);
    }

    return $dados ?? [];
}

function validarUsuario($dados, $parcial = false)
{
    $erros = [];

    if (!$parcial || array_key_exists('nome', $dados)) {
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '') {
            $erros['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($nome) < 3) {
            $erros['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($nome) > 100) {
            $erros['nome'] = 'O nome deve ter no máximo 100 caracteres.';
        }
    }

    if (!$parcial || array_key_exists('email', $dados)) {
        $email = trim((string) ($dados['email'] ?? ''));

        if ($email === '') {
            $erros['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um e-mail válido.';
        }
    }

    if (!$parcial || array_key_exists('idade', $dados)) {
        $idade = $dados['idade'] ?? null;

        if ($idade === null || $idade === '') {
            $erros['idade'] = 'A idade é obrigatória.';
        } elseif (filter_var($idade, FILTER_VALIDATE_INT) === false) {
            $erros['idade'] = 'A idade deve ser um número inteiro.';
        } elseif ((int) $idade < 0 || (int) $idade > 120) {
            $erros['idade'] = 'A idade deve estar entre 0 e 120.';
        }
    }

    return $erros;
}

function emailEmUso($usuarios, $email, $ignorarId = null)
{
    foreach ($usuarios as $usuario) {
        if (
            strtolower($usuario['email']) === strtolower($email) &&
            ($ignorarId === null || $usuario['id'] !== $ignorarId)
        ) {
            return true;
        }
    }

    return false;
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

        responder([
            'error' => true,
            'status' => 404,
            'message' => 'Usuário não encontrado'
        ], 404);
    }

    $resultado = $usuarios;

    if (isset($_GET['nome']) && trim($_GET['nome']) !== '') {
        $nome = mb_strtolower(trim($_GET['nome']));

        $resultado = array_filter($resultado, function ($usuario) use ($nome) {
            return str_contains(mb_strtolower($usuario['nome']), $nome);
        });
    }

    if (isset($_GET['email']) && trim($_GET['email']) !== '') {
        $email = mb_strtolower(trim($_GET['email']));

        $resultado = array_filter($resultado, function ($usuario) use ($email) {
            return str_contains(mb_strtolower($usuario['email']), $email);
        });
    }

    if (isset($_GET['idade']) && $_GET['idade'] !== '') {
        if (filter_var($_GET['idade'], FILTER_VALIDATE_INT) === false) {
            erroValidacao(['idade' => 'O filtro de idade deve ser um número inteiro.']);
        }

        $idade = (int) $_GET['idade'];

        $resultado = array_filter($resultado, function ($usuario) use ($idade) {
            return (int) $usuario['idade'] === $idade;
        });
    }

    $camposOrdenacao = ['id', 'nome', 'email', 'idade'];
    $sort = $_GET['sort'] ?? 'id';
    $order = strtolower($_GET['order'] ?? 'asc');

    if (!in_array($sort, $camposOrdenacao, true)) {
        erroValidacao([
            'sort' => 'Campo de ordenação inválido. Use id, nome, email ou idade.'
        ]);
    }

    if (!in_array($order, ['asc', 'desc'], true)) {
        erroValidacao([
            'order' => 'Direção inválida. Use asc ou desc.'
        ]);
    }

    usort($resultado, function ($a, $b) use ($sort, $order) {
        $valorA = $a[$sort];
        $valorB = $b[$sort];

        if (is_string($valorA)) {
            $comparacao = strcasecmp($valorA, $valorB);
        } else {
            $comparacao = $valorA <=> $valorB;
        }

        return $order === 'desc' ? -$comparacao : $comparacao;
    });

    responder([
        'total' => count($resultado),
        'filtros' => [
            'nome' => $_GET['nome'] ?? null,
            'email' => $_GET['email'] ?? null,
            'idade' => isset($_GET['idade']) && $_GET['idade'] !== '' ? (int) $_GET['idade'] : null
        ],
        'ordenacao' => [
            'campo' => $sort,
            'direcao' => $order
        ],
        'dados' => array_values($resultado)
    ]);
}

if ($metodo === 'POST') {
    $dados = lerJson();
    $erros = validarUsuario($dados);

    if ($erros) {
        erroValidacao($erros);
    }

    $email = trim($dados['email']);

    if (emailEmUso($usuarios, $email)) {
        erroValidacao(['email' => 'Este e-mail já está cadastrado.']);
    }

    $novoId = count($usuarios) > 0
        ? max(array_column($usuarios, 'id')) + 1
        : 1;

    $novoUsuario = [
        'id' => $novoId,
        'nome' => trim($dados['nome']),
        'email' => $email,
        'idade' => (int) $dados['idade']
    ];

    $usuarios[] = $novoUsuario;
    salvarUsuarios($arquivo, $usuarios);

    responder($novoUsuario, 201);
}

if ($metodo === 'PUT') {
    if ($id === null) {
        responder([
            'error' => true,
            'status' => 400,
            'message' => 'Informe o ID'
        ], 400);
    }

    $dados = lerJson();
    $erros = validarUsuario($dados);

    if ($erros) {
        erroValidacao($erros);
    }

    $email = trim($dados['email']);

    if (emailEmUso($usuarios, $email, $id)) {
        erroValidacao(['email' => 'Este e-mail já está cadastrado.']);
    }

    foreach ($usuarios as $indice => $usuario) {
        if ($usuario['id'] === $id) {
            $usuarios[$indice] = [
                'id' => $id,
                'nome' => trim($dados['nome']),
                'email' => $email,
                'idade' => (int) $dados['idade']
            ];

            salvarUsuarios($arquivo, $usuarios);
            responder($usuarios[$indice]);
        }
    }

    responder([
        'error' => true,
        'status' => 404,
        'message' => 'Usuário não encontrado'
    ], 404);
}

if ($metodo === 'PATCH') {
    if ($id === null) {
        responder([
            'error' => true,
            'status' => 400,
            'message' => 'Informe o ID'
        ], 400);
    }

    $dados = lerJson();

    if (empty($dados)) {
        erroValidacao(['body' => 'Envie ao menos um campo para atualizar.']);
    }

    $erros = validarUsuario($dados, true);

    if ($erros) {
        erroValidacao($erros);
    }

    if (
        array_key_exists('email', $dados) &&
        emailEmUso($usuarios, trim($dados['email']), $id)
    ) {
        erroValidacao(['email' => 'Este e-mail já está cadastrado.']);
    }

    foreach ($usuarios as $indice => $usuario) {
        if ($usuario['id'] === $id) {
            if (array_key_exists('nome', $dados)) {
                $usuarios[$indice]['nome'] = trim($dados['nome']);
            }

            if (array_key_exists('email', $dados)) {
                $usuarios[$indice]['email'] = trim($dados['email']);
            }

            if (array_key_exists('idade', $dados)) {
                $usuarios[$indice]['idade'] = (int) $dados['idade'];
            }

            salvarUsuarios($arquivo, $usuarios);
            responder($usuarios[$indice]);
        }
    }

    responder([
        'error' => true,
        'status' => 404,
        'message' => 'Usuário não encontrado'
    ], 404);
}

if ($metodo === 'DELETE') {
    if ($id === null) {
        responder([
            'error' => true,
            'status' => 400,
            'message' => 'Informe o ID'
        ], 400);
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

    responder([
        'error' => true,
        'status' => 404,
        'message' => 'Usuário não encontrado'
    ], 404);
}

responder([
    'error' => true,
    'status' => 405,
    'message' => 'Método não permitido'
], 405);
