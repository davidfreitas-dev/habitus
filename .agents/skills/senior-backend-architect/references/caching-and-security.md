# Cache Redis, Transações PDO e Segurança

A API do Habits utiliza **Redis** intensivamente (para cache de repositórios, revogação/blacklist de tokens JWT e Rate Limiting) e **MySQL (PDO)** para persistência relacional. Qualquer alteração deve preservar a consistência entre banco de dados e cache.

## 1. Decorators de Cache Redis (`Caching*Repository`)

Em `config/container.php`, diversas interfaces de domínio são resolvidas para Decorators em `src/Infrastructure/Persistence/Decorator/`:
- `HabitRepositoryInterface` → `CachingHabitRepository` (decorando `HabitRepository`)
- `UserRepositoryInterface` → `CachingUserRepository` (decorando `UserRepository`)
- `DayRepositoryInterface` → `CachingDayRepository` (decorando `DayRepository`)
- `RoleRepositoryInterface` → `CachingRoleRepository` (decorando `RoleRepository`)

### Regras de Ouro para o Cache:
1. **Serialização de Entidades**: Os Decorators usam `serialize()` e `unserialize()` do PHP para armazenar entidades e arrays no `RedisCache`. Certifique-se de que propriedades adicionadas às entidades sejam serializáveis.
2. **Invalidação em Escritas**:
   - Toda operação de escrita (`create`, `update`, `delete`) no Decorator deve invalidar tanto a chave individual quanto as chaves de listagem/resumo associadas ao `userId` (usando `delete()` ou `deleteByPattern()`).
   - Quando uma mutação ocorre fora do `HabitRepository` mas afeta o estado dos hábitos (como o `ToggleHabitUseCase`, que grava via `DayHabitRepository`), o UseCase deve acionar a invalidação do cache do usuário (`invalidateUserHabitsCache($userId)`).
3. **Chaves Escopadas por Usuário**: Nunca crie chaves de cache de hábitos, resumos ou dias possíveis sem incluir o `$userId` na chave (ex: `habit:summary:{userId}:{date}`), evitando vazamento de dados entre usuários.

---

## 2. Transações de Banco de Dados (PDO)

Operações que modificam mais de uma tabela ou dependem de leitura + escrita atômica devem usar transações explícitas do `PDO` no `UseCase`:
- **Criação e Atualização de Hábitos (`CreateHabitUseCase`, `UpdateHabitUseCase`)**: Gravam na tabela `habits` e recriam os vínculos na tabela `habit_week_days` dentro de `$this->pdo->beginTransaction()` / `commit()` / `rollBack()`.
- **Toggle de Hábito (`ToggleHabitUseCase`)**: Garante a existência do registro em `days` e alterna o registro em `day_habits` dentro de uma transação, invalidando o cache somente após o `commit()`.
- **Cadastro e Perfil de Usuário (`RegisterUserUseCase`, `UpdateUserProfileUseCase`, `AuthenticateWithSocialProviderUseCase`, `DeleteUserUseCase`)**: Coordenam gravações entre `persons`, `users` e `user_verifications` em transação.

> [!IMPORTANT]
> Nunca invalide ou preencha o cache definitivo antes de `$this->pdo->commit()` em blocos transacionais que podem sofrer `rollBack()`.

---

## 3. Segurança, JWT e Autenticação

1. **JWT Access Token & Refresh Token (`HttpOnly` Cookie)**:
   - O Access Token (curta duração) é enviado no header `Authorization: Bearer <token>` e validado pelo `JwtAuthMiddleware`, que injeta `user_id` e `user_role` nos atributos da `ServerRequestInterface`.
   - O Refresh Token (longa duração) é gerenciado estritamente via cookie `HttpOnly` (`refresh_token`) no `AuthController`, prevenindo roubo via XSS no cliente mobile/web.
   - O `JwtService` utiliza chaves assimétricas RSA (`config/keys/private_key.pem` e `public_key.pem`) e controla revogação/blacklist no `RedisCache`.
2. **Prevenção de IDOR (Insecure Direct Object Reference)**:
   - Sempre obtenha o ID do usuário autenticado via `$request->getAttribute('user_id')`.
   - Nunca confie em um `user_id` enviado no body/query de rotas de cliente para acessar recursos de hábitos; passe sempre o `$userId` autenticado para o UseCase e Repositório (`findById(int $id, int $userId)`).
3. **Rate Limiting (`RateLimitMiddleware`)**:
   - Protege a API contra abuso e força bruta utilizando contadores no `RedisCache`. Preserve os headers `X-RateLimit-*` nas respostas.
