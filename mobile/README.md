# 🌿 Habitus — Mobile App

Aplicativo para rastrear e gerenciar seus hábitos diários, construído com Ionic 8, Capacitor 8 e Vue 3 (Composition API).

---

## 📌 Índice

- [🌟 Tecnologias](#-tecnologias)
- [🚀 Configuração do Ambiente de Desenvolvimento](#-configuração-do-ambiente-de-desenvolvimento)
- [🛠️ Comandos de Uso Comum (Docker)](#️-comandos-de-uso-comum-docker)
- [🤖 Compilação e Builds Nativos](#-compilação-e-builds-nativos)
    - [Instalando Node.js via NVM (macOS)](#instalando-nodejs-via-nvm-macos)
    - [Configuração para Android](#configuração-para-android)
    - [Configuração para iOS](#configuração-para-ios)
- [🖼️ Gerando Assets (Ícones e Splash Screen)](#️-gerando-assets-ícones-e-splash-screen)
- [🏗️ Arquitetura do Projeto](#️-arquitetura-do-projeto)
- [📚 Recursos Úteis](#-recursos-úteis)

---

## 🌟 Tecnologias

- **[Ionic Framework](https://ionicframework.com/)** — Interface e componentes híbridos
- **[Vue 3](https://vuejs.org/)** — Framework JS com Composition API
- **[Capacitor](https://capacitorjs.com/)** — Runtime de integração nativa (iOS/Android)
- **[Docker](https://www.docker.com/)** — Ambiente isolado para desenvolvimento e builders

---

## 🚀 Configuração do Ambiente de Desenvolvimento

> **Pré-requisito:** Este projeto faz parte de um monorepo e depende da infraestrutura Docker definida no projeto `/api`. Antes de continuar, **siga completamente o passo a passo de configuração do [`/api`](../api/README.md)** (clone do repositório, criação do `.env` raiz, inicialização dos containers e importação do banco de dados).

Após concluir o setup do `/api` e com os containers já em execução (`docker compose up -d`), configure o ambiente mobile:

### 1. Configurar variáveis de ambiente do Mobile
Crie o arquivo `.env` específico do mobile a partir do exemplo:
```sh
cp mobile/.env.example mobile/.env
```
Preencha as variáveis conforme necessário (ex: `VITE_API_URL`).

### 2. Inicializar o Container do Mobile
Caso o container `mobile` ainda não tenha sido iniciado com o build completo:
```sh
docker compose up -d --build mobile
```
O container instalará as dependências do npm (`npm install`) e iniciará o servidor de desenvolvimento automaticamente.

### 3. Acessar o App no Navegador
Abra `http://mobile.localhost` ou `http://localhost:8100` no seu navegador.

---

## 🛠️ Comandos de Uso Comum (Docker)

Sempre execute os comandos a partir da raiz do monorepo.

### Ver Logs do Servidor de Desenvolvimento
```sh
docker compose logs -f mobile
```

### Instalar dependências adicionais no Container
```sh
docker compose exec mobile npm install <nome-do-pacote>
```

### Rodar testes unitários e linting
```sh
docker compose exec mobile npm run test:unit
docker compose exec mobile npm run lint
```

---

## 🤖 Compilação e Builds Nativos

> Os builds nativos (Android/iOS) rodam **fora do Docker**, diretamente na sua máquina — por isso é necessário ter o Node.js instalado localmente, além das ferramentas nativas de cada plataforma.

#### Instalando Node.js via NVM (macOS)

O projeto requer **Node.js v22+**. A forma recomendada de instalar e gerenciar versões do Node no macOS é através do [NVM](https://github.com/nvm-sh/nvm) (Node Version Manager).

1. **Instale o NVM:**
   - Via Homebrew (recomendado):
     ```sh
     brew install nvm
     ```
     Após a instalação, o Homebrew exibirá instruções para criar o diretório `~/.nvm` e adicionar o script de inicialização ao seu shell. Adicione ao seu `.zshrc` (ou `.bashrc`):
     ```sh
     export NVM_DIR="$HOME/.nvm"
     [ -s "/opt/homebrew/opt/nvm/nvm.sh" ] && \. "/opt/homebrew/opt/nvm/nvm.sh"
     [ -s "/opt/homebrew/opt/nvm/etc/bash_completion.d/nvm" ] && \. "/opt/homebrew/opt/nvm/etc/bash_completion.d/nvm"
     ```
     *(Em Macs Intel, o caminho do Homebrew costuma ser `/usr/local/opt/nvm` em vez de `/opt/homebrew/opt/nvm`.)*
   - Alternativa (script oficial de instalação):
     ```sh
     curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
     ```
2. **Recarregue o terminal** (ou abra uma nova aba) e confirme a instalação:
   ```sh
   command -v nvm
   ```
3. **Instale e utilize o Node.js v22:**
   ```sh
   nvm install 22
   nvm use 22
   nvm alias default 22
   ```
4. **Confirme as versões instaladas:**
   ```sh
   node -v   # deve exibir v22.x.x
   npm -v
   ```

#### Configuração para Android
 
1. **Instale o Node.js**: Certifique-se de ter o Node.js **v22+** instalado localmente.
2. **Instale o Java JDK 21**:
   - No macOS (via Homebrew): `brew install openjdk@21`
   Após a instalação, o Homebrew exibirá instruções para adicionar o script de inicialização ao seu shell. Adicione ao seu `.zshrc` (ou `.bashrc`).
    ```sh
     export PATH="/opt/homebrew/opt/openjdk@21/bin:$PATH"
    ```
   - Configure o `JAVA_HOME` no seu arquivo de configuração de shell (`.zshrc` ou `.    bashrc`):
    ```sh
     export JAVA_HOME=$(/usr/libexec/java_home -v 21)
     export PATH=$JAVA_HOME/bin:$PATH
    ```
    - O `openjdk@21` do Homebrew é `keg-only` — ou seja, ele não se registra automaticamente no sistema (`/usr/libexec/java_home` não o enxerga sozinho). É preciso criar um symlink manual:
    ```sh
     sudo ln -sfn $(brew --prefix openjdk@21)/libexec/openjdk.jdk /Library/Java/JavaVirtualMachines/openjdk-21.jdk
    ```
3. **Instale o Android Studio e SDK**:
   - Baixe e instale o [Android Studio](https://developer.android.com/studio).
   - Abra o SDK Manager do Android Studio e instale a versão correspondente (Android 16 / API 36), incluindo os pacotes **Android SDK Build-Tools 36.x**, **Android SDK Platform-Tools** e **Android SDK Command-line Tools**.
   - Certifique-se de usar **Android Studio Meerkat (2024.3.1) ou superior** e o **Android Gradle Plugin (AGP) 8.9.0 ou superior** — versões mais antigas não são compatíveis com o SDK do Android 16.
   - Adicione as variáveis do Android SDK ao seu perfil de shell:
    ```sh
     export ANDROID_HOME=$HOME/Library/Android/sdk
     export PATH=$PATH:$ANDROID_HOME/emulator
     export PATH=$PATH:$ANDROID_HOME/platform-tools
     export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin
    ```
   - Aceite as licenças do SDK (necessário para o Gradle compilar sem erros):
    ```sh
     sdkmanager --licenses
    ```
 
4. **Adicione a plataforma Android ao projeto** (necessário apenas na primeira vez, caso a pasta `android/` ainda não exista):
```sh
   npx cap add android
```

5. **Configurar Deep Links e Assets (Essencial caso a pasta Android seja recriada)**:
Para garantir que as configurações de Deep Link (Intent Filters no `AndroidManifest.xml`) e os ícones nativos sejam injetados corretamente toda vez que a plataforma for adicionada ou recriada do zero, execute:
```sh
   npm run android:setup
```
*(Este script programático faz o patch dos Intent Filters do Deep Link no manifest e gera as resoluções de ícones e splash screens automáticos).*

6. **Sincronize e Compile**:
```sh
   npm install
   npm run build
   npx cap sync android
   cd android && ./gradlew assembleDebug
```
7. **Instale e execute em um emulador ou dispositivo** (opcional, para testar o build gerado):
   - Crie um emulador pelo *Device Manager* do Android Studio (ou use um dispositivo físico com depuração USB habilitada), então:
```sh
     npx cap run android
```
   - Ou instale o APK gerado manualmente:
```sh
     adb install android/app/build/outputs/apk/debug/app-debug.apk
```

#### Configuração para iOS

*Nota: Requer obrigatoriamente um computador executando macOS.*

1. **Instale o Node.js**: Certifique-se de ter o Node.js **v22+** instalado localmente.
2. **Instale o Xcode**:
   - Instale o [Xcode](https://developer.apple.com/xcode/) através da Mac App Store.
   - Abra o Xcode para aceitar os termos de uso e instalar componentes adicionais, ou aceite via terminal:
     ```sh
     sudo xcodebuild -license accept
     sudo xcodebuild -runFirstLaunch
     ```
   - Instale as ferramentas de linha de comando:
     ```sh
     xcode-select --install
     ```
3. **Instale o CocoaPods**:
   - Recomendado (via Homebrew): `brew install cocoapods`
   - Alternativo (via Ruby): `sudo gem install cocoapods`
4. **Sincronize e Compile**:
   ```sh
   npm install
   npm run build
   npx cap add ios
   npx cap sync ios
   ```
   > O `cap sync` já executa o `pod install` automaticamente. Caso ocorra algum erro relacionado a Pods, rode manualmente:
   > ```sh
   > cd ios/App && pod install --repo-update
   > ```
5. **Abrir e Executar**:
   Abra o projeto no Xcode para emulação ou assinatura:
   ```sh
   npx cap open ios
   ```
   *(No Xcode, selecione o emulador desejado e clique no botão Play/Run para compilar e rodar).*

   > ⚠️ **Para rodar em um dispositivo físico** (não no simulador), é necessário configurar uma **Apple ID/Team de desenvolvimento** em *Xcode → Signing & Capabilities* e ter uma conta na [Apple Developer Program](https://developer.apple.com/) (gratuita para testes locais, paga para distribuição).

---

### Sincronizar e Abrir Projetos Natividade (Interface Gráfica)

Você também pode abrir os projetos diretamente no Android Studio ou Xcode locais para depuração visual ou emulação:

#### Sincronizar Assets
```sh
# Sincroniza os builds web locais com as pastas nativas
npx cap sync
```

#### Abrir nos IDEs
```sh
npx cap open ios
npx cap open android
```

---

## 🖼️ Gerando Assets (Ícones e Splash Screen)

Os assets gráficos do aplicativo móvel são gerados a partir da pasta `mobile/assets/`.

1. **Prepare as imagens fonte** na pasta `mobile/assets/`.
2. **Gere os assets nativos:**
   ```sh
   docker compose exec mobile npx capacitor-assets generate
   ```

---

## 🏗️ Arquitetura do Projeto

O projeto segue uma estrutura de pastas organizada por camadas (Views -> Stores -> Services -> API Client):

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

---

## 📚 Recursos Úteis

- [Documentação do Ionic Framework](https://ionicframework.com/docs)
- [Documentação do Vue 3](https://vuejs.org/guide/)
- [Documentação do Capacitor](https://capacitorjs.com/docs)
- [Capacitor Local Notifications](https://capacitorjs.com/docs/apis/local-notifications)