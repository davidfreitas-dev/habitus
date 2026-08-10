# Guia de Implementação — Social Login + GTM + AdMob (Habitus)

Stack: Vue 3 + Ionic + Capacitor (JS, sem TypeScript) · Backend: PHP 8.4 + Slim 4 (Clean Architecture)

Ordem recomendada: **1) Social Login → 2) GTM → 3) AdMob**
Motivo: login é a base de tudo (você quer rastrear eventos de usuários autenticados no GTM, e o AdMob deve vir por último por ser o mais invasivo/sensível a políticas de loja).

---

## 1. Social Login (`@capgo/capacitor-social-login`)

### 1.1 Instalação

```bash
npm install @capgo/capacitor-social-login
npx cap sync
```

### 1.2 Configuração Google

**Android** — `android/app/src/main/AndroidManifest.xml`, dentro de `<application>`:
```xml
<meta-data
  android:name="com.google.android.gms.wallet.api.enabled"
  android:value="false" />
```
Registre o app no [Google Cloud Console](https://console.cloud.google.com/) (OAuth 2.0 Client ID tipo Android, com o SHA-1 do seu keystore) e outro Client ID tipo Web (para obter o `idToken`, que é o que seu backend vai validar).

### 1.3 Código no app (Vue 3, Composition API, sem TS)

```js
// src/composables/useSocialAuth.js
import { SocialLogin } from '@capgo/capacitor-social-login'

export async function initSocialLogin() {
  await SocialLogin.initialize({
    google: {
      webClientId: 'SEU_WEB_CLIENT_ID.apps.googleusercontent.com',
    }
  })
}

export async function loginWithGoogle() {
  const result = await SocialLogin.login({
    provider: 'google',
    options: {},
  })
  // result.result.idToken -> mandar pro backend
  return result.result.idToken
}
```

Chame `initSocialLogin()` uma vez no boot do app (ex: `main.js` ou num hook do Ionic `onMounted` da tela inicial).

### 1.4 Backend PHP/Slim — validação do token (Clean Architecture)

Estrutura alinhada com a sua arquitetura atual em `api/src`:

```
src/
  Domain/
    Enum/
      SocialProvider.php          (enum: GOOGLE)
  Application/
    UseCase/
      AuthenticateWithSocialProviderUseCase.php
    DTO/
      SocialLoginRequestDTO.php
      AuthTokenResponseDTO.php
    Exception/
      InvalidSocialTokenException.php
  Infrastructure/
    Security/
      GoogleTokenVerifier.php     (usa google/apiclient ou verificação via JWK)
  Presentation/
    Api/
      V1/
        Controller/
          AuthController.php      (adicionar método socialLogin)
```

**Validação do Google** (sem precisar do Firebase Admin SDK completo):

```bash
composer require google/apiclient:^2.0
```

```php
// Infrastructure/Security/GoogleTokenVerifier.php
namespace App\Infrastructure\Security;

use Google_Client;
use App\Application\Exception\InvalidSocialTokenException;

final class GoogleTokenVerifier
{
    public function __construct(private readonly string $webClientId) {}

    public function verify(string $idToken): array
    {
        $client = new Google_Client(['client_id' => $this->webClientId]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            throw new InvalidSocialTokenException();
        }

        return [
            'providerId' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? null,
        ];
    }
}
```

**Use case** (orquestra: valida token → busca/cria usuário → gera sua própria sessão/JWT):

```php
// Application/UseCase/AuthenticateWithSocialProviderUseCase.php
namespace App\Application\UseCase;

use App\Application\DTO\SocialLoginRequestDTO;
use App\Application\DTO\AuthTokenResponseDTO;
use App\Infrastructure\Security\GoogleTokenVerifier;
use App\Domain\Repository\UserRepositoryInterface;
// Importe seu gerador de sessão atual aqui (ex: JwtTokenGenerator)

final class AuthenticateWithSocialProviderUseCase
{
    public function __construct(
        private readonly GoogleTokenVerifier $googleVerifier,
        private readonly UserRepositoryInterface $userRepository,
        private readonly $sessionTokenGenerator, // Substitua pelo tipo correto
    ) {}

    public function execute(SocialLoginRequestDTO $dto): AuthTokenResponseDTO
    {
        $identity = match ($dto->provider) {
            'google' => $this->googleVerifier->verify($dto->idToken),
            default => throw new \InvalidArgumentException('Provider não suportado'),
        };

        $user = $this->userRepository->findByEmail($identity['email'])
            ?? $this->userRepository->createFromSocial($identity, $dto->provider);

        $token = $this->sessionTokenGenerator->generateFor($user);

        return new AuthTokenResponseDTO($token, $user);
    }
}
```

Rota (`routes.php` ou arquivo de rotas v1):
```php
$app->post('/v1/auth/social/{provider}', [\App\Presentation\Api\V1\Controller\AuthController::class, 'socialLogin']);
```

> Isso mantém sua auth 100% no seu domínio — o token social só serve pra provar identidade, sua sessão continua sendo a mesma lógica que você já usa no Kinature/`authStore.ts`, agora reaproveitável.

---

## 2. Google Tag Manager (`@capgo/capacitor-gtm`)

### 2.1 Instalação

```bash
npm install @capgo/capacitor-gtm
npx cap sync
```

### 2.2 Configuração

Crie o container no [GTM](https://tagmanager.google.com/), tipo "Android".

**Android** — o container ID e config vêm do `google-services.json`, se você também usar Firebase Analytics como destino; senão o plugin usa o `containerId` diretamente.

### 2.3 Código no app

```js
// src/composables/useAnalytics.js
import { GoogleTagManager } from '@capgo/capacitor-gtm'

export async function initGTM() {
  await GoogleTagManager.initialize({ containerId: 'GTM-XXXXXX', timeout: 2000 })
}

export async function trackEvent(eventName, params = {}) {
  await GoogleTagManager.push({ event: eventName, parameters: params })
}

export async function setUserProperty(key, value) {
  await GoogleTagManager.setUserProperty({ key, value })
}
```

Uso após login bem-sucedido:
```js
await setUserProperty('user_id', user.id)
await trackEvent('login', { method: provider }) // provider = 'google'
```

Eventos sugeridos pro Habitus: `habit_created`, `habit_completed`, `streak_milestone`, `onboarding_step_completed` (você já tem o onboarding de 8 passos mapeado — dá pra rastrear cada `v-onboarding` step).

---

## 3. AdMob (`@capgo/capacitor-admob`)

### 3.1 Instalação

```bash
npm install @capgo/capacitor-admob
npx cap sync
```

### 3.2 Configuração nativa

**Android** — `AndroidManifest.xml`, dentro de `<application>`:
```xml
<meta-data
  android:name="com.google.android.gms.ads.APPLICATION_ID"
  android:value="ca-app-pub-XXXXXXXXXXXXXXXX~YYYYYYYYYY" />
```

### 3.3 Consentimento e privacidade (obrigatório, especialmente GDPR/UE)

```js
import { AdMob } from '@capgo/capacitor-admob'

await AdMob.start()
await AdMob.configRequest({
  maxAdContentRating: 'PG',
  tagForChildDirectedTreatment: false,
})
```

### 3.4 Código no app

```js
// src/composables/useAds.js
import { AdMob } from '@capgo/capacitor-admob'

export async function initAds() {
  await AdMob.start()
}

export async function showInterstitial() {
  const adUnitId = 'ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY' // ID do Android
  await AdMob.adCreate({ adUnitId })
  await AdMob.adLoad({ adUnitId })
  await AdMob.adShow({ adUnitId })
}
```

Sugestão pro Habitus: interstitial ao completar uma sequência de hábitos (não no meio de um fluxo crítico como registro de progresso — isso irrita o usuário e o Google penaliza UX ruim de ads).

---

## 4. Ordem de execução recomendada (checklist)

1. [ ] Implementar e testar Social Login (Google)
2. [ ] Criar endpoint `/auth/social/{provider}` no Slim, com o verifier
3. [ ] Adicionar GTM, disparar evento de login como primeiro teste real
4. [ ] Mapear os eventos principais do app (onboarding, criação de hábito, conclusão)
5. [ ] Adicionar AdMob por último, com IDs de teste do Google primeiro (`ca-app-pub-3940256099942544/...`)
6. [ ] Trocar IDs de teste por IDs de produção só na build de release

## 5. Observação importante

Este guia foi montado com base em documentação pública dos plugins Capgo (situação de ago/2026). Antes de ir pra produção, vale conferir a versão mais recente de cada plugin no GitHub (`Cap-go/capacitor-social-login`, `Cap-go/capacitor-gtm`, `Cap-go/capacitor-admob`), já que a major version deles segue a major version do Capacitor instalado no seu projeto.
