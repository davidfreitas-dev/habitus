# Guia de Configuração — Google Tag Manager (Mobile / Capacitor)

Este documento resume as etapas necessárias para configurar corretamente o Google Tag Manager (GTM) em aplicativos móveis usando o plugin `@capgo/capacitor-gtm`, com base na infraestrutura do Firebase Analytics.

## 💡 O Paradigma do GTM em Apps (Mobile vs Web)

Diferente do desenvolvimento Web, onde o GTM é responsável por capturar o evento e enviar para o Google Analytics, em aplicativos móveis (Android/iOS) a lógica é invertida:
- O plugin do GTM funciona em conjunto com o **Firebase Analytics**.
- Ao disparar um evento no código (ex: `trackEvent('login')`), o SDK do Firebase **já envia os dados automaticamente para o GA4**.
- O GTM no mobile age apenas como um **middleware (filtro)**. Você só precisa criar Tags/Acionadores no painel se quiser enviar dados para terceiros (ex: Pixel do Facebook) ou se quiser bloquear/modificar eventos antes de chegarem ao GA4.

Portanto, **não é necessário criar Tags para o GA4** no painel do GTM.

---

## Passo a Passo: Painel do GTM

### 1. Criar a Conta e o Container
1. Acesse o [Google Tag Manager](https://tagmanager.google.com/).
2. Clique em **Criar conta** e preencha os dados da empresa ou selecione uma conta já existente.
3. Na seção **Configuração do contêiner**:
   * Defina um nome (ex: `Habitus - Android`).
   * Em **Plataforma alvo**, selecione **Android** (se usar Firebase SDK, confirme).
4. Clique em **Criar** e aceite os termos.
5. Copie o seu ID do container (formato `GTM-XXXXXXX`), exibido no canto superior direito do painel.

### 2. Configurar a Variável de Ambiente
Com o ID do container em mãos, adicione-o ao seu arquivo `.env` na raiz do projeto `mobile/`:
```env
VITE_GTM_CONTAINER_ID=GTM-XXXXXXX
```
*(Isso garante que o seu `useAnalytics.js` consiga inicializar o GTM sem avisos).*

### 3. Publicar o Container (Importante!)
Como não precisamos de Tags para o GA4, você só precisa ativar o container recém-criado.
1. No painel do GTM, clique no botão azul **Enviar** (canto superior direito).
2. Dê um nome para a versão (ex: `v1 - Setup Inicial`).
3. Clique em **Publicar**.

### 4. Configuração Nativa (Evitando "Failed to load container")
Para que o GTM funcione corretamente no Android (via Firebase), você precisa de dois arquivos físicos no projeto:
1. **Container do GTM (`GTM-XXXXXX.json`)**:
   * Vá em "Versões" no GTM, clique nos três pontos da versão publicada e faça o download.
   * Coloque o arquivo em: `mobile/android/app/src/main/res/raw/`
   * **MUITO IMPORTANTE:** Renomeie o arquivo para letras minúsculas e troque o hífen por underline. (ex: de `GTM-KVV8QC78.json` para `gtm_kvv8qc78.json`). O Android não aceita letras maiúsculas ou hífens em pastas de recursos nativos.
2. **Configuração do Firebase (`google-services.json`)**:
   * Crie um projeto gratuito no [Firebase Console](https://console.firebase.google.com/) com Google Analytics ativado.
   * Adicione um aplicativo Android colocando seu Package Name (ex: `com.habitus.app`).
   * Baixe o arquivo `google-services.json` e coloque-o na raiz: `mobile/android/app/`
3. **Sincronize o projeto**: Execute `npx cap sync android` e recompile o app.

---

## Eventos Implementados no Código

A função `trackEvent` já foi implementada e distribuída pelas principais funcionalidades do app:

- [x] `login`: Disparado no `authStore.js` ao logar.
- [x] `habit_created`: Disparado no `habitsStore.js` ao salvar um novo hábito com sucesso.
- [x] `habit_completed`: Disparado no `habitsStore.js` (no toggle) quando o usuário marcar um hábito.
- [x] `streak_milestone`: Lógica inclusa no `habitsStore.js` (aguardando retorno da ofensiva pela API).
- [x] `onboarding_step_completed`: Disparado a cada clique de "Próximo" no `OnboardingStep.vue`.

### Como testar?
Rode o aplicativo, execute as ações (como criar hábito) e acompanhe o painel **Tempo Real (Realtime)** no Console do **Google Analytics 4** ou do **Firebase**. Os eventos devem aparecer lá automaticamente após alguns segundos.
