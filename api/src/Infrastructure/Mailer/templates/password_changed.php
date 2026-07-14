<?php
declare(strict_types=1);

/**
 * This file is included by renderHtmlTemplate, so variables like $name are available in this scope.
 *
 * @var string $name
 */
?>
<p>Olá <?php echo \htmlspecialchars($name ?? 'Usuário'); ?>,</p>
<p>Este é um aviso para informar que a sua senha da conta <?php echo \htmlspecialchars($appName ?? 'Aplicação'); ?> foi alterada com sucesso.</p>
<p>Se você não fez essa alteração, entre em contato com nossa equipe de suporte imediatamente.</p>
<p>Obrigado(a),</p>
<p>A Equipe <?php echo \htmlspecialchars($appName ?? 'Aplicação'); ?></p>
