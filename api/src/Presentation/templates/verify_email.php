<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \htmlspecialchars($title) ?> - Habitus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #09090a;
            --color-card: #18181b;
            --color-text-primary: #ffffff;
            --color-text-secondary: #a1a1aa;
            --color-primary: #a3e635; /* Lime-400 */
            --color-primary-hover: #bef264;
            --color-error: #ef4444;
            --color-border: #27272a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text-primary);
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 440px;
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-wrapper {
            margin-bottom: 32px;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--color-text-primary);
            letter-spacing: -0.05em;
        }

        .logo span {
            color: var(--color-primary);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            position: relative;
        }

        .icon-circle.success {
            background: rgba(163, 230, 53, 0.1);
            color: var(--color-primary);
            border: 2px solid rgba(163, 230, 53, 0.2);
            animation: pulseSuccess 2s infinite;
        }

        .icon-circle.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--color-error);
            border: 2px solid rgba(239, 68, 68, 0.2);
        }

        @keyframes pulseSuccess {
            0% { box-shadow: 0 0 0 0 rgba(163, 230, 53, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(163, 230, 53, 0); }
            100% { box-shadow: 0 0 0 0 rgba(163, 230, 53, 0); }
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .message {
            color: var(--color-text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            margin-bottom: 12px;
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: var(--color-bg);
        }

        .btn-primary:hover {
            background-color: var(--color-primary-hover);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            color: var(--color-text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            margin: 24px 0;
            opacity: 0.6;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--color-border);
        }

        .divider::before { margin-right: 16px; }
        .divider::after { margin-left: 16px; }

        .store-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .store-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            background: transparent;
            color: var(--color-text-primary);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .store-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--color-text-secondary);
        }

        .store-btn svg {
            margin-right: 8px;
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-wrapper">
            <h1 class="logo">Habi<span>tus</span></h1>
        </div>

        <div class="icon-circle <?= $statusClass ?>">
            <?= $iconSvg ?>
        </div>

        <h2><?= \htmlspecialchars($title) ?></h2>
        <p class="message"><?= \htmlspecialchars($message) ?></p>

        <a href="<?= \htmlspecialchars($deeplinkUrl) ?>" class="btn btn-primary">Abrir no Aplicativo</a>

        <div class="divider">ou baixe o app</div>

        <div class="store-buttons">
            <a href="https://play.google.com/store" class="store-btn" target="_blank">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3.609 1.814L13.783 12 3.609 22.186A2.235 2.235 0 0 1 3 20.59V3.41c0-.622.22-1.189.609-1.596zm11.29 9.073l3.204-1.848-13.313-7.67 10.109 9.518zM3.86 21.677l10.158-9.563 3.09 3.09-13.248 7.643a2.128 2.128 0 0 1-.5-.5c.164-.226.335-.453.5-.67zm14.33-10.457l3.633-2.094a1.869 1.869 0 0 1 0 3.238l-3.633-2.094c-.001-.001.001-.05.001-.05z"/></svg>
                Google Play
            </a>
            <a href="https://apps.apple.com" class="store-btn" target="_blank">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 4.17c.66-.81 1.11-1.93.99-3.06-1 .04-2.21.67-2.93 1.49-.62.69-1.16 1.84-1.01 2.96 1.12.09 2.27-.57 2.95-1.39z"/></svg>
                App Store
            </a>
        </div>
    </div>

    <script>
        if ("<?= $success ? '1' : '0' ?>" === "1") {
            window.location.href = "<?= $deeplinkUrl ?>";
        }
    </script>
</body>
</html>
