# 🌿 Habitus — Habit Tracking App

Aplicativo para rastrear e gerenciar seus hábitos diários, construído com Ionic, Vue 3 e Capacitor.

---

## 🌟 Tecnologias

- **[Ionic](https://ionicframework.com/)** — Framework para apps híbridos
- **[Vue 3](https://vuejs.org/)** — Framework JavaScript progressivo
- **[Capacitor](https://capacitorjs.com/)** — Runtime nativo para iOS e Android
- **[Docker](https://www.docker.com/)** — Ambiente de desenvolvimento containerizado

---

## 🏗️ Arquitetura

O projeto segue uma arquitetura modular baseada em camadas, com foco em separação de responsabilidades e escalabilidade:

```text
src/
├── api/            # Configuração do cliente HTTP (Axios) e interceptores
├── components/     # Componentes Vue reutilizáveis
│   ├── ui/         # Componentes base e atômicos (Button, Input)
│   ├── habits/     # Componentes de domínio (HabitDay, Summary)
│   └── layout/     # Componentes de estrutura (Header, Container)
├── composables/    # Lógica reutilizável com Composition API
├── constants/      # Strings globais, endpoints e chaves de storage
├── lib/            # Bibliotecas externas
├── router/         # Definições de rotas e guardas de navegação
├── services/       # Camada de comunicação com a API
├── stores/         # Gerenciamento de estado global (Pinia)
├── theme/          # Estilos globais e variáveis de tema Ionic
└── views/          # Páginas organizadas por contexto (auth, habits, settings)
```

**Fluxo de dados:** Views → Stores → Services → API

---

## 🚀 Configuração do Ambiente

> [!IMPORTANT]
> **Pré-requisito:** Este projeto faz parte de um monorepo e depende da infraestrutura Docker definida no projeto `/server`. Antes de continuar, **siga completamente o passo a passo de configuração do [`/server`](../server/README.md)** (clone do repositório, criação do `.env` raiz, geração de chaves JWT, inicialização dos containers e instalação das dependências do Composer).

Após concluir o setup do `/server` e com os containers já em execução (`docker compose up -d`), siga os passos abaixo para configurar o mobile:

### 1. Configure as variáveis de ambiente do Mobile

Crie o arquivo `.env` específico do mobile a partir do exemplo:
```sh
cp mobile/.env.example mobile/.env
```
Preencha as variáveis conforme necessário (ex: URL base da API).

### 2. Reconstrua o container mobile

Caso o container `mobile` ainda não tenha sido iniciado com o build completo:
```sh
docker compose up -d --build mobile
```
O container instalará automaticamente as dependências (`npm install`) durante o build.

### 3. Acesse o App

Abra `http://mobile.localhost` no seu navegador.

---

## 🛠️ Comandos (Executados via Docker)

Sempre execute os comandos a partir da raiz do projeto.

### Ver Logs
```sh
docker compose logs -f mobile
```

### Rodar comandos NPM/Ionic dentro do container
```sh
docker compose exec mobile npm install <package>
docker compose exec mobile npm run build
```

---

## 📱 Desenvolvimento Nativo (iOS & Android)

Para compilar e rodar em emuladores ou dispositivos físicos, você precisará do ambiente nativo configurado na sua máquina host (Xcode/Android Studio).

### Sincronizar Assets Natividade
```sh
# Após gerar o build web dentro do docker
docker compose exec mobile npm run build
npx cap sync
```

### Abrir nos IDEs Natividade
```sh
npx cap open ios
npx cap open android
```

---

## 🖼️ Gerando Assets (Ícones e Splash Screen)

Os assets do app são gerados a partir da pasta `mobile/assets/`.

1. **Prepare as imagens fonte em `mobile/assets/`**.
2. **Gere os assets:**
   ```sh
   docker compose exec mobile npx capacitor-assets generate
   ```

---

## 📚 Recursos

- [Documentação do Ionic](https://ionicframework.com/docs)
- [Documentação do Vue 3](https://vuejs.org/guide/)
- [Documentação do Capacitor](https://capacitorjs.com/docs)
- [Capacitor Local Notifications](https://capacitorjs.com/docs/apis/local-notifications)