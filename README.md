# Laravel Moloni

Package Laravel para integração completa com a [API Moloni](https://www.moloni.pt/dev/).

---

## Índice

- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
  - [Variáveis de ambiente](#variáveis-de-ambiente)
  - [Publicar o ficheiro de configuração](#publicar-o-ficheiro-de-configuração)
  - [Referência de todas as opções](#referência-de-todas-as-opções)
- [Autenticação](#autenticação)
  - [Password Grant — aplicações nativas](#password-grant--aplicações-nativas)
  - [Authorization Code Grant — aplicações web](#authorization-code-grant--aplicações-web)
  - [Como os tokens são geridos](#como-os-tokens-são-geridos)
- [Utilização básica](#utilização-básica)
  - [Facade vs injeção de dependência](#facade-vs-injeção-de-dependência)
  - [Definir a empresa](#definir-a-empresa)
- [Entidades](#entidades)
  - [Clientes](#clientes)
  - [Fornecedores](#fornecedores)
- [Produtos](#produtos)
  - [Produtos](#produtos-1)
  - [Categorias de produtos](#categorias-de-produtos)
- [Documentos](#documentos)
  - [Métodos comuns a todos os documentos](#métodos-comuns-a-todos-os-documentos)
  - [Faturas](#faturas)
  - [Faturas simplificadas](#faturas-simplificadas)
  - [Faturas-recibo](#faturas-recibo)
  - [Recibos](#recibos)
  - [Notas de crédito](#notas-de-crédito)
  - [Notas de débito](#notas-de-débito)
  - [Orçamentos](#orçamentos)
  - [Encomendas a clientes](#encomendas-a-clientes)
  - [Guias de remessa](#guias-de-remessa)
  - [Guias de transporte](#guias-de-transporte)
  - [Faturas de fornecedor](#faturas-de-fornecedor)
- [Configurações da empresa](#configurações-da-empresa)
  - [Impostos](#impostos)
  - [Métodos de pagamento](#métodos-de-pagamento)
  - [Armazéns](#armazéns)
  - [Unidades de medida](#unidades-de-medida)
  - [Séries de documentos](#séries-de-documentos)
  - [Contas bancárias](#contas-bancárias)
- [Dados globais](#dados-globais)
  - [Países](#países)
  - [Moedas](#moedas)
- [Empresas](#empresas)
- [Tratamento de erros](#tratamento-de-erros)
- [Testes](#testes)
- [Licença](#licença)

---

## Requisitos

| Requisito | Versão mínima |
|---|---|
| PHP | 8.1 |
| Laravel | 10, 11 ou 12 |
| Conta Moloni com acesso de developer | — |

---

## Instalação

```bash
composer require tomahock/laravel-moloni
```

O package regista-se automaticamente no Laravel via [Package Auto-Discovery](https://laravel.com/docs/packages#package-discovery). Não é necessário adicionar o provider manualmente.

---

## Configuração

### Variáveis de ambiente

Adicione ao seu ficheiro `.env` as credenciais obtidas na [área de developer Moloni](https://www.moloni.pt/dev/):

```env
# Credenciais OAuth (obrigatório)
MOLONI_CLIENT_ID=xxxxxxxxxxxxxxxx
MOLONI_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Tipo de autenticação: 'password' (padrão) ou 'authorization_code'
MOLONI_GRANT_TYPE=password

# Necessário apenas para o grant type 'password'
MOLONI_USERNAME=seuemail@empresa.pt
MOLONI_PASSWORD=a_sua_password

# Necessário apenas para o grant type 'authorization_code'
# MOLONI_REDIRECT_URI=https://a-sua-app.pt/moloni/callback
```

### Publicar o ficheiro de configuração

Para personalizar as opções avançadas, publique o ficheiro de configuração:

```bash
php artisan vendor:publish --tag=moloni-config
```

Isto cria o ficheiro `config/moloni.php` na sua aplicação.

### Referência de todas as opções

```php
// config/moloni.php

return [
    // Credenciais OAuth obtidas em https://www.moloni.pt/dev/
    'client_id'     => env('MOLONI_CLIENT_ID'),
    'client_secret' => env('MOLONI_CLIENT_SECRET'),
    'redirect_uri'  => env('MOLONI_REDIRECT_URI'),  // apenas para authorization_code
    'username'      => env('MOLONI_USERNAME'),       // apenas para password grant
    'password'      => env('MOLONI_PASSWORD'),       // apenas para password grant

    // 'password' ou 'authorization_code'
    'grant_type' => env('MOLONI_GRANT_TYPE', 'password'),

    // URL base da API (não alterar em produção)
    'base_url' => env('MOLONI_BASE_URL', 'https://api.moloni.pt/v1'),

    // Driver de cache para armazenar os tokens OAuth
    // null = usa o driver padrão da aplicação (config/cache.php)
    // Exemplos: 'redis', 'memcached', 'file', 'database'
    'token_cache_driver' => env('MOLONI_CACHE_DRIVER', null),

    // Prefixo das chaves de cache dos tokens
    'token_cache_prefix' => env('MOLONI_CACHE_PREFIX', 'moloni_token'),

    // Tempo limite em segundos para cada pedido HTTP
    'timeout' => env('MOLONI_TIMEOUT', 30),

    // Configuração de retry automático em caso de falha de rede
    'retry' => [
        'times' => env('MOLONI_RETRY_TIMES', 1),    // número de tentativas extra
        'sleep' => env('MOLONI_RETRY_SLEEP', 500),  // milissegundos entre tentativas
    ],
];
```

---

## Autenticação

A API Moloni utiliza **OAuth 2.0**. O package gere os tokens automaticamente — não precisa de tratar do processo manualmente em cada pedido.

### Password Grant — aplicações nativas

O método mais simples. As credenciais (email e password) são trocadas diretamente por um token de acesso. Ideal para integrações internas, scripts de sincronização e aplicações server-to-server onde não há um utilizador humano a fazer login.

**Configuração `.env`:**

```env
MOLONI_GRANT_TYPE=password
MOLONI_CLIENT_ID=xxxx
MOLONI_CLIENT_SECRET=xxxx
MOLONI_USERNAME=seuemail@empresa.pt
MOLONI_PASSWORD=a_sua_password
```

**Utilização:** Após configurar o `.env`, o package autentica-se automaticamente na primeira chamada à API. Não precisa de fazer mais nada.

```php
use Tomahock\Moloni\Facades\Moloni;

// O token é obtido e cacheado automaticamente
$clientes = Moloni::company(1)->customers()->getAll();
```

### Authorization Code Grant — aplicações web

Para aplicações onde um utilizador Moloni autoriza a integração através do browser. O fluxo tem dois passos.

**Configuração `.env`:**

```env
MOLONI_GRANT_TYPE=authorization_code
MOLONI_CLIENT_ID=xxxx
MOLONI_CLIENT_SECRET=xxxx
MOLONI_REDIRECT_URI=https://a-sua-app.pt/moloni/callback
```

**Passo 1 — Redirecionar o utilizador para o Moloni:**

```php
// routes/web.php
Route::get('/moloni/auth', function () {
    return redirect(Moloni::getAuthorizationUrl());
});
```

O utilizador será enviado para `https://www.moloni.pt/ac/root/oauth/` onde irá autenticar-se com a sua conta Moloni e autorizar a aplicação.

**Passo 2 — Tratar o callback:**

O Moloni redireciona para o `MOLONI_REDIRECT_URI` com um parâmetro `?code=xxxx` na URL.

```php
// routes/web.php
Route::get('/moloni/callback', function (Request $request) {
    $tokens = Moloni::handleAuthorizationCallback($request->code);

    // Os tokens ficam cacheados automaticamente.
    // A partir deste momento todas as chamadas à API funcionam.

    return redirect('/dashboard')->with('success', 'Moloni ligado com sucesso!');
});
```

O método `handleAuthorizationCallback` devolve o array de tokens recebido do Moloni:

```php
[
    'access_token'  => 'xxxxxxxxxxxxxxxx',
    'expires_in'    => 3600,
    'token_type'    => 'bearer',
    'refresh_token' => 'xxxxxxxxxxxxxxxx',
]
```

### Como os tokens são geridos

O package trata automaticamente de todo o ciclo de vida dos tokens:

| Token | Validade real | Cached durante |
|---|---|---|
| Access token | 1 hora | 55 minutos (margem de segurança) |
| Refresh token | 14 dias | 13 dias |

**Fluxo automático:**

1. Na primeira chamada à API, o token é obtido e guardado em cache.
2. Nas chamadas seguintes, o token é lido diretamente da cache — sem pedidos HTTP extra.
3. Quando o access token expira, o package usa o refresh token para obter um novo automaticamente.
4. Quando o refresh token também expira (ou não existe), o package volta a autenticar com as credenciais configuradas.
5. Se um pedido falhar com erro de autenticação, o package limpa a cache e retenta uma vez com um token novo.

**Forçar a limpeza dos tokens** (útil em testes ou quando as credenciais mudam):

```php
// Via facade
app(\Tomahock\Moloni\Http\MoloniAuthenticator::class)->forgetTokens();
```

---

## Utilização básica

### Facade vs injeção de dependência

Pode usar o package de duas formas equivalentes:

**Facade (recomendado para a maioria dos casos):**

```php
use Tomahock\Moloni\Facades\Moloni;

$clientes = Moloni::company(1)->customers()->getAll();
```

**Injeção de dependência:**

```php
use Tomahock\Moloni\Moloni;

class ClienteController extends Controller
{
    public function __construct(private Moloni $moloni) {}

    public function index()
    {
        return $this->moloni->company(1)->customers()->getAll();
    }
}
```

Ambas as abordagens usam a mesma instância singleton registada no container.

### Definir a empresa

**Obrigatório** antes de qualquer chamada a recursos da empresa. O ID da empresa pode ser encontrado na área de configurações do Moloni, ou listando as empresas acessíveis:

```php
// Listar as empresas disponíveis para a conta autenticada
$empresas = Moloni::companies()->getAll();
// Resultado: [['company_id' => 123456, 'name' => 'Empresa Lda', ...], ...]

// A partir daí, usar o company_id em todas as chamadas
Moloni::company(123456)->customers()->getAll();
```

O método `company()` devolve a própria instância (`$this`), pelo que é possível encadear:

```php
$moloni = Moloni::company(123456);
$clientes  = $moloni->customers()->getAll();
$produtos  = $moloni->products()->getAll();
$impostos  = $moloni->taxes()->getAll();
```

---

## Entidades

### Clientes

```php
$clientes = Moloni::company(1)->customers();
```

#### Listar todos os clientes

```php
$todos = $clientes->getAll();

// Com filtros opcionais
$ativos = $clientes->getAll(['active' => 1]);
```

#### Contar clientes

```php
$total = $clientes->countAll();
// Resultado: ['count' => 42]
```

#### Obter um cliente pelo ID

```php
$cliente = $clientes->getOne(['customer_id' => 5]);
```

#### Pesquisar por nome, NIF ou e-mail

```php
// Pesquisa geral (nome, referência interna, etc.)
$resultado = $clientes->search('Empresa Lda');

// Por NIF
$resultado = $clientes->getByVat('508025338');

// Por e-mail
$resultado = $clientes->getByEmail('geral@empresa.pt');
```

#### Obter o próximo número de cliente disponível

```php
$numero = $clientes->getNextNumber();
// Resultado: ['next_number' => '00043']
```

#### Criar um cliente

```php
$novo = $clientes->insert([
    // Campos obrigatórios
    'vat'               => '999999990',      // NIF (use '999999990' para consumidor final)
    'name'              => 'Empresa Exemplo Lda',
    'language_id'       => 1,                // 1 = Português
    'country_id'        => 1,                // 1 = Portugal
    'maturity_date_id'  => 1,                // ID do prazo de pagamento
    'payment_method_id' => 1,                // ID do método de pagamento
    'salesman_id'       => 0,                // 0 = sem vendedor
    'payment_day'       => 0,
    'discount'          => 0,
    'credit_limit'      => 0,
    'send_options'      => 3,                // 1=correio, 2=email, 3=ambos

    // Campos opcionais mas recomendados
    'email'             => 'geral@empresa.pt',
    'address'           => 'Rua Exemplo, nº 1',
    'zip_code'          => '1000-001',
    'city'              => 'Lisboa',
    'phone'             => '210000000',
    'fax'               => '',
    'website'           => 'https://empresa.pt',
    'notes'             => 'Cliente VIP',
    'number'            => '',               // deixar vazio para numeração automática
]);

// Resultado: ['customer_id' => 99, 'valid' => 1]
```

#### Atualizar um cliente

```php
$resultado = $clientes->update([
    'customer_id' => 99,
    'name'        => 'Empresa Exemplo S.A.',
    'email'       => 'novo@empresa.pt',
    // Incluir apenas os campos a alterar,
    // mas sempre com o customer_id
]);
```

#### Eliminar um cliente

```php
$resultado = $clientes->delete(['customer_id' => 99]);
// Resultado: ['valid' => 1]
```

---

### Fornecedores

A interface é idêntica à de clientes.

```php
$fornecedores = Moloni::company(1)->suppliers();

$todos       = $fornecedores->getAll();
$umFornecedor = $fornecedores->getOne(['supplier_id' => 3]);
$porNif      = $fornecedores->getByVat('508025338');
$pesquisa    = $fornecedores->search('Distribuidora');

$novo = $fornecedores->insert([
    'vat'               => '508025338',
    'name'              => 'Distribuidora XYZ Lda',
    'language_id'       => 1,
    'country_id'        => 1,
    'maturity_date_id'  => 1,
    'payment_method_id' => 1,
    'salesman_id'       => 0,
    'payment_day'       => 0,
    'discount'          => 0,
    'credit_limit'      => 0,
    'send_options'      => 2,
    'email'             => 'compras@distribuidora.pt',
    'address'           => 'Zona Industrial, Lote 5',
    'zip_code'          => '2000-001',
    'city'              => 'Santarém',
]);

$fornecedores->update(['supplier_id' => 3, 'phone' => '243000000']);
$fornecedores->delete(['supplier_id' => 3]);
```

---

## Produtos

### Produtos

```php
$produtos = Moloni::company(1)->products();
```

#### Listar e pesquisar

```php
$todos         = $produtos->getAll();
$umProduto     = $produtos->getOne(['product_id' => 10]);
$porReferencia = $produtos->getByReference('REF-001');
$porCategoria  = $produtos->getByCategory(5);
$pesquisa      = $produtos->search('camisola');
$total         = $produtos->countAll();
```

#### Criar um produto

```php
$novo = $produtos->insert([
    // Obrigatórios
    'category_id'         => 1,       // ID da categoria
    'type'                => 1,       // 1=produto, 2=serviço
    'name'                => 'Camisola Azul M',
    'reference'           => 'CAM-AZ-M',
    'price'               => 29.99,
    'unit_id'             => 1,       // ID da unidade de medida
    'has_stock'           => 1,       // 0=sem gestão de stock, 1=com gestão
    'stock'               => 50,
    'at_product_category' => 'M',     // Categoria AT: M=mercadoria, P=produto, S=serviço

    // Impostos (array de impostos aplicados)
    'taxes' => [
        [
            'tax_id'     => 1,    // ID do imposto (IVA 23%)
            'value'      => 23,   // percentagem
            'order'      => 0,
            'cumulative' => 0,
        ],
    ],

    // Campos opcionais
    'summary'             => 'Camisola de algodão azul, tamanho M',
    'notes'               => '',
    'barcode'             => '5601234567890',
    'price2'              => 0,
    'price2_date'         => '',
    'price3'              => 0,
    'price3_date'         => '',
    'price4'              => 0,
    'price4_date'         => '',
    'price5'              => 0,
    'price5_date'         => '',
    'price6'              => 0,
    'price6_date'         => '',
    'exemption_reason'    => '',       // código de isenção, se IVA = 0%
    'warehouse_id'        => 1,        // armazém principal
]);
```

#### Atualizar e eliminar

```php
$produtos->update(['product_id' => 10, 'price' => 24.99, 'stock' => 35]);
$produtos->delete(['product_id' => 10]);
```

#### Movimentar stock

```php
// Entrada de stock (adicionar)
$produtos->updateStock(
    productId:   10,
    qty:         20.0,
    movement:    'add',        // 'add' = entrada
    warehouseId: 1             // opcional; omitir para usar o armazém padrão
);

// Saída de stock (subtrair)
$produtos->updateStock(
    productId: 10,
    qty:       5.0,
    movement:  'sub'           // 'sub' = saída
);
```

---

### Categorias de produtos

```php
$categorias = Moloni::company(1)->productCategories();

$todas     = $categorias->getAll();
$umaCategoria = $categorias->getOne(['category_id' => 2]);

$nova = $categorias->insert([
    'name'      => 'Vestuário',
    'parent_id' => 0,           // 0 = categoria raiz
]);

$categorias->update(['category_id' => 2, 'name' => 'Vestuário e Calçado']);
$categorias->delete(['category_id' => 2]);
```

---

## Documentos

### Métodos comuns a todos os documentos

Todos os tipos de documento partilham os seguintes métodos:

| Método | Descrição |
|---|---|
| `getAll(array $params = [])` | Lista todos os documentos (com filtros opcionais) |
| `getOne(array $params)` | Obtém um documento pelo `document_id` |
| `insert(array $data)` | Cria um novo documento |
| `update(array $data)` | Atualiza um documento em rascunho |
| `delete(array $params)` | Elimina um documento em rascunho |
| `countAll(array $params = [])` | Conta documentos |
| `getNextNumber(array $params = [])` | Devolve o próximo número disponível para a série |
| `getByDate(string $inicio, string $fim, array $params = [])` | Filtra por intervalo de datas |
| `getByCustomer(int $customerId, array $params = [])` | Filtra por cliente |
| `getPdfLink(int $documentId)` | Obtém o link para download do PDF |
| `sendEmail(int $documentId, array $emailData)` | Envia o documento por e-mail |

**Filtros disponíveis no `getAll`:**

```php
$faturas->getAll([
    'status'      => 1,            // 0=rascunho, 1=fechado
    'customer_id' => 5,
    'date'        => '2024-01-01', // data de emissão
    'number'      => 'FT 2024/1',
]);
```

**Filtrar por intervalo de datas:**

```php
$faturas = Moloni::company(1)->invoices();

// Documentos de janeiro de 2024
$resultado = $faturas->getByDate('2024-01-01', '2024-01-31');

// Com filtros adicionais
$resultado = $faturas->getByDate('2024-01-01', '2024-01-31', ['customer_id' => 5]);
```

**Obter o link do PDF:**

```php
$resultado = $faturas->getPdfLink(123);
// Resultado: ['url' => 'https://...', 'valid' => 1]

$pdfUrl = $resultado['url'];
```

**Enviar por e-mail:**

```php
$resultado = $faturas->sendEmail(123, [
    'email'   => 'cliente@empresa.pt',
    'subject' => 'A sua fatura FT 2024/1',
    'message' => 'Segue em anexo a sua fatura. Obrigado pela preferência.',
]);
```

---

### Faturas

```php
$faturas = Moloni::company(1)->invoices();
```

#### Criar uma fatura

```php
$nova = $faturas->insert([
    // Cabeçalho — obrigatórios
    'date'                => '2024-01-15',   // data de emissão (YYYY-MM-DD)
    'expiration_date'     => '2024-02-14',   // data de vencimento
    'document_set_id'     => 1,              // ID da série de documentos
    'customer_id'         => 5,              // ID do cliente
    'status'              => 1,              // 0=rascunho, 1=fechado/enviado para AT

    // Cabeçalho — opcionais
    'financial_discount'  => 0,              // desconto financeiro (%)
    'special_discount'    => 0,              // desconto especial (%)
    'salesman_id'         => 0,
    'salesman_commission' => 0,
    'notes'               => '',
    'our_reference'       => '',
    'your_reference'      => '',

    // Linhas de produto — obrigatório pelo menos uma
    'products' => [
        [
            'product_id'       => 10,         // ID do produto (omitir para linha manual)
            'name'             => 'Camisola Azul M',
            'summary'          => '',
            'qty'              => 2,
            'price'            => 29.99,
            'discount'         => 0,          // desconto por linha (%)
            'order'            => 0,          // posição na lista (0-based)
            'unit_id'          => 1,
            'exemption_reason' => '',         // obrigatório se IVA = 0%
            'taxes' => [
                [
                    'tax_id'     => 1,
                    'value'      => 23,
                    'order'      => 0,
                    'cumulative' => 0,
                ],
            ],
        ],
    ],

    // Pagamentos — pode ser vazio em rascunhos
    'payments' => [
        [
            'payment_method_id' => 1,
            'date'              => '2024-01-15',
            'value'             => 73.58,      // valor com IVA
            'notes'             => '',
        ],
    ],
]);

// Resultado: ['document_id' => 456, 'valid' => 1, 'number' => 'FT 2024/5']
$documentId = $nova['document_id'];
```

#### Fluxo completo: criar, obter PDF e enviar

```php
$faturas = Moloni::company(1)->invoices();

// 1. Criar
$nova = $faturas->insert([...]);
$id   = $nova['document_id'];

// 2. Obter link do PDF
$pdf  = $faturas->getPdfLink($id);
$url  = $pdf['url'];

// 3. Enviar ao cliente
$faturas->sendEmail($id, [
    'email'   => 'cliente@empresa.pt',
    'subject' => 'Fatura ' . $nova['number'],
    'message' => 'Segue em anexo a sua fatura.',
]);
```

---

### Faturas simplificadas

Comportamento idêntico às faturas. Indicadas para consumidores finais (NIF 999999990).

```php
$fs = Moloni::company(1)->simplifiedInvoices();

$fs->getAll();
$fs->insert([/* mesmos campos das faturas */]);
$fs->getPdfLink($documentId);
$fs->sendEmail($documentId, [...]);
```

---

### Faturas-recibo

Documento que combina fatura e recibo numa só operação.

```php
$fr = Moloni::company(1)->invoiceReceipts();

$fr->getAll();
$fr->insert([
    'date'            => '2024-01-15',
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,
    'products'        => [/* ... */],
    'payments'        => [/* ... */],  // obrigatório nos faturas-recibo
]);
```

---

### Recibos

```php
$recibos = Moloni::company(1)->receipts();

$recibos->getAll();
$recibos->getOne(['receipt_id' => 10]);
$recibos->insert([
    'date'            => '2024-01-15',
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,
    'payments' => [
        [
            'payment_method_id' => 1,
            'date'              => '2024-01-15',
            'value'             => 100.00,
            'notes'             => 'Referência: FT 2024/3',
        ],
    ],
]);
```

---

### Notas de crédito

Utilizadas para devoluções ou correções a faturas já emitidas.

```php
$nc = Moloni::company(1)->creditNotes();

$nc->getAll();
$nc->insert([
    'date'            => '2024-01-20',
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,
    'products' => [
        [
            'product_id' => 10,
            'name'       => 'Camisola Azul M',
            'qty'        => 1,
            'price'      => 29.99,
            'discount'   => 0,
            'order'      => 0,
            'taxes'      => [['tax_id' => 1, 'value' => 23, 'order' => 0, 'cumulative' => 0]],
            'exemption_reason' => '',
        ],
    ],
]);
$nc->getPdfLink($documentId);
```

---

### Notas de débito

```php
$nd = Moloni::company(1)->debitNotes();

$nd->getAll();
$nd->insert([/* estrutura idêntica às notas de crédito */]);
```

---

### Orçamentos

```php
$orcamentos = Moloni::company(1)->estimates();

$orcamentos->getAll();
$orcamentos->insert([
    'date'            => '2024-01-15',
    'expiration_date' => '2024-02-15',  // validade do orçamento
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,
    'products'        => [/* ... */],
]);
$orcamentos->getNextNumber(['document_set_id' => 1]);
```

---

### Encomendas a clientes

```php
$encomendas = Moloni::company(1)->purchaseOrders();

$encomendas->getAll();
$encomendas->insert([
    'date'            => '2024-01-15',
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,
    'products'        => [/* ... */],
]);
```

---

### Guias de remessa

```php
$guias = Moloni::company(1)->deliveryNotes();

$guias->getAll();
$guias->insert([
    'date'            => '2024-01-15',
    'document_set_id' => 1,
    'customer_id'     => 5,
    'status'          => 1,

    // Dados de transporte (obrigatórios nas guias)
    'vehicle_name'        => 'XX-00-XX',
    'delivery_datetime'   => '2024-01-15 09:00',
    'delivery_method_id'  => 1,

    // Morada de carga
    'ship_from_address'   => 'Rua da Empresa, 1',
    'ship_from_city'      => 'Lisboa',
    'ship_from_zip_code'  => '1000-001',
    'ship_from_country'   => 'Portugal',
    'ship_from_date'      => '2024-01-15',
    'ship_from_time'      => '09:00',

    // Morada de descarga
    'ship_to_address'     => 'Rua do Cliente, 5',
    'ship_to_city'        => 'Porto',
    'ship_to_zip_code'    => '4000-001',
    'ship_to_country'     => 'Portugal',

    'products' => [/* ... */],
]);
```

---

### Guias de transporte (Waybills)

```php
$waybills = Moloni::company(1)->waybills();

$waybills->getAll();
$waybills->insert([/* estrutura idêntica às guias de remessa */]);
$waybills->getPdfLink($documentId);
```

---

### Faturas de fornecedor

```php
$faturasFornecedor = Moloni::company(1)->supplierInvoices();

$faturasFornecedor->getAll();
$faturasFornecedor->getOne(['document_id' => 20]);

$nova = $faturasFornecedor->insert([
    'date'            => '2024-01-10',
    'document_set_id' => 1,
    'supplier_id'     => 3,    // ID do fornecedor (não customer_id)
    'status'          => 1,
    'our_reference'   => 'FT/2024/001',
    'products'        => [/* ... */],
    'payments'        => [/* ... */],
]);
```

---

## Configurações da empresa

### Impostos

```php
$impostos = Moloni::company(1)->taxes();

$todos = $impostos->getAll();
// Resultado típico:
// [
//   ['tax_id' => 1, 'name' => 'IVA 23%', 'value' => 23, 'type' => 1, ...],
//   ['tax_id' => 2, 'name' => 'IVA 13%', 'value' => 13, 'type' => 1, ...],
//   ['tax_id' => 3, 'name' => 'IVA 6%',  'value' => 6,  'type' => 1, ...],
// ]

$umImposto = $impostos->getOne(['tax_id' => 1]);

$novo = $impostos->insert([
    'name'         => 'IVA 23%',
    'value'        => 23,
    'type'         => 1,         // 1=percentagem
    'fiscal_zone'  => 'PT',
    'active_by_default' => 1,
]);

$impostos->update(['tax_id' => 1, 'active_by_default' => 0]);
$impostos->delete(['tax_id' => 5]);
```

---

### Métodos de pagamento

```php
$metodos = Moloni::company(1)->paymentMethods();

$todos = $metodos->getAll();
// [
//   ['payment_method_id' => 1, 'name' => 'Numerário'],
//   ['payment_method_id' => 2, 'name' => 'Transferência Bancária'],
//   ['payment_method_id' => 3, 'name' => 'Multibanco'],
// ]

$novo = $metodos->insert(['name' => 'PayPal']);
$metodos->update(['payment_method_id' => 4, 'name' => 'PayPal / Stripe']);
$metodos->delete(['payment_method_id' => 4]);
```

---

### Armazéns

```php
$armazens = Moloni::company(1)->warehouses();

$todos = $armazens->getAll();
// [['warehouse_id' => 1, 'name' => 'Armazém Principal', 'is_default' => 1, ...]]

$novo = $armazens->insert([
    'name'       => 'Armazém Secundário',
    'address'    => 'Zona Industrial, Lote 3',
    'zip_code'   => '2000-001',
    'city'       => 'Santarém',
    'country_id' => 1,
]);

$armazens->update(['warehouse_id' => 2, 'name' => 'Armazém Norte']);
$armazens->delete(['warehouse_id' => 2]);
```

---

### Unidades de medida

```php
$unidades = Moloni::company(1)->measurementUnits();

$todas = $unidades->getAll();
// [
//   ['unit_id' => 1, 'name' => 'Unidade (un)'],
//   ['unit_id' => 2, 'name' => 'Quilograma (kg)'],
//   ['unit_id' => 3, 'name' => 'Metro (m)'],
// ]

$nova = $unidades->insert(['name' => 'Litro (L)', 'short_name' => 'L']);
$unidades->update(['unit_id' => 5, 'name' => 'Litro']);
$unidades->delete(['unit_id' => 5]);
```

---

### Séries de documentos

As séries determinam a numeração dos documentos (ex: "FT 2024/...").

```php
$series = Moloni::company(1)->documentSets();

$todas = $series->getAll();
// [['document_set_id' => 1, 'name' => 'Série 2024', 'active' => 1, ...]]

$nova = $series->insert([
    'name'                => 'Série 2025',
    'template_id'         => 1,     // ID do template de impressão
]);

$series->update(['document_set_id' => 2, 'name' => 'Série Exportação 2025']);
```

---

### Contas bancárias

```php
$contas = Moloni::company(1)->bankAccounts();

$todas = $contas->getAll();

$nova = $contas->insert([
    'name'    => 'Conta CGD',
    'iban'    => 'PT50003506510007341000358',
    'swift'   => 'CGDIPTPL',
    'initial_balance' => 0,
]);

$contas->update(['bank_account_id' => 1, 'name' => 'CGD — Conta Principal']);
$contas->delete(['bank_account_id' => 3]);
```

---

## Dados globais

Estes endpoints não requerem `company_id` — podem ser chamados diretamente sem `::company()`.

### Países

```php
$paises = Moloni::countries()->getAll();
// [
//   ['country_id' => 1,  'name' => 'Portugal', 'iso'  => 'PT'],
//   ['country_id' => 2,  'name' => 'Espanha',  'iso'  => 'ES'],
//   ...
// ]

// Com filtro
$portugal = Moloni::countries()->getAll(['search' => 'Portugal']);
```

### Moedas

```php
$moedas = Moloni::currencies()->getAll();
// [
//   ['currency_id' => 1, 'name' => 'Euro',   'iso' => 'EUR', 'symbol' => '€'],
//   ['currency_id' => 2, 'name' => 'Dólar',  'iso' => 'USD', 'symbol' => '$'],
//   ...
// ]
```

---

## Empresas

```php
// Listar todas as empresas às quais a conta autenticada tem acesso
// (útil para descobrir o company_id correto)
$empresas = Moloni::companies()->getAll();
// [
//   ['company_id' => 123456, 'name' => 'Empresa A Lda',   'vat' => '...'],
//   ['company_id' => 789012, 'name' => 'Empresa B Unip.',  'vat' => '...'],
// ]

// Obter os detalhes de uma empresa específica
$empresa = Moloni::company(123456)->companies()->getOne();
// Ou especificando o ID explicitamente:
$empresa = Moloni::companies()->getOne(['company_id' => 123456]);

// Atualizar dados da empresa
Moloni::company(123456)->companies()->update([
    'email'   => 'geral@empresa.pt',
    'phone'   => '210000000',
    'website' => 'https://empresa.pt',
]);
```

---

## Tratamento de erros

O package lança duas exceções específicas, ambas em `Tomahock\Moloni\Exceptions`:

| Exceção | Quando é lançada |
|---|---|
| `MoloniAuthException` | Falha de autenticação OAuth (credenciais inválidas, token expirado irrecuperável) |
| `MoloniException` | A API devolveu um erro lógico (`valid = 0`) ou a resposta não é JSON válido |

`MoloniAuthException` estende `MoloniException`, pelo que pode capturar ambas com um único `catch (MoloniException $e)`.

### Estrutura básica

```php
use Tomahock\Moloni\Exceptions\MoloniAuthException;
use Tomahock\Moloni\Exceptions\MoloniException;
use Tomahock\Moloni\Facades\Moloni;

try {
    $resultado = Moloni::company(1)->customers()->insert([...]);
} catch (MoloniAuthException $e) {
    // Problema de autenticação — verificar credenciais ou tokens
    Log::error('Moloni auth error: ' . $e->getMessage());
} catch (MoloniException $e) {
    // Erro lógico devolvido pela API
    Log::error('Moloni API error: ' . $e->getMessage());

    // Detalhes do erro (array com os erros reportados pelo Moloni)
    $erros = $e->getErrors();
    // Exemplo: [['message' => 'O NIF introduzido já existe'], ...]
}
```

### Inspecionar os erros da API

Quando o Moloni devolve `valid = 0`, a exceção `MoloniException` contém os detalhes no método `getErrors()`:

```php
try {
    Moloni::company(1)->customers()->insert(['vat' => '999999990', ...]);
} catch (MoloniException $e) {
    foreach ($e->getErrors() as $erro) {
        echo $erro['message']; // ex: "O campo email é inválido"
    }
}
```

### Exemplo com validação no controlador

```php
public function store(Request $request)
{
    try {
        $cliente = Moloni::company(config('services.moloni.company_id'))
            ->customers()
            ->insert($request->validated());

        return response()->json(['id' => $cliente['customer_id']], 201);

    } catch (MoloniAuthException $e) {
        return response()->json(['error' => 'Erro de autenticação Moloni'], 503);

    } catch (MoloniException $e) {
        return response()->json([
            'error'  => 'Erro ao criar cliente no Moloni',
            'detail' => $e->getMessage(),
            'errors' => $e->getErrors(),
        ], 422);
    }
}
```

---

## Testes

### Correr os testes do package

```bash
composer test
```

### Testar a integração na sua aplicação

Nos testes da sua aplicação, pode fazer mock do package usando o sistema de mocking do Laravel:

```php
use Tomahock\Moloni\Facades\Moloni;
use Tomahock\Moloni\Resources\Customers;

// No teste
Moloni::shouldReceive('company')->with(1)->andReturnSelf();
Moloni::shouldReceive('customers')->andReturn(
    tap(Mockery::mock(Customers::class), function ($mock) {
        $mock->shouldReceive('getAll')->andReturn([
            ['customer_id' => 1, 'name' => 'Cliente Teste'],
        ]);
    })
);
```

Ou, alternando para uma implementação fake no `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    if (app()->environment('testing')) {
        $this->app->bind(\Tomahock\Moloni\Moloni::class, \App\Fakes\FakeMoloni::class);
    }
}
```

---

## Licença

MIT
