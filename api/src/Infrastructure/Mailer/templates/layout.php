<?php
declare(strict_types=1);

/**
 * This file is included by renderHtmlTemplate, so variables are available in this scope.
 *
 * @var string $subject
 * @var string $contentHtml
 * @var string $siteUrl
 * @var string $appUrl
 * @var string $appName
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo \htmlspecialchars((string) $subject); ?></title>
  <style>
    body {
      font-family: 'Inter', Arial, sans-serif;
      background-color: #09090a;
      color: #ffffff;
      padding: 20px;
      margin: 0;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 30px 20px;
      background-color: #18181b;
      border: 1px solid #27272a;
      border-radius: 12px;
    }
    .logo {
      text-align: center;
      margin-bottom: 25px;
    }
    .logo img {
      max-width: 150px;
      height: auto;
    }
    .header h1 {
      color: #ffffff;
      margin: 0;
      text-align: center;
      font-size: 22px;
      font-weight: 700;
    }
    .content {
      font-size: 16px;
      line-height: 1.6;
      margin-top: 25px;
      color: #e8e8ec;
    }
    .footer {
      text-align: center;
      margin-top: 35px;
      font-size: 12px;
      color: #a1a1aa;
      border-top: 1px solid #27272a;
      padding-top: 20px;
    }
    .button {
      display: inline-block;
      padding: 12px 28px;
      background-color: #a3e635;
      color: #09090a !important;
      text-decoration: none;
      border-radius: 12px;
      font-weight: bold;
      margin: 20px 0;
      text-align: center;
    }
    .button-wrapper {
      text-align: center;
    }
    a {
      color: #a3e635;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    a.button:hover {
      background-color: #84cc16;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="<?php echo $appUrl; ?>/img/logo.png" alt="Logo">
    </div>
    <div class="header">
      <h1><?php echo \htmlspecialchars((string) $subject); ?></h1>
    </div>
    <div class="content">
      <?php echo $contentHtml; ?>
    </div>
    <div class="footer">
      <p>&copy; <?php echo \date('Y'); ?> <?php echo $appName; ?>. All rights reserved.</p>
    </div>
  </div>
</body>
</html>