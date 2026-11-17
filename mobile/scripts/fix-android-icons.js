import fs from 'fs';
import path from 'path';
import sharp from 'sharp';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const resPath = path.resolve(__dirname, '../android/app/src/main/res');
const assetsPath = path.resolve(__dirname, '../assets');

const foregroundInput = path.join(assetsPath, 'icon-foreground.png');
const backgroundInput = path.join(assetsPath, 'icon-background.png');

if (!fs.existsSync(resPath)) {
  console.log('Diretório res do Android não encontrado. Pulando fix-android-icons.');
  process.exit(0);
}

/**
 * Densidades oficiais do Android para Adaptive Icons (108x108 dp)
 * Visível no launcher: 72x72 dp (66.67% central, 16.67% de safe-zone em cada lado)
 */
const ADAPTIVE_ICON_DENSITIES = [
  { density: 'ldpi', size: 81 },
  { density: 'mdpi', size: 108 },
  { density: 'hdpi', size: 162 },
  { density: 'xhdpi', size: 216 },
  { density: 'xxhdpi', size: 324 },
  { density: 'xxxhdpi', size: 432 },
];

/**
 * Densidades oficiais do Android 12+ SplashScreen API para ícone sem background (288x288 dp)
 * Círculo visível central: 192x192 dp (66.67% central, 48dp / 16.67% de máscara em cada lado)
 */
const SPLASH_ICON_DENSITIES = [
  { dir: 'drawable', size: 288 },
  { dir: 'drawable-ldpi', size: 216 },
  { dir: 'drawable-mdpi', size: 288 },
  { dir: 'drawable-hdpi', size: 432 },
  { dir: 'drawable-xhdpi', size: 576 },
  { dir: 'drawable-xxhdpi', size: 864 },
  { dir: 'drawable-xxxhdpi', size: 1152 },
];

async function renderPaddedForeground(inputPath, totalSize, outputPath) {
  const innerSize = Math.round(totalSize * (2 / 3));
  const padTopLeft = Math.floor((totalSize - innerSize) / 2);
  const padBottomRight = totalSize - innerSize - padTopLeft;

  const resizedInner = await sharp(inputPath)
    .resize(innerSize, innerSize, {
      fit: 'contain',
      kernel: sharp.kernel.lanczos3,
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .toBuffer();

  await sharp(resizedInner)
    .extend({
      top: padTopLeft,
      bottom: padBottomRight,
      left: padTopLeft,
      right: padBottomRight,
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png({ compressionLevel: 9 })
    .toFile(outputPath);
}

async function run() {
  try {
    // 1. Regerar Adaptive Icons (ic_launcher_foreground.png e ic_launcher_background.png) em 108dp
    for (const { density, size } of ADAPTIVE_ICON_DENSITIES) {
      const mipmapDir = path.join(resPath, `mipmap-${density}`);
      fs.mkdirSync(mipmapDir, { recursive: true });

      const fgDest = path.join(mipmapDir, 'ic_launcher_foreground.png');
      const bgDest = path.join(mipmapDir, 'ic_launcher_background.png');

      await renderPaddedForeground(foregroundInput, size, fgDest);

      await sharp(backgroundInput)
        .resize(size, size, { fit: 'cover', kernel: sharp.kernel.lanczos3 })
        .png({ compressionLevel: 9 })
        .toFile(bgDest);
    }

    // 2. Atualizar mipmap-anydpi-v26/ic_launcher.xml e ic_launcher_round.xml sem inset redundante
    const anydpiDir = path.join(resPath, 'mipmap-anydpi-v26');
    fs.mkdirSync(anydpiDir, { recursive: true });

    const adaptiveIconXml = `<?xml version="1.0" encoding="utf-8"?>
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@mipmap/ic_launcher_background" />
    <foreground android:drawable="@mipmap/ic_launcher_foreground" />
</adaptive-icon>
`;

    fs.writeFileSync(path.join(anydpiDir, 'ic_launcher.xml'), adaptiveIconXml, 'utf8');
    fs.writeFileSync(path.join(anydpiDir, 'ic_launcher_round.xml'), adaptiveIconXml, 'utf8');

    // 3. Gerar ícone dedicado de alta resolução para a Splash Screen do Android 12+ (ic_splash_logo.png)
    for (const { dir, size } of SPLASH_ICON_DENSITIES) {
      const drawableDir = path.join(resPath, dir);
      fs.mkdirSync(drawableDir, { recursive: true });

      const splashLogoDest = path.join(drawableDir, 'ic_splash_logo.png');
      await renderPaddedForeground(foregroundInput, size, splashLogoDest);
    }

    // 4. Remover vetores legados de template do Android Studio se existirem
    const legacyVectors = [
      path.join(resPath, 'drawable/ic_launcher_background.xml'),
      path.join(resPath, 'drawable-v24/ic_launcher_foreground.xml'),
    ];
    for (const file of legacyVectors) {
      if (fs.existsSync(file)) {
        fs.unlinkSync(file);
      }
    }

    console.log('✓ Adaptive Icons (108dp) e Splash Screen Icon (288dp) gerados em alta definição.');
  } catch (error) {
    console.error('Erro ao corrigir ícones do Android:', error);
    process.exit(1);
  }
}

run();
