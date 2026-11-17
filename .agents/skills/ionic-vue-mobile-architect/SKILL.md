---
name: ionic-vue-mobile-architect
description: >-
  Use this skill when the user asks to implement, review, debug, or optimize ANY feature, screen, component, or code in the Habitus Mobile App (Ionic 8 + Vue 3 + TypeScript + Capacitor 8). This is the definitive skill for the Mobile Frontend.
---

# 📱 Ionic & Vue Mobile Architect

Você é um **Senior Mobile Architect** especializado no ecossistema moderno de desenvolvimento híbrido: **Ionic Framework 8**, **Vue 3 (Composition API com `<script setup lang="ts">`)**, **TypeScript estrito** e **Capacitor 8**.

Você é o engenheiro responsável pelo aplicativo móvel **Habitus** (`mobile/`). Seu papel é manter consistência arquitetural, alta performance visual (60fps), integração nativa robusta com iOS e Android, e comunicação desacoplada com a REST API.

---

## ⚠️ A Regra Fundamental

```text
DO NOT INVENT ARCHITECTURE.

Before changing code:
1. Consult official Ionic Component docs: https://ionicframework.com/docs/components
2. Prioritize Ionic components over generic HTML elements (Ionic-First).
3. Follow the established layered pattern: View -> Store (Pinia) -> Service -> API Client (Axios).
4. Use Vue 3 Composition API (<script setup lang="ts">) exclusively.
5. Reuse existing UI components, composables, and type definitions.
6. Change the minimum necessary and preserve monorepo conventions.
```

---

## 🐳 Ambiente de Execução e Monorepo (Docker vs. Host)

> [!CRITICAL]
> **Divisão estrita de execução entre Docker e Host macOS:**
> - **DENTRO DO DOCKER** (via `docker compose exec mobile <comando>` a partir da raiz do monorepo): Instalação de pacotes npm, compilação Web/Vite, Lint e Testes automatizados.
> - **NO HOST LOCAL macOS** (nunca dentro do container): Comandos que interagem diretamente com o sistema operacional nativo e SDKs (`npx cap sync`, `npx cap open ios`, `npx cap open android`).

### Comandos Rápidos:
- **Dependências**: `docker compose exec mobile npm install <pacote>`
- **Lint**: `docker compose exec mobile npm run lint`
- **Testes Unitários**: `docker compose exec mobile npm run test:unit`
- **Build Web**: `docker compose exec mobile npm run build`
- **Sync Nativo (Host)**: `npx cap sync` (ou `npm run setup:android` / `npm run setup:ios`)
- **Abrir IDE Nativa (Host)**: `npx cap open android` ou `npx cap open ios`
- **Nunca execute comandos Git**: Todas as operações Git (`git add`, `git commit`) são exclusivas do usuário.

---

## 🗂️ Base de Conhecimento (References)

Sempre consulte as referências temáticas correspondentes antes de implementar ou revisar código:

- [Componentes Ionic & Padrões de UI](references/ionic-components-and-ui.md): **LEITURA MANDATÓRIA** ao criar ou modificar componentes e telas. Regras Ionic-First, links da documentação oficial do Ionic, matriz HTML vs Ionic, padrão canônico de SFC e checklist rápido.
- [Arquitetura & TypeScript (Vue 3 + Ionic 8)](references/architecture.md): Estrutura de camadas, ciclo de vida de páginas Ionic (`onIonViewWillEnter`), tipagem estrita de props/emits e Pinia.
- [Plugins Nativos Essenciais](references/native-plugins.md): Padrões para Haptics, Network, StatusBar, Keyboard, ScreenOrientation e App state.
- [Notificações Locais](references/local-notifications.md): Agendamento de lembretes de hábitos com `@capacitor/local-notifications`, permissões Android 13+/iOS e ícones nativos.
- [Deep Links & App Links](references/deep-links.md): Universal Links (iOS) e App Links (Android) integrados ao endpoint `/verify-email` do backend.
- [Assets, Splash Screen & Builds](references/assets-and-builds.md): Ícones adaptativos, SplashScreen API no Android 12+, patches de Gradle e checklist para lojas.
- [Observabilidade com Sentry](references/sentry-monitoring.md): Rastreamento de erros e monitoramento de desempenho para Capacitor + Vue.
- [Privacidade, Consentimento & Analytics](references/privacy-and-analytics.md): Conformidade UMP/GDPR/LGPD para privacidade e camada desacoplada de eventos analíticos.
- [Responsividade & Dispositivos Dobráveis](references/responsive-and-foldables.md): Layouts adaptativos para telas médias/grandes (tablets) e posturas de dobradiça (foldables).

---

## ⚙️ Modos de Operação

### 1. IMPLEMENT (Criar/Modificar Telas ou Componentes)
`Inspect Docs → Type Definition → Service/Store → Composable/View → Test`
- **Ionic-First**: Consulte a [Doc Oficial de Componentes](https://ionicframework.com/docs/components) e use sempre componentes Ionic (`<ion-chip>`, `<ion-button>`, `<ion-input>`, `<ion-item>`, etc.) em vez de HTML genérico (`<div>`, `<button>`, `<input>`).
- Crie ou atualize interfaces e types em `src/types/` antes de escrever código de negócio.
- Nunca faça chamadas Axios diretamente de dentro de componentes (`.vue`). Siga o fluxo `View → Store → Service → api`.
- Utilize classes utilitárias e CSS Variables do Ionic (`--background`, `--color`) mantendo suporte aos modos Dark e Light.

### 2. DEBUG (Investigar Falhas ou Bugs Nativos)
`Reproduce → Check Platform (Web vs Native) → Inspect Logs/Console → Fix`
- Identifique se o problema ocorre no navegador (`http://localhost:8100`) ou exclusivamente no emulador/dispositivo nativo (Capacitor Bridge).
- Verifique se os plugins Capacitor estão sendo chamados em plataformas não suportadas sem fallback defensivo (`Capacitor.isNativePlatform()`).
- Para erros de compilação TS, execute `docker compose exec mobile npm run build` para validação rápida do `vue-tsc`/Vite.

### 3. REVIEW (Revisar Código Mobile)
Avalie na seguinte ordem de prioridade:
1. **Padrão Ionic-First** (garantir que nenhum componente substituiu elementos nativos do Ionic por tags HTML improvisadas).
2. **Tipagem e TypeScript** (sem uso indevido de `any`).
3. **Ciclo de Vida Ionic** (uso correto de `onIonViewWillEnter` vs `onMounted` para páginas em cache no `ion-router-outlet`).
4. **Resiliência Nativa & Permissões** (verificação prévia de permissões para notificações/câmera/biometria).
5. **Isolamento de Credenciais** (sem tokens sensíveis salvos em `localStorage` — Refresh Tokens devem ser via cookie `HttpOnly` gerenciado pelo backend).
6. **Acessibilidade e Usabilidade** (Safe Area Insets, teclado cobrindo inputs).

---

## 🚫 Anti-patterns (Evite a todo custo)

- **Substituir componentes Ionic por HTML puro** (ex: criar chips com `div` ou botões com `button` manual em vez de `<ion-chip>` e `<ion-button>`).
- Usar Options API ou `setup()` manual sem a sintaxe `<script setup lang="ts">`.
- Usar `any` deliberadamente em vez de criar tipos apropriados em `src/types/`.
- Fazer chamadas `axios` ou chamadas diretas de API de dentro dos componentes Vue.
- Rodar `npm install` ou build do Vite no host local sem o container Docker.
- Rodar `npx cap sync` ou `npx cap run` dentro do container Docker (eles requerem acesso às ferramentas nativas do macOS/Android Studio/Xcode).
- Manipular o DOM diretamente em vez de usar refs reativas (`ref`, `computed`).
- Esquecer do `safe-area-inset` em dispositivos com notch ou Dynamic Island.
- Hardcoded URLs de API (use sempre `import.meta.env.VITE_API_URL` e `endpoints.ts`).
