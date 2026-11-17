# Assets, Splash Screen & Builds Nativos

O Habitus possui scripts e automações para geração de assets gráficos e compatibilidade nativa com as versões mais recentes do Android e iOS.

---

## 1. Geração de Assets com `@capacitor/assets`

Todos os ícones de aplicativo e telas de splash screen são gerados automaticamente a partir dos arquivos base em `mobile/assets/`:
- `mobile/assets/icon.png` (1024x1024 px)
- `mobile/assets/splash.png` (2732x2732 px)
- `mobile/assets/ic_stat_habitus.png` (ícone monocromático de notificação com fundo transparente)

### Comando para gerar:
```bash
docker compose exec mobile npm run assets:generate
```
*(Executa a cópia do ícone de notificação para a pasta `res/drawable` e dispara `npx capacitor-assets generate`).*

---

## 2. Splash Screen no Android 12+ (SplashScreen API)

> [!IMPORTANT]
> No Android 12 ou superior, o sistema operacional controla a Splash Screen nativamente e **ignora** as propriedades de cor definidas no `capacitor.config.json` durante o arranque inicial.

A cor de fundo e o ícone animado são configurados em `android/app/src/main/res/values/styles.xml`:

```xml
<style name="AppTheme.NoActionBarLaunch" parent="Theme.SplashScreen">
    <item name="windowSplashScreenBackground">#C6FF34</item>
    <item name="windowSplashScreenAnimatedIcon">@mipmap/ic_launcher_round</item>
    <item name="postSplashScreenTheme">@style/AppTheme.NoActionBar</item>
</style>
```

Para aplicar modificações de estilo e manifesto:
```bash
docker compose exec mobile npm run patch:android
```
*(Executa os scripts `scripts/patch-android-manifest.js` e `scripts/patch-android-styles.js`).*

---

## 3. Checklist de Build & Lojas (Release)

### Android (Google Play Store):
1. **Compilação Web e Sincronização:**
   ```bash
   docker compose exec mobile npm run build
   npx cap sync android
   ```
2. **Declaração de AD_ID no Google Play Console:**
   - O aplicativo usa `@capgo/capacitor-social-login` (Google Sign-In), que inclui permissão de Advertising ID.
   - Na Google Play Console, em *Conteúdo do app → ID de publicidade*, marque **Sim** e selecione *Análise / Prevenção de Fraude e Segurança*.
3. **Gerar Bundle (AAB):**
   - Abra o Android Studio (`npx cap open android`) e gere o Signed App Bundle via *Build → Generate Signed Bundle / APK*.

### iOS (App Store):
1. **Compilação Web e Sincronização:**
   ```bash
   docker compose exec mobile npm run build
   npx cap sync ios
   ```
2. **Assinatura e Capabilities no Xcode:**
   - Abra o Xcode (`npx cap open ios`).
   - Em *Signing & Capabilities*, selecione sua equipe de desenvolvedor (Team ID).
   - Verifique se as capabilities necessárias (Push/Notifications, Associated Domains) estão ativas.
