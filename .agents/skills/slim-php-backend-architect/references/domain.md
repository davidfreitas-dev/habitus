# Domínio do Habits

A **Habits REST API** é o backend da plataforma de criação, acompanhamento diário e estatísticas de hábitos pessoais, contando também com gestão completa de usuários, autenticação avançada e painel administrativo.

## Core Domains (Módulos e Entidades Principais)

### 1. Habits & Tracking (Núcleo do Negócio)
- **`Habit` (`src/Domain/Entity/Habit.php`)**: Representa um hábito pertencente a um `User`, com `title`, `reminderTime` opcional e uma coleção (`Doctrine\Common\Collections\Collection`) de dias da semana planejados (`HabitWeekDay`).
- **`HabitWeekDay` (`src/Domain/Entity/HabitWeekDay.php`)**: Associa um hábito aos dias da semana em que ele deve ser praticado (`0` = Domingo a `6` = Sábado).
- **`Day` (`src/Domain/Entity/Day.php`)**: Representa uma data do calendário (`Y-m-d`) em que hábitos foram registrados/completados.
- **`DayHabit` (`src/Domain/Entity/DayHabit.php`)**: Registro de conclusão (check-in/toggle) de um `Habit` específico por um `User` em um `Day`.
- **Estatísticas e Resumos**:
  - **By Day (`GetHabitsByDayUseCase`)**: Retorna os hábitos possíveis para o dia da semana daquela data e quais já foram completados.
  - **Summary (`GetHabitsSummaryUseCase`)**: Retorna o histórico de dias com total de hábitos possíveis vs. completados.
  - **Stats (`GetHabitStatsUseCase` / `HabitStatsRepository`)**: Calcula métricas de desempenho, sequências (streaks) e taxas de conclusão por período (`week`, `month`, `year`, `all`).

### 2. Users, Person & Roles
- **`Person` (`src/Domain/Entity/Person.php`)**: Dados pessoais (nome, `CpfCnpj`, telefone, avatar/foto de perfil).
- **`User` (`src/Domain/Entity/User.php`)**: Credenciais e estado da conta (`email`, `passwordHash`, `isActive`, `isVerified`, associação com `Person` e `Role`).
- **`Role` (`src/Domain/Entity/Role.php`)**: Papel de autorização (`customer` para usuários comuns, `admin` para administradores).
- **Value Objects**: `CpfCnpj` (validação de CPF/CNPJ) e `Code` (códigos de verificação/recuperação).

### 3. Auth & Security
- **Autenticação Tradicional (`LoginUseCase`, `RegisterUserUseCase`)**: Emissão de JWT Access Token (retornado no payload) e Refresh Token (enviado via cookie `HttpOnly` no `AuthController`).
- **Social Login (`AuthenticateWithSocialProviderUseCase`, `GoogleTokenVerifier`)**: Autenticação OAuth via Google ID Token (`SocialProvider`), criando ou vinculando o `User`/`Person` automaticamente.
- **Verificação de E-mail (`VerifyEmailUseCase`, `UserVerification`)**: Fluxo de confirmação de conta por token enviado via `PHPMailerService`.
- **Recuperação de Senha (`ForgotPasswordUseCase`, `ValidateResetCodeUseCase`, `ResetPasswordUseCase`, `PasswordReset`)**: Fluxo de redefinição de senha baseado em código e expiração.

### 4. Administração e Observabilidade (`Admin` & `ErrorLog`)
- **Gestão de Usuários (`AdminController`)**: Listagem paginada, criação, edição e exclusão de usuários por administradores.
- **Monitoramento de Erros (`ErrorLog`, `ErrorLogController`, `DatabaseErrorLogHandler`)**: Erros críticos (`Level::Error` ou superior no Monolog) são automaticamente persistidos na tabela `error_logs` e podem ser listados, inspecionados e marcados como resolvidos (`ResolveErrorLogUseCase`).

---

## Fluxos Críticos de Domínio

### Ciclo de Vida do Hábito e Check-in Diário (Toggle)
```text
Criação do Hábito (CreateHabitUseCase)
  → Valida DTO + unicidade do título para o usuário
  → Abre transação PDO -> Insere em `habits` e `habit_week_days` -> Commit
  → Invalida caches do usuário no CachingHabitRepository
  ↓
Consulta do Dia (GetHabitsByDayUseCase)
  → Busca hábitos possíveis para o dia da semana (0-6) e data de criação <= data consultada
  → Busca hábitos completados na data (`day_habits`)
  ↓
Check-in / Desmarcar (ToggleHabitUseCase)
  → Verifica se o hábito existe e pertence ao `userId` autenticado
  → Abre transação PDO
  → Busca ou cria o registro `Day` para a data informada
  → Insere ou remove o vínculo em `day_habits` (`DayHabitRepository::toggle`)
  → Commit da transação
  → Invalida os caches de resumo, possíveis, completados e listagem do usuário
```

## Diretriz de Isolamento de Dados (Multi-user Safety)
Todo hábito, resumo, estatística e check-in é estritamente individual. **Nunca** execute consultas ou mutações de hábitos sem filtrar ou validar o `$userId` extraído do token JWT (`$request->getAttribute('user_id')`).
