# API Tests

Projeto simples desenvolvido em **PHP, HTML e JavaScript** para praticar o funcionamento dos principais métodos HTTP usados em APIs REST:

- `GET`
- `POST`
- `PUT`
- `PATCH`
- `DELETE`

## Objetivo

O objetivo do projeto é servir como um pequeno laboratório para entender, na prática, como o navegador se comunica com uma API.

A interface usa a `Fetch API` do JavaScript para enviar requisições ao arquivo `api.php`. O PHP interpreta o método HTTP recebido, executa a operação correspondente e retorna uma resposta em JSON.

Os dados são armazenados em um arquivo `dados.json`, portanto não é necessário configurar banco de dados.

## Estrutura do projeto

```text
API-Tests/
├── api.php
├── index.html
├── dados.json
└── README.md
```

### `index.html`

Contém a interface do sistema, os estilos visuais e o JavaScript responsável pelas requisições.

Na tela é possível informar:

- ID
- Nome
- E-mail
- Idade

Depois, basta escolher qual método HTTP deseja testar.

A resposta da API é exibida na própria página, incluindo o código de status HTTP e os dados retornados.

### `api.php`

É a API do projeto.

O arquivo identifica o método da requisição usando:

```php
$_SERVER['REQUEST_METHOD']
```

A partir disso, executa a operação correspondente sobre os usuários armazenados no arquivo JSON.

### `dados.json`

Funciona como armazenamento simples da aplicação.

Inicialmente, o arquivo pode conter:

```json
[]
```

À medida que usuários são criados, alterados ou removidos, o PHP atualiza esse arquivo.

## Como executar

É necessário ter o **PHP instalado** na máquina.

Clone o repositório:

```bash
git clone https://github.com/guerradiegop/API-Tests.git
```

Entre na pasta:

```bash
cd API-Tests
```

Inicie o servidor embutido do PHP:

```bash
php -S localhost:8000
```

Depois acesse no navegador:

```text
http://localhost:8000
```

> Evite abrir o `index.html` diretamente pelo explorador de arquivos. Execute o projeto através do servidor PHP.

## Métodos HTTP

### GET — listar usuários

```http
GET /api.php
```

Retorna todos os usuários cadastrados.

Exemplo de resposta:

```json
[
  {
    "id": 1,
    "nome": "Maria",
    "email": "maria@email.com",
    "idade": 25
  }
]
```

### GET — buscar um usuário

```http
GET /api.php?id=1
```

Retorna apenas o usuário que possui o ID informado.

Caso o ID não exista, a API retorna `404 Not Found`.

## POST — criar usuário

```http
POST /api.php
Content-Type: application/json
```

Corpo da requisição:

```json
{
  "nome": "João",
  "email": "joao@email.com",
  "idade": 30
}
```

O servidor gera automaticamente o ID do novo usuário.

Em caso de sucesso, a API retorna:

```text
201 Created
```

## PUT — substituir usuário

```http
PUT /api.php?id=1
Content-Type: application/json
```

Exemplo:

```json
{
  "nome": "Maria Silva",
  "email": "maria.silva@email.com",
  "idade": 26
}
```

Neste projeto, o `PUT` representa uma substituição completa dos dados editáveis do usuário.

Por isso, devem ser enviados:

- nome
- e-mail
- idade

## PATCH — atualização parcial

```http
PATCH /api.php?id=1
Content-Type: application/json
```

O `PATCH` permite alterar somente os campos desejados.

Por exemplo, para modificar apenas a idade:

```json
{
  "idade": 27
}
```

Os demais dados permanecem inalterados.

## DELETE — excluir usuário

```http
DELETE /api.php?id=1
```

Remove o usuário que possui o ID informado.

## Resumo dos métodos

| Método | Função |
|---|---|
| `GET` | Consultar dados |
| `POST` | Criar um novo registro |
| `PUT` | Substituir um registro |
| `PATCH` | Alterar parcialmente um registro |
| `DELETE` | Excluir um registro |

## Códigos HTTP usados

A API utiliza códigos HTTP para indicar o resultado das operações.

| Código | Significado |
|---|---|
| `200` | Requisição realizada com sucesso |
| `201` | Registro criado com sucesso |
| `204` | Requisição OPTIONS processada sem conteúdo |
| `400` | Dados obrigatórios não foram enviados |
| `404` | Usuário não encontrado |
| `405` | Método HTTP não permitido |
| `422` | Dados enviados não passaram pela validação |

## Como o JavaScript envia as requisições

A interface utiliza a função `fetch()`.

Exemplo de um `POST`:

```javascript
fetch('api.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        nome: 'João',
        email: 'joao@email.com',
        idade: 30
    })
});
```

Para métodos que trabalham com um usuário específico, o ID é enviado pela URL:

```text
api.php?id=1
```

## PUT x PATCH

Uma das principais ideias exploradas no projeto é a diferença entre `PUT` e `PATCH`.

### PUT

Substitui a representação completa do recurso.

```json
{
  "nome": "Ana",
  "email": "ana@email.com",
  "idade": 24
}
```

### PATCH

Envia somente o que precisa ser alterado.

```json
{
  "idade": 25
}
```

## Tecnologias utilizadas

- PHP
- HTML5
- CSS3
- JavaScript
- Fetch API
- JSON
- HTTP

## Possíveis evoluções

Este projeto foi mantido propositalmente simples para fins de estudo. Alguns próximos passos possíveis são:

- usar MySQL ou PostgreSQL;
- acessar o banco com PDO;
- criar rotas como `/usuarios/1`;
- separar HTML, CSS e JavaScript em arquivos diferentes;
- adicionar autenticação;
- criar uma API orientada a objetos;
- utilizar frameworks como Laravel;
- testar os endpoints com Postman, Insomnia ou Bruno.

## Licença

Projeto criado para estudos e prática de requisições HTTP.


## Validação de dados

A API agora valida os dados recebidos em `POST`, `PUT` e `PATCH`.

Regras implementadas:

- nome obrigatório, com 3 a 100 caracteres;
- e-mail obrigatório e em formato válido;
- e-mail não pode estar duplicado;
- idade deve ser um número inteiro entre 0 e 120;
- `PATCH` precisa enviar pelo menos um campo.

Quando houver erro de validação, a API retorna `422 Unprocessable Content`.

Exemplo:

```json
{
  "error": true,
  "status": 422,
  "message": "Dados inválidos",
  "errors": {
    "email": "Informe um e-mail válido."
  }
}
```

## Filtros de busca

O endpoint de listagem aceita filtros por query string.

### Filtrar por nome

```http
GET /api.php?nome=maria
```

A busca é parcial e não diferencia maiúsculas de minúsculas.

### Filtrar por e-mail

```http
GET /api.php?email=@gmail.com
```

### Filtrar por idade

```http
GET /api.php?idade=25
```

Os filtros podem ser combinados:

```http
GET /api.php?nome=maria&idade=25
```

## Ordenação

A listagem também aceita os parâmetros `sort` e `order`.

Campos permitidos em `sort`:

- `id`
- `nome`
- `email`
- `idade`

Valores permitidos em `order`:

- `asc`
- `desc`

Exemplos:

```http
GET /api.php?sort=nome&order=asc
```

```http
GET /api.php?sort=idade&order=desc
```

Também é possível combinar filtros e ordenação:

```http
GET /api.php?nome=ana&sort=idade&order=desc
```

A resposta da listagem agora inclui metadados:

```json
{
  "total": 2,
  "filtros": {
    "nome": "ana",
    "email": null,
    "idade": null
  },
  "ordenacao": {
    "campo": "idade",
    "direcao": "desc"
  },
  "dados": [
    {
      "id": 3,
      "nome": "Ana Souza",
      "email": "ana@email.com",
      "idade": 32
    }
  ]
}
```

## Interface para filtros e ordenação

A interface web possui campos específicos para testar:

- busca parcial por nome;
- busca parcial por e-mail;
- filtro por idade exata;
- ordenação por ID, nome, e-mail ou idade;
- ordenação crescente ou decrescente.

A URL que será enviada pela requisição GET é exibida na própria tela antes da execução.


## Múltiplas bases JSON

O projeto também permite trabalhar com diferentes bases de dados em formato JSON.

A base original continua sendo:

```text
dados.json
```

Além dela, é possível anexar até **5 arquivos JSON adicionais** pela própria interface.

Os arquivos enviados são armazenados em:

```text
bases/
```

### Selecionando a base ativa

Na interface existe o campo **Base ativa**.

Todas as requisições de CRUD usam somente a base selecionada.

Por exemplo:

```http
GET /api.php?base=clientes.json
```

```http
GET /api.php?base=clientes.json&id=3
```

```http
PATCH /api.php?base=clientes.json&id=3
```

Assim, alterações feitas em uma base não afetam as demais.

### Enviando uma base JSON

O upload é feito pela interface usando `multipart/form-data`.

Internamente, a chamada usa:

```http
POST /api.php?action=upload-base
```

Cada arquivo pode ter no máximo **2 MB**.

O limite é de **5 bases anexadas**, além da base padrão `dados.json`.

### Formato esperado do JSON

O arquivo deve conter um array de usuários na raiz:

```json
[
  {
    "id": 1,
    "nome": "Maria Silva",
    "email": "maria@email.com",
    "idade": 28
  },
  {
    "id": 2,
    "nome": "João Souza",
    "email": "joao@email.com",
    "idade": 35
  }
]
```

Cada registro precisa ter:

- `id`: inteiro positivo e único;
- `nome`: entre 3 e 100 caracteres;
- `email`: válido e único na base;
- `idade`: inteiro entre 0 e 120.

Se o arquivo não estiver nesse formato, a API rejeita o upload e retorna os erros encontrados.

### Listando as bases disponíveis

```http
GET /api.php?action=bases
```

Exemplo de resposta:

```json
{
  "max_uploads": 5,
  "uploads_utilizados": 2,
  "bases": [
    {
      "id": "dados.json",
      "nome": "Base padrão",
      "arquivo": "dados.json",
      "padrao": true
    },
    {
      "id": "clientes.json",
      "nome": "clientes",
      "arquivo": "clientes.json",
      "padrao": false
    }
  ]
}
```

### Removendo uma base anexada

Bases anexadas podem ser removidas pela interface ou pela API:

```http
DELETE /api.php?action=delete-base&base=clientes.json
```

A base padrão `dados.json` não pode ser removida.

### Estrutura atualizada

```text
API-Tests/
├── api.php
├── index.html
├── dados.json
├── bases/
│   └── .gitkeep
└── README.md
```
