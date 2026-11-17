# Fluxo de Implementação Sequencial

Sempre que a tarefa for criar um novo CRUD, endpoint ou funcionalidade na **Habits REST API**, siga rigorosamente a ordem abaixo (Fase 1 à Fase 5) para respeitar a Clean Architecture.

### Fase 1: Domínio (Core do Negócio)
1. **Entidades e Value Objects** (`src/Domain/Entity/` e `src/Domain/ValueObject/`):
   - Sempre adicione `declare(strict_types=1);` no topo de todos os arquivos PHP.
   - **Idioma**: Escreva código, nomes de classes/métodos e PHPDoc em **Inglês**, mas mantenha mensagens de retorno da API, mensagens de validação e descrições de teste em **Português**.
   - **Padrões de Código (PSR-12 & PHPDoc)**:
     - Use type hints em todos os parâmetros e retornos, e constantes de classe (`private const`) em vez de *magic strings*.
     - Mantenha baixa complexidade ciclomática e métodos pequenos/focados (Single Responsibility).
     - Documente classes e métodos com PHPDoc completo (`@param`, `@return`, `@throws`, `@var`).
   - Nas **Entidades**, utilize promoção de propriedades no construtor (`private` ou `private readonly`), getters/setters explícitos com validação de invariantes de domínio (lançando `ValidationException` quando inválido) e implemente `JsonSerializable` (`toArray()` e `fromArray()`).
2. **Exceções** (`src/Domain/Exception/`): Crie ou reutilize exceções de negócio semânticas (`ValidationException`, `NotFoundException`, `ConflictException`, `AuthenticationException`, `AuthorizationException`, `HabitNotFoundException`, `HabitAlreadyExistsException`).
3. **Interfaces de Repositório** (`src/Domain/Repository/`): Defina as assinaturas das operações de repositório com tipagem estrita e PHPDoc (ex: `HabitRepositoryInterface.php`).
4. **Enums** (`src/Domain/Enum/`): Crie Backed Enums (`string` ou `int`) para evitar "magic strings" quando necessário.

### Fase 2: Aplicação (Casos de Uso)
5. **DTOs de Request/Response** (`src/Application/DTO/{Auth,Habit,User,Common,ErrorLog}/`):
   - **Request DTOs**: Use propriedades `public readonly`, atributos do `symfony/validator` (`#[Assert\NotBlank]`, `#[Assert\Length]`, etc.) e método estático `fromArray(array $data): self`.
   - **Response DTOs**: Use propriedades `public readonly`, método estático `fromEntity(...)` e implemente `JsonSerializable` (`toArray()`).
6. **Casos de Uso** (`src/Application/UseCase/`):
   - Implemente a classe `*UseCase` que orquestra a regra de negócio e interage com as interfaces de repositório da Fase 1.
   - Injete dependências via construtor (`private readonly`).
   - Quando houver múltiplas operações de escrita no banco, injete `PDO` e envolva em `beginTransaction()` / `commit()` / `rollBack()`.

### Fase 3: Infraestrutura (Persistência, Cache e Configurações)
7. **Repositórios Concretos** (`src/Infrastructure/Persistence/Repository/` ou `src/Infrastructure/Persistence/MySQL/`):
   - Implemente a interface definida na Fase 1 utilizando `PDO` com prepared statements.
8. **Decorators de Cache Redis** (`src/Infrastructure/Persistence/Decorator/`):
   - Se a entidade utiliza Decorator de cache (`CachingHabitRepository`, `CachingUserRepository`, `CachingDayRepository`, `CachingRoleRepository`), implemente os novos métodos da interface no Decorator e garanta a invalidação das chaves de cache nas operações de escrita.
9. **Schema SQL** (`database/schema.sql`):
   - Caso a feature exija novas tabelas, colunas ou índices, atualize `api/database/schema.sql`.

### Fase 4: Apresentação (HTTP) e Container
10. **Controllers** (`src/Presentation/Api/V1/Controller/`):
    - Extraia e valide o `user_id` da `Request` quando o endpoint for autenticado.
    - Instancie o `RequestDTO::fromArray(...)` a partir de `getParsedBody()` ou `getQueryParams()`.
    - Use o `ValidationService` para validar o DTO.
    - Execute o `UseCase` correspondente.
    - Trate exceções de domínio (`ValidationException`, `NotFoundException`, etc.) retornando `JsonResponseFactory::fail(...)` com o status HTTP apropriado, e `Throwable` genérico logando no `LoggerInterface` e retornando `JsonResponseFactory::error(..., 500)`.
11. **Rotas e Injeção de Dependência**:
    - Registre os endpoints e middlewares (`JwtAuthMiddleware`, `EmailVerificationCheckMiddleware`, `AuthorizationMiddleware`) em `config/routes.php`.
    - Registre o Repositório, Decorator, UseCase e Controller em `config/container.php`.

### Fase 5: Testes e Qualidade (PHPUnit + CS-Fixer)
12. Consulte as diretrizes e comandos em [testing-and-validation.md](testing-and-validation.md) para criar os testes (`Unit`, `Integration`, `Functional`) e validar o estilo de código PSR-12.
