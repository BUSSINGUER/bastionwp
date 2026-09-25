<?php

if (!defined('ABSPATH')) {
    exit;
}

$reason = (string) ($auth_state['reason'] ?? 'missing');
$reason_message = '';
if ($reason === 'idle_expired') {
    $reason_message = __('Sua sessão BastionWP expirou por inatividade.', 'bastionwp');
} elseif ($reason === 'hard_expired') {
    $reason_message = __('O tempo máximo da sessão BastionWP foi atingido.', 'bastionwp');
} elseif ($reason === 'wordpress_session') {
    $reason_message = __('A sessão WordPress atual não pôde ser vinculada ao BastionWP. Faça login novamente se o problema persistir.', 'bastionwp');
}

$return_url = admin_url('admin.php?page=bastionwp');
if (!empty($_SERVER['REQUEST_URI'])) {
    $candidate = admin_url('admin.php?page=bastionwp');
    $request_uri = wp_unslash((string) $_SERVER['REQUEST_URI']);
    if (str_contains($request_uri, 'page=bastionwp')) {
        $candidate = admin_url('admin.php') . (str_contains($request_uri, '?') ? '?' . (string) wp_parse_url($request_uri, PHP_URL_QUERY) : '');
    }
    $return_url = $candidate;
}
?>
<div class="wrap bastionwp-wrap bastionwp-auth-wrap">
    <div class="bastionwp-auth-shell">
        <div class="bastionwp-auth-brand">
            <span class="bastionwp-brand-mark dashicons dashicons-shield-alt" aria-hidden="true"></span>
            <div>
                <strong><?php echo esc_html__('BastionWP', 'bastionwp'); ?></strong>
                <small><?php echo esc_html('v' . BASTIONWP_VERSION); ?></small>
            </div>
        </div>

        <section class="bastionwp-auth-card">
            <div class="bastionwp-auth-icon"><span class="dashicons dashicons-lock"></span></div>
            <div class="bastionwp-auth-copy">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Sessão protegida', 'bastionwp'); ?></span>
                <h1><?php echo esc_html__('Confirme sua identidade', 'bastionwp'); ?></h1>
                <p><?php echo esc_html__('O BastionWP exige uma autenticação adicional do Developer antes de liberar configurações sensíveis.', 'bastionwp'); ?></p>
            </div>

            <?php if ($reason_message !== '') : ?>
                <div class="notice notice-warning inline"><p><?php echo esc_html($reason_message); ?></p></div>
            <?php endif; ?>

            <?php if (!empty($auth_message) && is_array($auth_message)) : ?>
                <div class="notice notice-<?php echo esc_attr(($auth_message['type'] ?? '') === 'error' ? 'error' : (($auth_message['type'] ?? '') === 'success' ? 'success' : 'warning')); ?> inline"><p><?php echo esc_html((string) ($auth_message['text'] ?? '')); ?></p></div>
            <?php endif; ?>

            <div class="bastionwp-auth-user">
                <?php echo get_avatar($current_user->ID, 44, '', '', ['class' => 'bastionwp-user-avatar']); ?>
                <div>
                    <strong><?php echo esc_html($current_user->display_name); ?></strong>
                    <small><?php echo esc_html__('Developer Principal', 'bastionwp'); ?></small>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-auth-form" autocomplete="off">
                <input type="hidden" name="action" value="bastionwp_authenticate">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($return_url); ?>">
                <?php wp_nonce_field('bastionwp_authenticate'); ?>
                <label for="bastionwp_password">
                    <span><?php echo esc_html($auth_provider_label); ?></span>
                    <input id="bastionwp_password" name="bastionwp_password" type="password" autocomplete="current-password" required autofocus>
                </label>
                <button type="submit" class="button button-primary button-hero"><?php echo esc_html__('Autenticar e abrir BastionWP', 'bastionwp'); ?></button>
            </form>

            <div class="bastionwp-auth-policy">
                <div><span class="dashicons dashicons-clock"></span><strong><?php echo esc_html__('15 minutos', 'bastionwp'); ?></strong><small><?php echo esc_html__('por inatividade', 'bastionwp'); ?></small></div>
                <div><span class="dashicons dashicons-backup"></span><strong><?php echo esc_html__('60 minutos', 'bastionwp'); ?></strong><small><?php echo esc_html__('tempo máximo', 'bastionwp'); ?></small></div>
                <div><span class="dashicons dashicons-privacy"></span><strong><?php echo esc_html__('Senha não armazenada', 'bastionwp'); ?></strong><small><?php echo esc_html__('validação local', 'bastionwp'); ?></small></div>
            </div>
        </section>
    </div>
</div>
