# API Tests

Projeto simples desenvolvido em **PHP, HTML e JavaScript** para praticar o funcionamento dos principais métodos HTTP usados em APIs REST:

- `GET`
- `POST`
- `PUT`
- `PATCH`
- `DELETE`

A aplicação possui uma interface visual em tema **grafite com destaques em laranja**, criada para facilitar os testes e tornar mais claro o que cada requisição envia e recebe.

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
- validar e-mails;
- adicionar autenticação;
- criar uma API orientada a objetos;
- utilizar frameworks como Laravel;
- testar os endpoints com Postman, Insomnia ou Bruno.

## Licença

Projeto criado para estudos e prática de requisições HTTP.
