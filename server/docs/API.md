# Documentação da API

## Índice
- [Públicos (sem autenticação)](#públicos-sem-autenticação)
  - [Health Check](#health-check)
  - [Registro de Usuário](#registro-de-usuário)
  - [Login](#login)
  - [Logout](#logout)
  - [Refresh Token](#refresh-token)
  - [Esqueci Minha Senha](#esqueci-minha-senha)
  - [Validar Código de Redefinição de Senha](#validar-código-de-redefinição-de-senha)
  - [Reset de Senha](#reset-de-senha)
  - [Verificação de E-mail](#verificação-de-e-mail)
- [Protegidos (requerem autenticação)](#protegidos-requerem-autenticação)
  - [Perfil do Usuário](#perfil-do-usuário)
    - [Obter dados do perfil](#obter-dados-do-perfil)
    - [Atualizar dados do perfil](#atualizar-dados-do-perfil)
    - [Alterar senha](#alterar-senha)
    - [Deletar conta](#deletar-conta)
  - [Hábitos](#hábitos)
    - [Criar Hábito](#criar-hábito)
    - [Listar todos os hábitos](#listar-todos-os-hábitos)
    - [Listar hábitos por dia](#listar-hábitos-por-dia)
    - [Resumo de Hábitos](#resumo-de-hábitos)
    - [Estatísticas de Hábitos](#estatísticas-de-hábitos)
    - [Detalhes do Hábito](#detalhes-do-hábito)
    - [Atualizar Hábito](#atualizar-hábito)
    - [Marcar/Desmarcar Hábito como Completo](#marcar/desmarcar-hábito-como-completo)
    - [Deletar Hábito](#deletar-hábito)
  - [Admin](#admin-requer-autenticação-e-permissão)
    - [Criar Usuário](#criar-usuário)
    - [Listar Usuários](#listar-usuários)
    - [Obter Detalhes do Usuário](#obter-detalhes-do-usuário)
    - [Atualizar Usuário](#atualizar-usuário)
    - [Deletar Usuário](#deletar-usuário)

---

## Públicos (sem autenticação)

### Health Check
Verifica o status da API e seus serviços.

```http
GET /api/v1
```

---

### Registro de Usuário
Registra um novo usuário com acesso imediato (limitado até verificação de e-mail).

```http
POST /api/v1/auth/register
```

**Body:**
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

---

### Login
Autentica e retorna tokens de acesso.

```http
POST /api/v1/auth/login
```

**Body:**
```json
{
  "email": "joao@example.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

---

### Logout
Invalida os tokens do usuário.

```http
POST /api/v1/auth/logout
```

**Headers:** `Authorization: Bearer {token}`

**Resposta:**
```json
{
    "status": "success",
    "message": "Logout realizado com sucesso"
}
```

---

### Refresh Token
Gera novo token de acesso.

```http
POST /api/v1/auth/refresh
```

**Body:**
```json
{
  "refresh_token": "eyJ0eXAiOi..."
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Token atualizado com sucesso.",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

---

### Esqueci Minha Senha
Envia código de 6 dígitos por e-mail.

```http
POST /api/v1/auth/forgot-password
```

**Body:**
```json
{
  "email": "joao@example.com"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Se este e-mail existir, um e-mail de redefinição de senha foi enviado."
}
```

---

### Validar Código de Reset
Valida o código recebido por e-mail.

```http
POST /api/v1/auth/validate-reset-code
```

**Body:**
```json
{
  "email": "joao@example.com",
  "code": "123456"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Código válido."
}
```

---

### Reset de Senha
Redefine a senha (requer código validado).

```http
POST /api/v1/auth/reset-password
```

**Body:**
```json
{
  "email": "joao@example.com",
  "code": "123456",
  "password": "novaSenha123",
  "password_confirm": "novaSenha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Senha redefinida com sucesso."
}
```

---

### Verificação de E-mail
Valida a conta via token recebido por e-mail.

```http
GET /api/v1/auth/verify-email?token={verification_token}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "E-mail verificado com sucesso.",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

---

## Protegidos (requerem autenticação)

> **Nota:** Use o header `Authorization: Bearer {access_token}`
>
> **Aviso:** Para usuários com e-mail não verificado, o acesso a algumas rotas protegidas será negado com um `403 Forbidden`.

### Perfil do Usuário

As rotas a seguir referem-se ao usuário autenticado.

#### Obter dados do perfil

```http
GET /api/v1/profile
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "99988877766",
    "cpfcnpj": "12345678900",
    "avatar_url": "http://example.com/new_avatar.jpg",
    "is_active": true,
    "is_verified": true,
    "role_id": 3,
    "role_name": "admin",
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:00:00Z"
  }
}
```

---

#### Atualizar dados do perfil

```http
PUT /api/v1/profile
```

**Body:**
```json
{
  "name": "Nome Atualizado",
  "phone": "99988877766",
  "cpfcnpj": "12345678901"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Perfil atualizado com sucesso.",
  "data": {
    "id": 1,
    "name": "Nome Atualizado",
    "email": "joao@example.com",
    "phone": "99988877766",
    "cpfcnpj": "12345678901",
    "avatar_url": "http://example.com/new_avatar.jpg",
    "is_active": true,
    "is_verified": true,
    "role_id": 3,
    "role_name": "admin",
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-02T10:30:00Z"
  }
}
```

---

#### Alterar senha

```http
PATCH /api/v1/profile/change-password
```

**Body:**
```json
{
  "current_password": "senhaAtual",
  "new_password": "novaSenha123",
  "new_password_confirm": "novaSenha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Senha alterada com sucesso."
}
```

---

#### Deletar conta

```http
DELETE /api/v1/profile
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Sua conta foi excluída com sucesso."
}
```

---

### Hábitos

As rotas a seguir permitem gerenciar os hábitos do usuário autenticado.

#### Criar Hábito

Cria um novo hábito para o usuário autenticado.

```http
POST /api/v1/habits
```

**Body:**
```json
{
  "title": "Ler Livros",
  "week_days": [0, 1, 2, 3, 4],
  "reminder_time": "08:00",
  "created_at": "2024-03-01 10:00:00"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Hábito criado com sucesso.",
  "data": {
    "id": 1,
    "title": "Ler Livros",
    "week_days": [0, 1, 2, 3, 4],
    "reminder_time": "08:00",
    "created_at": "2024-03-01T10:00:00Z",
    "updated_at": "2024-03-01T10:00:00Z"
  }
}
```

---

#### Listar todos os hábitos

Retorna todos os hábitos cadastrados para o usuário autenticado.

```http
GET /api/v1/habits
```

**Resposta:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Ler Livros",
      "week_days": [0, 1, 2, 3, 4],
      "reminder_time": "08:00",
      "created_at": "2024-03-01T10:00:00Z",
      "updated_at": "2024-03-01T10:00:00Z"
    },
    {
      "id": 2,
      "title": "Meditar",
      "week_days": [0, 1, 2, 3, 4, 5, 6],
      "reminder_time": "09:00",
      "created_at": "2024-03-01T10:30:00Z",
      "updated_at": "2024-03-01T10:30:00Z"
    }
  ]
}
```

---

#### Listar Hábitos por Dia

Retorna todos os hábitos de um dia específico para o usuário autenticado.

```http
GET /api/v1/habits/day?date=YYYY-MM-DD
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "possible_habits": [
      {
        "id": 1,
        "title": "Ler Livros",
        "week_days": [0, 1, 2, 3, 4]
      },
      {
        "id": 2,
        "title": "Meditar",
        "week_days": [0, 1, 2, 3, 4]
      }
    ],
    "completed_habits": [
      {
        "id": 1,
        "title": "Ler Livros",
        "week_days": [0, 1, 2, 3, 4]
      }
    ]
  }
}
```

---

#### Resumo de Hábitos

Retorna um resumo de hábitos, incluindo a quantidade de hábitos possíveis e completados para todos os dias do ano atual até a data de referência.

```http
GET /api/v1/habits/summary?date=YYYY-MM-DD
```

**Query Params:**
- `date`: Opcional. Data de referência ("hoje") no formato `YYYY-MM-DD`. Se omitida, usa a data atual do servidor (UTC).

**Resposta:**
```json
{
  "status": "success",
  "data": [
    {
      "date": "YYYY-MM-DD",
      "completed": 0,
      "total": 0
    },
    {
      "date": "YYYY-MM-DD",
      "completed": 1,
      "total": 2
    },
    // ... (mais entradas para outros dias)
    {
      "date": "YYYY-MM-DD",
      "completed": 0,
      "total": 1
    }
  ]
}
```

---

#### Estatísticas de Hábitos

Retorna estatísticas detalhadas de hábitos agregadas por dia da semana (Domingo a Sábado) para um período específico, além do recorde e sequência atual de dias consecutivos.

```http
GET /api/v1/habits/stats?period={W|M|3M|6M|Y}
```

**Query Params:**
- `period`: Opcional. Período das estatísticas. Valores aceitos: `W` (Semana - padrão), `M` (Mês), `3M` (3 Meses), `6M` (6 Meses), `Y` (Ano).

**Resposta:**
```json
{
  "status": "success",
  "message": "Estatísticas obtidas com sucesso.",
  "data": {
    "daily_stats": [
      {
        "week_day": 0,
        "label": "D",
        "percentage": 50.0,
        "completed": 10,
        "total": 20
      },
      // ... (sempre retorna 7 itens, de 0 a 6)
      {
        "week_day": 6,
        "label": "S",
        "percentage": 0.0,
        "completed": 0,
        "total": 5
      }
    ],
    "current_streak": 5,
    "longest_streak": 12
  }
}
```

---

#### Detalhes do Hábito

Retorna os detalhes de um hábito específico.

```http
GET /api/v1/habits/{id}
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Ler Livros",
    "week_days": [0, 1, 2, 3, 4],
    "reminder_time": "08:00",
    "created_at": "2024-03-01T10:00:00Z",
    "updated_at": "2024-03-01T10:00:00Z"
  }
}
```

---

#### Atualizar Hábito

Atualiza um hábito existente para o usuário autenticado.

```http
PUT /api/v1/habits/{id}
```

**Body:**
```json
{
  "title": "Meditar",
  "week_days": [0, 1, 2, 3, 4, 5, 6],
  "reminder_time": "09:00"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Hábito atualizado com sucesso.",
  "data": {
    "id": 1,
    "title": "Meditar",
    "week_days": [0, 1, 2, 3, 4, 5, 6],
    "reminder_time": "09:00",
    "created_at": "2024-03-01T10:00:00Z",
    "updated_at": "2024-03-01T11:00:00Z"
  }
}
```

---

#### Marcar/Desmarcar Hábito como Completo

Marca ou desmarca um hábito como completo para um dia específico.

```http
PATCH /api/v1/habits/{id}/toggle
```

**Body:**
```json
{
  "date": "2024-03-01"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Hábito marcado com sucesso."
}
```

---

#### Deletar Hábito

Deleta um hábito específico.

```http
DELETE /api/v1/habits/{id}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Hábito excluído com sucesso."
}
```

---

### Admin

> **Nota:** Use o header `Authorization: Bearer {access_token}`
>
> As rotas a seguir requerem que o usuário autenticado tenha a função (role) de `admin`. Para usuários com e-mail não verificado, o acesso a estas rotas será negado com um `403 Forbidden`.

#### Criar Usuário

```http
POST /api/v1/admin/users
```

**Body:**
```json
{
  "name": "Admin User",
  "email": "admin.user@example.com",
  "password": "Password123",
  "role": "user",
  "phone": "11987654321",
  "cpfcnpj": "12345678900"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário criado com sucesso.",
  "data": {
    "id": 2,
    "name": "Admin User",
    "email": "admin.user@example.com",
    "phone": "11987654321",
    "cpfcnpj": "12345678900",
    "avatar_url": "http://example.com/new_avatar.jpg",
    "role_id": 1,
    "role_name": "user",
    "is_active": true,
    "is_verified": false,
    "created_at": "2024-01-02T14:00:00Z",
    "updated_at": "2024-01-02T14:00:00Z"
  }
}
```

---

#### Listar Usuários

```http
GET /api/v1/admin/users?limit=20&offset=0
```

**Resposta:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "phone": "11987654321",
      "cpfcnpj": "12345678900",
      "avatar_url": "http://example.com/new_avatar.jpg",
      "role_id": 3,
      "role_name": "admin",
      "is_active": true,
      "is_verified": true,
      "created_at": "2024-01-02T14:00:00Z",
      "updated_at": "2024-01-02T14:00:00Z"
    },
    {
      "id": 2,
      "name": "Admin User",
      "email": "admin.user@example.com",
      "role_id": 1,
      "role_name": "user",
      "phone": "11987654321",
      "cpfcnpj": "12345678900",
      "avatar_url": "http://example.com/new_avatar.jpg",
      "is_active": true,
      "is_verified": false,
      "created_at": "2024-01-02T14:00:00Z",
      "updated_at": "2024-01-02T14:00:00Z"
    }
  ],
  "total": 2,
  "limit": 20,
  "offset": 0
}
```

---

#### Obter Detalhes do Usuário

Obtém os detalhes de um usuário específico pelo seu ID.

```http
GET /api/v1/admin/users/{id}
```

**Resposta:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "99988877766",
    "cpfcnpj": "12345678900",
    "avatar_url": "http://example.com/new_avatar.jpg",
    "role_id": 3,
    "role_name": "admin",
    "is_active": true,
    "is_verified": true,
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-02T10:30:00Z"
  }
}
```

---

#### Atualizar Usuário

Atualiza os dados de um usuário específico, incluindo nome, email, telefone, CPF/CNPJ, função, status ativo e verificado.

```http
PUT /api/v1/admin/users/{id}
```

**Body:**
```json
{
  "name": "Nome Atualizado",
  "email": "email@example.com",
  "phone": "11999999999",
  "cpfcnpj": "00987654321",
  "role": "admin",
  "is_active": true,
  "is_verified": true
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário atualizado com sucesso.",
  "data": {
    "id": 1,
    "name": "Nome Atualizado",
    "email": "email@example.com",
    "phone": "11999999999",
    "cpfcnpj": "00987654321",
    "avatar_url": "http://example.com/new_avatar.jpg",
    "role_id": 3,
    "role_name": "admin",
    "is_active": true,
    "is_verified": true,
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-02T15:00:00Z"
  }
}
```

---

#### Deletar Usuário

Deleta um usuário específico pelo seu ID. Um administrador não pode deletar a própria conta.

```http
DELETE /api/v1/admin/users/{id}
```

**Resposta:**
```json
{
    "status": "success",
    "message": "Usuário excluído com sucesso."
}
```