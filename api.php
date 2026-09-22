<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const MAX_BASES_UPLOAD = 5;
const MAX_UPLOAD_BYTES = 2097152; // 2 MB

$arquivoPadrao = __DIR__ . '/dados.json';
$diretorioBases = __DIR__ . '/bases';

if (!is_dir($diretorioBases)) {
    mkdir($diretorioBases, 0775, true);
}

function responder($dados, $status = 200)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function erro($message, $status = 400, $errors = null)
{
    $payload = [
        'error' => true,
        'status' => $status,
        'message' => $message
    ];

    if ($errors !== null) {
        $payload['errors'] = $errors;
    }

    responder($payload, $status);
}

function erroValidacao($erros)
{
    erro('Dados inválidos', 422, $erros);
}

function listarBases($diretorioBases)
{
    $bases = [
        [
            'id' => 'dados.json',
            'nome' => 'Base padrão',
            'arquivo' => 'dados.json',
            'padrao' => true
        ]
    ];

    $arquivos = glob($diretorioBases . '/*.json') ?: [];
    sort($arquivos, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo);
        $bases[] = [
            'id' => $nome,
            'nome' => pathinfo($nome, PATHINFO_FILENAME),
            'arquivo' => $nome,
            'padrao' => false
        ];
    }

    return $bases;
}

function caminhoBaseSelecionada($base, $arquivoPadrao, $diretorioBases)
{
    if ($base === null || $base === '' || $base === 'dados.json') {
        return $arquivoPadrao;
    }

    $nomeSeguro = basename($base);

    if ($nomeSeguro !== $base || strtolower(pathinfo($nomeSeguro, PATHINFO_EXTENSION)) !== 'json') {
        erro('Base de dados inválida.', 400);
    }

    $caminho = $diretorioBases . '/' . $nomeSeguro;

    if (!is_file($caminho)) {
        erro('Base de dados não encontrada.', 404);
    }

    return $caminho;
}

function carregarUsuarios($arquivo)
{
    if (!file_exists($arquivo)) {
        file_put_contents($arquivo, '[]');
    }

    $conteudo = file_get_contents($arquivo);
    $dados = json_decode($conteudo, true);

    if (!is_array($dados)) {
        erro('A base selecionada não contém um array JSON válido.', 500);
    }

    return $dados;
}

function salvarUsuarios($arquivo, $usuarios)
{
    $resultado = file_put_contents(
        $arquivo,
        json_encode(array_values($usuarios), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($resultado === false) {
        erro('Não foi possível salvar a base de dados.', 500);
    }
}

function lerJson()
{
    $conteudo = file_get_contents('php://input');
    $dados = json_decode($conteudo, true);

    if ($conteudo !== '' && json_last_error() !== JSON_ERROR_NONE) {
        erro('JSON inválido', 400);
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

function validarBaseImportada($dados)
{
    if (!is_array($dados) || !array_is_list($dados)) {
        return ['base' => 'O JSON deve ter um array de usuários na raiz.'];
    }

    $erros = [];
    $ids = [];
    $emails = [];

    foreach ($dados as $indice => $usuario) {
        if (!is_array($usuario)) {
            $erros["registro_$indice"] = 'Cada item da base deve ser um objeto JSON.';
            continue;
        }

        if (!array_key_exists('id', $usuario) || filter_var($usuario['id'], FILTER_VALIDATE_INT) === false || (int) $usuario['id'] < 1) {
            $erros["registro_$indice.id"] = 'O ID deve ser um inteiro positivo.';
        } else {
            $id = (int) $usuario['id'];
            if (isset($ids[$id])) {
                $erros["registro_$indice.id"] = 'O ID está duplicado na base.';
            }
            $ids[$id] = true;
        }

        $validacao = validarUsuario($usuario);

        foreach ($validacao as $campo => $mensagem) {
            $erros["registro_$indice.$campo"] = $mensagem;
        }

        if (isset($usuario['email']) && filter_var($usuario['email'], FILTER_VALIDATE_EMAIL)) {
            $email = strtolower(trim($usuario['email']));
            if (isset($emails[$email])) {
                $erros["registro_$indice.email"] = 'O e-mail está duplicado na base.';
            }
            $emails[$email] = true;
        }

        if (count($erros) >= 20) {
            $erros['base'] = 'A validação foi interrompida após encontrar muitos erros.';
            break;
        }
    }

    return $erros;
}

function normalizarBaseImportada($dados)
{
    return array_map(function ($usuario) {
        return [
            'id' => (int) $usuario['id'],
            'nome' => trim($usuario['nome']),
            'email' => trim($usuario['email']),
            'idade' => (int) $usuario['idade']
        ];
    }, $dados);
}

function emailEmUso($usuarios, $email, $ignorarId = null)
{
    foreach ($usuarios as $usuario) {
        if (
            isset($usuario['email'], $usuario['id']) &&
            strtolower($usuario['email']) === strtolower($email) &&
            ($ignorarId === null || (int) $usuario['id'] !== $ignorarId)
        ) {
            return true;
        }
    }

    return false;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($action === 'bases') {
    if ($metodo !== 'GET') {
        erro('Método não permitido para esta ação.', 405);
    }

    $bases = listarBases($diretorioBases);

    responder([
        'max_uploads' => MAX_BASES_UPLOAD,
        'uploads_utilizados' => max(0, count($bases) - 1),
        'bases' => $bases
    ]);
}

if ($action === 'upload-base') {
    if ($metodo !== 'POST') {
        erro('Método não permitido para esta ação.', 405);
    }

    $bases = listarBases($diretorioBases);
    $uploadsUtilizados = max(0, count($bases) - 1);

    if ($uploadsUtilizados >= MAX_BASES_UPLOAD) {
        erro('O limite de 5 bases JSON anexadas foi atingido.', 409);
    }

    if (!isset($_FILES['base'])) {
        erro('Selecione um arquivo JSON para enviar.', 400);
    }

    $upload = $_FILES['base'];

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        erro('Falha no upload do arquivo.', 400);
    }

    if ($upload['size'] > MAX_UPLOAD_BYTES) {
        erro('O arquivo deve ter no máximo 2 MB.', 413);
    }

    $nomeOriginal = basename($upload['name']);
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

    if ($extensao !== 'json') {
        erro('Apenas arquivos com extensão .json são permitidos.', 415);
    }

    $nomeBase = pathinfo($nomeOriginal, PATHINFO_FILENAME);
    $nomeSeguro = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $nomeBase);
    $nomeSeguro = trim($nomeSeguro, '-_');

    if ($nomeSeguro === '') {
        $nomeSeguro = 'base';
    }

    $nomeFinal = $nomeSeguro . '.json';
    $destino = $diretorioBases . '/' . $nomeFinal;

    if (file_exists($destino)) {
        erro('Já existe uma base com esse nome. Renomeie o arquivo e tente novamente.', 409);
    }

    $conteudo = file_get_contents($upload['tmp_name']);
    $dados = json_decode($conteudo, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        erro('O arquivo enviado não contém JSON válido.', 422);
    }

    $erros = validarBaseImportada($dados);

    if ($erros) {
        erro('A base JSON não é compatível com o formato esperado.', 422, $erros);
    }

    $dadosNormalizados = normalizarBaseImportada($dados);

    $salvou = file_put_contents(
        $destino,
        json_encode($dadosNormalizados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($salvou === false) {
        erro('Não foi possível armazenar a base enviada.', 500);
    }

    responder([
        'message' => 'Base adicionada com sucesso.',
        'base' => [
            'id' => $nomeFinal,
            'nome' => $nomeSeguro,
            'arquivo' => $nomeFinal,
            'padrao' => false
        ],
        'registros' => count($dadosNormalizados)
    ], 201);
}

if ($action === 'delete-base') {
    if ($metodo !== 'DELETE') {
        erro('Método não permitido para esta ação.', 405);
    }

    $base = $_GET['base'] ?? '';

    if ($base === '' || $base === 'dados.json') {
        erro('A base padrão não pode ser removida.', 400);
    }

    $nomeSeguro = basename($base);

    if ($nomeSeguro !== $base || strtolower(pathinfo($nomeSeguro, PATHINFO_EXTENSION)) !== 'json') {
        erro('Base de dados inválida.', 400);
    }

    $caminho = $diretorioBases . '/' . $nomeSeguro;

    if (!is_file($caminho)) {
        erro('Base de dados não encontrada.', 404);
    }

    if (!unlink($caminho)) {
        erro('Não foi possível remover a base de dados.', 500);
    }

    responder(['message' => 'Base removida com sucesso.']);
}

$baseSelecionada = $_GET['base'] ?? 'dados.json';
$arquivo = caminhoBaseSelecionada($baseSelecionada, $arquivoPadrao, $diretorioBases);
$usuarios = carregarUsuarios($arquivo);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($metodo === 'GET') {
    if ($id !== null) {
        foreach ($usuarios as $usuario) {
            if ((int) $usuario['id'] === $id) {
                responder([
                    'base' => $baseSelecionada,
                    'dados' => $usuario
                ]);
            }
        }

        erro('Usuário não encontrado', 404);
    }

    $resultado = $usuarios;

    if (isset($_GET['nome']) && trim($_GET['nome']) !== '') {
        $nome = mb_strtolower(trim($_GET['nome']));

        $resultado = array_filter($resultado, function ($usuario) use ($nome) {
            return isset($usuario['nome']) && str_contains(mb_strtolower($usuario['nome']), $nome);
        });
    }

    if (isset($_GET['email']) && trim($_GET['email']) !== '') {
        $email = mb_strtolower(trim($_GET['email']));

        $resultado = array_filter($resultado, function ($usuario) use ($email) {
            return isset($usuario['email']) && str_contains(mb_strtolower($usuario['email']), $email);
        });
    }

    if (isset($_GET['idade']) && $_GET['idade'] !== '') {
        if (filter_var($_GET['idade'], FILTER_VALIDATE_INT) === false) {
            erroValidacao(['idade' => 'O filtro de idade deve ser um número inteiro.']);
        }

        $idade = (int) $_GET['idade'];

        $resultado = array_filter($resultado, function ($usuario) use ($idade) {
            return isset($usuario['idade']) && (int) $usuario['idade'] === $idade;
        });
    }

    $camposOrdenacao = ['id', 'nome', 'email', 'idade'];
    $sort = $_GET['sort'] ?? 'id';
    $order = strtolower($_GET['order'] ?? 'asc');

    if (!in_array($sort, $camposOrdenacao, true)) {
        erroValidacao(['sort' => 'Campo de ordenação inválido. Use id, nome, email ou idade.']);
    }

    if (!in_array($order, ['asc', 'desc'], true)) {
        erroValidacao(['order' => 'Direção inválida. Use asc ou desc.']);
    }

    usort($resultado, function ($a, $b) use ($sort, $order) {
        $valorA = $a[$sort] ?? null;
        $valorB = $b[$sort] ?? null;

        if (is_string($valorA)) {
            $comparacao = strcasecmp($valorA, (string) $valorB);
        } else {
            $comparacao = $valorA <=> $valorB;
        }

        return $order === 'desc' ? -$comparacao : $comparacao;
    });

    responder([
        'base' => $baseSelecionada,
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

    $ids = array_map(fn($usuario) => (int) ($usuario['id'] ?? 0), $usuarios);
    $novoId = count($ids) > 0 ? max($ids) + 1 : 1;

    $novoUsuario = [
        'id' => $novoId,
        'nome' => trim($dados['nome']),
        'email' => $email,
        'idade' => (int) $dados['idade']
    ];

    $usuarios[] = $novoUsuario;
    salvarUsuarios($arquivo, $usuarios);

    responder([
        'base' => $baseSelecionada,
        'dados' => $novoUsuario
    ], 201);
}

if ($metodo === 'PUT') {
    if ($id === null) {
        erro('Informe o ID', 400);
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
        if ((int) $usuario['id'] === $id) {
            $usuarios[$indice] = [
                'id' => $id,
                'nome' => trim($dados['nome']),
                'email' => $email,
                'idade' => (int) $dados['idade']
            ];

            salvarUsuarios($arquivo, $usuarios);

            responder([
                'base' => $baseSelecionada,
                'dados' => $usuarios[$indice]
            ]);
        }
    }

    erro('Usuário não encontrado', 404);
}

if ($metodo === 'PATCH') {
    if ($id === null) {
        erro('Informe o ID', 400);
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
        if ((int) $usuario['id'] === $id) {
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

            responder([
                'base' => $baseSelecionada,
                'dados' => $usuarios[$indice]
            ]);
        }
    }

    erro('Usuário não encontrado', 404);
}

if ($metodo === 'DELETE') {
    if ($id === null) {
        erro('Informe o ID', 400);
    }

    foreach ($usuarios as $indice => $usuario) {
        if ((int) $usuario['id'] === $id) {
            $usuarioExcluido = $usuario;
            array_splice($usuarios, $indice, 1);
            salvarUsuarios($arquivo, $usuarios);

            responder([
                'base' => $baseSelecionada,
                'message' => 'Usuário excluído',
                'dados' => $usuarioExcluido
            ]);
        }
    }

    erro('Usuário não encontrado', 404);
}

erro('Método não permitido', 405);
