import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const stylesPath = path.resolve(__dirname, '../android/app/src/main/res/values/styles.xml');

if (!fs.existsSync(stylesPath)) {
  console.log('styles.xml não encontrado. Certifique-se de que a plataforma Android foi adicionada.');
  process.exit(0);
}

try {
  let stylesContent = fs.readFileSync(stylesPath, 'utf8');

  if (stylesContent.includes('windowSplashScreenBackground')) {
    console.log('✓ Splash Screen Android 12+ já configurada no styles.xml.');
    process.exit(0);
  }

  const launchStylePattern = /<style name="AppTheme\.NoActionBarLaunch"[\s\S]*?<\/style>/;
  const customLaunchStyle = `<style name="AppTheme.NoActionBarLaunch" parent="Theme.SplashScreen">
        <item name="windowSplashScreenBackground">#C6FF34</item>
        <item name="postSplashScreenTheme">@style/AppTheme.NoActionBar</item>
    </style>`;

  if (launchStylePattern.test(stylesContent)) {
    stylesContent = stylesContent.replace(launchStylePattern, customLaunchStyle);
    fs.writeFileSync(stylesPath, stylesContent, 'utf8');
    console.log('✓ styles.xml atualizado com tema da Splash Screen Android 12+.');
  } else {
    console.error('Erro: Não foi possível localizar o estilo AppTheme.NoActionBarLaunch no styles.xml.');
  }
} catch (error) {
  console.error('Erro ao aplicar patch no styles.xml:', error);
}
