import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const packageJsonPath = path.resolve(__dirname, '../package.json');
const buildGradlePath = path.resolve(__dirname, '../android/app/build.gradle');
const proguardRulesPath = path.resolve(__dirname, '../android/app/proguard-rules.pro');
const mainActivityPath = path.resolve(
  __dirname,
  '../android/app/src/main/java/br/dev/davidfreitas/habitus/MainActivity.java'
);

if (!fs.existsSync(buildGradlePath)) {
  console.log('android/app/build.gradle não encontrado. Certifique-se de que a plataforma Android foi adicionada.');
  process.exit(0);
}

try {
  const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
  const versionName = pkg.version || '0.0.1';
  const [major = 0, minor = 0, patch = 1] = versionName.split('.').map((n) => parseInt(n, 10) || 0);
  const computedVersionCode = major * 10000 + minor * 100 + patch;

  let gradleContent = fs.readFileSync(buildGradlePath, 'utf8');
  let updatedGradle = false;

  // 1. Sincroniza versionCode e versionName a partir do package.json
  const versionCodeMatch = gradleContent.match(/versionCode\s+(\d+)/);
  const currentVersionCode = versionCodeMatch ? parseInt(versionCodeMatch[1], 10) : 1;
  const targetVersionCode = Math.max(currentVersionCode, computedVersionCode);

  const afterVersionCode = gradleContent.replace(/versionCode\s+\d+/, `versionCode ${targetVersionCode}`);
  const afterVersionName = afterVersionCode.replace(/versionName\s+"[^"]+"/, `versionName "${versionName}"`);

  if (afterVersionName !== gradleContent) {
    gradleContent = afterVersionName;
    updatedGradle = true;
  }

  // 2. Habilita R8 (minifyEnabled + shrinkResources + proguard-android-optimize.txt + debugSymbolLevel)
  const releaseBlockPattern = /buildTypes\s*\{\s*release\s*\{[\s\S]*?\}\s*\}/;
  const optimizedReleaseBlock = `buildTypes {
        release {
            minifyEnabled true
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
            ndk {
                debugSymbolLevel 'FULL'
            }
        }
    }`;

  if (
    !gradleContent.includes('minifyEnabled true') ||
    !gradleContent.includes('shrinkResources true') ||
    !gradleContent.includes('proguard-android-optimize.txt')
  ) {
    if (releaseBlockPattern.test(gradleContent)) {
      gradleContent = gradleContent.replace(releaseBlockPattern, optimizedReleaseBlock);
      updatedGradle = true;
    } else {
      console.error('Erro: Não foi possível localizar o bloco buildTypes.release no android/app/build.gradle.');
    }
  }

  // 3. Garante dependência explícita do androidx.activity para EdgeToEdge.enable(this)
  if (!gradleContent.includes('androidx.activity:activity:')) {
    gradleContent = gradleContent.replace(
      'implementation "androidx.appcompat:appcompat:$androidxAppCompatVersion"',
      'implementation "androidx.activity:activity:$androidxActivityVersion"\n    implementation "androidx.appcompat:appcompat:$androidxAppCompatVersion"'
    );
    updatedGradle = true;
  }

  // 4. Exclui androidbrowserhelper (usado apenas pelo AppleProvider não utilizado e que chama APIs descontinuadas do Android 15)
  if (!gradleContent.includes('module: \'androidbrowserhelper\'')) {
    const excludeBlock = '\nconfigurations.configureEach {\n    exclude group: \'com.google.androidbrowserhelper\', module: \'androidbrowserhelper\'\n}\n';
    gradleContent = gradleContent.replace(/\nrepositories\s*\{/, `${excludeBlock}\nrepositories {`);
    updatedGradle = true;
  }

  if (updatedGradle) {
    fs.writeFileSync(buildGradlePath, gradleContent, 'utf8');
    console.log(`✓ android/app/build.gradle atualizado (v${versionName} [${targetVersionCode}], R8/ProGuard e EdgeToEdge ativados).`);
  } else {
    console.log('✓ android/app/build.gradle já está com R8/ProGuard, versão e exclusões configurados.');
  }

  // 5. Garante regras do ProGuard (SourceFile, LineNumberTable e dontwarn androidbrowserhelper)
  if (fs.existsSync(proguardRulesPath)) {
    let proguardContent = fs.readFileSync(proguardRulesPath, 'utf8');
    let updatedProguard = false;

    if (proguardContent.includes('#-keepattributes SourceFile,LineNumberTable')) {
      proguardContent = proguardContent.replace(
        '#-keepattributes SourceFile,LineNumberTable',
        '-keepattributes SourceFile,LineNumberTable'
      );
      updatedProguard = true;
    } else if (!proguardContent.includes('-keepattributes SourceFile,LineNumberTable')) {
      proguardContent += '\n-keepattributes SourceFile,LineNumberTable\n';
      updatedProguard = true;
    }

    if (proguardContent.includes('#-renamesourcefileattribute SourceFile')) {
      proguardContent = proguardContent.replace(
        '#-renamesourcefileattribute SourceFile',
        '-renamesourcefileattribute SourceFile'
      );
      updatedProguard = true;
    } else if (!proguardContent.includes('-renamesourcefileattribute SourceFile')) {
      proguardContent += '-renamesourcefileattribute SourceFile\n';
      updatedProguard = true;
    }

    if (!proguardContent.includes('-dontwarn com.google.androidbrowserhelper.**')) {
      proguardContent += '\n# Ignora referências do AppleProvider ao androidbrowserhelper excluído\n-dontwarn com.google.androidbrowserhelper.**\n';
      updatedProguard = true;
    }

    if (updatedProguard) {
      fs.writeFileSync(proguardRulesPath, proguardContent, 'utf8');
      console.log('✓ android/app/proguard-rules.pro atualizado.');
    } else {
      console.log('✓ android/app/proguard-rules.pro já está configurado.');
    }
  }

  // 6. Habilita EdgeToEdge.enable(this) na MainActivity.java para compatibilidade ponta a ponta (Android 15+)
  if (fs.existsSync(mainActivityPath)) {
    let mainActivityContent = fs.readFileSync(mainActivityPath, 'utf8');
    const edgeToEdgeMainActivity = `package br.dev.davidfreitas.habitus;

import android.os.Bundle;
import androidx.activity.EdgeToEdge;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
    }
}
`;
    if (mainActivityContent !== edgeToEdgeMainActivity) {
      fs.writeFileSync(mainActivityPath, edgeToEdgeMainActivity, 'utf8');
      console.log('✓ MainActivity.java atualizada com EdgeToEdge.enable(this) após super.onCreate.');
    } else {
      console.log('✓ MainActivity.java já possui EdgeToEdge.enable(this) configurado.');
    }
  }
} catch (error) {
  console.error('Erro ao aplicar patch no Gradle/ProGuard/MainActivity:', error);
  process.exit(1);
}
