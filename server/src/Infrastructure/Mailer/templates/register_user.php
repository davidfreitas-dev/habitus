<?php
declare(strict_types=1);

/**
 * This file is included by renderHtmlTemplate, so variables like $name are available in this scope.

 *
 * @var string $name
 * @var string $appName
 * @var string $siteUrl
 */
?>
<p>Olá <?php echo \htmlspecialchars($name ?? 'Usuário'); ?>,</p>
<p>Bem-vindo(a) ao <?php echo \htmlspecialchars($appName ?? 'Nosso Aplicativo'); ?>! Estamos animados para tê-lo(a) a bordo.</p>
<p>Você já pode fazer login em sua conta e começar a explorar.</p>
<div class="button-wrapper">
    <a href="<?php echo \htmlspecialchars($siteUrl ?? ''); ?>/login" class="button">Login Now</a>
</div>
<p>If you have any questions, feel free to contact our support team.</p>
<p>Obrigado(a),</p>
<p>A Equipe <?php echo \htmlspecialchars($appName ?? 'Aplicação'); ?></p>
