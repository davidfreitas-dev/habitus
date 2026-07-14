import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const manifestPath = path.resolve(__dirname, '../android/app/src/main/AndroidManifest.xml');

if (!fs.existsSync(manifestPath)) {
  console.log('AndroidManifest.xml não encontrado. Certifique-se de que a plataforma Android foi adicionada.');
  process.exit(0);
}

try {
  let manifestContent = fs.readFileSync(manifestPath, 'utf8');

  // Verifica se o intent-filter já foi aplicado
  if (manifestContent.includes('android:scheme="habits"')) {
    console.log('✓ Intent filters já configurados no AndroidManifest.xml.');
    process.exit(0);
  }

  // Intent filters a serem injetados
  const intentFilters = `
            <!-- Intent Filter para Deep Linking Custom Scheme (habits://) -->
            <intent-filter android:autoVerify="true">
                <action android:name="android.intent.action.VIEW" />
                <category android:name="android.intent.category.DEFAULT" />
                <category android:name="android.intent.category.BROWSABLE" />
                <data android:scheme="habits" />
            </intent-filter>

            <!-- Intent Filter para Android App Links (HTTPS) -->
            <intent-filter android:autoVerify="true">
                <action android:name="android.intent.action.VIEW" />
                <category android:name="android.intent.category.DEFAULT" />
                <category android:name="android.intent.category.BROWSABLE" />
                <data android:scheme="http" />
                <data android:scheme="https" />
                <data android:host="habits.davidfreitas.dev.br" android:pathPrefix="/verify-email" />
            </intent-filter>`;

  // Injeta logo após o intent-filter padrão do LAUNCHER
  const targetPattern = /<activity[\s\S]*?\.MainActivity[\s\S]*?>[\s\S]*?<\/intent-filter>/;

  if (targetPattern.test(manifestContent)) {
    manifestContent = manifestContent.replace(targetPattern, (match) => {
      return `${match}\n${intentFilters}`;
    });

    fs.writeFileSync(manifestPath, manifestContent, 'utf8');
    console.log('✓ AndroidManifest.xml atualizado com os Intent Filters do Deep Link.');
  } else {
    console.error('Erro: Não foi possível localizar a MainActivity no AndroidManifest.xml para aplicar o patch.');
  }
} catch (error) {
  console.error('Erro ao aplicar patch no AndroidManifest.xml:', error);
}
