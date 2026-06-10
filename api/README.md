# API REST com Slim Framework e Arquitetura Limpa

API REST moderna construída com Slim Framework 4, PHP 8.4, e fortemente inspirada em princípios de Arquitetura Limpa (Clean Architecture) e Domain-Driven Design (DDD).

Esta API serve como uma base robusta para novos projetos, incluindo autenticação completa com JWT (Access e Refresh tokens), cache com Redis, e uma estrutura de código organizada para escalabilidade e manutenção.

## 📄 Índice

- [✨ Features](#-features)
- [🚀 Tecnologias](#-tecnologias)
- [🔧 Instalação e Execução (Docker)](#-instalação-e-execução-docker)
  - [1. Pré-requisitos](#1-pré-requisitos)
  - [2. Clone o repositório](#2-clone-o-repositório)
  - [3. Configure o ambiente](#3-configure-o-ambiente)
  - [4. Gere as Chaves de Criptografia](#4-gere-as-chaves-de-criptografia)
  - [5. Inicie os containers](#5-inicie-os-containers)
  - [6. Instale as dependências do Composer](#6-instale-as-dependências-do-composer)
  - [7. Popule o Banco de Dados (Seeders)](#7-popule-o-banco-de-dados-seeders)
  - [8. Acesse a aplicação](#8-acesse-a-aplicação)
  - [Acessando o PHPMyAdmin](#acessando-o-phpmyadmin)
- [🧪 Testes](#-testes)
  - [Banco de Dados de Testes](#banco-de-dados-de-testes)
  - [Teste de E-mails com MailHog](#teste-de-e-mails-com-mailhog)
  - [Como Executar os Testes](#como-executar-os-testes)
    - [1. Executar todos os testes](#1-executar-todos-os-testes)
    - [2. Executar suítes específicas de testes](#2-executar-suítes-específicas-de-testes)
    - [3. Executar um arquivo de teste específico](#3-executar-um-arquivo-de-teste-específico)
    - [4. Gerar relatório de cobertura de código](#4-gerar-relatório-de-cobertura-de-código)
    - [5. Limpar o banco de dados de testes](#5-limpar-o-banco-de-dados-de-testes)
- [🏗️ Arquitetura](#-arquitetura)
  - [Estrutura de Pastas](#estrutura-de-pastas)
  - [Destaques Arquiteturais](#destaques-arquiteturais)
- [📡 Documentação da API](#-documentação-da-api)
- [🛠️ Qualidade de Código](#-qualidade-de-código)
  - [PHP-CS-Fixer (Formatação de Código)](#php-cs-fixer-formatação-de-código)
  - [Rector (Refatoração Automática)](#rector-refatoração-automática)
- [🛠️ Troubleshooting e Comandos Úteis](#-troubleshooting-e-comandos-úteis)
  - [Solução de Problemas](#solução-de-problemas)
  - [Comandos Docker](#comandos-docker)

---

## ✨ Features

- **Autenticação Completa**: Fluxo de Registro, Login, Logout, Refresh Token e Reset de Senha.
- **Verificação de E-mail**: Permite ao usuário logar imediatamente após o registro com acesso limitado até a verificação do e-mail. Após a verificação, um novo token de acesso é emitido automaticamente.
- **Acesso Imediato com Funcionalidades Limitadas**: Usuários registrados podem acessar o sistema imediatamente, mas funcionalidades críticas (como atualização de perfil sensível e ações administrativas) são bloqueadas até a verificação do e-mail.
- **Controle de Acesso por Função (RBAC)**: Sistema de permissões baseado em funções (`user`, `admin`).
- **Cache Inteligente com Redis**: Usa o padrão **Decorator** para adicionar uma camada de cache ao repositório de usuários, melhorando a performance em leituras.
- **Segurança**:
  - Tokens JWT **RS256 (assimétrico)** com tempo de vida curto para acesso.
  - Senhas com hash usando Argon2ID.
  - Uso de **DTOs (Data Transfer Objects)** para garantir que dados sensíveis (como senhas) nunca sejam expostos nos endpoints.
  - Rate Limiting para proteção contra ataques de força bruta.
  - CORS configurável.
- **Arquitetura Robusta**:
  - Separação clara de responsabilidades em camadas (Presentation, Application, Domain, Infrastructure).
  - Uso de **Enums** para evitar "magic strings", tornando o código mais seguro e legível.
  - Injeção de Dependência com PHP-DI.
- **Ambiente de Desenvolvimento com Docker**: Ambiente 100% containerizado para consistência e facilidade de configuração.
- **Banco de Dados Isolado para Testes**: Ambiente de testes com banco de dados dedicado, garantindo que os testes nunca afetem os dados de desenvolvimento.

## 🚀 Tecnologias

- **PHP 8.4+**
- **Slim Framework 4**
- **PHP-DI** (Injeção de Dependência)
- **MySQL 8.0** (Banco de Dados)
- **Redis 7.0** (Cache, Rate Limiting, Armazenamento de Refresh Tokens)
- **JWT (Firebase)** (Autenticação)
- **Monolog** (Logging)
- **Docker & Docker Compose**

---

## 🔧 Instalação e Execução (Docker)

O uso de Docker é o **único método recomendado** para garantir que o ambiente de desenvolvimento seja idêntico ao de produção.

#### 1. Pré-requisitos
- Docker
- Docker Compose

#### 2. Clone o repositório
```bash
git clone <repository-url>
cd <project-folder>
```

#### 3. Configure o ambiente
Copie o arquivo de exemplo `.env.example` da pasta `api` e o personalize conforme necessário.
```bash
cp api/.env.example api/.env
```

O arquivo `api/.env` deve conter as configurações para **dois bancos de dados**:
- **Banco de Desenvolvimento** (`DB_*`): Para uso durante o desenvolvimento
- **Banco de Testes** (`DB_TEST_*`): Usado exclusivamente pelos testes automatizados

> **Importante:** Certifique-se de preencher todas as variáveis de ambiente no arquivo `api/.env`, especialmente as senhas de banco de dados e Redis.

#### 4. Gere as Chaves de Criptografia
Para a autenticação JWT com RS256, você precisa de um par de chaves pública/privada.

```bash
# Crie o diretório se não existir (a partir da raiz do projeto)
mkdir -p api/config/keys

# Gere a chave privada
openssl genrsa -out api/config/keys/private_key.pem 2048

# Extraia a chave pública
openssl rsa -in api/config/keys/private_key.pem -pubout -out api/config/keys/public_key.pem
```

#### 5. Inicie os containers
Execute este comando a partir da raiz do projeto:
```bash
docker compose up -d --build
```

#### 6. Instale as dependências do Composer
```bash
docker compose exec api composer install
```

#### 7. Popule o Banco de Dados (Seeders)
As tabelas `days` e `habits` precisam ser preenchidas com dados iniciais para que os endpoints de resumo e listagem de hábitos funcionem corretamente, especialmente em um novo ambiente de desenvolvimento.

```bash
docker compose exec api composer seed
```

#### 8. Acesse a aplicação
- **API**: `http://api.localhost`
- **PHPMyAdmin**: `http://localhost:8080`

### Acessando o PHPMyAdmin

O PHPMyAdmin está configurado para permitir acesso a ambos os bancos de dados. Na tela de login:

**Para o banco de desenvolvimento:**
- Servidor: `database`
- Usuário: `user` (ou o valor de `DB_USER` do seu `.env`)
- Senha: `resu` (ou o valor de `DB_PASS` do seu `.env`)

**Para o banco de testes:**
- Servidor: `database_test`
- Usuário: `test_user` (ou o valor de `DB_TEST_USER` do seu `.env`)
- Senha: `test_resu` (ou o valor de `DB_TEST_PASS` do seu `.env`)

---

## 🧪 Testes

Este projeto utiliza **PHPUnit** para garantir a qualidade e a estabilidade do código através de um conjunto de testes automatizados. Os testes estão organizados em três categorias principais:

- **Testes Unitários**: Verificam o funcionamento de classes individuais e isoladas, como `Entities`, `ValueObjects` e `UseCases` (com suas dependências mockadas).
- **Testes de Integração**: Garantem que diferentes componentes do sistema funcionam corretamente em conjunto (ex: `UseCase` com um repositório real).
- **Testes Funcionais (API)**: Testam os endpoints da API de ponta a ponta, simulando requisições HTTP e validando as respostas.

### Banco de Dados de Testes

O ambiente Docker inclui um banco de dados MySQL dedicado exclusivamente para testes (`database_test`). Isso garante que:

- ✅ **Seus dados de desenvolvimento nunca sejam afetados** pelos testes
- ✅ Os testes podem limpar e recriar dados livremente sem preocupações
- ✅ Testes de integração e funcionais rodam em um ambiente isolado e previsível

O PHPUnit está configurado para usar automaticamente o banco de testes através do arquivo `tools/phpunit.xml`, que sobrescreve as variáveis de ambiente `DB_*` para apontar para `database_test`.

### Teste de E-mails com MailHog

Para testar o envio de e-mails sem depender de serviços externos como o Mailtrap, integramos o **MailHog** no ambiente Docker. Durante a execução dos testes (incluindo testes de integração e funcionais), todos os e-mails são interceptados pelo MailHog.

- ✅ **Intercepta todos os e-mails** enviados pelos testes
- ✅ Proporciona um ambiente de teste isolado e rápido para e-mails
- ✅ Não consome créditos de serviços de e-mail reais

**Como acessar o MailHog:**

-   **Interface Web (visualizar e-mails):** `http://localhost:8025`
-   **Servidor SMTP (para configuração interna):** `mailhog:1025` (acessível de dentro dos containers Docker, por exemplo, do serviço `api`)

### Como Executar os Testes

Os testes devem ser executados dentro do contêiner de serviço da `api` para garantir o ambiente correto com todas as extensões PHP necessárias.

Execute os comandos a partir do diretório raiz do projeto.

#### 1. Executar todos os testes
Para rodar a suíte de testes completa (unitários, integração e funcionais):

```bash
docker compose exec api composer test
```

Para uma saída mais detalhada e legível (testdox):
```bash
docker compose exec api composer test:testdox
```

#### 2. Executar suítes específicas de testes

```bash
# Apenas testes unitários
docker compose exec api composer test:unit

# Apenas testes de integração
docker compose exec api composer test:integration

# Apenas testes funcionais (API)
docker compose exec api composer test:functional
```

#### 3. Executar um arquivo de teste específico
Se você precisa validar um arquivo de teste em particular:

```bash
docker compose exec api vendor/bin/phpunit tests/Unit/Domain/Entity/UserTest.php
```

#### 4. Gerar relatório de cobertura de código
Para gerar um relatório HTML de cobertura de código:

```bash
docker compose exec api composer test:coverage
```

O relatório será gerado em `tools/coverage/index.html`.

#### 5. Limpar o banco de dados de testes
Se precisar resetar completamente o banco de dados de testes:

```bash
docker compose down database_test -v
docker compose up -d database_test
```

> **Nota:** O banco de testes é automaticamente limpo entre cada teste pela classe `DatabaseTestCase`, então raramente você precisará fazer isso manualmente.

---

## 🏗️ Arquitetura

O projeto segue uma arquitetura em camadas inspirada na Arquitetura Limpa e DDD.

- **Domain Layer**: O coração da aplicação. Contém as entidades de negócio (`User`, `Person`), exceções de domínio e as interfaces dos repositórios (ports). Não depende de nenhum framework.
- **Application Layer**: Orquestra a lógica de negócio através de Casos de Uso (`UseCases`). Usa DTOs para transferência de dados.
- **Infrastructure Layer**: Contém as implementações concretas das interfaces do domínio (adapters). Aqui ficam o acesso ao banco de dados (MySQL), a implementação do cache (Redis), o serviço de email, etc.
- **Presentation Layer**: A camada mais externa, responsável por lidar com HTTP. Contém os Controllers, Middlewares e a definição das rotas.

### Estrutura de Pastas
```
project/
├── config/                # Configurações da aplicação
│   ├── bootstrap.php
│   ├── container.php      # Injeção de dependências
│   ├── routes.php
│   └── settings.php
├── database/
│   └── schema.sql         # Schema do banco de dados
├── docs/                  # Documentação do projeto
│   ├── API.md
│   └── postman_collection.json
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Application/       # Casos de uso e lógica de aplicação
│   │   ├── DTO/
│   │   ├── UseCase/
│   │   └── Validation/
│   ├── Domain/            # Lógica de negócio
│   │   ├── Entity/
│   │   ├── Repository/
│   │   └── Exception/
│   ├── Infrastructure/    # Implementações técnicas
│   │   ├── Http/
│   │   ├── Persistence/
│   │   ├── Security/
│   │   └── Mailer/
│   └── Presentation/      # Camada de API
│       └── Api/V1/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
├── tools/                 # Ferramentas de desenvolvimento
│   ├── .php-cs-fixer.dist.php
│   ├── phpunit.xml
│   └── rector.php
└── composer.json
```

### Destaques Arquiteturais
- **Padrão Decorator para Cache**: O `UserRepositoryInterface` é decorado pelo `CachingUserRepository`. Isso adiciona a lógica de cache de forma transparente, sem que a camada de Aplicação precise saber se o dado vem do cache ou do banco.
- **DTOs para Segurança e Contratos**: DTOs são usados para validar dados de entrada (`RegisterUserRequestDTO`) e para formatar dados de saída (`UserProfileResponseDTO`), garantindo que apenas informações seguras sejam expostas pela API.
- **Enums para Robustez**: Tipos de token (`JwtTokenType`) e chaves de resposta (`JsonResponseKey`) são definidos como Enums para evitar erros com "magic strings" e facilitar a manutenção.

---

## 📡 Documentação da API

**Ver documentação completa:** [docs/API.md](./docs/API.md)

**Importar no Postman:** `docs/postman_collection.json`

---

## 🛠️ Qualidade de Código

### PHP-CS-Fixer (Formatação de Código)

```bash
# Verificar problemas de formatação (sem fazer alterações)
docker compose exec api composer cs-check

# Corrigir automaticamente problemas de formatação
docker compose exec api composer cs-fix
```

### Rector (Refatoração Automática)

```bash
# Executar refatorações automáticas
docker compose exec api composer rector

# Simular refatorações sem aplicar (dry-run)
docker compose exec api composer rector:dry
```

---

## 🛠️ Troubleshooting e Comandos Úteis

### Solução de Problemas
- **Dados desatualizados ou incorretos sendo retornados pela API?** Isso é provavelmente um problema de cache obsoleto (stale cache). Para forçar a busca de dados novos do banco de dados, limpe o cache do Redis com o comando abaixo.
- **Testes falhando com erro de conexão?** Verifique se ambos os bancos de dados estão rodando: `docker compose ps`. Certifique-se de que o banco `database_test` está saudável antes de executar os testes.
- **PHPMyAdmin não mostra o banco de testes?** Use o modo de servidor arbitrário (já configurado) e digite manualmente o servidor `database_test` na tela de login.

### Comandos Docker

```bash
# Iniciar ambiente
docker compose up -d

# Parar ambiente (mantém volumes/dados)
docker compose down

# Parar ambiente e remover volumes (limpa tudo)
docker compose down -v 

# Acessar o terminal do container da API
docker compose exec api sh

# Limpar o cache do Redis (substitua pela sua senha)
docker compose exec redis redis-cli -a SUA_SENHA_DO_REDIS FLUSHALL

# Ver logs da API em tempo real
docker compose logs -f api

# Reconstruir a imagem da API sem cache
docker compose build --no-cache api

# Acessar o MySQL do banco de desenvolvimento
docker compose exec database mysql -uuser -presu slim_base_api_db

# Acessar o MySQL do banco de testes
docker compose exec database_test mysql -utest_user -ptest_resu slim_base_api_test_db
```
