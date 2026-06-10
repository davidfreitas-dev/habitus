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

## 🚀 Configuração Inicial (Docker)

O uso de Docker é o método recomendado para desenvolvimento, garantindo que todas as dependências (Node, Ionic CLI) estejam configuradas corretamente.

### Pré-requisitos
- Docker
- Docker Compose

### Instalação e Execução

1. **Clone o repositório e acesse a raiz:**
   ```sh
   git clone <repository-url>
   cd habits
   ```

2. **Configure as variáveis de ambiente:**
   Crie o arquivo `.env` na raiz do projeto (se ainda não existir) e o `.env` na pasta `mobile/`:
   ```sh
   cp mobile/.env.example mobile/.env
   ```

3. **Inicie os containers:**
   ```sh
   docker compose up -d --build
   ```
   O container `mobile` instalará automaticamente as dependências (`npm install`) durante o build.

4. **Acesse o App:**
   Abra `http://localhost:8100` no seu navegador.

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