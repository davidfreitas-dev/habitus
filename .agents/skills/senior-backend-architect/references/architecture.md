# Arquitetura do Projeto (Clean Architecture / DDD)

A **Habits REST API** (PHP 8.4 + Slim Framework 4.x) emprega uma **Arquitetura em Camadas** robusta orientada a **Clean Architecture** e **Domain-Driven Design (DDD)**, com Injeção de Dependências via **PHP-DI**.

## O Fluxo de uma Requisição

1. **Rotas (`config/routes.php`) e Middlewares (`config/middleware.php`)**:
   - Mapeiam a URL HTTP para um `Controller` específico em `src/Presentation/Api/V1/Controller/`.
   - Aplicam middlewares de infraestrutura (`CorsMiddleware`, `RateLimitMiddleware`, `JwtAuthMiddleware`, `EmailVerificationCheckMiddleware`, `AuthorizationMiddleware`, `ErrorMiddleware`).
2. **Controller (`src/Presentation/Api/V1/Controller/`)**:
   - Ponto de entrada HTTP.
   - Extrai atributos da requisição (ex: `user_id` injetado pelo `JwtAuthMiddleware`) e instancia o `RequestDTO` (`::fromArray(...)`) a partir do body ou query params.
   - Usa o `App\Application\Service\ValidationService` para validar o DTO com atributos do Symfony Validator.
   - Chama o `UseCase` correspondente passando o DTO e parâmetros de contexto (como `$userId`).
   - Usa a `App\Infrastructure\Http\Response\JsonResponseFactory` para retornar a resposta padronizada (`status`, `message`, `data`).
3. **UseCase (`src/Application/UseCase/`)**:
   - Orquestrador da regra de negócio.
   - Não conhece HTTP (Requests ou Responses do Slim).
   - Comunica-se com as interfaces de repositório (`src/Domain/Repository/*RepositoryInterface.php`) e serviços de aplicação/infraestrutura.
   - Instancia/manipula `Entities` e `ValueObjects`.
   - Gerencia transações de banco de dados (`$this->pdo->beginTransaction()`, `commit()`, `rollBack()`) quando a operação envolve múltiplas escritas.
   - Retorna um `ResponseDTO` (ou tipo primitivo/array de DTOs conforme contrato do caso de uso).
4. **Decorators de Cache e Repositórios (`src/Infrastructure/Persistence/`)**:
   - **Interfaces no Domínio**: Definidas em `src/Domain/Repository/`.
   - **Decorators de Cache Redis (`src/Infrastructure/Persistence/Decorator/`)**: Implementam a mesma interface do domínio (ex: `CachingHabitRepository`, `CachingUserRepository`, `CachingDayRepository`, `CachingRoleRepository`), interceptando leituras para buscar no `RedisCache` e invalidando chaves em mutações.
   - **Repositórios Concretos MySQL (PDO)**:
     - `src/Infrastructure/Persistence/Repository/`: `HabitRepository`, `DayRepository`, `DayHabitRepository`, `HabitStatsRepository`.
     - `src/Infrastructure/Persistence/MySQL/`: `UserRepository`, `PersonRepository`, `RoleRepository`, `PasswordResetRepository`, `UserVerificationRepository`, `DatabaseErrorLogRepository`.

## Regras de Decisão Arquitetural

### UseCase vs Controller
- **Lógica de Negócio e Transações** → Sempre no UseCase (ou validações de invariantes na própria Entity).
- **Tratamento HTTP (Headers, Cookies, Status Codes, Query/Body Parsers)** → Sempre no Controller.

### DTO vs Array
- **Sempre use DTOs** (`src/Application/DTO/{Auth,Habit,User,Common,ErrorLog}/`) para entrada e saída estruturada.
- Request DTOs utilizam propriedades `public readonly` e atributos do `symfony/validator` (`#[Assert\...]`), além de um método estático `fromArray(array $data): self`.
- Response DTOs utilizam `fromEntity(...)` e implementam `JsonSerializable` (`toArray()`).

### Repositories, Decorators e DI Container
- Toda persistência deve ter uma Interface definida em `src/Domain/Repository/`.
- O mapeamento entre `*RepositoryInterface`, o Decorator `Caching*Repository` (quando aplicável) e a classe concreta MySQL/PDO fica centralizado em `config/container.php`.
- Se você adicionar um método em `HabitRepositoryInterface`, `UserRepositoryInterface`, `DayRepositoryInterface` ou `RoleRepositoryInterface`, **você deve implementá-lo tanto no repositório concreto quanto no respectivo `Caching*Repository`**.

### Formato de Resposta da API
Preserve o formato existente usando o `JsonResponseFactory` (`success`, `fail`, `error`), baseado nos Enums `JsonResponseKey` e `JsonResponseStatus`:

```json
{
  "status": "success",
  "message": "Operação realizada com sucesso.",
  "data": { ... }
}
```

- `fail(...)` (`status: "fail"`): Erros de cliente/negócio (400, 401, 403, 404, 409, 422).
- `error(...)` (`status: "error"`): Erros internos de servidor (500), sempre logados via `LoggerInterface` (que grava tanto em arquivo quanto na tabela `error_logs` via `DatabaseErrorLogHandler`). **Nunca exponha credenciais, traces ou SQL errors para o client.**
