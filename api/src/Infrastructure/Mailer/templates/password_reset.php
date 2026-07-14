<?php
declare(strict_types=1);

/**
 * This file is included by renderHtmlTemplate, so variables like $name, $token, etc., are available in this scope.
 *
 * @var string $name
 * @var string $code
 * @var string $appName
 */
?>
<p>Olá <?php echo \htmlspecialchars($name ?? 'Usuário'); ?>,</p>
<p>Você solicitou recentemente a redefinição de senha para sua conta.</p>
<p>Por favor, use o seguinte código de 6 dígitos para redefinir sua senha:</p>
<div style="text-align: center; margin: 20px 0;">
  <p style="font-size: 24px; font-weight: bold; color: #191919; letter-spacing: 5px;"><?php echo \htmlspecialchars($code ?? ''); ?></p>
</div>
<p>Este código é válido por 1 hora. Por favor, insira-o na tela de validação de redefinição de senha.</p>
<p>Se você não solicitou uma redefinição de senha, por favor, ignore este e-mail.</p>
<p>Obrigado(a),</p>
<p>A Equipe <?php echo \htmlspecialchars($appName ?? 'Aplicação'); ?></p>