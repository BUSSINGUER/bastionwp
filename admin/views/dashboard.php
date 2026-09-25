<?php

if (!defined('ABSPATH')) {
    exit;
}

$status_key = isset($core_status['status']) ? (string) $core_status['status'] : 'invalid';
$status_label = [
    'ok'       => __('OK', 'bastionwp'),
    'missing'  => __('Não instalado', 'bastionwp'),
    'outdated' => __('Atenção', 'bastionwp'),
    'invalid'  => __('Crítico', 'bastionwp'),
][$status_key] ?? __('Desconhecido', 'bastionwp');

$core_message_map = [
    'ok'       => __('Bastion Core instalado e validado.', 'bastionwp'),
    'missing'  => __('Bastion Core não está instalado.', 'bastionwp'),
    'outdated' => __('Bastion Core está instalado, mas precisa ser atualizado.', 'bastionwp'),
    'invalid'  => __('Bastion Core foi encontrado, mas não pôde ser validado.', 'bastionwp'),
];

$action_message = get_transient('bastionwp_core_action_message_' . get_current_user_id());
if ($action_message) {
    delete_transient('bastionwp_core_action_message_' . get_current_user_id());
}

$access_message = get_transient('bastionwp_access_message_' . get_current_user_id());
if ($access_message) {
    delete_transient('bastionwp_access_message_' . get_current_user_id());
}

$update_message = get_transient('bastionwp_update_message_' . get_current_user_id());
if ($update_message) {
    delete_transient('bastionwp_update_message_' . get_current_user_id());
}

$hardening_message = get_transient('bastionwp_hardening_message_' . get_current_user_id());
if ($hardening_message) {
    delete_transient('bastionwp_hardening_message_' . get_current_user_id());
}

$integration_message = get_transient('bastionwp_integration_message_' . get_current_user_id());
if ($integration_message) {
    delete_transient('bastionwp_integration_message_' . get_current_user_id());
}

$logs_message = get_transient('bastionwp_logs_message_' . get_current_user_id());
if ($logs_message) {
    delete_transient('bastionwp_logs_message_' . get_current_user_id());
}

$wizard_message = get_transient('bastionwp_wizard_message_' . get_current_user_id());
if ($wizard_message) {
    delete_transient('bastionwp_wizard_message_' . get_current_user_id());
}

$request_message = get_transient('bastionwp_request_message_' . get_current_user_id());
if ($request_message) {
    delete_transient('bastionwp_request_message_' . get_current_user_id());
}

$backup_message = get_transient('bastionwp_backup_message_' . get_current_user_id());
if ($backup_message) {
    delete_transient('bastionwp_backup_message_' . get_current_user_id());
}

$config_backup_targets = class_exists('BastionWP_Config_Backup') ? BastionWP_Config_Backup::get_targets() : [];
$config_snapshots = class_exists('BastionWP_Config_Backup') ? BastionWP_Config_Backup::get_snapshots() : [];
$config_backup_storage = class_exists('BastionWP_Config_Backup') ? BastionWP_Config_Backup::get_storage_status() : [];
$config_snapshot_compare = null;
if (class_exists('BastionWP_Config_Backup') && !empty($_GET['snapshot_compare'])) {
    $config_snapshot_compare = BastionWP_Config_Backup::compare_snapshot(sanitize_text_field(wp_unslash($_GET['snapshot_compare'])));
}

$is_ssl = is_ssl();
$current_user = wp_get_current_user();

$page_meta = [
    'overview' => [
        'icon' => 'dashicons-admin-home',
        'title' => __('Saúde do ambiente', 'bastionwp'),
        'description' => !empty($diagnostics_report['summary']['error'])
            ? __('Foram detectados erros nas verificações disponíveis. Revise os cards abaixo antes de considerar o ambiente validado.', 'bastionwp')
            : (!empty($diagnostics_report['summary']['warning'])
                ? __('O ambiente está operacional, mas existem pontos de atenção nas verificações disponíveis.', 'bastionwp')
                : __('Nenhum erro ou atenção foi detectado pelas verificações atualmente disponíveis.', 'bastionwp')),
        'status' => !empty($diagnostics_report['summary']['error'])
            ? __('Requer atenção', 'bastionwp')
            : (
                !empty($diagnostics_report['summary']['warning'])
                    ? __('Ambiente com atenção', 'bastionwp')
                    : __('Sem alertas detectados', 'bastionwp')
            ),
        'status_class' => !empty($diagnostics_report['summary']['error'])
            ? 'error'
            : (
                !empty($diagnostics_report['summary']['warning'])
                    ? 'warning'
                    : 'success'
            ),
    ],
    'wizard' => [
        'icon' => 'bastionwp-magic-wand-icon',
        'title' => __('Assistente BastionWP', 'bastionwp'),
        'description' => __('Revise a configuração inicial do plugin e acompanhe o progresso das áreas essenciais.', 'bastionwp'),
        'status' => __('Configuração guiada', 'bastionwp'),
        'status_class' => 'info',
    ],
    'access' => [
        'icon' => 'dashicons-groups',
        'title' => __('Proteção de acesso', 'bastionwp'),
        'description' => __('Gerencie usuários do cliente, defina permissões e habilite menus de forma amigável.', 'bastionwp'),
        'status' => __('Sistema ativo', 'bastionwp'),
        'status_class' => 'success',
    ],
    'requests' => [
        'icon' => 'dashicons-unlock',
        'title' => __('Solicitações de configuração', 'bastionwp'),
        'description' => __('Analise pedidos de privilégios temporários, aprove por período definido ou encerre acessos ativos.', 'bastionwp'),
        'status' => __('Acesso temporário', 'bastionwp'),
        'status_class' => 'info',
    ],
    'hardening' => [
        'icon' => 'dashicons-shield',
        'title' => __('Segurança', 'bastionwp'),
        'description' => __('Ajuste o nível de proteção do ambiente WordPress de acordo com cada fase do projeto.', 'bastionwp'),
        'status' => BastionWP_Hardening::get_profile() === BastionWP_Hardening::PROFILE_UNCONFIGURED
            ? __('Não configurado', 'bastionwp')
            : __('Perfil aplicado', 'bastionwp'),
        'status_class' => BastionWP_Hardening::get_profile() === BastionWP_Hardening::PROFILE_UNCONFIGURED ? 'warning' : 'success',
    ],
    'integrations' => [
        'icon' => 'dashicons-admin-plugins',
        'title' => __('Integrações', 'bastionwp'),
        'description' => __('Gerencie ferramentas especializadas conectadas ao BastionWP e acompanhe seus estados.', 'bastionwp'),
        'status' => __('Integrações técnicas', 'bastionwp'),
        'status_class' => 'info',
    ],
    'diagnostics' => [
        'icon' => 'dashicons-chart-area',
        'title' => __('Status do Sistema', 'bastionwp'),
        'description' => __('Centralize as verificações de WordPress, servidor, Segurança e integrações em um só lugar.', 'bastionwp'),
        'status' => !empty($diagnostics_report['summary']['error'])
            ? __('Erros encontrados', 'bastionwp')
            : __('Diagnóstico disponível', 'bastionwp'),
        'status_class' => !empty($diagnostics_report['summary']['error']) ? 'error' : 'success',
    ],
    'system' => [
        'icon' => 'dashicons-admin-tools',
        'title' => __('Sistema', 'bastionwp'),
        'description' => __('Agrupe a zona de risco, atualizações do BastionWP e auditoria técnica do ambiente.', 'bastionwp'),
        'status' => __('Central técnica', 'bastionwp'),
        'status_class' => 'warning',
    ],
    'logs' => [
        'icon' => 'dashicons-media-text',
        'title' => __('Logs e auditoria', 'bastionwp'),
        'description' => __('Acompanhe eventos técnicos do BastionWP, filtre registros e exporte o histórico para análise.', 'bastionwp'),
        'status' => __('Auditoria ativa', 'bastionwp'),
        'status_class' => 'success',
    ],
    'updates' => [
        'icon' => 'dashicons-update',
        'title' => __('Atualizações', 'bastionwp'),
        'description' => __('Acompanhe a fonte GitHub, atualização automática e a versão instalada do BastionWP.', 'bastionwp'),
        'status' => BastionWP_Update_Manager::is_auto_update_enabled()
            ? __('Atualização automática ativa', 'bastionwp')
            : __('Atualização manual', 'bastionwp'),
        'status_class' => BastionWP_Update_Manager::is_auto_update_enabled() ? 'success' : 'warning',
    ],
];

$current_page_meta = $page_meta[$tab] ?? $page_meta['overview'];
$wizard_focus = $tab === 'wizard' && ($this->wizard->is_focus_mode() || isset($_GET['wizard_focus']));
?>
<div class="wrap bastionwp-wrap <?php echo $wizard_focus ? 'bastionwp-wrap-focus' : ''; ?>">
<?php if (!$wizard_focus) : ?>
    <header class="bastionwp-app-header">
        <div class="bastionwp-brand">
            <span class="bastionwp-brand-mark dashicons dashicons-shield-alt" aria-hidden="true"></span>
            <div>
                <div class="bastionwp-brand-line">
                    <h1><?php echo esc_html__('BastionWP', 'bastionwp'); ?></h1>
                    <span class="bastionwp-version"><?php echo esc_html('v' . BASTIONWP_VERSION); ?></span>
                </div>
                <p><?php echo esc_html__('Controle e proteção para WordPress', 'bastionwp'); ?></p>
            </div>
        </div>

        <div class="bastionwp-header-actions">
            <div class="bastionwp-notification-center" data-bastionwp-notifications>
                <button type="button" class="bastionwp-notification-trigger" data-bastionwp-notification-trigger aria-expanded="false" aria-label="<?php echo esc_attr__('Notificações do BastionWP', 'bastionwp'); ?>">
                    <span class="dashicons dashicons-bell" aria-hidden="true"></span>
                    <?php if ($notification_count > 0) : ?><span class="bastionwp-notification-count"><?php echo esc_html((string) $notification_count); ?></span><?php endif; ?>
                </button>
                <div class="bastionwp-notification-popover" data-bastionwp-notification-popover hidden>
                    <div class="bastionwp-notification-popover-head"><strong><?php echo esc_html__('Notificações', 'bastionwp'); ?></strong><?php if ($notification_count > 0) : ?><span><?php echo esc_html(sprintf(_n('%d pendência', '%d pendências', $notification_count, 'bastionwp'), $notification_count)); ?></span><?php endif; ?></div>
                    <?php if ($notification_count === 0) : ?>
                        <div class="bastionwp-notification-empty"><span class="dashicons dashicons-yes-alt"></span><p><?php echo esc_html__('Nenhuma pendência aberta.', 'bastionwp'); ?></p></div>
                    <?php else : ?>
                        <div class="bastionwp-notification-list">
                            <?php if ($pending_request_count > 0) : ?>
                                <article><span class="bastionwp-notification-icon is-warning dashicons dashicons-unlock"></span><div><strong><?php echo esc_html(sprintf(_n('%d solicitação temporária pendente', '%d solicitações temporárias pendentes', $pending_request_count, 'bastionwp'), $pending_request_count)); ?></strong><small><?php echo esc_html__('Revise e aprove ou negue para encerrar a notificação.', 'bastionwp'); ?></small><a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access&access_section=requests&request_status=pending')); ?>"><?php echo esc_html__('Revisar solicitações', 'bastionwp'); ?></a></div></article>
                            <?php endif; ?>
                            <?php if ($has_update_notification) : ?>
                                <article><span class="bastionwp-notification-icon is-update dashicons dashicons-update"></span><div><strong><?php echo esc_html(sprintf(__('Nova versão disponível: %s', 'bastionwp'), $latest_update_version)); ?></strong><small><?php echo esc_html__('Instale agora ou dispense somente esta versão.', 'bastionwp'); ?></small><div class="bastionwp-notification-actions"><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>"><?php echo esc_html__('Ver atualização', 'bastionwp'); ?></a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_dismiss_update_notification"><input type="hidden" name="version" value="<?php echo esc_attr($latest_update_version); ?>"><?php wp_nonce_field('bastionwp_dismiss_update_notification'); ?><button type="submit" class="button-link"><?php echo esc_html__('Dispensar esta versão', 'bastionwp'); ?></button></form></div></div></article>
                            <?php endif; ?>
                            <?php foreach (array_slice($security_alerts, 0, 5) as $security_alert) : ?>
                                <?php $alert_severity = (string) ($security_alert['severity'] ?? 'warning'); ?>
                                <article>
                                    <span class="bastionwp-notification-icon is-security dashicons dashicons-shield"></span>
                                    <div>
                                        <strong><?php echo esc_html((string) ($security_alert['title'] ?? __('Alerta de segurança', 'bastionwp'))); ?></strong>
                                        <small><?php echo esc_html((string) ($security_alert['message'] ?? '')); ?></small>
                                        <div class="bastionwp-notification-actions">
                                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening&security_section=monitor#bastionwp-security-controls')); ?>"><?php echo esc_html__('Revisar', 'bastionwp'); ?></a>
                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                                <input type="hidden" name="action" value="bastionwp_resolve_security_alert">
                                                <input type="hidden" name="alert_id" value="<?php echo esc_attr((string) ($security_alert['id'] ?? '')); ?>">
                                                <?php wp_nonce_field('bastionwp_resolve_security_alert'); ?>
                                                <button type="submit" class="button-link"><?php echo esc_html__('Marcar como resolvido', 'bastionwp'); ?></button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bastionwp-auth-session-chip" data-bastionwp-auth-session data-idle-expires="<?php echo esc_attr((string) ($bastion_auth_state['idle_expires_at'] ?? 0)); ?>" data-hard-expires="<?php echo esc_attr((string) ($bastion_auth_state['hard_expires_at'] ?? 0)); ?>">
                <span class="dashicons dashicons-unlock" aria-hidden="true"></span>
                <span><small><?php echo esc_html__('Sessão Bastion', 'bastionwp'); ?></small><strong data-bastionwp-auth-timer>--:--</strong></span>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_lock_auth">
                    <?php wp_nonce_field('bastionwp_lock_auth'); ?>
                    <button type="submit" class="button-link" title="<?php echo esc_attr__('Bloquear BastionWP agora', 'bastionwp'); ?>"><span class="dashicons dashicons-lock"></span></button>
                </form>
            </div>
            <div class="bastionwp-current-user">
                <?php echo get_avatar($current_user->ID, 36, '', '', ['class' => 'bastionwp-user-avatar']); ?>
                <div>
                    <strong><?php echo esc_html($current_user->display_name); ?></strong>
                    <small><?php echo esc_html(BastionWP_Users::is_developer() ? __('Developer', 'bastionwp') : __('Administrador', 'bastionwp')); ?></small>
                </div>
            </div>
        </div>
    </header>

    <nav class="nav-tab-wrapper bastionwp-tabs" aria-label="<?php echo esc_attr__('Navegação do BastionWP', 'bastionwp'); ?>">
        <a class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=overview')); ?>">
            <span class="dashicons dashicons-admin-home" aria-hidden="true"></span><span><?php echo esc_html__('Visão Geral', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'wizard' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=wizard')); ?>">
            <span class="bastionwp-magic-wand-icon bastionwp-nav-wand" aria-hidden="true"></span><span><?php echo esc_html__('Assistente', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'access' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
            <span class="dashicons dashicons-groups" aria-hidden="true"></span><span><?php echo esc_html__('Proteção de acesso', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'hardening' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening')); ?>">
            <span class="dashicons dashicons-shield" aria-hidden="true"></span><span><?php echo esc_html__('Segurança', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'integrations' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=integrations')); ?>">
            <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span><span><?php echo esc_html__('Integrações', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
            <span class="dashicons dashicons-chart-area" aria-hidden="true"></span><span><?php echo esc_html__('Status do Sistema', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'system' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>">
            <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span><span><?php echo esc_html__('Sistema', 'bastionwp'); ?></span>
        </a>
    </nav>

    <section class="bastionwp-page-hero <?php echo $tab === 'wizard' ? 'bastionwp-page-hero-wizard' : ''; ?>">
        <div class="bastionwp-page-hero-main">
            <?php if ($tab === 'wizard') : ?><span class="bastionwp-page-icon bastionwp-magic-wand-icon" aria-hidden="true"></span><?php else : ?><span class="bastionwp-page-icon dashicons <?php echo esc_attr($current_page_meta['icon']); ?>" aria-hidden="true"></span><?php endif; ?>
            <div>
                <h2><?php echo esc_html($current_page_meta['title']); ?></h2>
                <p><?php echo esc_html($current_page_meta['description']); ?></p>
            </div>
        </div>

        <?php if ($tab === 'wizard') : ?>
            <div class="bastionwp-wizard-hero-progress">
                <div class="bastionwp-wizard-progress-head">
                    <strong>
                        <?php
                        echo esc_html(
                            sprintf(
                                __('%1$d de %2$d etapas obrigatórias concluídas', 'bastionwp'),
                                (int) $wizard_progress['required_ok'],
                                (int) $wizard_progress['required_total']
                            )
                        );
                        ?>
                    </strong>
                    <span><?php echo esc_html((string) $wizard_progress['percent'] . '%'); ?></span>
                </div>
                <div class="bastionwp-progress-track">
                    <span style="width: <?php echo esc_attr((string) $wizard_progress['percent']); ?>%;"></span>
                </div>
                <div class="bastionwp-wizard-hero-note">
                    <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                    <span>
                        <?php echo esc_html__('Revise as etapas abaixo para finalizar a configuração inicial e deixar seu ambiente ainda mais seguro.', 'bastionwp'); ?>
                    </span>
                </div>
            </div>
        <?php else : ?>
            <div class="bastionwp-overview-hero-status">
                <span class="bastionwp-hero-status bastionwp-hero-status-<?php echo esc_attr($current_page_meta['status_class']); ?>">
                    <span class="bastionwp-status-dot" aria-hidden="true"></span>
                    <?php echo esc_html($current_page_meta['status']); ?>
                </span>
                <?php if ($tab === 'overview') : ?>
                    <small>
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Última verificação: %s', 'bastionwp'),
                                wp_date('d/m/Y H:i')
                            )
                        );
                        ?>
                    </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

    <div class="bastionwp-feedback-region" aria-live="polite">

    <?php if (isset($_GET['bastionwp_core']) && sanitize_key(wp_unslash($_GET['bastionwp_core'])) === 'success') : ?>
        <div class="notice notice-success inline"><p><?php echo esc_html__('Bastion Core instalado/reparado com sucesso.', 'bastionwp'); ?></p></div>
    <?php endif; ?>

    <?php if ($action_message) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html($action_message); ?></p></div>
    <?php elseif (!empty($install_error)) : ?>
        <div class="notice notice-warning inline"><p><?php echo esc_html($install_error); ?></p></div>
    <?php endif; ?>

    <?php if (is_array($access_message) && !empty($access_message['text'])) : ?>
        <div class="notice <?php echo $access_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($access_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($update_message) && !empty($update_message['text'])) : ?>
        <div class="notice <?php echo $update_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($update_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($hardening_message) && !empty($hardening_message['text'])) : ?>
        <div class="notice <?php echo $hardening_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($hardening_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($integration_message) && !empty($integration_message['text'])) : ?>
        <div class="notice <?php echo $integration_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($integration_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($logs_message) && !empty($logs_message['text'])) : ?>
        <div class="notice <?php echo $logs_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($logs_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($wizard_message) && !empty($wizard_message['text'])) : ?>
        <div class="notice <?php echo $wizard_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($wizard_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($request_message) && !empty($request_message['text'])) : ?>
        <div class="notice <?php echo $request_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($request_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (is_array($backup_message) && !empty($backup_message['text'])) : ?>
        <div class="notice <?php echo $backup_message['type'] === 'error' ? 'notice-error' : 'notice-success'; ?> inline">
            <p><?php echo esc_html($backup_message['text']); ?></p>
        </div>
    <?php endif; ?>

    </div>

    <?php if ($tab === 'overview') : ?>
        <?php
        $overview_developer = !empty($developer_ids)
            ? get_userdata((int) $developer_ids[0])
            : false;

        $overview_profile_label = isset($hardening_profiles[$hardening_profile]['label'])
            ? (string) $hardening_profiles[$hardening_profile]['label']
            : __('Não configurado', 'bastionwp');

        $overview_profile_description = isset($hardening_profiles[$hardening_profile]['description'])
            ? (string) $hardening_profiles[$hardening_profile]['description']
            : __('Nenhum perfil de hardening foi definido.', 'bastionwp');

        $overview_diag_errors = (int) ($diagnostics_report['summary']['error'] ?? 0);
        $overview_diag_warnings = (int) ($diagnostics_report['summary']['warning'] ?? 0);

        $overview_update_ready = !empty($update_status['configured']) && empty($update_status['error']) && !empty($update_status['release']) && $auto_update_enabled;
        $overview_recent_logs = array_slice($log_rows, 0, 4);
        ?>

        <div class="bastionwp-grid bastionwp-page-overview">
            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-archive" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Bastion Core', 'bastionwp'); ?></h2>
                        <span class="bastionwp-status bastionwp-status-<?php echo esc_attr($status_key); ?>">
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </div>
                </div>

                <dl>
                    <div>
                        <dt><?php echo esc_html__('BastionWP', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html(BASTIONWP_VERSION); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Bastion Core', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html($core_status['target_version'] ?: $core_status['source_version'] ?: '—'); ?></dd>
                    </div>
                </dl>

                <p class="bastionwp-overview-card-note">
                    <?php echo esc_html($core_message_map[$status_key] ?? $core_status['message']); ?>
                </p>

                <?php if ($status_key !== 'ok') : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_repair_core">
                        <?php wp_nonce_field('bastionwp_repair_core'); ?>
                        <?php submit_button(__('Instalar / Reparar', 'bastionwp'), 'secondary', 'submit', false); ?>
                    </form>
                <?php endif; ?>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-wordpress" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('WordPress / Ambiente', 'bastionwp'); ?></h2>
                    </div>
                </div>

                <dl>
                    <div>
                        <dt><?php echo esc_html__('WordPress', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html(get_bloginfo('version')); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('PHP', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html(PHP_VERSION); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('HTTPS', 'bastionwp'); ?></dt>
                        <dd class="<?php echo $is_ssl ? 'bastionwp-overview-positive' : 'bastionwp-overview-warning'; ?>">
                            <?php echo $is_ssl ? esc_html__('Ativo', 'bastionwp') : esc_html__('Não detectado', 'bastionwp'); ?>
                        </dd>
                    </div>
                </dl>

                <p class="bastionwp-overview-card-note">
                    <?php echo esc_html__('Versões do WordPress/PHP e HTTPS exibidos conforme a verificação desta requisição.', 'bastionwp'); ?>
                </p>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-groups" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Proteção de acesso', 'bastionwp'); ?></h2>
                        <span class="bastionwp-hero-status bastionwp-hero-status-success">
                            <span class="bastionwp-status-dot" aria-hidden="true"></span>
                            <?php echo esc_html__('Política ativa', 'bastionwp'); ?>
                        </span>
                    </div>
                </div>

                <div class="bastionwp-overview-key-value">
                    <span><?php echo esc_html__('Developer principal', 'bastionwp'); ?></span>
                    <strong>
                        <?php echo esc_html(
                            $overview_developer
                                ? $overview_developer->display_name
                                : __('Não configurado', 'bastionwp')
                        ); ?>
                    </strong>
                </div>
                <div class="bastionwp-overview-key-value">
                    <span><?php echo esc_html__('Usuários protegidos', 'bastionwp'); ?></span>
                    <strong><?php echo esc_html((string) (count($client_managers) + count($protected_admins))); ?></strong>
                </div>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
                    <?php echo esc_html__('Gerenciar proteção', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-shield" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Segurança', 'bastionwp'); ?></h2>
                    </div>
                </div>

                <div class="bastionwp-overview-key-value">
                    <span><?php echo esc_html__('Perfil ativo', 'bastionwp'); ?></span>
                    <strong><?php echo esc_html($overview_profile_label); ?></strong>
                </div>

                <p class="bastionwp-overview-card-note">
                    <?php echo esc_html($overview_profile_description); ?>
                </p>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening')); ?>">
                    <?php echo esc_html__('Ver configurações', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-update" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Atualizações', 'bastionwp'); ?></h2>
                        <span class="bastionwp-hero-status <?php echo $overview_update_ready ? 'bastionwp-hero-status-success' : 'bastionwp-hero-status-warning'; ?>">
                            <?php echo $overview_update_ready ? esc_html__('Configuradas', 'bastionwp') : esc_html__('Revisar', 'bastionwp'); ?>
                        </span>
                    </div>
                </div>

                <ul class="bastionwp-overview-mini-checklist">
                    <li class="<?php echo !empty($update_status['configured']) && empty($update_status['error']) && !empty($update_status['release']) ? 'is-ok' : 'is-warning'; ?>">
                        <?php echo esc_html__('Release GitHub validada', 'bastionwp'); ?>
                    </li>
                    <li class="<?php echo $auto_update_enabled ? 'is-ok' : 'is-warning'; ?>">
                        <?php echo esc_html__('Automáticas', 'bastionwp'); ?>
                    </li>
                    <li class="is-ok">
                        <?php echo esc_html__('Pacote validado antes da instalação', 'bastionwp'); ?>
                    </li>
                </ul>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>">
                    <?php echo esc_html__('Ver atualizações', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-chart-pie" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Status do Sistema', 'bastionwp'); ?></h2>
                        <span class="bastionwp-hero-status <?php echo $overview_diag_errors > 0 ? 'bastionwp-hero-status-error' : ($overview_diag_warnings > 0 ? 'bastionwp-hero-status-warning' : 'bastionwp-hero-status-success'); ?>">
                            <?php echo $overview_diag_errors > 0
                                ? esc_html__('Erro', 'bastionwp')
                                : ($overview_diag_warnings > 0 ? esc_html__('Atenção', 'bastionwp') : esc_html__('OK', 'bastionwp')); ?>
                        </span>
                    </div>
                </div>

                <div class="bastionwp-overview-diagnostic-count">
                    <span class="bastionwp-diagnostic-state bastionwp-diagnostic-error"></span>
                    <strong><?php echo esc_html((string) $overview_diag_errors); ?></strong>
                    <span><?php echo esc_html(_n('erro', 'erros', $overview_diag_errors, 'bastionwp')); ?></span>
                </div>
                <div class="bastionwp-overview-diagnostic-count">
                    <span class="bastionwp-diagnostic-state bastionwp-diagnostic-warning"></span>
                    <strong><?php echo esc_html((string) $overview_diag_warnings); ?></strong>
                    <span><?php echo esc_html(_n('atenção', 'atenções', $overview_diag_warnings, 'bastionwp')); ?></span>
                </div>

                <p class="bastionwp-overview-card-note">
                    <?php echo esc_html__('Validação do ambiente consolidada.', 'bastionwp'); ?>
                </p>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
                    <?php echo esc_html__('Revisar diagnóstico', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-permissions-card bastionwp-release-notes-card">
                <div class="bastionwp-overview-section-head"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-megaphone" aria-hidden="true"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Versão ' . BASTIONWP_VERSION, 'bastionwp'); ?></span><h2><?php echo esc_html__('Novidades da versão', 'bastionwp'); ?></h2><p><?php echo esc_html__('Principais melhorias incluídas nesta atualização do BastionWP.', 'bastionwp'); ?></p></div></div></div>
                <ul class="bastionwp-checklist bastionwp-release-note-list">
                    <li><?php echo esc_html__('Assistente mais claro, com pendências detalhadas e retomada de configuração.', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Perfis de Segurança mais intuitivos e com prévia dinâmica das regras.', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Atualizador compatível com versões hotfix como 0.9.9.1.', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Integrações reorganizadas em painel lateral real e com status de compatibilidade de acesso.', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Ajustes visuais na Saúde do ambiente, Solicitações e Proteção de acesso.', 'bastionwp'); ?></li>
                </ul>
                <div class="bastionwp-overview-info-strip"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><p><?php echo esc_html__('As regras críticas de menor privilégio introduzidas na auditoria continuam ativas.', 'bastionwp'); ?></p></div>
            </section>

            <aside class="bastionwp-overview-side">
                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-head">
                        <div class="bastionwp-overview-section-title">
                            <span class="bastionwp-overview-card-icon dashicons dashicons-media-text" aria-hidden="true"></span>
                            <div>
                                <h2><?php echo esc_html__('Atividade recente', 'bastionwp'); ?></h2>
                            </div>
                        </div>
                        <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>">
                            <?php echo esc_html__('Ver todos os logs', 'bastionwp'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                    </div>

                    <?php if (empty($overview_recent_logs)) : ?>
                        <div class="bastionwp-empty-state">
                            <span class="dashicons dashicons-media-text" aria-hidden="true"></span>
                            <p><?php echo esc_html__('Ainda não há atividades registradas.', 'bastionwp'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="bastionwp-overview-activity-list">
                            <?php foreach ($overview_recent_logs as $overview_log) : ?>
                                <?php
                                $activity_user = !empty($overview_log['user_id'])
                                    ? get_userdata((int) $overview_log['user_id'])
                                    : false;

                                $activity_level = in_array(
                                    (string) ($overview_log['level'] ?? 'info'),
                                    ['success', 'warning', 'error', 'info'],
                                    true
                                )
                                    ? (string) $overview_log['level']
                                    : 'info';

                                $activity_time = !empty($overview_log['event_time'])
                                    ? get_date_from_gmt((string) $overview_log['event_time'], 'd/m H:i')
                                    : '—';
                                ?>
                                <div class="bastionwp-overview-activity-item">
                                    <span class="bastionwp-overview-activity-dot bastionwp-overview-activity-<?php echo esc_attr($activity_level); ?>" aria-hidden="true"></span>
                                    <div>
                                        <strong><?php echo esc_html((string) $overview_log['message']); ?></strong>
                                        <small>
                                            <?php echo esc_html(
                                                $activity_user
                                                    ? $activity_user->display_name
                                                    : __('Sistema', 'bastionwp')
                                            ); ?>
                                        </small>
                                    </div>
                                    <time><?php echo esc_html($activity_time); ?></time>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>
        </div>

        <footer class="bastionwp-overview-footer">
            <div>
                <span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
                <strong><?php echo esc_html__('BastionWP', 'bastionwp'); ?></strong>
                <span><?php echo esc_html__('Protegendo o que importa no seu WordPress.', 'bastionwp'); ?></span>
            </div>
            <div>
                <span><?php echo esc_html('Versão ' . BASTIONWP_VERSION); ?></span>
                <span><?php echo esc_html__('Feito para uma web mais segura.', 'bastionwp'); ?></span>
            </div>
        </footer>

<?php elseif ($tab === 'wizard') : ?>
    <?php
    $wizard_state = $this->wizard->get_state();
    $wizard_step = isset($_GET['wizard_step'])
        ? max(0, min(6, absint(wp_unslash($_GET['wizard_step']))))
        : $this->wizard->get_current_step();
    $wizard_completed = !empty($wizard_state['completed']);
    $wizard_paused = !empty($wizard_state['paused']) && !$wizard_completed;
    $wizard_blocking_steps = array_values(array_filter($wizard_steps, static fn(array $step): bool => !empty($step['required']) && ($step['status'] ?? '') !== 'ok'));
    $wizard_selected_profile = BastionWP_Hardening::get_profile() !== BastionWP_Hardening::PROFILE_UNCONFIGURED ? BastionWP_Hardening::get_profile() : BastionWP_Hardening::PROFILE_PRODUCTION;
    $security_intensity = [BastionWP_Hardening::PROFILE_DEVELOPMENT => ['label' => __('Proteção mínima', 'bastionwp'), 'class' => 'minimal'], BastionWP_Hardening::PROFILE_STAGING => ['label' => __('Proteção moderada', 'bastionwp'), 'class' => 'moderate'], BastionWP_Hardening::PROFILE_PRODUCTION => ['label' => __('Proteção alta', 'bastionwp'), 'class' => 'high'], BastionWP_Hardening::PROFILE_LOCKED => ['label' => __('Mais restritiva', 'bastionwp'), 'class' => 'maximum']];

    $security_plugin_candidates = [
        'wordfence/wordfence.php' => 'Wordfence',
        'better-wp-security/better-wp-security.php' => 'Solid Security',
        'all-in-one-wp-security-and-firewall/wp-security.php' => 'All-In-One Security',
        'sucuri-scanner/sucuri.php' => 'Sucuri Security',
        'wp-cerber/wp-cerber.php' => 'WP Cerber',
    ];
    $active_security_plugins = [];
    foreach ($security_plugin_candidates as $security_file => $security_name) {
        if (isset($installed_plugins[$security_file]) && is_plugin_active($security_file)) {
            $active_security_plugins[$security_file] = $security_name;
        }
    }
    ?>

    <?php if ($wizard_completed && !$wizard_focus) : ?>
        <div class="bastionwp-grid bastionwp-page-wizard">
            <section class="bastionwp-card bastionwp-card-wide bastionwp-wizard-complete-card">
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <div>
                        <span class="bastionwp-eyebrow"><?php echo esc_html__('Assistente concluído', 'bastionwp'); ?></span>
                        <h2><?php echo esc_html__('Configuração inicial finalizada', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('O Assistente continua disponível para uma nova revisão guiada sempre que necessário.', 'bastionwp'); ?></p>
                    </div>
                </div>
                <div class="bastionwp-wizard-summary-boxes">
                    <div><span class="dashicons dashicons-yes-alt"></span><strong><?php echo esc_html((string) $wizard_progress['required_ok']); ?></strong><small><?php echo esc_html__('etapas obrigatórias prontas', 'bastionwp'); ?></small></div>
                    <div><span class="dashicons dashicons-shield"></span><strong><?php echo esc_html(BastionWP_Hardening::get_profiles()[BastionWP_Hardening::get_profile()]['label'] ?? __('Não configurado', 'bastionwp')); ?></strong><small><?php echo esc_html__('perfil de Segurança', 'bastionwp'); ?></small></div>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_reopen_wizard">
                    <?php wp_nonce_field('bastionwp_reopen_wizard'); ?>
                    <?php submit_button(__('Reabrir Assistente em modo foco', 'bastionwp'), 'primary', 'submit', false); ?>
                </form>
            </section>
        </div>
    <?php elseif ($wizard_paused && !$wizard_focus) : ?>
        <div class="bastionwp-grid bastionwp-page-wizard"><section class="bastionwp-card bastionwp-card-wide bastionwp-wizard-resume-card"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon bastionwp-magic-wand-icon" aria-hidden="true"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Configuração em andamento', 'bastionwp'); ?></span><h2><?php echo esc_html__('Continue de onde parou ou reinicie a configuração', 'bastionwp'); ?></h2><p><?php echo esc_html(sprintf(__('O Assistente foi pausado na etapa %d de 6.', 'bastionwp'), max(1, $wizard_step))); ?></p></div></div><div class="bastionwp-wizard-resume-actions"><a class="button button-primary" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'wizard','wizard_focus'=>'1','wizard_step'=>max(1,$wizard_step)], admin_url('admin.php'))); ?>"><?php echo $wizard_step >= 6 ? esc_html__('Concluir última configuração', 'bastionwp') : esc_html__('Continuar configuração', 'bastionwp'); ?></a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_reopen_wizard"><?php wp_nonce_field('bastionwp_reopen_wizard'); ?><?php submit_button(__('Reiniciar configuração', 'bastionwp'), 'secondary', 'submit', false); ?></form></div></section></div>
    <?php else : ?>
        <div class="bastionwp-focus-shell">
            <header class="bastionwp-focus-header">
                <div class="bastionwp-brand">
                    <span class="bastionwp-brand-mark dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <div><div class="bastionwp-brand-line"><h1><?php echo esc_html__('BastionWP', 'bastionwp'); ?></h1><span class="bastionwp-version"><?php echo esc_html('v' . BASTIONWP_VERSION); ?></span></div><p><?php echo esc_html__('Assistente de configuração', 'bastionwp'); ?></p></div>
                </div>
                <?php if ($wizard_step > 0) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-inline-form">
                        <input type="hidden" name="action" value="bastionwp_wizard_pause">
                        <?php wp_nonce_field('bastionwp_wizard_pause'); ?>
                        <button type="submit" class="button"><?php echo esc_html__('Sair e continuar depois', 'bastionwp'); ?></button>
                    </form>
                <?php endif; ?>
            </header>

            <div class="bastionwp-focus-progress">
                <span><?php echo esc_html(sprintf(__('Etapa %1$d de %2$d', 'bastionwp'), $wizard_step, 6)); ?></span>
                <div class="bastionwp-progress-track"><span style="width: <?php echo esc_attr((string) round(($wizard_step / 6) * 100)); ?>%;"></span></div>
            </div>

            <?php if ($wizard_step === 0) : ?>
                <section class="bastionwp-focus-welcome">
                    <span class="bastionwp-focus-hero-icon bastionwp-magic-wand-icon" aria-hidden="true"></span>
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Bem-vindo', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Configure uma base de segurança previsível para este WordPress', 'bastionwp'); ?></h2>
                    <p><?php echo esc_html__('O BastionWP organiza proteção de acesso, Segurança, integrações, auditoria e atualizações sem depender apenas de esconder menus. O Assistente revisa o ambiente antes de aplicar mudanças importantes.', 'bastionwp'); ?></p>
                    <ul class="bastionwp-focus-benefits">
                        <li><span class="dashicons dashicons-lock"></span><?php echo esc_html__('Protege áreas técnicas contra alterações acidentais.', 'bastionwp'); ?></li>
                        <li><span class="dashicons dashicons-groups"></span><?php echo esc_html__('Converte usuários do cliente para uma política controlada.', 'bastionwp'); ?></li>
                        <li><span class="dashicons dashicons-shield"></span><?php echo esc_html__('Aplica Segurança somente depois de verificar conflitos conhecidos.', 'bastionwp'); ?></li>
                    </ul>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_wizard_start">
                        <?php wp_nonce_field('bastionwp_wizard_start'); ?>
                        <?php submit_button(__('Iniciar configuração', 'bastionwp'), 'primary', 'submit', false); ?>
                    </form>
                </section>

            <?php elseif ($wizard_step === 1) : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-update"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 1', 'bastionwp'); ?></span><h2><?php echo esc_html__('Atualização do BastionWP', 'bastionwp'); ?></h2><p><?php echo esc_html__('O Assistente já verificou a fonte oficial e tentou atualizar automaticamente antes de continuar.', 'bastionwp'); ?></p></div></div>
                    <div class="bastionwp-wizard-update-state <?php echo !empty($update_status['update_available']) ? 'has-update' : 'is-current'; ?>">
                        <div><span><?php echo esc_html__('Versão instalada', 'bastionwp'); ?></span><strong><?php echo esc_html(BASTIONWP_VERSION); ?></strong></div>
                        <div><span><?php echo esc_html__('Versão disponível', 'bastionwp'); ?></span><strong><?php echo esc_html((string) ($update_status['latest_version'] ?: BASTIONWP_VERSION)); ?></strong></div>
                        <div><span><?php echo esc_html__('Estado', 'bastionwp'); ?></span><strong><?php echo !empty($update_status['update_available']) ? esc_html__('Atualização disponível', 'bastionwp') : esc_html__('Atualizado', 'bastionwp'); ?></strong></div>
                    </div>
                    <?php if (!empty($update_status['error'])) : ?><div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('A verificação encontrou uma limitação:', 'bastionwp'); ?></strong> <?php echo esc_html($update_status['error']); ?></div><?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-focus-footer-actions"><input type="hidden" name="action" value="bastionwp_wizard_step"><input type="hidden" name="next_step" value="2"><?php wp_nonce_field('bastionwp_wizard_step'); ?><?php submit_button(__('Continuar para varredura do site', 'bastionwp'), 'primary', 'submit', false); ?></form>
                </section>

            <?php elseif ($wizard_step === 2) : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-admin-plugins"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 2', 'bastionwp'); ?></span><h2><?php echo esc_html__('Varredura de plugins e integrações', 'bastionwp'); ?></h2><p><?php echo esc_html__('O BastionWP lista o que está instalado e destaca integrações relevantes para segurança e operação.', 'bastionwp'); ?></p></div></div>
                    <?php if (!empty($active_security_plugins) && empty($wordfence_status['active'])) : ?><div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('Outra solução de segurança já está ativa.', 'bastionwp'); ?></strong> <?php echo esc_html(sprintf(__('Detectado: %s. Revise compatibilidade antes de adicionar Wordfence para evitar sobreposição de firewall ou login.', 'bastionwp'), implode(', ', $active_security_plugins))); ?></div><?php endif; ?>
                    <div class="bastionwp-wizard-plugin-grid">
                        <?php foreach ($installed_plugins as $plugin_file => $plugin_data) : ?>
                            <article class="bastionwp-wizard-plugin-card"><div><strong><?php echo esc_html($plugin_data['Name']); ?></strong><small><?php echo esc_html(sprintf(__('Versão %s', 'bastionwp'), (string) $plugin_data['Version'])); ?></small></div><span class="bastionwp-hero-status <?php echo is_plugin_active($plugin_file) ? 'bastionwp-hero-status-success' : 'bastionwp-hero-status-info'; ?>"><?php echo is_plugin_active($plugin_file) ? esc_html__('Ativo', 'bastionwp') : esc_html__('Instalado', 'bastionwp'); ?></span></article>
                        <?php endforeach; ?>
                    </div>
                    <div class="bastionwp-wizard-required-integration">
                        <div><span class="bastionwp-overview-card-icon dashicons dashicons-shield-alt"></span><div><strong><?php echo esc_html__('Wordfence — integração recomendada para a política padrão BastionWP', 'bastionwp'); ?></strong><p><?php echo esc_html__('Firewall, scanner e proteção especializada continuam sendo configurados no próprio Wordfence. O BastionWP instala/ativa o plugin, mas não simula licença, 2FA ou otimização do WAF.', 'bastionwp'); ?></p></div></div>
                        <div class="bastionwp-integration-actions-bar">
                            <?php if (!$wordfence_status['installed']) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wordfence_install"><input type="hidden" name="return_to" value="wizard"><input type="hidden" name="wizard_step" value="2"><?php wp_nonce_field('bastionwp_wordfence_install'); ?><?php submit_button(__('Instalar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?></form>
                            <?php elseif (!$wordfence_status['active']) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wordfence_activate"><input type="hidden" name="return_to" value="wizard"><input type="hidden" name="wizard_step" value="2"><?php wp_nonce_field('bastionwp_wordfence_activate'); ?><?php submit_button(__('Ativar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?></form>
                            <?php else : ?><span class="bastionwp-hero-status bastionwp-hero-status-success"><span class="bastionwp-status-dot"></span><?php echo esc_html__('Wordfence ativo', 'bastionwp'); ?></span><?php endif; ?>
                        </div>
                    </div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-focus-footer-actions"><input type="hidden" name="action" value="bastionwp_wizard_step"><input type="hidden" name="next_step" value="3"><?php wp_nonce_field('bastionwp_wizard_step'); ?><?php submit_button(__('Continuar para usuários', 'bastionwp'), 'primary', 'submit', false); ?></form>
                </section>

            <?php elseif ($wizard_step === 3) : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-groups"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 3', 'bastionwp'); ?></span><h2><?php echo esc_html__('Quais usuários receberão proteção BastionWP?', 'bastionwp'); ?></h2><p><?php echo esc_html__('O Developer não aparece nesta lista. Marque os usuários que devem iniciar como Cliente Protegido; níveis avançados podem ser ajustados depois em Proteção de acesso.', 'bastionwp'); ?></p></div></div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wizard_users"><?php wp_nonce_field('bastionwp_wizard_users'); ?>
                        <div class="bastionwp-wizard-users-list">
                            <?php foreach ($site_users as $site_user) : $is_managed = BastionWP_Users::is_client_manager_user_id((int) $site_user->ID); ?>
                                <label class="bastionwp-wizard-user-row"><input type="checkbox" name="client_users[]" value="<?php echo esc_attr((string) $site_user->ID); ?>" <?php checked($is_managed); ?>><span><?php echo get_avatar($site_user->ID, 42); ?></span><div><strong><?php echo esc_html($site_user->display_name); ?></strong><small><?php echo esc_html($site_user->user_login . ' · ' . implode(', ', $site_user->roles)); ?></small></div><span class="bastionwp-hero-status <?php echo $is_managed ? 'bastionwp-hero-status-success' : 'bastionwp-hero-status-info'; ?>"><?php echo $is_managed ? esc_html__('Já gerenciado', 'bastionwp') : esc_html__('Não gerenciado', 'bastionwp'); ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="bastionwp-focus-footer-actions"><?php submit_button(__('Converter selecionados e continuar', 'bastionwp'), 'primary', 'submit', false); ?></div>
                    </form>
                </section>

            <?php elseif ($wizard_step === 4) : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-admin-network"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 4', 'bastionwp'); ?></span><h2><?php echo esc_html__('Permissões dos usuários convertidos', 'bastionwp'); ?></h2><p><?php echo esc_html__('Escolha Bloqueio total ou Personalizado. Menus administrativos amplos continuam bloqueados neste nível; para acesso administrativo completo, use Administrador Protegido depois da configuração inicial.', 'bastionwp'); ?></p></div></div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wizard_permissions"><?php wp_nonce_field('bastionwp_wizard_permissions'); ?>
                        <div class="bastionwp-wizard-permission-users">
                            <?php foreach (BastionWP_Users::get_client_managers() as $managed_user) : $uid=(int)$managed_user->ID; $mode=BastionWP_Menu_Access::get_user_mode($uid); $allowed=BastionWP_Menu_Access::get_user_allowed_groups($uid); ?>
                                <article class="bastionwp-wizard-permission-card">
                                    <div class="bastionwp-access-user-card-head"><?php echo get_avatar($uid,42); ?><div><strong><?php echo esc_html($managed_user->display_name); ?></strong><small><?php echo esc_html($managed_user->user_login); ?></small></div></div>
                                    <div class="bastionwp-mode-grid bastionwp-mode-grid-friendly">
                                        <label class="bastionwp-mode-card bastionwp-mode-card-strict"><input type="radio" name="mode_<?php echo esc_attr((string)$uid); ?>" value="strict" <?php checked($mode,'strict'); ?>><span class="bastionwp-mode-icon dashicons dashicons-lock"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Bloqueio total', 'bastionwp'); ?></strong><span><?php echo esc_html__('Somente áreas editoriais básicas.', 'bastionwp'); ?></span></span></label>
                                        <label class="bastionwp-mode-card bastionwp-mode-card-custom"><input type="radio" name="mode_<?php echo esc_attr((string)$uid); ?>" value="custom" <?php checked($mode,'custom'); ?>><span class="bastionwp-mode-icon dashicons dashicons-admin-generic"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Personalizado', 'bastionwp'); ?></strong><span><?php echo esc_html__('Permite escolher menus compatíveis.', 'bastionwp'); ?></span></span></label>
                                    </div>
                                    <div class="bastionwp-wizard-menu-grid <?php echo $mode !== 'custom' ? 'is-locked' : ''; ?>" data-permission-menus>
                                        <div class="bastionwp-menu-lock-overlay"><span class="dashicons dashicons-lock"></span><?php echo esc_html__('Selecione Personalizado para habilitar menus.', 'bastionwp'); ?></div>
                                        <?php foreach ($menu_catalog as $menu_id=>$menu_item) : $selected=isset($allowed[$menu_id]); $needs_adapter=!empty($menu_item['requires_adapter']); ?>
                                            <label class="bastionwp-menu-option bastionwp-menu-option-switch <?php echo $needs_adapter ? 'requires-adapter' : ''; ?>"><span class="bastionwp-menu-option-copy"><strong><?php echo esc_html($menu_item['label']); ?></strong><?php if ($needs_adapter) : ?><small><?php echo esc_html__('Requer Administrador Protegido ou compatibilidade BastionWP futura.', 'bastionwp'); ?></small><?php else : ?><small><?php echo esc_html__('Delegação segura disponível com as permissões atuais.', 'bastionwp'); ?></small><?php endif; ?></span><input class="bastionwp-menu-switch" type="checkbox" name="menus_<?php echo esc_attr((string)$uid); ?>[]" value="<?php echo esc_attr($menu_id); ?>" <?php checked($selected); ?> <?php disabled($needs_adapter); ?>></label>
                                        <?php endforeach; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="bastionwp-focus-footer-actions"><?php submit_button(__('Salvar permissões e continuar', 'bastionwp'), 'primary', 'submit', false); ?></div>
                    </form>
                </section>

            <?php elseif ($wizard_step === 5) : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-shield"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 5', 'bastionwp'); ?></span><h2><?php echo esc_html__('Perfil e preflight de Segurança', 'bastionwp'); ?></h2><p><?php echo esc_html__('Escolha o nível de Segurança. Antes de aplicar, o BastionWP também verifica proteções já efetivas e informa a origem quando consegue identificá-la com segurança.', 'bastionwp'); ?></p></div></div>
                    <form id="bastionwp-hardening-root" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wizard_hardening"><?php wp_nonce_field('bastionwp_wizard_hardening'); ?>
                        <div class="bastionwp-hardening-profiles bastionwp-hardening-profiles-row">
                            <?php foreach ($hardening_profiles as $profile_key=>$profile_data) : $intensity=$security_intensity[$profile_key] ?? ['label'=>__('Proteção', 'bastionwp'),'class'=>'neutral']; ?><label class="bastionwp-hardening-profile bastionwp-hardening-profile-modern <?php echo $hardening_profile === $profile_key ? 'is-applied' : ''; ?>"><input type="radio" name="hardening_profile" value="<?php echo esc_attr($profile_key); ?>" <?php checked($profile_key, $wizard_selected_profile); ?>><span class="bastionwp-hardening-profile-icon dashicons dashicons-shield"></span><span class="bastionwp-hardening-profile-copy"><strong><?php echo esc_html($profile_data['label']); ?></strong><small><?php echo esc_html($profile_data['description']); ?></small><span class="bastionwp-security-intensity is-<?php echo esc_attr($intensity['class']); ?>"><?php echo esc_html($intensity['label']); ?></span></span><span class="bastionwp-hardening-radio-visual"></span></label><?php endforeach; ?>
                        </div>
                        <div class="bastionwp-wizard-security-preview"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-shield-alt" aria-hidden="true"></span><div><h3><?php echo esc_html__('Regras do perfil selecionado', 'bastionwp'); ?></h3><p><?php echo esc_html__('Esta prévia muda imediatamente ao selecionar outro perfil.', 'bastionwp'); ?></p></div></div><div class="bastionwp-effective-rules bastionwp-effective-rules-compact"><?php $preview_settings=$hardening_ui_profiles[$wizard_selected_profile]['settings'] ?? []; $wizard_rule_preview=['block_file_editors'=>[__('Editor de arquivos','bastionwp'),__('Bloqueado','bastionwp'),__('Permitido','bastionwp')],'disable_xmlrpc'=>[__('XML-RPC','bastionwp'),__('Bloqueado','bastionwp'),__('Permitido','bastionwp')],'disable_application_passwords'=>[__('Application Passwords','bastionwp'),__('Bloqueadas','bastionwp'),__('Permitidas','bastionwp')],'hide_wordpress_version'=>[__('Versão do WordPress','bastionwp'),__('Ocultada','bastionwp'),__('Visível','bastionwp')],'generic_login_errors'=>[__('Erros de login','bastionwp'),__('Genéricos','bastionwp'),__('Padrão WordPress','bastionwp')],'block_public_rest_users'=>[__('REST Users público','bastionwp'),__('Bloqueado','bastionwp'),__('Padrão WordPress','bastionwp')],'block_manual_infrastructure_changes'=>[__('Alterações manuais de plugins/temas/core','bastionwp'),__('Bloqueadas','bastionwp'),__('Permitidas ao Developer','bastionwp')]]; foreach($wizard_rule_preview as $rule_key=>$rule_data) : $enabled=!empty($preview_settings[$rule_key]); ?><div class="bastionwp-effective-rule" data-hardening-rule="<?php echo esc_attr($rule_key); ?>"><div class="bastionwp-effective-rule-head"><div class="bastionwp-rule-title"><strong><?php echo esc_html($rule_data[0]); ?></strong></div><span class="bastionwp-rule-badge <?php echo $enabled?'bastionwp-badge-blocked':'bastionwp-badge-allowed'; ?>"><?php echo esc_html($enabled?$rule_data[1]:$rule_data[2]); ?></span></div><small class="bastionwp-rule-helper"></small></div><?php endforeach; ?></div></div><h3 class="bastionwp-preflight-heading"><?php echo esc_html__('Conflitos e proteções já existentes', 'bastionwp'); ?></h3>
                        <div class="bastionwp-preflight-list">
                            <?php foreach ($hardening_preflight as $preflight) : $source_lower = function_exists('mb_strtolower') ? mb_strtolower((string)($preflight['source'] ?? '')) : strtolower((string)($preflight['source'] ?? '')); $external_identified = !empty($preflight['protected']) && $source_lower !== '' && !str_contains($source_lower, 'nenhuma origem') && !str_contains($source_lower, 'origem não identificada') && !str_contains($source_lower, 'origem nao identificada'); $default_owner = $external_identified ? 'external' : 'bastion'; ?>
                                <article class="bastionwp-preflight-item"><div><span class="bastionwp-diagnostic-state <?php echo !empty($preflight['protected']) ? 'bastionwp-diagnostic-ok' : 'bastionwp-diagnostic-warning'; ?>"></span><div><strong><?php echo esc_html($preflight['label']); ?></strong><small><?php echo esc_html($preflight['description']); ?></small><code><?php echo esc_html(sprintf(__('Origem detectada: %s', 'bastionwp'), $preflight['source'])); ?></code></div></div><div class="bastionwp-preflight-choices"><label class="<?php echo !$external_identified ? 'is-disabled' : ''; ?>"><input type="radio" name="ownership[<?php echo esc_attr($preflight['key']); ?>]" value="external" <?php checked($default_owner,'external'); ?> <?php disabled(!$external_identified); ?>><?php echo !$external_identified ? esc_html__('Nenhuma proteção externa identificada', 'bastionwp') : esc_html__('Manter configuração existente', 'bastionwp'); ?></label><label><input type="radio" name="ownership[<?php echo esc_attr($preflight['key']); ?>]" value="bastion" <?php checked($default_owner,'bastion'); ?>><?php echo esc_html__('Gerenciar também pelo BastionWP', 'bastionwp'); ?></label></div></article>
                            <?php endforeach; ?>
                        </div>
                        <div class="bastionwp-callout"><strong><?php echo esc_html__('O BastionWP não edita silenciosamente configurações de outros plugins ou arquivos externos.', 'bastionwp'); ?></strong> <?php echo esc_html__('Quando você escolhe “Manter configuração existente”, o Bastion deixa aquela regra sob responsabilidade da origem detectada.', 'bastionwp'); ?></div>
                        <div class="bastionwp-focus-footer-actions"><?php submit_button(__('Aplicar Segurança e revisar', 'bastionwp'), 'primary', 'submit', false); ?></div>
                    </form>
                </section>

            <?php else : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-yes-alt"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 6', 'bastionwp'); ?></span><h2><?php echo esc_html__('Revisão final', 'bastionwp'); ?></h2><p><?php echo esc_html__('Confira o resultado antes de encerrar o modo foco.', 'bastionwp'); ?></p></div></div>
                    <div class="bastionwp-wizard-review-grid">
                        <div><span><?php echo esc_html__('Bastion Core', 'bastionwp'); ?></span><strong><?php echo esc_html($core_status['status']); ?></strong></div>
                        <div><span><?php echo esc_html__('Clientes Protegidos', 'bastionwp'); ?></span><strong><?php echo esc_html((string) count(BastionWP_Users::get_client_managers())); ?></strong></div>
                        <div><span><?php echo esc_html__('Segurança', 'bastionwp'); ?></span><strong><?php echo esc_html($hardening_profiles[BastionWP_Hardening::get_profile()]['label'] ?? __('Não configurado', 'bastionwp')); ?></strong></div>
                        <div><span><?php echo esc_html__('Wordfence', 'bastionwp'); ?></span><strong><?php echo !empty($wordfence_status['active']) ? esc_html__('Ativo', 'bastionwp') : esc_html__('Revisar depois', 'bastionwp'); ?></strong></div>
                        <div><span><?php echo esc_html__('Diagnóstico', 'bastionwp'); ?></span><strong><?php echo esc_html(sprintf(__('%1$d erros · %2$d atenções', 'bastionwp'), (int)($diagnostics_report['summary']['error']??0), (int)($diagnostics_report['summary']['warning']??0))); ?></strong></div>
                    </div>
                    <?php if (empty($wizard_progress['can_complete'])) : ?><div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('Ainda existem itens obrigatórios para revisar.', 'bastionwp'); ?></strong><p><?php echo esc_html__('Resolva os itens abaixo para liberar a conclusão. Avisos de integrações ou indisponibilidade do GitHub não bloqueiam mais o Assistente.', 'bastionwp'); ?></p></div><div class="bastionwp-wizard-blockers"><?php foreach ($wizard_blocking_steps as $blocking_step) : ?><article><span class="dashicons <?php echo ($blocking_step['status']??'')==='error'?'dashicons-dismiss':'dashicons-warning'; ?>"></span><div><strong><?php echo esc_html($blocking_step['title']); ?></strong><small><?php echo esc_html($blocking_step['value']); ?></small></div><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=' . $blocking_step['tab'])); ?>"><?php echo esc_html__('Revisar', 'bastionwp'); ?></a></article><?php endforeach; ?></div><?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-focus-footer-actions"><input type="hidden" name="action" value="bastionwp_complete_wizard"><?php wp_nonce_field('bastionwp_complete_wizard'); ?><?php submit_button(__('Concluir configuração inicial', 'bastionwp'), 'primary', 'submit', false, empty($wizard_progress['can_complete']) ? ['disabled'=>'disabled'] : []); ?></form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>


<?php elseif ($tab === 'access') : ?>
    <?php
    $access_section = isset($_GET['access_section']) ? sanitize_key(wp_unslash($_GET['access_section'])) : 'users';
    if (!in_array($access_section, ['users', 'permissions', 'requests'], true)) {
        $access_section = 'users';
    }
    ?>
    <div class="bastionwp-access-accordion" data-bastionwp-access-accordion>
        <details class="bastionwp-access-section" <?php echo $access_section === 'users' ? 'open' : ''; ?>>
            <summary>
                <span class="bastionwp-overview-card-icon dashicons dashicons-groups"></span>
                <div><span class="bastionwp-eyebrow"><?php echo esc_html__('Usuários', 'bastionwp'); ?></span><strong><?php echo esc_html__('Níveis de acesso do site', 'bastionwp'); ?></strong><small><?php echo esc_html__('Veja todos os usuários, exceto o Developer, e identifique rapidamente a proteção aplicada.', 'bastionwp'); ?></small></div>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </summary>
            <div class="bastionwp-access-section-body">
                <div class="bastionwp-access-flow-note"><strong><?php echo esc_html__('Modelo de acesso', 'bastionwp'); ?></strong><span><?php echo esc_html__('WordPress Nativo mantém a role original; Cliente Protegido aplica menor privilégio; Administrador Protegido concede administração real com bloqueios BastionWP escolhidos pelo Developer.', 'bastionwp'); ?></span></div>
                <div class="bastionwp-access-users-list-lines bastionwp-access-users-table">
                    <div class="bastionwp-access-users-head" aria-hidden="true"><span><?php echo esc_html__('Usuário', 'bastionwp'); ?></span><span><?php echo esc_html__('Nível', 'bastionwp'); ?></span><span><?php echo esc_html__('Política', 'bastionwp'); ?></span><span><?php echo esc_html__('Menus / acesso', 'bastionwp'); ?></span><span></span></div>
                    <?php foreach ($site_users as $site_user) :
                        $uid = (int) $site_user->ID;
                        $level = BastionWP_Users::get_access_level($uid);
                        $mode = $level === BastionWP_Users::ACCESS_LEVEL_CLIENT ? BastionWP_Menu_Access::get_user_mode($uid) : '';
                        $active = $level === BastionWP_Users::ACCESS_LEVEL_CLIENT ? BastionWP_Menu_Access::get_user_active_groups($uid) : [];
                        $policy = $level === BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN ? BastionWP_Protected_Admin::get_policy($uid) : [];
                        $role_label = !empty($site_user->roles) ? implode(', ', array_map(static function ($role_key) use ($native_role_options) { return $native_role_options[$role_key] ?? $role_key; }, $site_user->roles)) : __('Sem role', 'bastionwp');
                        $policy_count = $policy ? count(array_filter($policy)) : 0;
                    ?>
                        <article class="bastionwp-access-user-line">
                            <div class="bastionwp-access-user-line-person"><?php echo get_avatar($uid, 46); ?><div><strong><?php echo esc_html($site_user->display_name); ?></strong><small><?php echo esc_html($site_user->user_login . ' · ' . $role_label); ?></small></div></div>
                            <div class="bastionwp-access-user-line-status">
                                <?php if ($level === BastionWP_Users::ACCESS_LEVEL_CLIENT) : ?>
                                    <span class="bastionwp-access-role-pill is-client"><span class="bastionwp-status-dot"></span><?php echo esc_html__('Cliente Protegido', 'bastionwp'); ?></span>
                                <?php elseif ($level === BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN) : ?>
                                    <span class="bastionwp-access-role-pill is-protected-admin"><span class="bastionwp-status-dot"></span><?php echo esc_html__('Administrador Protegido', 'bastionwp'); ?></span>
                                <?php else : ?>
                                    <?php if (in_array('administrator', (array) $site_user->roles, true)) : ?>
                                        <span class="bastionwp-access-role-pill is-native-admin"><?php echo esc_html__('Administrador nativo', 'bastionwp'); ?></span>
                                    <?php else : ?>
                                        <span class="bastionwp-access-role-pill is-native"><?php echo esc_html__('WordPress Nativo', 'bastionwp'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="bastionwp-access-user-line-policy">
                                <?php if ($level === BastionWP_Users::ACCESS_LEVEL_CLIENT) : ?><strong><?php echo $mode === 'custom' ? esc_html__('Personalizado', 'bastionwp') : esc_html__('Bloqueio total', 'bastionwp'); ?></strong><small><?php echo esc_html__('Política BastionWP de menor privilégio', 'bastionwp'); ?></small>
                                <?php elseif ($level === BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN) : ?><strong><?php echo esc_html(sprintf(__('%d proteções ativas', 'bastionwp'), $policy_count)); ?></strong><small><?php echo esc_html__('Administrador real com restrições selecionadas', 'bastionwp'); ?></small>
                                <?php else : ?><strong><?php echo esc_html($role_label); ?></strong><small><?php echo esc_html__('Permissões padrão do WordPress', 'bastionwp'); ?></small><?php endif; ?>
                            </div>
                            <div class="bastionwp-access-user-line-menus">
                                <?php if ($level === BastionWP_Users::ACCESS_LEVEL_CLIENT && $mode === 'custom' && !empty($active)) : ?><div class="bastionwp-active-tags"><?php foreach (array_slice($active, 0, 4) as $group) : ?><span class="bastionwp-active-tag"><span class="dashicons dashicons-yes-alt"></span><?php echo esc_html($group['label'] ?? $group['top_slug']); ?></span><?php endforeach; ?></div>
                                <?php elseif ($level === BastionWP_Users::ACCESS_LEVEL_CLIENT) : ?><small><?php echo esc_html__('Somente áreas editoriais básicas.', 'bastionwp'); ?></small>
                                <?php elseif ($level === BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN) : ?><small><?php echo esc_html__('Administração do WordPress, exceto bloqueios ativos.', 'bastionwp'); ?></small>
                                <?php else : ?><small><?php echo esc_html__('Conforme a role nativa.', 'bastionwp'); ?></small><?php endif; ?>
                            </div>
                            <div class="bastionwp-access-user-line-action"><a class="button" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'access','access_section'=>'permissions','access_user'=>$uid], admin_url('admin.php'))); ?>"><?php echo esc_html__('Configurar proteção', 'bastionwp'); ?></a></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>

        <details class="bastionwp-access-section" <?php echo $access_section === 'permissions' ? 'open' : ''; ?>>
            <summary><span class="bastionwp-overview-card-icon dashicons dashicons-admin-network"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Permissões', 'bastionwp'); ?></span><strong><?php echo esc_html__('Definir nível e proteções', 'bastionwp'); ?></strong><small><?php echo esc_html__('Escolha o nível de confiança adequado para cada usuário.', 'bastionwp'); ?></small></div><span class="dashicons dashicons-arrow-down-alt2"></span></summary>
            <div class="bastionwp-access-section-body">
                <?php if (empty($site_users)) : ?><div class="bastionwp-empty-state"><span class="dashicons dashicons-groups"></span><p><?php echo esc_html__('Nenhum usuário disponível além do Developer.', 'bastionwp'); ?></p></div>
                <?php else : ?>
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-access-permission-selector"><input type="hidden" name="page" value="bastionwp"><input type="hidden" name="tab" value="access"><input type="hidden" name="access_section" value="permissions"><div><label class="bastionwp-field-label" for="access_user"><?php echo esc_html__('Usuário', 'bastionwp'); ?></label><select id="access_user" name="access_user"><?php foreach ($site_users as $access_user_option) : ?><option value="<?php echo esc_attr((string) $access_user_option->ID); ?>" <?php selected($selected_access_user_id, (int) $access_user_option->ID); ?>><?php echo esc_html($access_user_option->display_name . ' (' . $access_user_option->user_login . ')'); ?></option><?php endforeach; ?></select><?php submit_button(__('Selecionar usuário', 'bastionwp'),'secondary','submit',false); ?></div><?php if ($selected_access_user) : ?><div class="bastionwp-access-selected-inline"><?php echo get_avatar($selected_access_user->ID,46); ?><div><strong><?php echo esc_html($selected_access_user->display_name); ?></strong><small><?php echo esc_html(BastionWP_Users::access_level_label((int) $selected_access_user->ID)); ?></small></div></div><?php endif; ?></form>

                    <?php if ($selected_access_user) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-bastionwp-access-level-form>
                        <input type="hidden" name="action" value="bastionwp_save_user_access_policy"><input type="hidden" name="access_user_id" value="<?php echo esc_attr((string) $selected_access_user_id); ?>"><?php wp_nonce_field('bastionwp_save_user_access_policy'); ?>
                        <h3><?php echo esc_html__('Nível de acesso', 'bastionwp'); ?></h3>
                        <div class="bastionwp-access-level-grid">
                            <label class="bastionwp-access-level-card"><input type="radio" name="access_level" value="native" <?php checked($selected_access_level, BastionWP_Users::ACCESS_LEVEL_NATIVE); ?>><span class="dashicons dashicons-wordpress"></span><strong><?php echo esc_html__('WordPress Nativo', 'bastionwp'); ?></strong><small><?php echo esc_html__('Mantém uma role padrão e não cria permissões adicionais.', 'bastionwp'); ?></small></label>
                            <label class="bastionwp-access-level-card"><input type="radio" name="access_level" value="client" <?php checked($selected_access_level, BastionWP_Users::ACCESS_LEVEL_CLIENT); ?>><span class="dashicons dashicons-shield"></span><strong><?php echo esc_html__('Cliente Protegido', 'bastionwp'); ?></strong><small><?php echo esc_html__('Menor privilégio com Bloqueio total ou menus personalizados seguros.', 'bastionwp'); ?></small></label>
                            <label class="bastionwp-access-level-card is-admin"><input type="radio" name="access_level" value="protected_admin" <?php checked($selected_access_level, BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN); ?>><span class="dashicons dashicons-admin-network"></span><strong><?php echo esc_html__('Administrador Protegido', 'bastionwp'); ?></strong><small><?php echo esc_html__('Administrador real do WordPress com proteções BastionWP selecionáveis.', 'bastionwp'); ?></small></label>
                        </div>

                        <section class="bastionwp-access-level-panel" data-access-level-panel="native" <?php echo $selected_access_level !== BastionWP_Users::ACCESS_LEVEL_NATIVE ? 'hidden' : ''; ?>>
                            <div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Permissões nativas do WordPress', 'bastionwp'); ?></strong><p><?php echo esc_html__('O BastionWP não adiciona capabilities extras neste nível. As proteções globais de Segurança do site continuam ativas. Para administração completa, escolha Administrador Protegido.', 'bastionwp'); ?></p></div>
                            <label class="bastionwp-field-label" for="native_role"><?php echo esc_html__('Política do WordPress', 'bastionwp'); ?></label><select id="native_role" name="native_role"><?php foreach ($native_role_options as $role_key=>$role_name) : ?><option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_native_role,$role_key); ?>><?php echo esc_html($role_name); ?></option><?php endforeach; ?></select>
                        </section>

                        <section class="bastionwp-access-level-panel" data-access-level-panel="client" <?php echo $selected_access_level !== BastionWP_Users::ACCESS_LEVEL_CLIENT ? 'hidden' : ''; ?>>
                            <div class="bastionwp-mode-grid bastionwp-mode-grid-friendly"><label class="bastionwp-mode-card bastionwp-mode-card-strict"><input type="radio" name="client_access_mode" value="strict" <?php checked($client_access_mode,'strict'); ?>><span class="bastionwp-mode-icon dashicons dashicons-lock"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Bloqueio total', 'bastionwp'); ?></strong><span><?php echo esc_html__('Mantém somente as áreas editoriais básicas.', 'bastionwp'); ?></span></span></label><label class="bastionwp-mode-card bastionwp-mode-card-custom"><input type="radio" name="client_access_mode" value="custom" <?php checked($client_access_mode,'custom'); ?>><span class="bastionwp-mode-icon dashicons dashicons-admin-generic"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Personalizado', 'bastionwp'); ?></strong><span><?php echo esc_html__('Permite escolher menus que possam ser delegados sem elevar privilégios globais.', 'bastionwp'); ?></span></span></label></div>
                            <div class="bastionwp-access-menu-lockable <?php echo $client_access_mode!=='custom'?'is-locked':''; ?>" data-bastionwp-menu-lockable><div class="bastionwp-menu-lock-overlay"><span class="dashicons dashicons-lock"></span><strong><?php echo esc_html__('Menus adicionais bloqueados', 'bastionwp'); ?></strong><small><?php echo esc_html__('Selecione Personalizado para liberar esta área.', 'bastionwp'); ?></small></div><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-admin-plugins"></span><div><h3><?php echo esc_html__('Menus adicionais detectados', 'bastionwp'); ?></h3><p><?php echo esc_html__('Menus administrativos amplos não recebem permissões globais. Para esses casos, utilize Administrador Protegido ou uma compatibilidade BastionWP específica no futuro.', 'bastionwp'); ?></p></div></div><div class="bastionwp-menu-list bastionwp-menu-list-switches">
                                <?php $selected_ids = array_keys($client_allowed_groups); foreach ($menu_catalog as $menu_id => $menu_item) :
                                    $needs_adapter = !empty($menu_item['requires_adapter']);
                                    $compat_enabled = class_exists('BastionWP_Plugin_Compatibility') && BastionWP_Plugin_Compatibility::is_enabled((string) $menu_id);
                                    $compat_can_enable = class_exists('BastionWP_Plugin_Compatibility') && BastionWP_Plugin_Compatibility::can_enable($menu_item);
                                    $menu_locked = $needs_adapter && !$compat_enabled;
                                    $compat_label = class_exists('BastionWP_Plugin_Compatibility') ? BastionWP_Plugin_Compatibility::compatibility_label($menu_item) : '';
                                ?>
                                    <div class="bastionwp-menu-option bastionwp-menu-option-switch <?php echo $menu_locked ? 'requires-adapter' : ($compat_enabled ? 'has-compatibility' : ''); ?>">
                                        <div class="bastionwp-menu-option-layout">
                                            <div class="bastionwp-menu-option-details">
                                                <span class="bastionwp-menu-option-copy">
                                                    <strong><?php echo esc_html($menu_item['label']); ?></strong>
                                                    <small><?php echo esc_html($compat_label); ?></small>
                                                    <?php if ($compat_enabled) : ?><small class="bastionwp-compatibility-note"><?php echo esc_html__('Permissão administrativa limitada ao contexto reconhecido deste plugin.', 'bastionwp'); ?></small><?php endif; ?>
                                                </span>
                                                <?php if ($needs_adapter && $compat_can_enable) : ?>
                                                    <?php $compat_url = wp_nonce_url(add_query_arg(['action'=>'bastionwp_toggle_plugin_compatibility','group_id'=>$menu_id,'enabled'=>$compat_enabled ? 0 : 1,'access_user'=>$selected_access_user_id], admin_url('admin-post.php')), 'bastionwp_toggle_plugin_compatibility'); ?>
                                                    <div class="bastionwp-compatibility-action">
                                                        <span class="dashicons <?php echo $compat_enabled ? 'dashicons-shield-alt' : 'dashicons-admin-network'; ?>"></span>
                                                        <span><?php echo $compat_enabled ? esc_html__('Compatibilidade BastionWP ativa para este plugin.', 'bastionwp') : esc_html__('A origem deste plugin foi reconhecida e pode receber uma compatibilidade de acesso escopada.', 'bastionwp'); ?></span>
                                                        <a class="button button-small" href="<?php echo esc_url($compat_url); ?>"><?php echo $compat_enabled ? esc_html__('Desabilitar compatibilidade', 'bastionwp') : esc_html__('Habilitar compatibilidade', 'bastionwp'); ?></a>
                                                    </div>
                                                <?php elseif ($needs_adapter && !$compat_can_enable) : ?>
                                                    <div class="bastionwp-compatibility-action is-blocked"><span class="dashicons dashicons-lock"></span><span><?php echo esc_html__('A fronteira deste plugin não pôde ser determinada com segurança. Utilize Administrador Protegido para acesso completo.', 'bastionwp'); ?></span></div>
                                                <?php endif; ?>
                                            </div>
                                            <label class="bastionwp-menu-option-toggle">
                                                <span class="screen-reader-text"><?php echo esc_html(sprintf(__('Habilitar %s para o Cliente Protegido', 'bastionwp'), $menu_item['label'])); ?></span>
                                                <input class="bastionwp-menu-switch" type="checkbox" name="allowed_menus[]" value="<?php echo esc_attr($menu_id); ?>" <?php checked(in_array($menu_id, $selected_ids, true)); ?> <?php disabled($menu_locked); ?>>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div></div>
                        </section>

                        <section class="bastionwp-access-level-panel" data-access-level-panel="protected_admin" <?php echo $selected_access_level !== BastionWP_Users::ACCESS_LEVEL_PROTECTED_ADMIN ? 'hidden' : ''; ?>>
                            <div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('Nível de alta confiança', 'bastionwp'); ?></strong><p><?php echo esc_html__('Este usuário terá a role Administrator real do WordPress. Plugins administrativos funcionarão normalmente, exceto operações bloqueadas abaixo ou por uma política global de Segurança do site.', 'bastionwp'); ?></p></div>
                            <h3><?php echo esc_html__('Proteções adicionais', 'bastionwp'); ?></h3>
                            <div class="bastionwp-protected-admin-policy-grid">
                                <?php $protected_policy_labels = [
                                    'block_file_editor'=>[__('Bloquear editor PHP do WordPress','bastionwp'),__('Impede editores de arquivos de plugins e temas.','bastionwp')],
                                    'block_code_tools'=>[__('Bloquear ferramentas de execução PHP','bastionwp'),__('Bloqueia Code Snippets e rotas conhecidas de execução de snippets.','bastionwp')],
                                    'block_plugin_install'=>[__('Bloquear instalação de plugins','bastionwp'),__('Remove a capability de instalar plugins.','bastionwp')],
                                    'block_plugin_delete'=>[__('Bloquear exclusão de plugins','bastionwp'),__('Impede excluir plugins instalados.','bastionwp')],
                                    'block_plugin_activation'=>[__('Bloquear ativação/desativação de plugins','bastionwp'),__('Impede alterar o estado dos plugins.','bastionwp')],
                                    'block_theme_install'=>[__('Bloquear instalação de temas','bastionwp'),__('Impede instalar novos temas.','bastionwp')],
                                    'block_theme_delete'=>[__('Bloquear exclusão de temas','bastionwp'),__('Impede remover temas.','bastionwp')],
                                    'block_theme_activation'=>[__('Bloquear troca de tema','bastionwp'),__('Impede ativar outro tema.','bastionwp')],
                                    'block_users'=>[__('Bloquear administração de usuários','bastionwp'),__('Impede criar, editar, promover ou remover usuários.','bastionwp')],
                                    'block_updates'=>[__('Bloquear atualizações manuais','bastionwp'),__('Impede atualizar core, plugins e temas manualmente.','bastionwp')],
                                    'protect_bastion'=>[__('Proteger BastionWP','bastionwp'),__('Mantém a desativação convencional do BastionWP bloqueada para este usuário.','bastionwp')],
                                    'protect_other_admins'=>[__('Proteger outros Administradores','bastionwp'),__('Impede editar, remover ou rebaixar outras contas Administrator.','bastionwp')],
                                ]; foreach ($protected_policy_labels as $policy_key=>$policy_copy) : ?>
                                    <label class="bastionwp-switch-row bastionwp-protected-admin-switch"><span><strong><?php echo esc_html($policy_copy[0]); ?></strong><small><?php echo esc_html($policy_copy[1]); ?></small></span><input type="checkbox" name="protected_admin_policy[<?php echo esc_attr($policy_key); ?>]" value="1" <?php checked(!empty($selected_protected_admin_policy[$policy_key])); ?>></label>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <div class="bastionwp-access-savebar"><span class="description"><?php echo esc_html__('As alterações afetam somente o usuário selecionado. Alterar o nível pode trocar a role WordPress da conta.', 'bastionwp'); ?></span><?php submit_button(__('Salvar nível e proteções', 'bastionwp'),'primary','submit',false); ?></div>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </details>

        <details class="bastionwp-access-section" <?php echo $access_section === 'requests' ? 'open' : ''; ?>>
            <summary><span class="bastionwp-overview-card-icon dashicons dashicons-unlock"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Solicitações', 'bastionwp'); ?></span><strong><?php echo esc_html__('Privilégios temporários', 'bastionwp'); ?></strong><small><?php echo esc_html__('Revise pedidos de configuração temporária sem sair da Proteção de acesso.', 'bastionwp'); ?></small></div><?php if ($pending_request_count > 0) : ?><span class="bastionwp-accordion-badge"><?php echo esc_html((string) $pending_request_count); ?></span><?php endif; ?><span class="dashicons dashicons-arrow-down-alt2"></span></summary>
            <div class="bastionwp-access-section-body bastionwp-access-requests-body"><?php require BASTIONWP_DIR . 'admin/views/requests-panel.php'; ?></div>
        </details>
    </div>

<?php elseif ($tab === 'hardening') : ?>
        <?php
        $hardening_icon_map = [
            BastionWP_Hardening::PROFILE_DEVELOPMENT => 'dashicons-editor-code',
            BastionWP_Hardening::PROFILE_STAGING     => 'dashicons-admin-tools',
            BastionWP_Hardening::PROFILE_PRODUCTION  => 'dashicons-admin-site-alt3',
            BastionWP_Hardening::PROFILE_LOCKED      => 'dashicons-lock',
        ];
        $security_intensity = [
            BastionWP_Hardening::PROFILE_DEVELOPMENT => ['label' => __('Proteção mínima', 'bastionwp'), 'class' => 'minimal'],
            BastionWP_Hardening::PROFILE_STAGING => ['label' => __('Proteção moderada', 'bastionwp'), 'class' => 'moderate'],
            BastionWP_Hardening::PROFILE_PRODUCTION => ['label' => __('Proteção alta', 'bastionwp'), 'class' => 'high'],
            BastionWP_Hardening::PROFILE_LOCKED => ['label' => __('Mais restritiva', 'bastionwp'), 'class' => 'maximum'],
        ];
        $selected_profile_preview = $hardening_ui_profiles[$hardening_profile]['settings'] ?? $hardening_effective;
        $hardening_policy_without_ownership = BastionWP_Hardening::get_effective_settings($hardening_profile, false);
        $two_factor_provider = '';
        if (!empty($wordfence_status['active'])) {
            $two_factor_provider = __('Wordfence ativo — o estado do 2FA deve ser confirmado na própria ferramenta.', 'bastionwp');
        } elseif (function_exists('is_plugin_active') && is_plugin_active('two-factor/two-factor.php')) {
            $two_factor_provider = __('Plugin Two-Factor ativo — confirme quais usuários estão com 2FA configurado.', 'bastionwp');
        } elseif (function_exists('is_plugin_active') && is_plugin_active('wp-2fa/wp-2fa.php')) {
            $two_factor_provider = __('WP 2FA ativo — confirme a política diretamente no plugin.', 'bastionwp');
        }
        $security_section = isset($_GET['security_section']) ? sanitize_key(wp_unslash($_GET['security_section'])) : 'state';
        if (!in_array($security_section, ['state', 'wordpress', 'login', 'http', 'rest', 'monitor', 'additional'], true)) {
            $security_section = 'state';
        }
        $security_source_label = static function (string $source) use ($installed_plugins): string {
            $normalized = str_replace('\\', '/', $source);
            if (preg_match('#wp-content/plugins/([^/]+)/#i', $normalized, $matches)) {
                $slug = strtolower((string) $matches[1]);
                foreach ($installed_plugins as $plugin_file => $plugin_data) {
                    $plugin_slug = strtolower((string) strtok($plugin_file, '/'));
                    if ($plugin_slug === $slug) {
                        return (string) ($plugin_data['Name'] ?? $slug);
                    }
                }
                return $slug;
            }
            if (str_contains($normalized, 'wp-config.php')) {
                return __('wp-config.php / WordPress', 'bastionwp');
            }
            if (str_contains($normalized, 'PHP / wp-config.php / servidor')) {
                return __('PHP / servidor', 'bastionwp');
            }
            return $source !== '' ? $source : __('Origem não identificada', 'bastionwp');
        };
        ?>

        <div class="bastionwp-security-layout" data-bastionwp-security-layout data-security-section="<?php echo esc_attr($security_section); ?>">
            <aside class="bastionwp-security-sidebar" aria-label="<?php echo esc_attr__('Áreas de Segurança', 'bastionwp'); ?>">
                <div class="bastionwp-security-sidebar-head"><span class="bastionwp-eyebrow"><?php echo esc_html__('Configurações', 'bastionwp'); ?></span><strong><?php echo esc_html__('Segurança', 'bastionwp'); ?></strong></div>
                <nav class="bastionwp-security-sidebar-nav">
                    <a data-security-target="state" class="<?php echo $security_section === 'state' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'state'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-shield"></span><span><strong><?php echo esc_html__('Estado de segurança', 'bastionwp'); ?></strong><small><?php echo esc_html__('Perfil e regras aplicadas', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="wordpress" class="<?php echo $security_section === 'wordpress' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'wordpress'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-wordpress"></span><span><strong><?php echo esc_html__('WordPress e arquivos', 'bastionwp'); ?></strong><small><?php echo esc_html__('PHP, XML-RPC e integridade', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="login" class="<?php echo $security_section === 'login' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'login'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-lock"></span><span><strong><?php echo esc_html__('Login e sessão', 'bastionwp'); ?></strong><small><?php echo esc_html__('Brute force e cache privado', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="http" class="<?php echo $security_section === 'http' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'http'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-admin-site-alt3"></span><span><strong><?php echo esc_html__('HTTP e navegador', 'bastionwp'); ?></strong><small><?php echo esc_html__('Headers, CSP e HSTS', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="rest" class="<?php echo $security_section === 'rest' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'rest'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-rest-api"></span><span><strong><?php echo esc_html__('REST e APIs', 'bastionwp'); ?></strong><small><?php echo esc_html__('Inventário e allowlist', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="monitor" class="<?php echo $security_section === 'monitor' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'monitor'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-chart-area"></span><span><strong><?php echo esc_html__('Monitoramento', 'bastionwp'); ?></strong><small><?php echo esc_html__('WAF, DNS, TLS e alertas', 'bastionwp'); ?></small></span></a>
                    <a data-security-target="additional" class="<?php echo $security_section === 'additional' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'hardening','security_section'=>'additional'], admin_url('admin.php'))); ?>"><span class="dashicons dashicons-admin-generic"></span><span><strong><?php echo esc_html__('Ajustes adicionais', 'bastionwp'); ?></strong><small><?php echo esc_html__('Controles independentes', 'bastionwp'); ?></small></span></a>
                </nav>
            </aside>
            <div class="bastionwp-security-content">
                <div class="bastionwp-grid bastionwp-page-hardening" id="bastionwp-hardening-root">
            <section class="bastionwp-card bastionwp-card-wide bastionwp-hardening-profile-section" id="bastionwp-hardening-profile-section" data-security-panel="state" <?php echo $security_section !== 'state' ? 'hidden' : ''; ?>>
                <?php if ($hardening_profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) : ?>
                    <div class="bastionwp-callout bastionwp-callout-warning">
                        <strong><?php echo esc_html__('Segurança ainda não configurada.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Escolha um perfil abaixo. Para um site publicado, a recomendação padrão é Proteção Recomendada.', 'bastionwp'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_hardening">
                    <?php wp_nonce_field('bastionwp_save_hardening'); ?>

                    <div class="bastionwp-hardening-profiles bastionwp-hardening-profiles-row">
                        <?php foreach ($hardening_profiles as $profile_key => $profile_data) : ?>
                            <label class="bastionwp-hardening-profile bastionwp-hardening-profile-modern <?php echo $hardening_profile === $profile_key ? 'is-applied' : ''; ?>">
                                <input
                                    type="radio"
                                    name="hardening_profile"
                                    value="<?php echo esc_attr($profile_key); ?>"
                                    <?php checked($hardening_profile, $profile_key); ?>
                                >
                                <span class="bastionwp-hardening-profile-icon dashicons <?php echo esc_attr($hardening_icon_map[$profile_key] ?? 'dashicons-shield'); ?>" aria-hidden="true"></span>
                                <span class="bastionwp-hardening-profile-copy">
                                    <strong><?php echo esc_html($profile_data['label']); ?></strong>
                                    <small><?php echo esc_html($profile_data['description']); ?></small>
                                    <?php $intensity=$security_intensity[$profile_key] ?? ['label'=>__('Proteção', 'bastionwp'),'class'=>'neutral']; ?>
                                    <span class="bastionwp-security-intensity is-<?php echo esc_attr($intensity['class']); ?>"><?php echo esc_html($intensity['label']); ?></span>
                                </span>
                                <span class="bastionwp-hardening-radio-visual" aria-hidden="true"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="bastionwp-hardening-profile-actions">
                        <?php submit_button(__('Aplicar perfil', 'bastionwp'), 'primary', 'submit', false); ?>

                    </div>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-hardening-rules-card" id="bastionwp-hardening-rules" data-security-panel="state" <?php echo $security_section !== 'state' ? 'hidden' : ''; ?>>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Regras do perfil selecionado', 'bastionwp'); ?></h2>
                        <p>
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('Principais permissões e comportamentos no perfil %s.', 'bastionwp'),
                                    $current_hardening_ui['label'] ?? __('selecionado', 'bastionwp')
                                )
                            );
                            ?>
                        </p>
                    </div>
                </div>

                <div class="bastionwp-security-state-legend"><strong><?php echo esc_html__('Estado atual do site', 'bastionwp'); ?></strong><div class="bastionwp-security-current-tags"><span><?php echo esc_html__('Editor: ' . (!empty($hardening_effective['block_file_editors']) ? 'bloqueado' : 'permitido'), 'bastionwp'); ?></span><span><?php echo esc_html__('XML-RPC: ' . (!empty($hardening_rule_states['xmlrpc']['protected']) ? 'protegido' : 'permitido'), 'bastionwp'); ?></span><span><?php echo esc_html__('Application Passwords: ' . (!empty($hardening_rule_states['application_passwords']['protected']) ? 'bloqueadas' : 'permitidas'), 'bastionwp'); ?></span><span><?php echo esc_html__('REST Users: ' . (!empty($hardening_effective['block_public_rest_users']) ? 'protegido' : 'padrão'), 'bastionwp'); ?></span></div></div>
                <div class="bastionwp-security-future-label"><span><?php echo esc_html__('Após aplicar o perfil selecionado', 'bastionwp'); ?></span></div>
                <div class="bastionwp-effective-rules">
                    <div class="bastionwp-effective-rule" data-hardening-rule="block_file_editors">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-media-code" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Editor de arquivos', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['block_file_editors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['block_file_editors']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['block_file_editors']) ? esc_html__('Editor de arquivos de plugins e temas ficará indisponível.', 'bastionwp') : esc_html__('Editor de arquivos permanecerá disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_xmlrpc">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-share" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('XML-RPC', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['disable_xmlrpc']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['disable_xmlrpc']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['disable_xmlrpc']) ? esc_html__('Os métodos XML-RPC do WordPress ficam indisponíveis; o endpoint ainda pode responder com uma mensagem de falha.', 'bastionwp') : esc_html__('XML-RPC continuará disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_application_passwords">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Application Passwords', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['disable_application_passwords']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['disable_application_passwords']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['disable_application_passwords']) ? esc_html__('Application Passwords não poderão ser usadas.', 'bastionwp') : esc_html__('Application Passwords continuarão disponíveis.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="hide_wordpress_version">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Versão WordPress no HTML', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['hide_wordpress_version']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['hide_wordpress_version']) ? esc_html__('Ocultada', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['hide_wordpress_version']) ? esc_html__('A versão do WordPress será ocultada no HTML.', 'bastionwp') : esc_html__('A saída padrão do WordPress será mantida.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="generic_login_errors">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Erros de login', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['generic_login_errors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['generic_login_errors']) ? esc_html__('Mensagem genérica', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['generic_login_errors']) ? esc_html__('Erros de login exibirão texto genérico.', 'bastionwp') : esc_html__('Erros padrão do WordPress serão mantidos.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_public_rest_users">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-rest-api" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('REST / usuários públicos', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['block_public_rest_users']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['block_public_rest_users']) ? esc_html__('Bloqueado sem login', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['block_public_rest_users']) ? esc_html__('A listagem pública de usuários pela REST API será bloqueada.', 'bastionwp') : esc_html__('A REST API seguirá o comportamento padrão do WordPress.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_manual_infrastructure_changes">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Alterações manuais de plugins/temas/core', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($selected_profile_preview['block_manual_infrastructure_changes']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($selected_profile_preview['block_manual_infrastructure_changes']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas ao Developer', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($selected_profile_preview['block_manual_infrastructure_changes']) ? esc_html__('Alterações manuais de plugins, temas e core serão bloqueadas.', 'bastionwp') : esc_html__('Manutenção manual continuará disponível ao Developer.', 'bastionwp'); ?></small>
                    </div>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-hardening-summary-card" data-security-panel="state" <?php echo $security_section !== 'state' ? 'hidden' : ''; ?>>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-media-document" aria-hidden="true"></span>
                    <div>
                        <h2 id="bastionwp-hardening-summary-title">
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('O que muda ao aplicar %s', 'bastionwp'),
                                    $current_hardening_ui['label'] ?? __('este perfil', 'bastionwp')
                                )
                            );
                            ?>
                        </h2>
                        <p><?php echo esc_html__('Veja os principais pontos que serão aplicados ao ambiente.', 'bastionwp'); ?></p>
                    </div>
                </div>

                <ul class="bastionwp-summary-list bastionwp-hardening-change-list" id="bastionwp-hardening-summary-list">
                    <?php foreach (($current_hardening_ui['summary'] ?? []) as $summary_item) : ?>
                        <li><?php echo esc_html($summary_item); ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="bastionwp-hardening-compatibility-box">
                    <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                    <div>
                        <strong id="bastionwp-hardening-compatibility-title">
                            <?php echo esc_html($current_hardening_ui['compatibilityTitle'] ?? __('Quando usar', 'bastionwp')); ?>
                        </strong>
                        <ul id="bastionwp-hardening-compatibility-list">
                            <?php foreach (($current_hardening_ui['compatibility'] ?? []) as $compatibility_item) : ?>
                                <li><?php echo esc_html($compatibility_item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide bastionwp-security-wordpress-panel" data-security-panel="wordpress" <?php echo $security_section !== 'wordpress' ? 'hidden' : ''; ?>>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-wordpress" aria-hidden="true"></span>
                    <div><span class="bastionwp-eyebrow"><?php echo esc_html__('WordPress e arquivos', 'bastionwp'); ?></span><h2><?php echo esc_html__('Responsabilidade das proteções', 'bastionwp'); ?></h2><p><?php echo esc_html__('O BastionWP separa a política desejada, o estado efetivo e a camada que está aplicando cada proteção.', 'bastionwp'); ?></p></div>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_security_ownership">
                    <?php wp_nonce_field('bastionwp_save_security_ownership'); ?>
                    <div class="bastionwp-security-ownership-list">
                        <?php foreach ($hardening_preflight as $preflight) :
                            $preflight_key = (string) ($preflight['key'] ?? '');
                            $source = (string) ($preflight['source'] ?? '');
                            $source_lower = function_exists('mb_strtolower') ? mb_strtolower($source) : strtolower($source);
                            $source_identified = !empty($preflight['protected']) && $source_lower !== '' && !str_contains($source_lower, 'nenhuma origem') && !str_contains($source_lower, 'origem não identificada') && !str_contains($source_lower, 'origem nao identificada');
                            $owner = (string) ($hardening_ownership[$preflight_key] ?? ($source_identified ? 'external' : 'bastion'));
                            $source_label = $source_identified ? $security_source_label($source) : (!empty($preflight['protected']) ? __('Origem não identificada', 'bastionwp') : __('Nenhuma proteção externa detectada', 'bastionwp'));
                        ?>
                            <article class="bastionwp-security-ownership-card">
                                <div class="bastionwp-security-ownership-status"><span class="bastionwp-diagnostic-state <?php echo !empty($preflight['protected']) ? 'bastionwp-diagnostic-ok' : 'bastionwp-diagnostic-warning'; ?>"></span><div><strong><?php echo esc_html((string) $preflight['label']); ?></strong><small><?php echo esc_html((string) $preflight['description']); ?></small></div></div>
                                <?php
                                $policy_key_map = ['xmlrpc'=>'disable_xmlrpc','application_passwords'=>'disable_application_passwords','file_editors'=>'block_file_editors','display_errors'=>'suppress_display_errors'];
                                $policy_setting_key = $policy_key_map[$preflight_key] ?? '';
                                $policy_wants_protection = $policy_setting_key !== '' && !empty($hardening_policy_without_ownership[$policy_setting_key]);
                                ?>
                                <div class="bastionwp-security-ownership-meta">
                                    <span><?php echo esc_html__('Política do perfil', 'bastionwp'); ?></span><strong><?php echo $policy_wants_protection ? esc_html__('Proteger / bloquear', 'bastionwp') : esc_html__('Sem bloqueio obrigatório', 'bastionwp'); ?></strong>
                                    <span><?php echo esc_html__('Estado efetivo', 'bastionwp'); ?></span><strong><?php echo !empty($preflight['protected']) ? esc_html__('Protegido', 'bastionwp') : esc_html__('Disponível', 'bastionwp'); ?></strong>
                                    <span><?php echo esc_html__('Responsável detectado', 'bastionwp'); ?></span><strong><?php echo esc_html($source_label); ?></strong>
                                </div>
                                <div class="bastionwp-security-ownership-options">
                                    <label class="<?php echo !$source_identified ? 'is-disabled' : ''; ?>"><input type="radio" name="ownership[<?php echo esc_attr($preflight_key); ?>]" value="external" <?php checked($owner, 'external'); ?> <?php disabled(!$source_identified); ?>><span><strong><?php echo esc_html__('Manter configuração existente', 'bastionwp'); ?></strong><small><?php echo $source_identified ? esc_html(sprintf(__('Continuar sob responsabilidade de %s.', 'bastionwp'), $source_label)) : esc_html__('Disponível somente quando a origem externa é identificável.', 'bastionwp'); ?></small></span></label>
                                    <label><input type="radio" name="ownership[<?php echo esc_attr($preflight_key); ?>]" value="bastion" <?php checked($owner, 'bastion'); ?>><span><strong><?php echo esc_html__('Tornar BastionWP responsável', 'bastionwp'); ?></strong><small><?php echo esc_html__('O BastionWP aplica sua própria regra quando o perfil exigir esta proteção.', 'bastionwp'); ?></small></span></label>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Arquivos do Core permanecem intactos.', 'bastionwp'); ?></strong><p><?php echo esc_html__('O BastionWP não modifica wp-admin/* nem wp-login.php. Alterações sensíveis suportadas usam snapshots técnicos em Sistema → Backups de configuração.', 'bastionwp'); ?></p></div>
                    <?php submit_button(__('Salvar responsabilidade das proteções', 'bastionwp'), 'primary'); ?>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide bastionwp-hardening-additional-section" id="bastionwp-hardening-additional-section" data-security-panel="additional" <?php echo $security_section !== 'additional' ? 'hidden' : ''; ?>>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-admin-generic" aria-hidden="true"></span>
                    <div>
                        <span class="bastionwp-eyebrow"><?php echo esc_html__('Ajustes adicionais', 'bastionwp'); ?></span>
                        <h2><?php echo esc_html__('Controles independentes do perfil', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('Ajustes que podem ser ativados ou desativados sem trocar o perfil de Segurança.', 'bastionwp'); ?></p>
                    </div>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_hardening_overrides">
                    <?php wp_nonce_field('bastionwp_save_hardening_overrides'); ?>

                    <div class="bastionwp-switch-list bastionwp-hardening-switch-grid">
                        <label class="bastionwp-switch-row">
                            <span>
                                <strong><?php echo esc_html__('Desabilitar comentários', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Fecha comentários e trackbacks, remove suporte dos tipos de conteúdo e oculta o menu Comentários.', 'bastionwp'); ?></small>
                            </span>
                            <input type="checkbox" name="disable_comments" value="1" <?php checked(!empty($hardening_effective['disable_comments'])); ?>>
                        </label>

                        <label class="bastionwp-switch-row">
                            <span>
                                <strong><?php echo esc_html__('Ocultar menu Painel do Cliente Protegido', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Ao entrar no /wp-admin, o cliente será direcionado para Páginas.', 'bastionwp'); ?></small>
                            </span>
                            <input type="checkbox" name="hide_client_dashboard" value="1" <?php checked(!empty($hardening_effective['hide_client_dashboard'])); ?>>
                        </label>

                        <label class="bastionwp-switch-row">
                            <span>
                                <strong><?php echo esc_html__('Forçar supressão de display_errors', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Suprime a exibição de erros PHP em runtime sem editar automaticamente o wp-config.php.', 'bastionwp'); ?></small>
                            </span>
                            <input type="checkbox" name="force_suppress_display_errors" value="1" <?php checked(!empty($hardening_effective['force_suppress_display_errors'])); ?>>
                        </label>
                    </div>

                    <?php submit_button(__('Salvar ajustes adicionais', 'bastionwp')); ?>
                </form>
                <div class="bastionwp-custom-adjustments-card">
                    <div><span class="bastionwp-eyebrow"><?php echo esc_html__('Extensão segura','bastionwp'); ?></span><h3><?php echo esc_html__('Ajustes personalizados seguros','bastionwp'); ?></h3><p><?php echo esc_html__('Adicione regras estruturadas sem executar PHP arbitrário dentro do BastionWP.','bastionwp'); ?></p></div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_save_custom_adjustments"><?php wp_nonce_field('bastionwp_save_custom_adjustments'); ?>
                        <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Headers HTTP personalizados','bastionwp'); ?></strong><small><?php echo esc_html__('Até 10 headers. Cabeçalhos sensíveis já gerenciados pelo BastionWP não podem ser sobrescritos por esta área.','bastionwp'); ?></small></span><input type="checkbox" name="custom[custom_headers_enabled]" value="1" <?php checked(!empty($security_settings['custom_headers_enabled'])); ?>></label>
                        <label class="bastionwp-field-label"><?php echo esc_html__('Headers adicionais — um por linha','bastionwp'); ?><textarea name="custom[custom_headers]" rows="4" placeholder="Cross-Origin-Opener-Policy: same-origin"><?php echo esc_textarea((string)($security_settings['custom_headers']??'')); ?></textarea></label>
                        <label class="bastionwp-field-label"><?php echo esc_html__('Namespaces REST personalizados que exigem autenticação','bastionwp'); ?><textarea name="custom[custom_rest_auth_namespaces]" rows="3" placeholder="plugin-x/v1"><?php echo esc_textarea(implode("\n",(array)($security_settings['custom_rest_auth_namespaces']??[]))); ?></textarea></label>
                        <label class="bastionwp-field-label"><?php echo esc_html__('CSS administrativo do BastionWP','bastionwp'); ?><textarea name="custom[custom_admin_css]" rows="4" placeholder=".minha-regra { ... }"><?php echo esc_textarea((string)($security_settings['custom_admin_css']??'')); ?></textarea><small><?php echo esc_html__('Aplicado somente às telas administrativas do BastionWP. Não executa PHP ou JavaScript.','bastionwp'); ?></small></label>
                        <?php submit_button(__('Salvar ajustes personalizados','bastionwp'),'secondary'); ?>
                    </form>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide bastionwp-security-controls" id="bastionwp-security-controls" data-security-panel="controls" <?php echo in_array($security_section, ['wordpress','login','http','rest','monitor'], true) ? '' : 'hidden'; ?>>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <div>
                        <span class="bastionwp-eyebrow"><?php echo esc_html__('Camada complementar', 'bastionwp'); ?></span>
                        <h2><?php echo esc_html__('Controles desta área', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('Cada grupo mostra somente os controles relacionados ao item selecionado na navegação lateral.', 'bastionwp'); ?></p>
                    </div>
                </div>

                <?php if ($security_section === 'monitor') : ?>
                    <div class="bastionwp-security-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_run_security_monitor"><?php wp_nonce_field('bastionwp_run_security_monitor'); ?><button type="submit" class="button"><span class="dashicons dashicons-update"></span><?php echo esc_html__('Executar verificações agora', 'bastionwp'); ?></button></form></div>
                    <div class="bastionwp-security-status-grid">
                        <article><span><?php echo esc_html__('WAF de borda', 'bastionwp'); ?></span><strong class="<?php echo !empty($security_waf_status['edge_detected']) ? 'is-good' : 'is-warning'; ?>"><?php echo esc_html((string) $security_waf_status['edge_label']); ?></strong><small><?php echo esc_html__('Proxy detectado não comprova WAF/rate limiting. A validação completa depende da integração com o provedor.', 'bastionwp'); ?></small></article>
                        <article><span><?php echo esc_html__('Firewall WordPress', 'bastionwp'); ?></span><strong class="<?php echo !empty($security_waf_status['local_waf']) ? 'is-good' : 'is-warning'; ?>"><?php echo esc_html((string) $security_waf_status['local_waf_label']); ?></strong><small><?php echo esc_html__('Camada executada no origin/WordPress.', 'bastionwp'); ?></small></article>
                        <article><span><?php echo esc_html__('Auditoria / scan', 'bastionwp'); ?></span><strong class="<?php echo !empty($security_waf_status['scanner']) ? 'is-good' : 'is-warning'; ?>"><?php echo esc_html((string) $security_waf_status['scanner_label']); ?></strong><small><?php echo esc_html__('Integridade PHP não substitui malware scan especializado.', 'bastionwp'); ?></small></article>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_security_controls">
                    <input type="hidden" name="security_section" value="<?php echo esc_attr($security_section); ?>">
                    <?php wp_nonce_field('bastionwp_save_security_controls'); ?>

                    <div class="bastionwp-security-control-groups bastionwp-security-control-groups-single">
                        <section class="bastionwp-security-control-group" data-security-group="wordpress" <?php echo $security_section !== 'wordpress' ? 'hidden' : ''; ?>>
                            <div><h3><?php echo esc_html__('Integridade e arquivos WordPress', 'bastionwp'); ?></h3><p><?php echo esc_html__('Monitora PHP e mantém arquivos do Core intactos. O BastionWP não edita wp-admin/* nem wp-login.php.', 'bastionwp'); ?></p></div>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Integridade de arquivos PHP', 'bastionwp'); ?></strong><small><?php echo esc_html__('Mantém baseline SHA-256 e alerta inclusões, mudanças e PHP encontrado em uploads.', 'bastionwp'); ?></small></span><input type="checkbox" name="security[integrity_monitor]" value="1" <?php checked(!empty($security_settings['integrity_monitor'])); ?>></label>
                            <div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Rollback técnico disponível', 'bastionwp'); ?></strong><p><?php echo esc_html__('Arquivos de configuração suportados usam snapshots antes de gravações sensíveis. Gerencie-os em Sistema → Backups de configuração.', 'bastionwp'); ?></p><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system&system_view=backups')); ?>"><?php echo esc_html__('Abrir backups', 'bastionwp'); ?></a></div>
                        </section>

                        <section class="bastionwp-security-control-group" data-security-group="login" <?php echo $security_section !== 'login' ? 'hidden' : ''; ?>>
                            <div><h3><?php echo esc_html__('Proteção de login e sessão', 'bastionwp'); ?></h3><p><?php echo esc_html__('Rate limiting local como segunda camada. Para ataques volumétricos, use também WAF de borda.', 'bastionwp'); ?></p></div>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Proteção local de login', 'bastionwp'); ?></strong><small><?php echo esc_html__('Bloqueia temporariamente combinações de origem/usuário após muitas falhas.', 'bastionwp'); ?></small></span><input type="checkbox" name="security[login_rate_limit]" value="1" <?php checked(!empty($security_settings['login_rate_limit'])); ?>></label>
                            <div class="bastionwp-inline-fields"><label><?php echo esc_html__('Falhas', 'bastionwp'); ?><input type="number" min="3" max="100" name="security[login_limit]" value="<?php echo esc_attr((string) $security_settings['login_limit']); ?>"></label><label><?php echo esc_html__('Janela (min)', 'bastionwp'); ?><input type="number" min="1" max="120" name="security[login_window_minutes]" value="<?php echo esc_attr((string) $security_settings['login_window_minutes']); ?>"></label><label><?php echo esc_html__('Bloqueio (min)', 'bastionwp'); ?><input type="number" min="1" max="1440" name="security[login_block_minutes]" value="<?php echo esc_attr((string) $security_settings['login_block_minutes']); ?>"></label></div>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Não cachear admin/login', 'bastionwp'); ?></strong><small><?php echo esc_html__('Envia no-store/private quando o WordPress controla a resposta.', 'bastionwp'); ?></small></span><input type="checkbox" name="security[private_nocache]" value="1" <?php checked(!empty($security_settings['private_nocache'])); ?>></label>
                            <div class="bastionwp-security-provider-state"><span class="dashicons dashicons-shield"></span><div><strong><?php echo esc_html__('2FA', 'bastionwp'); ?></strong><p><?php echo $two_factor_provider !== '' ? esc_html($two_factor_provider) : esc_html__('Nenhum provedor de 2FA conhecido foi identificado. O BastionWP não afirma que 2FA está desativado; apenas não conseguiu validar um provedor.', 'bastionwp'); ?></p></div></div>
                        </section>

                        <section class="bastionwp-security-control-group" data-security-group="rest" <?php echo $security_section !== 'rest' ? 'hidden' : ''; ?>>
                            <div><h3><?php echo esc_html__('REST API', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('A REST API é usada pelo WordPress, Block Editor e muitos plugins. Restrinja somente depois de revisar os namespaces necessários.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre REST API', 'bastionwp'); ?>">?</button></h3><p><?php echo esc_html__('Escolha o nível de proteção e veja exatamente qual impacto será aplicado antes de salvar.', 'bastionwp'); ?></p></div>
                            <div class="bastionwp-rest-mode-grid" data-bastionwp-rest-modes>
                                <label class="bastionwp-rest-mode-card <?php echo $security_settings['rest_mode']==='observe'?'is-selected':''; ?>" data-rest-mode-card="observe"><input type="radio" name="security[rest_mode]" value="observe" <?php checked($security_settings['rest_mode'], 'observe'); ?>><span class="bastionwp-rest-impact is-low"><?php echo esc_html__('Baixo impacto', 'bastionwp'); ?></span><strong><?php echo esc_html__('Somente observar', 'bastionwp'); ?></strong><small><?php echo esc_html__('Nenhuma rota é bloqueada. O BastionWP apenas inventaria os namespaces encontrados.', 'bastionwp'); ?></small></label>
                                <label class="bastionwp-rest-mode-card <?php echo $security_settings['rest_mode']==='recommended'?'is-selected':''; ?>" data-rest-mode-card="recommended"><input type="radio" name="security[rest_mode]" value="recommended" <?php checked($security_settings['rest_mode'], 'recommended'); ?>><span class="bastionwp-rest-impact is-medium"><?php echo esc_html__('Proteção moderada', 'bastionwp'); ?></span><strong><?php echo esc_html__('Proteção recomendada', 'bastionwp'); ?></strong><small><?php echo esc_html__('Mantém compatibilidade e adiciona bloqueios pontuais conhecidos como seguros.', 'bastionwp'); ?></small></label>
                                <label class="bastionwp-rest-mode-card <?php echo $security_settings['rest_mode']==='allowlist'?'is-selected':''; ?>" data-rest-mode-card="allowlist"><input type="radio" name="security[rest_mode]" value="allowlist" <?php checked($security_settings['rest_mode'], 'allowlist'); ?>><span class="bastionwp-rest-impact is-high"><?php echo esc_html__('Restritivo', 'bastionwp'); ?></span><strong><?php echo esc_html__('Allowlist avançada', 'bastionwp'); ?></strong><small><?php echo esc_html__('Somente namespaces autorizados permanecem disponíveis. Exige homologação.', 'bastionwp'); ?></small></label>
                            </div>
                            <div class="bastionwp-rest-mode-summary" data-rest-summary="observe" <?php echo $security_settings['rest_mode']!=='observe'?'hidden':''; ?>><strong><?php echo esc_html__('Modo atual: Somente observar', 'bastionwp'); ?></strong><p><?php echo esc_html__('Nenhum bloqueio adicional está ativo. Use este modo para formar o inventário antes de restringir a API.', 'bastionwp'); ?></p></div>
                            <div class="bastionwp-rest-mode-summary is-recommended" data-rest-summary="recommended" <?php echo $security_settings['rest_mode']!=='recommended'?'hidden':''; ?>><strong><?php echo esc_html__('Proteções aplicadas', 'bastionwp'); ?></strong><ul><li><?php echo esc_html__('Enumeração pública de usuários em /wp/v2/users bloqueada.', 'bastionwp'); ?></li><li><?php echo esc_html__('REST permanece disponível para WordPress, editor e plugins.', 'bastionwp'); ?></li><li><?php echo esc_html__('Regras REST personalizadas que exigem autenticação continuam sendo aplicadas.', 'bastionwp'); ?></li></ul></div>
                            <div class="bastionwp-rest-mode-summary is-warning" data-rest-summary="allowlist" <?php echo $security_settings['rest_mode']!=='allowlist'?'hidden':''; ?>><strong><?php echo esc_html__('Allowlist avançada', 'bastionwp'); ?></strong><p><?php echo esc_html__('Marcado = permitido. Desmarcado = bloqueado. Namespaces essenciais permanecem obrigatórios e não podem ser removidos.', 'bastionwp'); ?></p><div class="bastionwp-rest-block-count" data-rest-block-count></div></div>
                            <?php if (!empty($rest_inventory)) : ?><div class="bastionwp-rest-inventory <?php echo $security_settings['rest_mode']!=='allowlist'?'is-readonly':''; ?>" data-rest-inventory><strong><?php echo esc_html__('Namespaces observados', 'bastionwp'); ?></strong><?php foreach ($rest_inventory as $rest_item) : $ns = (string) ($rest_item['namespace'] ?? ''); if ($ns === '') continue; $rest_source = (string) ($rest_item['source'] ?? ''); $required_ns = in_array($ns, (array) $required_rest_namespaces, true); $allowed_ns = $required_ns || in_array($ns, (array) $security_settings['rest_allowed_namespaces'], true); ?><label class="<?php echo $required_ns?'is-required':''; ?>"><input type="checkbox" name="security[rest_allowed_namespaces][]" value="<?php echo esc_attr($ns); ?>" <?php checked($allowed_ns); ?> <?php echo $required_ns?'disabled':''; ?> data-rest-namespace><span><strong><?php echo esc_html($ns); ?></strong><?php if ($required_ns) : ?><em><?php echo esc_html__('Obrigatório', 'bastionwp'); ?></em><?php elseif ($rest_source !== '') : ?><em><?php echo esc_html($rest_source); ?></em><?php else : ?><em><?php echo esc_html__('Origem não identificada', 'bastionwp'); ?></em><?php endif; ?></span><small><?php echo esc_html(sprintf(_n('%d rota', '%d rotas', (int) ($rest_item['routes'] ?? 0), 'bastionwp'), (int) ($rest_item['routes'] ?? 0))); ?></small><?php if ($required_ns) : ?><input type="hidden" name="security[rest_allowed_namespaces][]" value="<?php echo esc_attr($ns); ?>"><?php endif; ?></label><?php endforeach; ?></div><?php else : ?><div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Inventário ainda vazio', 'bastionwp'); ?></strong><p><?php echo esc_html__('Mantenha Somente observar até revisar os namespaces realmente usados.', 'bastionwp'); ?></p></div><?php endif; ?>
                        </section>

                        <section class="bastionwp-security-control-group" data-security-group="http" <?php echo $security_section !== 'http' ? 'hidden' : ''; ?>>
                            <div><h3><?php echo esc_html__('Cabeçalhos HTTP e navegador', 'bastionwp'); ?></h3><p><?php echo esc_html__('O WordPress aplica a política quando participa da resposta. Servidor/CDN podem sobrescrever o resultado final.', 'bastionwp'); ?></p></div>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Aplicar cabeçalhos de segurança', 'bastionwp'); ?></strong><small><?php echo esc_html__('Ativa a política HTTP configurada abaixo.', 'bastionwp'); ?></small></span><input type="checkbox" name="security[headers_enabled]" value="1" <?php checked(!empty($security_settings['headers_enabled'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong>X-Content-Type-Options: nosniff <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Evita MIME sniffing: o navegador respeita o tipo de conteúdo declarado em vez de tentar reinterpretar o arquivo.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre X-Content-Type-Options', 'bastionwp'); ?>">?</button></strong></span><input type="checkbox" name="security[nosniff]" value="1" <?php checked(!empty($security_settings['nosniff'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('X-Frame-Options como compatibilidade', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Camada adicional contra clickjacking para navegadores legados. A política principal deve ser CSP frame-ancestors.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre X-Frame-Options', 'bastionwp'); ?>">?</button></strong></span><input type="checkbox" name="security[x_frame_options]" value="1" <?php checked(!empty($security_settings['x_frame_options'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Remover X-Powered-By quando possível', 'bastionwp'); ?></strong></span><input type="checkbox" name="security[remove_powered_by]" value="1" <?php checked(!empty($security_settings['remove_powered_by'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Remover X-XSS-Protection legado', 'bastionwp'); ?></strong></span><input type="checkbox" name="security[remove_xss_header]" value="1" <?php checked(!empty($security_settings['remove_xss_header'])); ?>></label>
                            <div class="bastionwp-inline-fields bastionwp-inline-fields-wide">
                                <label><span><?php echo esc_html__('Referrer-Policy', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Controla quanto da URL de origem é enviado quando o visitante navega para outro endereço.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre Referrer-Policy', 'bastionwp'); ?>">?</button></span><input type="text" name="security[referrer_policy]" value="<?php echo esc_attr((string) $security_settings['referrer_policy']); ?>"></label>
                                <label><span><?php echo esc_html__('Permissions-Policy', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Restringe recursos do navegador como câmera, microfone, geolocalização e pagamento.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre Permissions-Policy', 'bastionwp'); ?>">?</button></span><input type="text" name="security[permissions_policy]" value="<?php echo esc_attr((string) $security_settings['permissions_policy']); ?>"></label>
                            </div>
                            <div class="bastionwp-inline-fields">
                                <label><span><?php echo esc_html__('HSTS', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Instrui o navegador a usar HTTPS nas próximas visitas. Ative de forma gradual e revise subdomínios antes de usar preload.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre HSTS', 'bastionwp'); ?>">?</button></span><select name="security[hsts_mode]"><option value="off" <?php selected($security_settings['hsts_mode'],'off'); ?>><?php echo esc_html__('Desativado', 'bastionwp'); ?></option><option value="test" <?php selected($security_settings['hsts_mode'],'test'); ?>><?php echo esc_html__('Teste — 5 min', 'bastionwp'); ?></option><option value="validated" <?php selected($security_settings['hsts_mode'],'validated'); ?>><?php echo esc_html__('Validado — 30 dias', 'bastionwp'); ?></option><option value="production" <?php selected($security_settings['hsts_mode'],'production'); ?>><?php echo esc_html__('Produção — 1 ano', 'bastionwp'); ?></option></select></label>
                                <label><span><?php echo esc_html__('CSP', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('Content Security Policy restringe de onde scripts, estilos, frames e outros recursos podem ser carregados. Comece em Report-Only.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre CSP', 'bastionwp'); ?>">?</button></span><select name="security[csp_mode]"><option value="off" <?php selected($security_settings['csp_mode'],'off'); ?>><?php echo esc_html__('Desativada', 'bastionwp'); ?></option><option value="report_only" <?php selected($security_settings['csp_mode'],'report_only'); ?>><?php echo esc_html__('Report-Only', 'bastionwp'); ?></option><option value="enforce" <?php selected($security_settings['csp_mode'],'enforce'); ?>><?php echo esc_html__('Efetiva', 'bastionwp'); ?></option></select></label>
                            </div>
                            <label class="bastionwp-field-label"><span><?php echo esc_html__('Política CSP', 'bastionwp'); ?> <button type="button" class="bastionwp-help-tip" data-tooltip="<?php echo esc_attr__('A diretiva frame-ancestors dentro da CSP é a proteção preferencial contra clickjacking.', 'bastionwp'); ?>" aria-label="<?php echo esc_attr__('Ajuda sobre política CSP', 'bastionwp'); ?>">?</button></span><textarea name="security[csp_policy]" rows="4"><?php echo esc_textarea((string) $security_settings['csp_policy']); ?></textarea></label>
                            <div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('Comece em Report-Only', 'bastionwp'); ?></strong><p><?php echo esc_html__('Valide as origens legítimas antes de tornar a CSP efetiva.', 'bastionwp'); ?></p></div>
                            <?php if (!empty($csp_reports)) : ?><div class="bastionwp-csp-report-list"><strong><?php echo esc_html__('Origens observadas pelos relatórios CSP', 'bastionwp'); ?></strong><?php foreach (array_slice($csp_reports, 0, 12) as $csp_report) : ?><div><span><?php echo esc_html((string) ($csp_report['origin'] ?: __('Origem local/inline', 'bastionwp'))); ?></span><small><?php echo esc_html((string) ($csp_report['directive'] ?? '')); ?> · <?php echo esc_html(sprintf(_n('%d ocorrência', '%d ocorrências', (int) ($csp_report['count'] ?? 0), 'bastionwp'), (int) ($csp_report['count'] ?? 0))); ?></small></div><?php endforeach; ?></div><?php endif; ?>
                            <div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Limite da camada PHP', 'bastionwp'); ?></strong><p><?php echo esc_html__('Server: Apache/nginx pode ser adicionado depois do PHP. Expect-CT e HPKP não fazem parte desta política moderna.', 'bastionwp'); ?></p></div>
                        </section>

                        <section class="bastionwp-security-control-group" data-security-group="monitor" <?php echo $security_section !== 'monitor' ? 'hidden' : ''; ?>>
                            <div><h3><?php echo esc_html__('Monitoramento de segurança', 'bastionwp'); ?></h3><p><?php echo esc_html__('O dashboard separa o que o BastionWP observa localmente do que depende de DNS, TLS, servidor ou edge.', 'bastionwp'); ?></p></div>
                            <div class="bastionwp-monitor-dashboard">
                                <?php $monitor_cards = [
                                    'integrity' => [__('Integridade PHP','bastionwp'),'dashicons-editor-code'],
                                    'login' => [__('Login','bastionwp'),'dashicons-lock'],
                                    'traffic' => [__('Requisições','bastionwp'),'dashicons-chart-line'],
                                    'http' => [__('HTTP 403/404/5xx','bastionwp'),'dashicons-warning'],
                                    'dns' => [__('DNS','bastionwp'),'dashicons-admin-site-alt3'],
                                    'tls' => [__('TLS','bastionwp'),'dashicons-shield-alt'],
                                ]; foreach ($monitor_cards as $monitor_key => $monitor_meta) : $monitor_item = $security_monitor_dashboard[$monitor_key] ?? []; ?>
                                    <article class="bastionwp-monitor-card <?php echo !empty($monitor_item['enabled'])?'is-enabled':'is-disabled'; ?>"><div class="bastionwp-monitor-card-head"><span class="dashicons <?php echo esc_attr($monitor_meta[1]); ?>"></span><strong><?php echo esc_html($monitor_meta[0]); ?></strong></div><span class="bastionwp-monitor-state"><?php echo esc_html((string) ($monitor_item['state'] ?? '')); ?></span><dl><div><dt><?php echo esc_html__('Última verificação','bastionwp'); ?></dt><dd><?php $checked=(int)($monitor_item['checked_at']??0); echo esc_html($checked>0?human_time_diff($checked,time()).' '.__('atrás','bastionwp'):__('Tempo real / ainda não executado','bastionwp')); ?></dd></div><div><dt><?php echo esc_html__('Cobertura','bastionwp'); ?></dt><dd><?php if ($monitor_key==='http' && is_array($monitor_item['coverage']??null)) { $c=$monitor_item['coverage']; echo esc_html('403: '.(int)($c['403']??0).' · 404: '.(int)($c['404']??0).' · 5xx: '.(int)($c['500']??0)); } elseif ($monitor_key==='tls' && $monitor_item['coverage']!==null) { echo esc_html(sprintf(__('%d dias restantes','bastionwp'),(int)$monitor_item['coverage'])); } else { echo esc_html((string)($monitor_item['coverage']??0)); } ?></dd></div><div><dt><?php echo esc_html__('Fonte','bastionwp'); ?></dt><dd><?php echo esc_html((string)($monitor_item['source']??'')); ?></dd></div></dl></article>
                                <?php endforeach; ?>
                            </div>
                            <div class="bastionwp-monitor-scope-grid"><div><strong><?php echo esc_html__('Monitoramento local','bastionwp'); ?></strong><p><?php echo esc_html__('Usuários, plugins, login, arquivos PHP e requisições que efetivamente chegam ao WordPress.', 'bastionwp'); ?></p></div><div><strong><?php echo esc_html__('Monitoramento externo','bastionwp'); ?></strong><p><?php echo esc_html__('DNS e certificado TLS público. Eventos do Cloudflare/WAF exigirão integração de API para cobertura completa.', 'bastionwp'); ?></p></div></div>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Monitorar nameservers/DNS', 'bastionwp'); ?></strong></span><input type="checkbox" name="security[dns_monitor]" value="1" <?php checked(!empty($security_settings['dns_monitor'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Monitorar validade do TLS público', 'bastionwp'); ?></strong></span><input type="checkbox" name="security[tls_monitor]" value="1" <?php checked(!empty($security_settings['tls_monitor'])); ?>></label>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Monitorar tráfego observado pelo WordPress', 'bastionwp'); ?></strong><small><?php echo esc_html__('Não inclui requisições bloqueadas pelo edge/servidor antes do PHP.', 'bastionwp'); ?></small></span><input type="checkbox" name="security[traffic_monitor]" value="1" <?php checked(!empty($security_settings['traffic_monitor'])); ?>></label>
                            <div class="bastionwp-inline-fields"><label><?php echo esc_html__('Alerta por requisições / 10 min', 'bastionwp'); ?><input type="number" min="50" name="security[traffic_threshold]" value="<?php echo esc_attr((string) $security_settings['traffic_threshold']); ?>"></label><label><?php echo esc_html__('Alerta por erros / 10 min', 'bastionwp'); ?><input type="number" min="5" name="security[error_threshold]" value="<?php echo esc_attr((string) $security_settings['error_threshold']); ?>"></label></div>
                        </section>
                    </div>
                    <?php submit_button(__('Salvar configurações desta camada', 'bastionwp'), 'primary'); ?>
                </form>
                <?php if ($security_section === 'monitor') : ?>
                    <div class="bastionwp-security-alert-list bastionwp-alert-history"><div class="bastionwp-alert-history-head"><div><h3><?php echo esc_html__('Alertas recentes', 'bastionwp'); ?></h3><p><?php echo esc_html__('O sino exibe pendências acionáveis; aqui fica o contexto e o histórico de estados.', 'bastionwp'); ?></p></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_run_security_monitor"><?php wp_nonce_field('bastionwp_run_security_monitor'); ?><button type="submit" class="button"><span class="dashicons dashicons-update"></span><?php echo esc_html__('Executar verificações agora','bastionwp'); ?></button></form></div>
                    <?php if (empty($security_all_alerts)) : ?><div class="bastionwp-callout bastionwp-callout-success"><strong><?php echo esc_html__('Nenhum alerta registrado','bastionwp'); ?></strong><p><?php echo esc_html__('Os monitores ativos ainda não registraram eventos que exigem atenção.','bastionwp'); ?></p></div><?php else : foreach (array_slice($security_all_alerts,0,30) as $security_alert) : $alert_status=(string)($security_alert['status']??'open'); ?>
                        <article class="is-<?php echo esc_attr((string)($security_alert['severity']??'warning')); ?> is-status-<?php echo esc_attr($alert_status); ?>"><div><div class="bastionwp-alert-title-line"><strong><?php echo esc_html((string)($security_alert['title']??'')); ?></strong><span class="bastionwp-alert-status"><?php echo esc_html(['open'=>__('Aberto','bastionwp'),'acknowledged'=>__('Reconhecido','bastionwp'),'resolved'=>__('Resolvido','bastionwp'),'ignored'=>__('Ignorado','bastionwp')][$alert_status]??$alert_status); ?></span></div><p><?php echo esc_html((string)($security_alert['message']??'')); ?></p><small><?php echo esc_html((string)($security_alert['created_at']??'')); ?></small></div><?php if (in_array($alert_status,['open','acknowledged'],true)) : ?><div class="bastionwp-alert-actions"><?php foreach (['acknowledged'=>__('Reconhecer','bastionwp'),'resolved'=>__('Resolver','bastionwp'),'ignored'=>__('Ignorar','bastionwp')] as $next_status=>$next_label) : if ($next_status===$alert_status) continue; ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_resolve_security_alert"><input type="hidden" name="alert_id" value="<?php echo esc_attr((string)($security_alert['id']??'')); ?>"><input type="hidden" name="alert_status" value="<?php echo esc_attr($next_status); ?>"><?php wp_nonce_field('bastionwp_resolve_security_alert'); ?><button type="submit" class="button button-small"><?php echo esc_html($next_label); ?></button></form><?php endforeach; ?></div><?php endif; ?></article>
                    <?php endforeach; endif; ?></div>
                <?php endif; ?>
            </section>
                </div>
            </div>
        </div>


<?php elseif ($tab === 'integrations') : ?>
    <?php
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $integration_key = isset($_GET['integration']) ? sanitize_key(wp_unslash($_GET['integration'])) : 'wordfence';
    $integration_items = [
        'wordfence' => [
            'label' => 'Wordfence',
            'icon' => 'dashicons-shield-alt',
            'status' => !empty($wordfence_status['active']) ? __('Plugin ativo', 'bastionwp') : (!empty($wordfence_status['installed']) ? __('Instalado, inativo', 'bastionwp') : __('Não instalado', 'bastionwp')),
            'status_class' => !empty($wordfence_status['active']) ? 'success' : 'muted',
            'access_compatibility' => __('Área técnica — sempre oculta', 'bastionwp'),
            'access_class' => 'blocked',
        ],
        'rest-api' => [
            'label' => 'API REST',
            'icon' => 'dashicons-rss',
            'status' => function_exists('rest_get_server') ? __('Disponível no WordPress', 'bastionwp') : __('Indisponível', 'bastionwp'),
            'status_class' => function_exists('rest_get_server') ? 'success' : 'muted',
            'access_compatibility' => __('Não aplicável', 'bastionwp'),
            'access_class' => 'neutral',
        ],
        'litespeed' => [
            'label' => 'LiteSpeed',
            'icon' => 'dashicons-performance',
            'status' => is_plugin_active('litespeed-cache/litespeed-cache.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('litespeed-cache/litespeed-cache.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade de acesso necessária', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'yoastseo' => [
            'label' => 'Yoast SEO',
            'icon' => 'dashicons-chart-line',
            'status' => is_plugin_active('wordpress-seo/wp-seo.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('wordpress-seo/wp-seo.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade de acesso necessária', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'elementor' => [
            'label' => 'Elementor',
            'icon' => 'dashicons-screenoptions',
            'status' => is_plugin_active('elementor/elementor.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('elementor/elementor.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade de acesso necessária', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'sitekit' => [
            'label' => 'Site Kit Google',
            'icon' => 'dashicons-chart-bar',
            'status' => is_plugin_active('google-site-kit/google-site-kit.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('google-site-kit/google-site-kit.php') ? 'success' : 'muted',
            'access_compatibility' => __('Permissão nativa do plugin', 'bastionwp'),
            'access_class' => 'success',
        ],
    ];
    if (!isset($integration_items[$integration_key])) {
        $integration_key = 'wordfence';
    }
    $selected_integration = $integration_items[$integration_key];
    $plugin_versions = [];
    foreach (['litespeed-cache/litespeed-cache.php', 'wordpress-seo/wp-seo.php', 'elementor/elementor.php', 'google-site-kit/google-site-kit.php', 'wordfence/wordfence.php'] as $plugin_file) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
            $plugin_versions[$plugin_file] = (string) ($plugin_data['Version'] ?? '—');
        }
    }
    ?>
    <div class="bastionwp-grid bastionwp-page-integrations-modern">
        <section class="bastionwp-card bastionwp-integration-sidebar">
            <div class="bastionwp-overview-section-title">
                <span class="bastionwp-overview-card-icon dashicons dashicons-admin-links" aria-hidden="true"></span>
                <div>
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Catálogo', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Integrações recomendadas', 'bastionwp'); ?></h2>
                    <p><?php echo esc_html__('Selecione uma integração para visualizar instalação, ativação e sinais disponíveis. Um plugin ativo não significa que sua configuração funcional foi validada.', 'bastionwp'); ?></p>
                </div>
            </div>
            <div class="bastionwp-integration-nav-list">
                <?php foreach ($integration_items as $item_key => $item_data) : ?>
                    <a class="bastionwp-integration-nav-card <?php echo $integration_key === $item_key ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'bastionwp', 'tab' => 'integrations', 'integration' => $item_key], admin_url('admin.php'))); ?>">
                        <span class="dashicons <?php echo esc_attr($item_data['icon']); ?>" aria-hidden="true"></span>
                        <div>
                            <strong><?php echo esc_html($item_data['label']); ?></strong>
                            <small class="is-<?php echo esc_attr($item_data['status_class']); ?>"><?php echo esc_html($item_data['status']); ?></small>
                            <small class="bastionwp-integration-access is-<?php echo esc_attr($item_data['access_class']); ?>"><?php echo esc_html($item_data['access_compatibility']); ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bastionwp-card bastionwp-card-wide bastionwp-integration-detail-panel">
            <?php if ($integration_key === 'wordfence') : ?>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Wordfence', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('O BastionWP usa o Wordfence como ferramenta externa para firewall, scanner de malware, vulnerabilidades e segurança de login.', 'bastionwp'); ?></p>
                    </div>
                </div>
                <div class="bastionwp-integration-kpis">
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Instalação', 'bastionwp'); ?></span><strong><?php echo $wordfence_status['installed'] ? esc_html__('Instalado', 'bastionwp') : esc_html__('Pendente', 'bastionwp'); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Ativação', 'bastionwp'); ?></span><strong><?php echo $wordfence_status['active'] ? esc_html__('Ativo', 'bastionwp') : esc_html__('Inativo', 'bastionwp'); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Versão instalada', 'bastionwp'); ?></span><strong><?php echo esc_html($plugin_versions['wordfence/wordfence.php'] ?? (string) ($wordfence_status['version'] ?? '—')); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Auto-update', 'bastionwp'); ?></span><strong><?php echo !empty($wordfence_status['auto_update']) ? esc_html__('Ativado', 'bastionwp') : esc_html__('Desativado', 'bastionwp'); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Sinal WAF', 'bastionwp'); ?></span><strong><?php echo !empty($wordfence_status['waf_loaded']) ? esc_html__('Detectado', 'bastionwp') : esc_html__('Não detectado', 'bastionwp'); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Acesso do cliente', 'bastionwp'); ?></span><strong><?php echo esc_html__('Área técnica — sempre oculta', 'bastionwp'); ?></strong></div>
                </div>
                <div class="bastionwp-integration-actions-bar">
                    <?php if (!$wordfence_status['installed']) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wordfence_install"><?php wp_nonce_field('bastionwp_wordfence_install'); ?><?php submit_button(__('Instalar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?></form>
                    <?php elseif (!$wordfence_status['active']) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wordfence_activate"><?php wp_nonce_field('bastionwp_wordfence_activate'); ?><?php submit_button(__('Ativar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?></form>
                    <?php else : ?>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=Wordfence')); ?>"><?php echo esc_html__('Abrir Wordfence', 'bastionwp'); ?></a>
                    <?php endif; ?>
                    <a class="button" href="https://wordpress.org/plugins/wordfence/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__('Ver documentação', 'bastionwp'); ?></a>
                </div>
                <div class="bastionwp-grid bastionwp-integration-detail-grid">
                    <section class="bastionwp-card">
                        <h3><?php echo esc_html__('Wordfence sempre atualizado', 'bastionwp'); ?></h3>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="bastionwp_wordfence_auto_update">
                            <?php wp_nonce_field('bastionwp_wordfence_auto_update'); ?>
                            <label class="bastionwp-switch-row"><span><strong><?php echo esc_html__('Atualizar Wordfence automaticamente', 'bastionwp'); ?></strong><small><?php echo esc_html__('Usa o mecanismo nativo de auto-update do WordPress.', 'bastionwp'); ?></small></span><input type="checkbox" name="wordfence_auto_update" value="1" <?php checked(!empty($wordfence_status['auto_update'])); ?>></label>
                            <?php submit_button(__('Salvar', 'bastionwp'), 'primary'); ?>
                        </form>
                    </section>
                    <section class="bastionwp-card">
                        <h3><?php echo esc_html__('Firewall do Wordfence', 'bastionwp'); ?></h3>
                        <div class="bastionwp-callout <?php echo !empty($wordfence_status['waf_loaded']) ? '' : 'bastionwp-callout-warning'; ?>"><strong><?php echo !empty($wordfence_status['waf_loaded']) ? esc_html__('Sinal de carregamento do WAF do Wordfence detectado nesta requisição.', 'bastionwp') : esc_html__('Sinal de carregamento do WAF do Wordfence não detectado nesta requisição.', 'bastionwp'); ?></strong></div>
                        <p><?php echo esc_html__('O BastionWP não altera automaticamente arquivos de bootstrap do firewall. A otimização do WAF continua sendo feita pelo fluxo oficial do Wordfence.', 'bastionwp'); ?></p>
                    </section>
                </div>
            <?php else : ?>
                <?php
                $integration_docs = [
                    'rest-api' => [
                        'title' => __('API REST', 'bastionwp'),
                        'description' => __('Use esta integração para revisar disponibilidade da REST API e compatibilidade com Application Passwords e XML-RPC controlados pela Segurança.', 'bastionwp'),
                        'version' => get_bloginfo('version'),
                        'status' => function_exists('rest_get_server') ? __('Disponível no WordPress', 'bastionwp') : __('Indisponível', 'bastionwp'),
                        'plugin_file' => '',
                        'actions' => [['label' => __('Abrir /wp-json', 'bastionwp'), 'url' => rest_url(), 'primary' => True]],
                    ],
                    'litespeed' => [
                        'title' => __('LiteSpeed', 'bastionwp'),
                        'description' => __('Acompanhe se o plugin de cache está ativo e abra a administração do LiteSpeed quando disponível.', 'bastionwp'),
                        'version' => $plugin_versions['litespeed-cache/litespeed-cache.php'] ?? '—',
                        'status' => is_plugin_active('litespeed-cache/litespeed-cache.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
                        'plugin_file' => 'litespeed-cache/litespeed-cache.php',
                        'actions' => [],
                    ],
                    'yoastseo' => [
                        'title' => __('Yoast SEO', 'bastionwp'),
                        'description' => __('Revise o status do plugin de SEO e acesse suas telas principais.', 'bastionwp'),
                        'version' => $plugin_versions['wordpress-seo/wp-seo.php'] ?? '—',
                        'status' => is_plugin_active('wordpress-seo/wp-seo.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
                        'plugin_file' => 'wordpress-seo/wp-seo.php',
                        'actions' => [],
                    ],
                    'elementor' => [
                        'title' => __('Elementor', 'bastionwp'),
                        'description' => __('Gerencie rapidamente o status do construtor visual e verifique a versão instalada.', 'bastionwp'),
                        'version' => $plugin_versions['elementor/elementor.php'] ?? '—',
                        'status' => is_plugin_active('elementor/elementor.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
                        'plugin_file' => 'elementor/elementor.php',
                        'actions' => [],
                    ],
                    'sitekit' => [
                        'title' => __('Site Kit do Google', 'bastionwp'),
                        'description' => __('Confira se o plugin está ativo e lembre-se de que permissões adicionais podem ser necessárias no Google.', 'bastionwp'),
                        'version' => $plugin_versions['google-site-kit/google-site-kit.php'] ?? '—',
                        'status' => is_plugin_active('google-site-kit/google-site-kit.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
                        'plugin_file' => 'google-site-kit/google-site-kit.php',
                        'actions' => [],
                    ],
                ];
                $integration_doc = $integration_docs[$integration_key];
                $plugin_file = (string) $integration_doc['plugin_file'];
                $plugin_installed = $plugin_file !== '' && file_exists(WP_PLUGIN_DIR . '/' . $plugin_file);
                $plugin_active = $plugin_file !== '' && is_plugin_active($plugin_file);
                ?>
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons <?php echo esc_attr($selected_integration['icon']); ?>" aria-hidden="true"></span>
                    <div><h2><?php echo esc_html($integration_doc['title']); ?></h2><p><?php echo esc_html($integration_doc['description']); ?></p></div>
                </div>
                <div class="bastionwp-integration-kpis">
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Status', 'bastionwp'); ?></span><strong><?php echo esc_html($integration_doc['status']); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Versão', 'bastionwp'); ?></span><strong><?php echo esc_html($integration_doc['version']); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Plugin instalado', 'bastionwp'); ?></span><strong><?php echo $plugin_file === '' ? esc_html__('Nativo do WordPress', 'bastionwp') : ($plugin_installed ? esc_html__('Sim', 'bastionwp') : esc_html__('Não', 'bastionwp')); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Ativação', 'bastionwp'); ?></span><strong><?php echo $plugin_file === '' ? esc_html__('Sempre ativa', 'bastionwp') : ($plugin_active ? esc_html__('Ativa', 'bastionwp') : esc_html__('Inativa', 'bastionwp')); ?></strong></div>
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Acesso do cliente', 'bastionwp'); ?></span><strong><?php echo esc_html($selected_integration['access_compatibility']); ?></strong></div>
                </div>
                <div class="bastionwp-integration-actions-bar">
                    <?php if ($plugin_file !== '') : ?>
                        <?php if ($plugin_installed && !$plugin_active) : ?>
                            <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file)); ?>"><?php echo esc_html__('Ativar plugin', 'bastionwp'); ?></a>
                        <?php elseif ($plugin_active) : ?>
                            <a class="button button-primary" href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php echo esc_html__('Gerenciar plugin', 'bastionwp'); ?></a>
                        <?php else : ?>
                            <a class="button button-primary" href="<?php echo esc_url(admin_url('plugin-install.php?s=' . rawurlencode($integration_doc['title']) . '&tab=search&type=term')); ?>"><?php echo esc_html__('Instalar plugin', 'bastionwp'); ?></a>
                        <?php endif; ?>
                    <?php else : ?>
                        <a class="button button-primary" href="<?php echo esc_url(rest_url()); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_html__('Abrir endpoint', 'bastionwp'); ?></a>
                    <?php endif; ?>
                    <a class="button" href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php echo esc_html__('Abrir Plugins', 'bastionwp'); ?></a>
                </div>
                <div class="bastionwp-grid bastionwp-integration-detail-grid">
                    <section class="bastionwp-card">
                        <h3><?php echo esc_html__('Opções disponíveis', 'bastionwp'); ?></h3>
                        <ul class="bastionwp-simple-list">
                            <li><?php echo esc_html__('Consultar se o plugin está instalado e ativo.', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Ver versão instalada.', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Abrir a área nativa do WordPress para instalar/ativar quando aplicável.', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Usar a área Segurança para revisar compatibilidades quando necessário.', 'bastionwp'); ?></li>
                        </ul>
                    </section>
                    <section class="bastionwp-card">
                        <h3><?php echo esc_html__('Observações do BastionWP', 'bastionwp'); ?></h3>
                        <div class="bastionwp-callout"><strong><?php echo esc_html__('Status atual:', 'bastionwp'); ?></strong> <?php echo esc_html($integration_doc['status']); ?></div>
                        <p><?php echo esc_html__('O BastionWP libera diretamente menus que usam permissões editoriais seguras. Quando um plugin exige permissões administrativas amplas, ele permanece bloqueado até existir uma compatibilidade de acesso que reconheça somente as operações daquele plugin, sem conceder privilégios globais ao cliente.', 'bastionwp'); ?></p>
                    </section>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php elseif ($tab === 'diagnostics') : ?>
        <div class="bastionwp-grid bastionwp-page-diagnostics">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Visão consolidada', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Status do Sistema', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Este relatório reúne o estado do plugin, Bastion Core, ambiente WordPress, atualizações, Segurança e Wordfence sem incluir senhas, tokens ou outras credenciais.', 'bastionwp'); ?>
                </p>

                <div class="bastionwp-diagnostic-summary">
                    <div class="bastionwp-summary-ok">
                        <strong><?php echo esc_html((string) ($diagnostics_report['summary']['ok'] ?? 0)); ?></strong>
                        <span><?php echo esc_html__('OK', 'bastionwp'); ?></span>
                    </div>
                    <div class="bastionwp-summary-warning">
                        <strong><?php echo esc_html((string) ($diagnostics_report['summary']['warning'] ?? 0)); ?></strong>
                        <span><?php echo esc_html__('Atenções', 'bastionwp'); ?></span>
                    </div>
                    <div class="bastionwp-summary-error">
                        <strong><?php echo esc_html((string) ($diagnostics_report['summary']['error'] ?? 0)); ?></strong>
                        <span><?php echo esc_html__('Erros', 'bastionwp'); ?></span>
                    </div>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_export_diagnostics">
                    <?php wp_nonce_field('bastionwp_export_diagnostics'); ?>
                    <?php submit_button(__('Baixar relatório JSON', 'bastionwp'), 'secondary', 'submit', false); ?>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Verificações', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Estado atual do ambiente', 'bastionwp'); ?></h2>

                <div class="bastionwp-diagnostic-list">
                    <?php foreach (($diagnostics_report['checks'] ?? []) as $check) : ?>
                        <div class="bastionwp-diagnostic-item">
                            <span class="bastionwp-diagnostic-state bastionwp-diagnostic-<?php echo esc_attr($check['status']); ?>"></span>
                            <div>
                                <strong><?php echo esc_html($check['label']); ?></strong>
                                <small><?php echo esc_html($check['description']); ?></small>
                            </div>
                            <div class="bastionwp-diagnostic-action">
                                <span class="bastionwp-diagnostic-value"><?php echo esc_html($check['value']); ?></span>
                                <?php if (($check['action'] ?? '') === 'fix_display_errors') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="bastionwp_fix_display_errors">
                                        <?php wp_nonce_field('bastionwp_fix_display_errors'); ?>
                                        <button type="submit" class="button button-small"><?php echo esc_html__('Corrigir agora', 'bastionwp'); ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

</div>


<?php elseif ($tab === 'system') : ?>
    <?php
    $system_view = isset($_GET['system_view']) ? sanitize_key(wp_unslash($_GET['system_view'])) : 'overview';
    if (!in_array($system_view, ['overview', 'logs', 'backups'], true)) { $system_view = 'overview'; }
    $recent_logs = BastionWP_Logger::get_logs([], 10, 0);
    $has_update = !empty($update_status['update_available']);
    $source_owner = (string) ($update_settings['owner'] ?? 'BUSSINGUER');
    $source_repo = (string) ($update_settings['repo'] ?? 'bastionwp');
    ?>
    <nav class="bastionwp-system-subnav"><a class="<?php echo $system_view==='overview'?'is-active':''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>"><span class="dashicons dashicons-dashboard"></span><?php echo esc_html__('Visão geral', 'bastionwp'); ?></a><a class="<?php echo $system_view==='logs'?'is-active':''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system&system_view=logs')); ?>"><span class="dashicons dashicons-media-text"></span><?php echo esc_html__('Logs', 'bastionwp'); ?></a><a class="<?php echo $system_view==='backups'?'is-active':''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system&system_view=backups')); ?>"><span class="dashicons dashicons-backup"></span><?php echo esc_html__('Backups de configuração', 'bastionwp'); ?></a></nav>

    <?php if ($system_view === 'logs') : ?>
        <div class="bastionwp-grid bastionwp-page-system-modern">
            <section class="bastionwp-card bastionwp-card-wide"><div class="bastionwp-overview-section-head"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-media-text"></span><div><h2><?php echo esc_html__('Logs do BastionWP', 'bastionwp'); ?></h2><p><?php echo esc_html__('Histórico completo de eventos registrados pelo BastionWP.', 'bastionwp'); ?></p></div></div><div class="bastionwp-log-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_export_logs"><?php wp_nonce_field('bastionwp_export_logs'); ?><?php submit_button(__('Exportar CSV','bastionwp'),'secondary','submit',false); ?></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Deseja realmente limpar os logs?', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_clear_logs"><?php wp_nonce_field('bastionwp_clear_logs'); ?><?php submit_button(__('Limpar logs','bastionwp'),'delete','submit',false); ?></form></div></div>
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-log-filters"><input type="hidden" name="page" value="bastionwp"><input type="hidden" name="tab" value="system"><input type="hidden" name="system_view" value="logs"><label><span><?php echo esc_html__('Nível','bastionwp'); ?></span><select name="log_level"><option value=""><?php echo esc_html__('Todos','bastionwp'); ?></option><?php foreach(['info','success','warning','error'] as $level): ?><option value="<?php echo esc_attr($level); ?>" <?php selected($log_filters['level'],$level); ?>><?php echo esc_html(ucfirst($level)); ?></option><?php endforeach; ?></select></label><label><span><?php echo esc_html__('Evento','bastionwp'); ?></span><select name="log_event"><option value=""><?php echo esc_html__('Todos','bastionwp'); ?></option><?php foreach($log_event_types as $event_type): ?><option value="<?php echo esc_attr($event_type); ?>" <?php selected($log_filters['event_type'],$event_type); ?>><?php echo esc_html($event_type); ?></option><?php endforeach; ?></select></label><?php submit_button(__('Filtrar','bastionwp'),'secondary','submit',false); ?></form>
                <?php if (empty($log_rows)) : ?><div class="bastionwp-empty-state"><p><?php echo esc_html__('Nenhum log encontrado.', 'bastionwp'); ?></p></div><?php else : ?><div class="bastionwp-log-table-wrap"><table class="widefat striped bastionwp-log-table"><thead><tr><th><?php echo esc_html__('Data','bastionwp'); ?></th><th><?php echo esc_html__('Nível','bastionwp'); ?></th><th><?php echo esc_html__('Evento','bastionwp'); ?></th><th><?php echo esc_html__('Usuário','bastionwp'); ?></th><th><?php echo esc_html__('Mensagem','bastionwp'); ?></th></tr></thead><tbody><?php foreach($log_rows as $log_row): $log_user=!empty($log_row['user_id'])?get_userdata((int)$log_row['user_id']):false; ?><tr><td><?php echo esc_html(get_date_from_gmt((string)$log_row['event_time'],'d/m/Y H:i:s')); ?></td><td><span class="bastionwp-log-level bastionwp-log-<?php echo esc_attr($log_row['level']); ?>"><?php echo esc_html($log_row['level']); ?></span></td><td><code><?php echo esc_html($log_row['event_type']); ?></code></td><td><?php echo esc_html($log_user?$log_user->display_name:__('Sistema','bastionwp')); ?></td><td><?php echo esc_html($log_row['message']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php $log_total_pages=max(1,(int)ceil($log_total/$log_per_page)); if($log_total_pages>1): $pagination_base=add_query_arg(['page'=>'bastionwp','tab'=>'system','system_view'=>'logs','log_level'=>$log_filters['level'],'log_event'=>$log_filters['event_type'],'log_page'=>'%#%'],admin_url('admin.php')); ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(['base'=>$pagination_base,'format'=>'','current'=>$log_page,'total'=>$log_total_pages,'prev_text'=>'‹','next_text'=>'›'])); ?></div></div><?php endif; endif; ?>
            </section>
        </div>
    <?php elseif ($system_view === 'backups') : ?>
        <div class="bastionwp-grid bastionwp-page-system-modern bastionwp-page-config-backups">
            <section class="bastionwp-card bastionwp-card-wide">
                <div class="bastionwp-overview-section-head">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-backup"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Rollback técnico','bastionwp'); ?></span><h2><?php echo esc_html__('Backups de configuração','bastionwp'); ?></h2><p><?php echo esc_html__('Snapshots de arquivos de configuração suportados antes de gravações sensíveis. Não substitui um backup completo do site.','bastionwp'); ?></p></div></div>
                    <span class="bastionwp-hero-status <?php echo !empty($config_backup_storage['writable']) ? 'bastionwp-hero-status-success' : 'bastionwp-hero-status-warning'; ?>"><span class="bastionwp-status-dot"></span><?php echo !empty($config_backup_storage['writable']) ? esc_html__('Armazenamento pronto','bastionwp') : esc_html__('Armazenamento será preparado no primeiro snapshot','bastionwp'); ?></span>
                </div>
                <div class="bastionwp-callout bastionwp-callout-info"><strong><?php echo esc_html__('Arquivos do Core não são editados.','bastionwp'); ?></strong><p><?php echo esc_html__('wp-admin/* e wp-login.php nunca são alvos deste mecanismo. Os snapshots são limitados a arquivos de configuração explicitamente suportados.','bastionwp'); ?></p></div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-backup-create-form">
                    <input type="hidden" name="action" value="bastionwp_create_config_snapshot"><?php wp_nonce_field('bastionwp_create_config_snapshot'); ?>
                    <label><span><?php echo esc_html__('Arquivo','bastionwp'); ?></span><select name="target_key" required><?php foreach($config_backup_targets as $target_key=>$target): ?><option value="<?php echo esc_attr($target_key); ?>" <?php disabled(empty($target['exists']) || empty($target['readable'])); ?>><?php echo esc_html((string)$target['label']); ?><?php echo empty($target['exists']) ? esc_html__(' — não encontrado','bastionwp') : ''; ?></option><?php endforeach; ?></select></label>
                    <label class="is-wide"><span><?php echo esc_html__('Motivo','bastionwp'); ?></span><input type="text" name="reason" maxlength="180" value="<?php echo esc_attr__('Snapshot manual antes de manutenção','bastionwp'); ?>"></label>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Criar snapshot','bastionwp'); ?></button>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-list-view"></span><div><h2><?php echo esc_html__('Snapshots disponíveis','bastionwp'); ?></h2><p><?php echo esc_html(sprintf(__('O BastionWP mantém até %d snapshots por arquivo.','bastionwp'), BastionWP_Config_Backup::MAX_PER_TARGET)); ?></p></div></div>
                <?php if (empty($config_snapshots)) : ?>
                    <div class="bastionwp-empty-state"><p><?php echo esc_html__('Nenhum snapshot foi criado ainda.','bastionwp'); ?></p></div>
                <?php else : ?>
                    <div class="bastionwp-backup-list">
                    <?php foreach($config_snapshots as $snapshot): $snapshot_user=!empty($snapshot['user_id'])?get_userdata((int)$snapshot['user_id']):false; ?>
                        <article class="bastionwp-backup-item">
                            <div class="bastionwp-backup-main"><strong><?php echo esc_html((string)($snapshot['target_label'] ?? $snapshot['target_key'])); ?></strong><span><?php echo esc_html((string)($snapshot['reason'] ?? '')); ?></span><small><?php echo esc_html(get_date_from_gmt((string)$snapshot['created_at'],'d/m/Y H:i:s')); ?> · <?php echo esc_html($snapshot_user?$snapshot_user->display_name:__('Sistema','bastionwp')); ?> · BastionWP <?php echo esc_html((string)($snapshot['bastion_version'] ?? '—')); ?></small></div>
                            <div class="bastionwp-backup-hash"><span>SHA-256</span><code><?php echo esc_html(substr((string)$snapshot['sha256'],0,16).'…'); ?></code><small><?php echo esc_html(size_format((int)($snapshot['size'] ?? 0))); ?></small></div>
                            <div class="bastionwp-backup-actions"><a class="button button-small" href="<?php echo esc_url(add_query_arg(['page'=>'bastionwp','tab'=>'system','system_view'=>'backups','snapshot_compare'=>(string)$snapshot['id']],admin_url('admin.php'))); ?>"><?php echo esc_html__('Comparar','bastionwp'); ?></a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Restaurar este snapshot? O estado atual será salvo antes da restauração.','bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_restore_config_snapshot"><input type="hidden" name="snapshot_id" value="<?php echo esc_attr((string)$snapshot['id']); ?>"><?php wp_nonce_field('bastionwp_restore_config_snapshot'); ?><button class="button button-small" type="submit"><?php echo esc_html__('Restaurar','bastionwp'); ?></button></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Excluir definitivamente este snapshot?','bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_delete_config_snapshot"><input type="hidden" name="snapshot_id" value="<?php echo esc_attr((string)$snapshot['id']); ?>"><?php wp_nonce_field('bastionwp_delete_config_snapshot'); ?><button class="button-link-delete" type="submit"><?php echo esc_html__('Excluir','bastionwp'); ?></button></form></div>
                        </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($config_snapshot_compare !== null) : ?>
                <section class="bastionwp-card bastionwp-card-wide bastionwp-backup-compare">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-editor-code"></span><div><h2><?php echo esc_html__('Comparação do snapshot','bastionwp'); ?></h2><p><?php echo esc_html__('Valores potencialmente sensíveis são ocultados na visualização.','bastionwp'); ?></p></div></div>
                    <?php if (is_wp_error($config_snapshot_compare)) : ?><div class="bastionwp-callout bastionwp-callout-error"><?php echo esc_html($config_snapshot_compare->get_error_message()); ?></div><?php else : ?>
                        <div class="bastionwp-backup-compare-summary"><span><?php echo esc_html__('Snapshot','bastionwp'); ?><code><?php echo esc_html(substr((string)$config_snapshot_compare['snapshot']['sha256'],0,20).'…'); ?></code></span><span><?php echo esc_html__('Atual','bastionwp'); ?><code><?php echo esc_html($config_snapshot_compare['current_sha256']!==''?substr((string)$config_snapshot_compare['current_sha256'],0,20).'…':'—'); ?></code></span><strong class="<?php echo !empty($config_snapshot_compare['same'])?'is-good':'is-warning'; ?>"><?php echo !empty($config_snapshot_compare['same'])?esc_html__('Sem diferenças','bastionwp'):esc_html__('Diferenças detectadas','bastionwp'); ?></strong></div>
                        <?php if (empty($config_snapshot_compare['same'])) : ?><div class="bastionwp-backup-diff"><div class="bastionwp-backup-diff-head"><span><?php echo esc_html__('Linha','bastionwp'); ?></span><span><?php echo esc_html__('Snapshot','bastionwp'); ?></span><span><?php echo esc_html__('Arquivo atual','bastionwp'); ?></span></div><?php foreach((array)$config_snapshot_compare['changes'] as $change): ?><div><span><?php echo esc_html((string)$change['line']); ?></span><code><?php echo esc_html((string)($change['backup'] ?? '∅')); ?></code><code><?php echo esc_html((string)($change['current'] ?? '∅')); ?></code></div><?php endforeach; ?></div><?php if(!empty($config_snapshot_compare['changes_limited'])): ?><p class="description"><?php echo esc_html__('A visualização foi limitada às primeiras 120 diferenças.','bastionwp'); ?></p><?php endif; endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="bastionwp-grid bastionwp-page-system-modern">
            <section class="bastionwp-card bastionwp-card-wide bastionwp-update-status-card <?php echo $has_update?'has-update':'is-current'; ?>">
                <div class="bastionwp-overview-section-head"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-update"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Atualizações','bastionwp'); ?></span><h2><?php echo esc_html__('Status da versão instalada','bastionwp'); ?></h2><p><?php echo esc_html__('Verifique e instale novas versões sem sair do BastionWP. O pacote continua sendo validado antes do upgrade.', 'bastionwp'); ?></p></div></div><span class="bastionwp-hero-status <?php echo $has_update?'bastionwp-hero-status-success':'bastionwp-hero-status-info'; ?>"><span class="bastionwp-status-dot"></span><?php echo $has_update?esc_html__('Nova atualização disponível','bastionwp'):esc_html__('Versão atualizada','bastionwp'); ?></span></div>
                <div class="bastionwp-update-version-grid"><div class="<?php echo $has_update?'is-outdated':''; ?>"><span><?php echo esc_html__('Versão instalada','bastionwp'); ?></span><strong><?php echo esc_html(BASTIONWP_VERSION); ?></strong><?php if($has_update): ?><small><?php echo esc_html__('Desatualizada','bastionwp'); ?></small><?php endif; ?></div><div class="<?php echo $has_update?'is-new':''; ?>"><span><?php echo esc_html__('Última disponível','bastionwp'); ?></span><strong><?php echo esc_html((string)($update_status['latest_version']?:'—')); ?></strong><?php if($has_update): ?><small><?php echo esc_html__('Pronta para instalar','bastionwp'); ?></small><?php endif; ?></div><div><span><?php echo esc_html__('Atualização automática','bastionwp'); ?></span><strong><?php echo $auto_update_enabled?esc_html__('Ativada','bastionwp'):esc_html__('Desativada','bastionwp'); ?></strong></div></div>
                <?php if(!empty($update_status['error'])): ?><div class="bastionwp-callout bastionwp-callout-warning"><?php echo esc_html($update_status['error']); ?></div><?php endif; ?>
                <div class="bastionwp-update-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_check_updates"><?php wp_nonce_field('bastionwp_check_updates'); ?><button type="submit" class="button bastionwp-check-update-button"><span class="dashicons dashicons-search"></span><?php echo esc_html__('Verificar atualizações agora','bastionwp'); ?></button></form><?php if($has_update): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_install_update"><?php wp_nonce_field('bastionwp_install_update'); ?><button type="submit" class="button button-primary"><span class="dashicons dashicons-update"></span><?php echo esc_html__('Instalar atualização','bastionwp'); ?></button></form><?php endif; ?></div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide bastionwp-recent-logs-card"><div class="bastionwp-overview-section-head"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-media-text"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Auditoria','bastionwp'); ?></span><h2><?php echo esc_html__('Atividade recente','bastionwp'); ?></h2><p><?php echo esc_html__('Os 10 eventos mais recentes registrados pelo BastionWP.', 'bastionwp'); ?></p></div></div><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system&system_view=logs')); ?>"><?php echo esc_html__('Ver todos os logs','bastionwp'); ?><span class="dashicons dashicons-arrow-right-alt2"></span></a></div>
                <div class="bastionwp-system-log-list"><?php foreach($recent_logs as $log_row): $u=!empty($log_row['user_id'])?get_userdata((int)$log_row['user_id']):false; ?><article><span class="bastionwp-overview-activity-dot bastionwp-overview-activity-<?php echo esc_attr($log_row['level']); ?>"></span><div><strong><?php echo esc_html($log_row['message']); ?></strong><small><code><?php echo esc_html($log_row['event_type']); ?></code> · <?php echo esc_html($u?$u->display_name:__('Sistema','bastionwp')); ?></small></div><time><?php echo esc_html(get_date_from_gmt((string)$log_row['event_time'],'d/m H:i')); ?></time></article><?php endforeach; ?></div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide bastionwp-system-risk-zone <?php echo $risk_zone_unlocked ? 'is-unlocked' : 'is-locked'; ?>" id="bastionwp-risk-zone" data-bastionwp-global-risk-zone>
                <div class="bastionwp-risk-zone-toolbar">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-warning"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Zona de risco','bastionwp'); ?></span><h2><?php echo esc_html__('Configurações sensíveis do sistema','bastionwp'); ?></h2><p><?php echo esc_html__('Um único desbloqueio libera temporariamente todas as opções desta área. Futuramente, este passo poderá exigir autenticação adicional.', 'bastionwp'); ?></p></div></div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-risk-master-form"><input type="hidden" name="action" value="bastionwp_toggle_risk_zone"><?php wp_nonce_field('bastionwp_toggle_risk_zone'); ?><button type="submit" class="button <?php echo $risk_zone_unlocked ? '' : 'button-primary'; ?> bastionwp-risk-master-button"><span class="dashicons <?php echo $risk_zone_unlocked ? 'dashicons-lock' : 'dashicons-unlock'; ?>"></span><?php echo $risk_zone_unlocked ? esc_html__('Bloquear Zona de risco','bastionwp') : esc_html__('Desbloquear Zona de risco','bastionwp'); ?></button></form>
                </div>
                <?php if (!$risk_zone_unlocked) : ?><div class="bastionwp-risk-locked-note"><span class="dashicons dashicons-lock"></span><strong><?php echo esc_html__('Zona de risco bloqueada', 'bastionwp'); ?></strong><span><?php echo esc_html__('As opções abaixo estão desfocadas e indisponíveis até o desbloqueio.', 'bastionwp'); ?></span></div><?php endif; ?>
                <div class="bastionwp-risk-sensitive-content" <?php echo !$risk_zone_unlocked ? 'aria-disabled="true"' : ''; ?>>
                    <div class="bastionwp-risk-two-column">
                        <article class="bastionwp-risk-card"><div class="bastionwp-risk-card-head"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-admin-users"></span><div><strong><?php echo esc_html__('Developer Principal','bastionwp'); ?></strong><small><?php echo esc_html__('Conta técnica que administra o BastionWP.','bastionwp'); ?></small></div></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_save_access"><?php wp_nonce_field('bastionwp_save_access'); ?><fieldset <?php echo !$risk_zone_unlocked ? 'disabled' : ''; ?>><label class="bastionwp-field-label" for="developer_user_id"><?php echo esc_html__('Usuário Developer','bastionwp'); ?></label><select id="developer_user_id" name="developer_user_id"><?php foreach($administrators as $administrator): ?><option value="<?php echo esc_attr((string)$administrator->ID); ?>" <?php selected(in_array((int)$administrator->ID,$developer_ids,true)); ?>><?php echo esc_html($administrator->display_name.' ('.$administrator->user_login.')'); ?></option><?php endforeach; ?></select><?php submit_button(__('Salvar Developer','bastionwp'),'primary'); ?></fieldset></form></article>
                        <article class="bastionwp-risk-card"><div class="bastionwp-risk-card-head"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-admin-links"></span><div><strong><?php echo esc_html__('Fonte de atualização','bastionwp'); ?></strong><small><?php echo esc_html__('Provider usado para localizar Releases oficiais.','bastionwp'); ?></small></div></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_save_update_settings"><input type="hidden" name="source_unlocked" value="<?php echo $risk_zone_unlocked ? '1' : '0'; ?>"><?php wp_nonce_field('bastionwp_save_update_settings'); ?><fieldset <?php echo !$risk_zone_unlocked ? 'disabled' : ''; ?>><label class="bastionwp-field-label" for="github_owner"><?php echo esc_html__('Proprietário','bastionwp'); ?></label><input type="text" id="github_owner" name="github_owner" value="<?php echo $risk_zone_unlocked ? esc_attr($source_owner) : '********'; ?>"><label class="bastionwp-field-label" for="github_repo"><?php echo esc_html__('Repositório','bastionwp'); ?></label><input type="text" id="github_repo" name="github_repo" value="<?php echo $risk_zone_unlocked ? esc_attr($source_repo) : '********'; ?>"><label class="bastionwp-field-label" for="update_channel"><?php echo esc_html__('Canal','bastionwp'); ?></label><select id="update_channel" name="update_channel"><option value="stable" <?php selected($update_settings['channel'],'stable'); ?>><?php echo esc_html__('Estável','bastionwp'); ?></option><option value="beta" <?php selected($update_settings['channel'],'beta'); ?>><?php echo esc_html__('Beta','bastionwp'); ?></option></select><label class="bastionwp-checkbox-line"><input type="checkbox" name="auto_update" value="1" <?php checked($auto_update_enabled); ?>><span><strong><?php echo esc_html__('Atualização automática','bastionwp'); ?></strong></span></label><?php submit_button(__('Salvar fonte','bastionwp'),'primary'); ?></fieldset></form></article>
                    </div>
                    <fieldset class="bastionwp-risk-exit-fieldset" <?php echo !$risk_zone_unlocked ? 'disabled' : ''; ?>>
                        <div class="bastionwp-plugin-exit-card"><div><span class="dashicons dashicons-exit"></span><div><strong><?php echo esc_html__('Handoff ou encerramento do BastionWP','bastionwp'); ?></strong><p><?php echo esc_html__('Use estas ações para desligar o Core de forma controlada ou remover o plugin durante uma troca de desenvolvedor.', 'bastionwp'); ?></p></div></div><div class="bastionwp-plugin-exit-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Desativar o BastionWP e remover o Bastion Core? As configurações e logs serão preservados.', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_system_deactivate"><?php wp_nonce_field('bastionwp_system_deactivate'); ?><button class="button" type="submit"><?php echo esc_html__('Desativar BastionWP','bastionwp'); ?></button></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Remover o BastionWP do site? Os Clientes Protegidos serão convertidos para a role escolhida.', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_system_remove"><?php wp_nonce_field('bastionwp_system_remove'); ?><label><?php echo esc_html__('Role após remoção','bastionwp'); ?><select name="replacement_role"><option value="editor"><?php echo esc_html__('Editor','bastionwp'); ?></option><option value="administrator"><?php echo esc_html__('Administrador','bastionwp'); ?></option><option value="author"><?php echo esc_html__('Autor','bastionwp'); ?></option><option value="subscriber"><?php echo esc_html__('Assinante','bastionwp'); ?></option></select></label><label class="bastionwp-checkbox-line"><input type="checkbox" name="cleanup_data" value="1"><span><?php echo esc_html__('Remover também opções, logs e role do BastionWP','bastionwp'); ?></span></label><button class="button bastionwp-danger-button" type="submit"><?php echo esc_html__('Remover BastionWP do site','bastionwp'); ?></button></form></div></div>
                    </fieldset>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <?php else : ?>
        <div class="bastionwp-grid"><section class="bastionwp-card bastionwp-card-wide"><p><?php echo esc_html__('Área não encontrada.', 'bastionwp'); ?></p></section></div>
    <?php endif; ?>
</div>
