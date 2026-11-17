# Testes e Validação de Qualidade de Código

Ao finalizar arquivos de código para a API, você deve sempre validá-los usando as ferramentas nativas já configuradas no container da aplicação.

## Comandos Úteis

> [!IMPORTANT]
> Todos os comandos abaixo devem ser executados na **raiz do workspace** (`/Users/davidfreitas/www/habits`). Nunca rode comandos PHP/Composer diretamente no host macOS nem faça `cd api`.

### 1. Testes Automatizados (PHPUnit)

```bash
# Rodar teste específico (RECOMENDADO para validações rápidas durante o desenvolvimento):
docker compose exec api vendor/bin/phpunit tests/Unit/Domain/Entity/HabitTest.php

# Rodar teste filtrando por nome do método:
docker compose exec api vendor/bin/phpunit --filter testShouldCreateHabitSuccessfully

# Rodar apenas a suíte Unitária (rápido, sem banco):
docker compose exec api composer test:unit

# Rodar apenas a suíte de Integração:
docker compose exec api composer test:integration

# Rodar apenas a suíte Funcional:
docker compose exec api composer test:functional

# Rodar a suíte completa com saída formatada (testdox):
docker compose exec api composer test:testdox

# Rodar a suíte completa (Unit, Integration, Functional):
docker compose exec api composer test
```

### 2. Cobertura de Testes (Coverage)

```bash
# Rodar um arquivo de teste específico com cobertura em texto no terminal:
docker compose exec api vendor/bin/phpunit tests/Unit/Domain/Entity/HabitTest.php \
  --coverage-filter src/Domain/Entity \
  --coverage-text

# Gerar relatório completo de cobertura em HTML (salvo em api/tools/coverage/):
docker compose exec api composer test:coverage

# Inspecionar relatórios HTML diretamente no terminal:
cat api/tools/coverage/Domain/index.html
cat api/tools/coverage/Domain/Entity/Habit.php.html
```
> **Nota:** Em `--coverage-filter`, sempre aponte para o diretório de código-fonte (`src/...`), nunca para o arquivo de teste.

**Ciclo iterativo de desenvolvimento de testes:**
1. **Identificar lacunas** — Use os relatórios de cobertura para encontrar linhas/métodos não testados.
2. **Ler código-fonte e testes existentes** — Entenda a implementação e o padrão de teste atual.
3. **Escrever/Refatorar teste** — Adicione novos casos ou melhore os existentes.
4. **Rodar teste específico com coverage** — Verifique a melhoria na cobertura.
5. **Depurar** — Analise a saída (atenção a `TypeErrors`, que podem indicar bugs no código principal).

### 3. Estilo de Código (PHP-CS-Fixer / PSR-12)
```bash
# Verifica violações de estilo de código (dry-run):
docker compose exec api composer cs-check

# Ajusta automaticamente para o padrão PSR-12 (sempre rode se o check falhar):
docker compose exec api composer cs-fix
```

### 4. Refatoração e Análise Estática (Rector)
```bash
# Simula refatorações do Rector sem alterar arquivos:
docker compose exec api composer rector:dry

# Aplica refatorações do Rector:
docker compose exec api composer rector
```

### 5. Checagem de Sintaxe e Seed do Banco
```bash
# Checar sintaxe PHP de um arquivo modificado:
docker compose exec api php -l src/Domain/Entity/Habit.php

# Popular o banco de dados com dados iniciais (dias, hábitos, etc.):
docker compose exec api composer seed
```

## Tipos de Teste (`api/tests/`)

A suíte de testes divide-se da seguinte forma:

- **Testes Unitários (`tests/Unit/`)**: Escreva testes unitários isolados para camadas de Domínio (`Entities`, `ValueObjects`, `Exceptions`) e Aplicação (`UseCases`, `DTOs`, `Services`) usando Mocks (`$this->createMock()`). Sem acesso a banco.
- **Testes de Integração (`tests/Integration/`)**: Teste a comunicação de Repositórios concretos e Decorators com o banco de dados de teste (`database_test`) e Redis.
- **Testes Funcionais (`tests/Functional/`)**: Cubra os endpoints da API (`Presentation`) de ponta a ponta simulando a requisição HTTP. Crie uma pasta para cada grupo de endpoints e estenda a classe base `FunctionalTestCase`.

## Testando Comportamentos, Não Apenas Linhas

Ao criar testes, garanta a cobertura destes cenários:
1. Caminho feliz (Happy path).
2. Falha de validação de DTO / Entidade (`ValidationException` / HTTP 400 ou 422).
3. Falha de autenticação/autorização (Token ausente/inválido, acesso negado ou IDOR entre usuários diferentes).
4. Recurso não encontrado (`HabitNotFoundException` / `NotFoundException` / HTTP 404).
5. Conflito de estado (`HabitAlreadyExistsException` / `ConflictException` / HTTP 409).
6. Consistência de cache (garantir que mutações invalidam ou atualizam o `Caching*Repository`).
