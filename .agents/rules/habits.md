# Project: Habits — Developer Guidelines

This document consolidates the guidelines for both projects in the Habits ecosystem:
- **Habits REST API** — PHP 8.4 + Slim Framework 4.x (backend)
- **Habits Mobile App** — Ionic 7 + Vue 3 (frontend)

---

## Table of Contents

1. [Critical Rules (All Projects)](#1-critical-rules-all-projects-)
2. [REST API](#2-rest-api)
   - [2.1 Overview](#21-overview)
   - [2.2 Architecture & Code Structure](#22-architecture--code-structure)
   - [2.3 Code Quality Standards](#23-code-quality-standards)
   - [2.4 Security Requirements](#24-security-requirements)
   - [2.5 Development Workflow](#25-development-workflow)
   - [2.6 Commands Reference](#26-commands-reference)
   - [2.7 File Structure](#27-file-structure)
3. [Mobile App](#3-mobile-app)
   - [3.1 Overview](#31-overview)
   - [3.2 Architecture & Code Structure](#32-architecture--code-structure)
   - [3.3 Code Quality Standards](#33-code-quality-standards)
   - [3.4 Security & Authentication](#34-security--authentication)
   - [3.5 Development Workflow](#35-development-workflow)
   - [3.6 Commands Reference](#36-commands-reference)
   - [3.7 File Structure](#37-file-structure)
4. [Quick Reference](#4-quick-reference)

---

## 1. Critical Rules (All Projects) ⚠️

These rules apply to **both projects** and must **NEVER** be violated under any circumstances:

### Rule #1: No Git Operations
**NEVER** use shell commands like `git add` or `git commit`. All Git operations must be done manually by the user. The assistant should only create, modify, or delete files as requested.

### Rule #2: No Hardcoded Credentials
- Never hardcode credentials, API keys, secrets, or Base URLs
- Always use environment variables and `.env` files for sensitive data
- Always ensure newly created sensitive files (`.env`, private keys, logs, temp files) are immediately added to `.gitignore`

---

## 2. REST API

### 2.1 Overview

| Property | Value |
|---|---|
| Environment | Docker-based (all tools run through Docker containers) |
| PHP Version | 8.4 |
| Framework | Slim Framework 4.x |
| Language | Portuguese (messages & test output), English (code & docs) |

**Key Characteristics:**
- RESTful API architecture
- Layered architecture inspired by DDD and Clean Architecture
- Dependency injection using PHP-DI
- Docker-first development workflow

> **Additional Critical Rule — No Local Composer**: **NEVER** run Composer locally. All Composer commands must be executed through Docker: `docker compose exec api composer <command>`

---

### 2.2 Architecture & Code Structure

#### Layered Architecture

| Layer | Location | Responsibility |
|---|---|---|
| Presentation | `src/Presentation` | Handle HTTP requests and responses (controllers) |
| Application | `src/Application` | Orchestrate business logic (use cases, DTOs, validation) |
| Domain | `src/Domain` | Core business logic (entities, value objects, repository interfaces) |
| Infrastructure | `src/Infrastructure` | Technical implementations (DB repositories, caching, JWT, mailers) |

**Dependency Direction**: Presentation → Application → Domain ← Infrastructure

#### Design Principles

- **SOLID Principles**: Applied throughout the codebase
- **Single Responsibility**: Keep methods small and focused on one task
- **Dependency Injection**: Use PHP-DI instead of instantiating objects directly
- **Composition Over Inheritance**: Prefer composition when appropriate
- **Interface Segregation**: Define focused repository interfaces in the Domain layer

---

### 2.3 Code Quality Standards

#### Code Style (PSR-12)

- Use strict type declarations at the beginning of all files:
  ```php
  declare(strict_types=1);
  ```
- Use type hints for all function parameters and return types
- Use class constants instead of magic strings
- Avoid global functions; prefer class methods or namespaced functions
- Always use **Constructor Property Promotion** to simplify class property declarations
- Follow PSR standards: PSR-4, PSR-7, PSR-12

#### Documentation (PHPDoc)

All new code must have proper PHPDoc blocks with:
- `@param` — Document all parameters
- `@return` — Document return types
- `@throws` — Document exceptions that can be thrown
- `@var` — Document class properties

**Best practices**: Document the purpose of complex classes, add usage examples when functionality is non-obvious, and keep documentation in sync with code changes.

#### General Practices

- Keep cyclomatic complexity low
- Avoid deep nesting; refactor complex conditionals into separate methods
- Use PHP-CS-Fixer to maintain consistent style
- Prefer PSR-4 autoloading

---

### 2.4 Security Requirements

- Use JWT tokens for API authentication with proper validation and expiration
- Input validation and sanitization required for **all** user inputs
- Place data input validations using `symfony/validator` in Request DTOs (`src/Application/DTO`)
- Always use environment variables for sensitive data; never hardcode credentials, API keys, or secrets

---

### 2.5 Development Workflow

#### Performance & Best Practices
- Implement caching where appropriate (Redis, Memcached, or file cache)
- Use dependency injection (PHP-DI) instead of instantiating objects directly
- Keep methods small and focused (Single Responsibility Principle)

#### Validation
- Place data input validations using `symfony/validator` in Request DTOs
- Location: `src/Application/DTO`
- Validate at the application layer boundary

#### Testing Strategy

| Type | Location | Purpose |
|---|---|---|
| Unit | `tests/Unit` | Test individual components in isolation |
| Integration | `tests/Integration` | Test component interactions |
| Functional | `tests/Functional` | Test API endpoints end-to-end |

**Functional test structure**:
- Create a folder for each group of endpoints
- Inside the folder, create a test file containing all tests for that endpoint
- Extend the `FunctionalTestCase` class

#### Refactoring Guidelines
- Maintain existing functionality
- Preserve environment variable usage
- Keep Docker compatibility
- Update `composer.json` via Docker commands when needed
- Ensure all configuration files in `tools/` are properly referenced

---

### 2.6 Commands Reference

#### Composer Commands
```bash
docker compose exec api composer install
docker compose exec api composer update
docker compose exec api composer require <package/name>
docker compose exec api composer require --dev <package/name>
docker compose exec api composer remove <package/name>
```
> Use Composer scripts when available. Prefer `composer test` over direct PHPUnit calls.

#### Code Quality Commands
```bash
docker compose exec api composer cs-check      # Check code style (dry-run)
docker compose exec api composer cs-fix        # Fix code style automatically
docker compose exec api composer rector        # Run Rector refactoring
docker compose exec api composer rector:dry    # Simulate Rector (dry-run)
```

#### Testing Commands
```bash
docker compose exec api composer test                  # Run all tests
docker compose exec api composer test:testdox          # Detailed output (testdox)
docker compose exec api composer test:unit             # Unit tests only
docker compose exec api composer test:integration      # Integration tests only
docker compose exec api composer test:functional       # Functional tests only
docker compose exec api composer test:coverage         # Generate coverage report
```

#### Advanced Testing Workflow

**Run a specific test file with text coverage:**
```bash
docker compose exec api vendor/bin/phpunit tests/Unit/Domain/Entity/<TestFileName>.php \
  --coverage-filter src/Domain/Entity \
  --coverage-text
```
> If you get a "No filter is configured" warning, ensure `--coverage-filter` points to the *source directory* (e.g., `src/Domain/Entity`), not the test file.

**Inspect HTML coverage reports:**
```bash
docker compose exec api composer test:coverage         # Generate full HTML report (output: tools/coverage/)
cat tools/coverage/Domain/index.html                   # View summary for a specific module
cat tools/coverage/Domain/Entity/ErrorLog.php.html     # Inspect line-by-line coverage
```
> Coverage reports are typically ignored by `.gitignore`. Use `cat` to inspect them directly.

**Iterative test development cycle:**
1. **Identify gaps** — Use coverage reports to find untested lines/methods/classes
2. **Read source & existing tests** — Understand the code and current test approach
3. **Write/Refactor test** — Add new test cases or improve existing ones
4. **Run specific test with coverage** — Verify changes and coverage improvement
5. **Debug** — Analyze output; pay attention to `TypeErrors` which may indicate bugs in the main codebase
6. **Repeat** — Until desired coverage for the component is achieved

---

### 2.7 File Structure

```
project/
├── config/
│   ├── bootstrap.php            # Application bootstrap
│   ├── container.php            # Dependency injection container
│   ├── routes.php               # API routes definition
│   └── settings.php             # Application settings
├── database/
│   └── schema.sql
├── docs/
│   ├── API.md
│   └── postman_collection.json
├── public/
│   └── index.php                # Application entry point
├── src/
│   ├── Application/
│   │   ├── DTO/                 # Data Transfer Objects & validation
│   │   ├── UseCase/
│   │   └── Validation/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── Repository/          # Repository interfaces
│   │   └── Exception/
│   ├── Infrastructure/
│   │   ├── Http/
│   │   ├── Persistence/         # Database repository implementations
│   │   ├── Security/            # JWT, etc.
│   │   └── Mailer/
│   └── Presentation/
│       └── Api/V1/              # Controllers
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
├── tools/
│   ├── .php-cs-fixer.dist.php
│   ├── phpunit.xml
│   └── rector.php
└── composer.json
```

---

## 3. Mobile App

### 3.1 Overview

| Property | Value |
|---|---|
| Environment | Docker-based (development server) |
| Framework | Ionic Framework 7.x with Vue 3 |
| Language | JavaScript (Composition API with `<script setup>`) |
| Build Tool | Vite |
| Mobile Engine | Capacitor 8.x |
| State Management | Pinia |
| Styling | Vanilla CSS with Ionic Variables (CSS Variables) |

**Key Characteristics:**
- Hybrid Mobile Architecture (iOS & Android)
- Layered architecture (Separation of Concerns)
- Composition API focused
- Modular components (UI, Layout, Domain)

> **Additional Critical Rule — Composition API Only**: **ALWAYS** use Vue 3 Composition API with `<script setup>` syntax. Avoid Options API or non-setup composition.

---

### 3.2 Architecture & Code Structure

#### Layered Architecture

| Layer | Location | Responsibility |
|---|---|---|
| Presentation | `src/views` | Handle user interaction and screen structure |
| Component | `src/components` | UI (`ui/`), Layout (`layout/`), Domain-specific (`habits/`) |
| Service | `src/services` | Stateless API communication (e.g., `AuthService.login(credentials)`) |
| State | `src/stores` | Global application state via Pinia (calls Services, updates local state) |
| Composables | `src/composables` | Reusable logic and lifecycle hooks |
| API Client | `src/api/index.js` | Axios configuration and interceptors (exported as `api`) |

**Data flow**: View → Store → Service → API Client

#### Naming Conventions

| Type | Convention | Example |
|---|---|---|
| Components | PascalCase | `HabitDay.vue` |
| Composables | camelCase starting with `use` | `useLoading.js` |
| Services | PascalCase (export), camelCase (file) | `AuthService.js` |
| Stores | camelCase | `auth.js` |
| Views | PascalCase | `Signin.vue` |

---

### 3.3 Code Quality Standards

#### Vue Style Guide
- Use `PascalCase` for component names in templates
- Use `kebab-case` for events (e.g., `@handle-change`)
- Keep components small and focused
- Prefer Vanilla CSS with CSS Variables for styling

#### Composables Pattern
- Encapsulate logic that uses Vue lifecycle hooks
- Return objects with refs and functions
- Example: `const { isLoading, withLoading } = useLoading()`

#### Documentation
- Use JSDoc for complex functions and service methods
- Document Props and Emits in all components

---

### 3.4 Security & Authentication

- Access and Refresh tokens are handled via `AuthStore` and `api` interceptors
- Tokens are stored in `localStorage`
- The `api` interceptor automatically handles 401 errors by attempting a token refresh
- Always use `src/constants/storage.js` for storage keys
- Always use `src/constants/endpoints.js` for API paths
- Always use `.env` files and `import.meta.env` for configuration; ensure `.env` files are in `.gitignore`

---

### 3.5 Development Workflow

#### Local Development (Docker)
The development server starts automatically with `docker compose up`.
```bash
docker compose logs -f mobile    # View development server logs
```
The app is accessible at `http://localhost:8100`.

#### Mobile Platform Sync
Native operations must be run on the host machine.
```bash
docker compose exec mobile npm run build    # Build web assets inside Docker
npx cap sync                                # Sync to native projects (host)
```

#### Testing Strategy

| Type | Tool | Location |
|---|---|---|
| Unit | Vitest + Vue Test Utils | `tests/unit` |
| E2E | Cypress | `tests/e2e` |

---

### 3.6 Commands Reference

#### Dependency Management (via Docker)
```bash
docker compose exec mobile npm install <package>
docker compose exec mobile npm install --save-dev <package>
```

#### Mobile (Host Machine)
```bash
npx cap sync              # Sync web assets to native projects
npx cap open ios          # Open native iOS IDE
npx cap open android      # Open native Android IDE
```

#### Build (via Docker)
```bash
docker compose exec mobile npm run build
```

---

### 3.7 File Structure

```
mobile/
├── public/
├── src/
│   ├── api/                    # Axios client configuration
│   ├── assets/
│   ├── components/
│   │   ├── ui/                 # Base/Atomic components (Button, Input, Checkbox)
│   │   ├── layout/             # Structural components (Header, Container, Modal)
│   │   └── habits/             # Domain-specific components (HabitDay, HabitForm)
│   ├── composables/
│   ├── constants/              # endpoints.js, storage.js
│   ├── router/
│   ├── services/
│   ├── stores/
│   ├── theme/
│   ├── views/
│   │   ├── auth/
│   │   ├── habits/
│   │   └── settings/
│   ├── App.vue
│   └── main.js
├── tests/
│   ├── unit/
│   └── e2e/
└── vite.config.js
```

---

## 4. Quick Reference

### Common Rules (Both Projects)
✅ Never use Git commands (`git add`, `git commit`)  
✅ Never hardcode credentials, API keys, or secrets  
✅ Always use environment variables for sensitive data  
✅ Always add sensitive files to `.gitignore`  

### REST API
✅ Always use Docker commands: `docker compose exec api composer <command>`  
✅ Never run Composer locally  
✅ Follow PSR-12 for code style  
✅ Use strict types: `declare(strict_types=1);`  
✅ Document with PHPDoc  
✅ Follow layered architecture  
✅ Use dependency injection (PHP-DI)  

### Mobile App
✅ Use `<script setup>` in all components  
✅ Follow the layered architecture (View → Store → Service → API)  
✅ Use `@/` alias for all imports  
✅ Use `useToast` for user notifications  
✅ Use `useLoading` for async operations  
✅ Define all API endpoints in `src/constants/endpoints.js`  
✅ Run `npx cap sync` after asset changes