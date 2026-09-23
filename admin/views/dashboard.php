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

$is_ssl = is_ssl();
$current_user = wp_get_current_user();

$page_meta = [
    'overview' => [
        'icon' => 'dashicons-shield-alt',
        'title' => __('Saúde do ambiente', 'bastionwp'),
        'description' => __('Seu site está protegido e funcionando corretamente. Acompanhe abaixo o status dos principais componentes.', 'bastionwp'),
        'status' => !empty($diagnostics_report['summary']['error'])
            ? __('Requer atenção', 'bastionwp')
            : (
                !empty($diagnostics_report['summary']['warning'])
                    ? __('Ambiente com atenção', 'bastionwp')
                    : __('Ambiente saudável', 'bastionwp')
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
        'icon' => 'dashicons-list-view',
        'title' => __('Assistente BastionWP', 'bastionwp'),
        'description' => __('Revise a configuração inicial do plugin e acompanhe o progresso das áreas essenciais.', 'bastionwp'),
        'status' => __('Configuração guiada', 'bastionwp'),
        'status_class' => 'info',
    ],
    'access' => [
        'icon' => 'dashicons-groups',
        'title' => __('Controle de acessos', 'bastionwp'),
        'description' => __('Configure o Developer principal, Gerenciadores do Cliente e os menus liberados por usuário.', 'bastionwp'),
        'status' => __('Sistema ativo', 'bastionwp'),
        'status_class' => 'success',
    ],
    'requests' => [
        'icon' => 'dashicons-unlock',
        'title' => __('Solicitações administrativas', 'bastionwp'),
        'description' => __('Analise pedidos de privilégios temporários, aprove por período definido ou encerre acessos ativos.', 'bastionwp'),
        'status' => __('Acesso temporário', 'bastionwp'),
        'status_class' => 'info',
    ],
    'hardening' => [
        'icon' => 'dashicons-shield',
        'title' => __('Perfil de Hardening', 'bastionwp'),
        'description' => __('Ajuste o nível de segurança do ambiente WordPress de acordo com cada fase do projeto.', 'bastionwp'),
        'status' => BastionWP_Hardening::get_profile() === BastionWP_Hardening::PROFILE_UNCONFIGURED
            ? __('Não configurado', 'bastionwp')
            : __('Ambiente protegido', 'bastionwp'),
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
        'icon' => 'dashicons-search',
        'title' => __('Diagnóstico do ambiente', 'bastionwp'),
        'description' => __('Revise verificações de WordPress, servidor, hardening, atualizações e integrações.', 'bastionwp'),
        'status' => !empty($diagnostics_report['summary']['error'])
            ? __('Erros encontrados', 'bastionwp')
            : __('Diagnóstico disponível', 'bastionwp'),
        'status_class' => !empty($diagnostics_report['summary']['error']) ? 'error' : 'success',
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
?>
<div class="wrap bastionwp-wrap">
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
            <a class="button bastionwp-button bastionwp-button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <?php echo esc_html__('Executar diagnóstico', 'bastionwp'); ?>
            </a>

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
            <span class="dashicons dashicons-heart" aria-hidden="true"></span><span><?php echo esc_html__('Visão Geral', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'wizard' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=wizard')); ?>">
            <span class="dashicons dashicons-list-view" aria-hidden="true"></span><span><?php echo esc_html__('Assistente', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'access' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
            <span class="dashicons dashicons-groups" aria-hidden="true"></span><span><?php echo esc_html__('Acessos', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'requests' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=requests')); ?>">
            <span class="dashicons dashicons-unlock" aria-hidden="true"></span><span><?php echo esc_html__('Solicitações', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'hardening' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening')); ?>">
            <span class="dashicons dashicons-shield" aria-hidden="true"></span><span><?php echo esc_html__('Hardening', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'integrations' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=integrations')); ?>">
            <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span><span><?php echo esc_html__('Integrações', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
            <span class="dashicons dashicons-search" aria-hidden="true"></span><span><?php echo esc_html__('Diagnóstico', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'logs' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=logs')); ?>">
            <span class="dashicons dashicons-media-text" aria-hidden="true"></span><span><?php echo esc_html__('Logs', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'updates' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=updates')); ?>">
            <span class="dashicons dashicons-update" aria-hidden="true"></span><span><?php echo esc_html__('Atualizações', 'bastionwp'); ?></span>
        </a>
    </nav>

    <section class="bastionwp-page-hero <?php echo $tab === 'wizard' ? 'bastionwp-page-hero-wizard' : ''; ?>">
        <div class="bastionwp-page-hero-main">
            <span class="bastionwp-page-icon dashicons <?php echo esc_attr($current_page_meta['icon']); ?>" aria-hidden="true"></span>
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

        $overview_update_ready = !empty($update_status['configured']) && $auto_update_enabled;
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
                        <dt><?php echo esc_html__('Versão incluída', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html($core_status['source_version'] ?: '—'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Versão instalada', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html($core_status['target_version'] ?: '—'); ?></dd>
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
                    <?php echo esc_html__('Ambiente configurado e operando normalmente.', 'bastionwp'); ?>
                </p>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-groups" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Controle de Acesso', 'bastionwp'); ?></h2>
                        <span class="bastionwp-hero-status bastionwp-hero-status-success">
                            <span class="bastionwp-status-dot" aria-hidden="true"></span>
                            <?php echo esc_html__('Protegido', 'bastionwp'); ?>
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
                    <span><?php echo esc_html__('Gerenciadores do Cliente', 'bastionwp'); ?></span>
                    <strong><?php echo esc_html((string) count($client_managers)); ?></strong>
                </div>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
                    <?php echo esc_html__('Gerenciar acessos', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-shield" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Hardening', 'bastionwp'); ?></h2>
                        <span class="bastionwp-hero-status bastionwp-hero-status-info">
                            <?php echo esc_html($overview_profile_label); ?>
                        </span>
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
                    <li class="<?php echo !empty($update_status['configured']) ? 'is-ok' : 'is-warning'; ?>">
                        <?php echo esc_html__('Via GitHub Releases', 'bastionwp'); ?>
                    </li>
                    <li class="<?php echo $auto_update_enabled ? 'is-ok' : 'is-warning'; ?>">
                        <?php echo esc_html__('Automáticas', 'bastionwp'); ?>
                    </li>
                    <li class="is-ok">
                        <?php echo esc_html__('Seguras e controladas', 'bastionwp'); ?>
                    </li>
                </ul>

                <a class="button bastionwp-overview-card-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=updates')); ?>">
                    <?php echo esc_html__('Ver atualizações', 'bastionwp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-chart-pie" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Diagnóstico', 'bastionwp'); ?></h2>
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

            <section class="bastionwp-card bastionwp-overview-permissions-card">
                <div class="bastionwp-overview-section-head">
                    <div class="bastionwp-overview-section-title">
                        <span class="bastionwp-overview-card-icon dashicons dashicons-groups" aria-hidden="true"></span>
                        <div>
                            <h2><?php echo esc_html__('Controle de usuários e permissões', 'bastionwp'); ?></h2>
                            <p><?php echo esc_html__('Principais recursos ativos no BastionWP para controle de acesso e restrição de áreas.', 'bastionwp'); ?></p>
                        </div>
                    </div>
                    <span class="bastionwp-hero-status bastionwp-hero-status-success">
                        <span class="bastionwp-status-dot" aria-hidden="true"></span>
                        <?php echo esc_html__('Sistema ativo', 'bastionwp'); ?>
                    </span>
                </div>

                <ul class="bastionwp-checklist bastionwp-overview-feature-list">
                    <li><?php echo esc_html__('Developer Principal identificado por ID interno', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Role Gerenciador do Cliente criada', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Capabilities técnicas removidas do cliente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Rotas técnicas bloqueadas para Client Manager', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Conta Developer protegida contra edição/exclusão', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Interface e descrição em português-BR', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Controle granular dos menus liberados ao cliente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Sistema de atualização via GitHub Releases', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Políticas configuradas individualmente por usuário', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Registro de menus de plugins com capabilities próprias', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Atualização automática via mecanismo nativo', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Perfis de hardening por ambiente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Produção Bloqueada com alterações restritas', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Integração operacional com Wordfence', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Logs de auditoria do BastionWP', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Diagnóstico consolidado e exportável', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Assistente de configuração inicial', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Privilégios administrativos temporários', 'bastionwp'); ?></li>
                </ul>

                <div class="bastionwp-overview-info-strip">
                    <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                    <p><?php echo esc_html__('O BastionWP trabalha em segundo plano para manter seu ambiente mais seguro, sem atrapalhar sua produtividade.', 'bastionwp'); ?></p>
                </div>
            </section>

            <aside class="bastionwp-overview-side">
                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-title">
                        <span class="bastionwp-overview-card-icon dashicons dashicons-performance" aria-hidden="true"></span>
                        <div>
                            <h2><?php echo esc_html__('Ações rápidas', 'bastionwp'); ?></h2>
                            <p><?php echo esc_html__('Acesse as principais ações do BastionWP.', 'bastionwp'); ?></p>
                        </div>
                    </div>

                    <div class="bastionwp-overview-quick-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
                            <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html__('Gerenciar acessos', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Configurar usuários e permissões', 'bastionwp'); ?></small>
                            </div>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening')); ?>">
                            <span class="dashicons dashicons-shield" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html__('Ajustar hardening', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Alterar perfil de segurança', 'bastionwp'); ?></small>
                            </div>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
                            <span class="dashicons dashicons-performance" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html__('Verificar diagnóstico', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Validar saúde do ambiente', 'bastionwp'); ?></small>
                            </div>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=updates')); ?>">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html__('Configurar atualizações', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Revisar fonte e comportamento', 'bastionwp'); ?></small>
                            </div>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                    </div>
                </section>

                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-head">
                        <div class="bastionwp-overview-section-title">
                            <span class="bastionwp-overview-card-icon dashicons dashicons-media-text" aria-hidden="true"></span>
                            <div>
                                <h2><?php echo esc_html__('Atividade recente', 'bastionwp'); ?></h2>
                            </div>
                        </div>
                        <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=logs')); ?>">
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
        $wizard_completed_total = 0;
        $wizard_warning_total = 0;
        $wizard_error_total = 0;
        $wizard_required_total = 0;
        $wizard_recommended_total = 0;
        $wizard_next_step = null;

        foreach ($wizard_steps as $wizard_step_data) {
            if (($wizard_step_data['status'] ?? '') === 'ok') {
                $wizard_completed_total++;
            } elseif (($wizard_step_data['status'] ?? '') === 'warning') {
                $wizard_warning_total++;
            } elseif (($wizard_step_data['status'] ?? '') === 'error') {
                $wizard_error_total++;
            }

            if (!empty($wizard_step_data['required'])) {
                $wizard_required_total++;

                if (
                    $wizard_next_step === null
                    && ($wizard_step_data['status'] ?? '') !== 'ok'
                ) {
                    $wizard_next_step = $wizard_step_data;
                }
            } else {
                $wizard_recommended_total++;
            }
        }

        $wizard_pending_total = $wizard_warning_total + $wizard_error_total;
        $wizard_circle_angle = max(0, min(360, (int) round(((int) $wizard_progress['percent'] / 100) * 360)));

        $wizard_icon_map = [
            'foundation'  => 'dashicons-archive',
            'access'      => 'dashicons-groups',
            'hardening'   => 'dashicons-shield',
            'wordfence'   => 'dashicons-shield-alt',
            'updates'     => 'dashicons-update',
            'diagnostics' => 'dashicons-clock',
        ];
        ?>

        <div class="bastionwp-wizard-layout">
            <main class="bastionwp-wizard-main">
                <section class="bastionwp-card bastionwp-wizard-stages-card">
                    <div class="bastionwp-overview-section-head bastionwp-wizard-stages-head">
                        <div class="bastionwp-overview-section-title">
                            <span class="bastionwp-overview-card-icon dashicons dashicons-archive" aria-hidden="true"></span>
                            <div>
                                <h2><?php echo esc_html__('Etapas de configuração', 'bastionwp'); ?></h2>
                                <p><?php echo esc_html__('Verifique o status de cada área e siga as recomendações do assistente.', 'bastionwp'); ?></p>
                            </div>
                        </div>

                        <div class="bastionwp-wizard-static-filters">
                            <button type="button" class="button bastionwp-filter-chip is-active" data-wizard-filter="all">
                                <?php echo esc_html__('Todas', 'bastionwp'); ?>
                                <span><?php echo esc_html((string) count($wizard_steps)); ?></span>
                            </button>
                            <button type="button" class="button bastionwp-filter-chip" data-wizard-filter="required">
                                <?php echo esc_html__('Obrigatórias', 'bastionwp'); ?>
                                <span><?php echo esc_html((string) $wizard_required_total); ?></span>
                            </button>
                            <button type="button" class="button bastionwp-filter-chip" data-wizard-filter="recommended">
                                <?php echo esc_html__('Recomendadas', 'bastionwp'); ?>
                                <span><?php echo esc_html((string) $wizard_recommended_total); ?></span>
                            </button>
                        </div>
                    </div>

                    <div class="bastionwp-wizard-steps bastionwp-wizard-steps-modern">
                        <?php foreach ($wizard_steps as $step) : ?>
                            <?php
                            $step_id = (string) ($step['id'] ?? '');
                            $step_status = (string) ($step['status'] ?? 'warning');
                            $step_icon = $wizard_icon_map[$step_id] ?? 'dashicons-admin-generic';

                            $step_status_label = __('Atenção', 'bastionwp');

                            if ($step_status === 'ok') {
                                if ($step_id === 'foundation') {
                                    $step_status_label = __('Pronto', 'bastionwp');
                                } elseif ($step_id === 'wordfence') {
                                    $step_status_label = __('Ativo', 'bastionwp');
                                } else {
                                    $step_status_label = __('Configurado', 'bastionwp');
                                }
                            } elseif ($step_status === 'error') {
                                $step_status_label = __('Erro', 'bastionwp');
                            } elseif ($step_id === 'hardening') {
                                $step_status_label = __('Em desenvolvimento', 'bastionwp');
                            }
                            ?>
                            <div
                                class="bastionwp-wizard-step bastionwp-wizard-step-modern"
                                data-wizard-type="<?php echo !empty($step['required']) ? 'required' : 'recommended'; ?>"
                            >
                                <span class="bastionwp-overview-card-icon dashicons <?php echo esc_attr($step_icon); ?>" aria-hidden="true"></span>

                                <div class="bastionwp-wizard-step-content">
                                    <div class="bastionwp-wizard-step-title">
                                        <strong><?php echo esc_html($step['title']); ?></strong>
                                        <?php if (!empty($step['required'])) : ?>
                                            <span class="bastionwp-required-badge"><?php echo esc_html__('Obrigatório', 'bastionwp'); ?></span>
                                        <?php else : ?>
                                            <span class="bastionwp-optional-badge"><?php echo esc_html__('Recomendado', 'bastionwp'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small><?php echo esc_html($step['description']); ?></small>
                                </div>

                                <span class="bastionwp-wizard-step-status bastionwp-wizard-step-status-<?php echo esc_attr($step_status); ?>">
                                    <span class="bastionwp-status-dot" aria-hidden="true"></span>
                                    <?php echo esc_html($step_status_label); ?>
                                </span>

                                <strong class="bastionwp-wizard-value"><?php echo esc_html($step['value']); ?></strong>

                                <a class="button bastionwp-wizard-review-button" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=' . $step['tab'])); ?>">
                                    <?php echo esc_html__('Revisar', 'bastionwp'); ?>
                                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </main>

            <aside class="bastionwp-wizard-sidebar">
                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-title">
                        <span class="bastionwp-overview-card-icon dashicons dashicons-chart-bar" aria-hidden="true"></span>
                        <div>
                            <h2><?php echo esc_html__('Resumo do progresso', 'bastionwp'); ?></h2>
                            <p><?php echo esc_html__('Acompanhe o status das etapas do assistente.', 'bastionwp'); ?></p>
                        </div>
                    </div>

                    <div class="bastionwp-wizard-progress-summary">
                        <div class="bastionwp-wizard-progress-ring" style="--bwp-wizard-angle: <?php echo esc_attr((string) $wizard_circle_angle . 'deg'); ?>;">
                            <span><?php echo esc_html((string) $wizard_progress['percent'] . '%'); ?></span>
                        </div>

                        <ul>
                            <li><span class="bastionwp-progress-dot is-success"></span><strong><?php echo esc_html((string) $wizard_completed_total); ?></strong> <?php echo esc_html__('concluídas', 'bastionwp'); ?></li>
                            <li><span class="bastionwp-progress-dot is-primary"></span><strong>0</strong> <?php echo esc_html__('em andamento', 'bastionwp'); ?></li>
                            <li><span class="bastionwp-progress-dot is-warning"></span><strong><?php echo esc_html((string) $wizard_pending_total); ?></strong> <?php echo esc_html__('pendência', 'bastionwp'); ?></li>
                            <li><span class="bastionwp-progress-dot is-neutral"></span><strong>0</strong> <?php echo esc_html__('não iniciadas', 'bastionwp'); ?></li>
                        </ul>
                    </div>

                    <div class="bastionwp-wizard-summary-boxes">
                        <div>
                            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                            <strong>
                                <?php echo esc_html(
                                    sprintf(
                                        __('%1$d de %2$d', 'bastionwp'),
                                        (int) $wizard_progress['required_ok'],
                                        (int) $wizard_progress['required_total']
                                    )
                                ); ?>
                            </strong>
                            <small><?php echo esc_html__('obrigatórias concluídas', 'bastionwp'); ?></small>
                        </div>
                        <div>
                            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                            <strong><?php echo esc_html((string) $wizard_pending_total); ?></strong>
                            <small><?php echo esc_html__('precisa de atenção', 'bastionwp'); ?></small>
                        </div>
                    </div>
                </section>

                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-head">
                        <div class="bastionwp-overview-section-title">
                            <span class="bastionwp-overview-card-icon bastionwp-wizard-recommend-icon dashicons dashicons-star-filled" aria-hidden="true"></span>
                            <div>
                                <h2><?php echo esc_html__('Próxima recomendação', 'bastionwp'); ?></h2>
                            </div>
                        </div>

                        <?php if ($wizard_next_step !== null) : ?>
                            <span class="bastionwp-hero-status bastionwp-hero-status-warning">
                                <?php echo esc_html__('Atenção', 'bastionwp'); ?>
                            </span>
                        <?php else : ?>
                            <span class="bastionwp-hero-status bastionwp-hero-status-success">
                                <?php echo esc_html__('Tudo certo', 'bastionwp'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($wizard_next_step !== null) : ?>
                        <p>
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('Revise %s para concluir as etapas obrigatórias da configuração inicial.', 'bastionwp'),
                                    (string) $wizard_next_step['title']
                                )
                            );
                            ?>
                        </p>
                        <a class="button bastionwp-wizard-side-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=' . $wizard_next_step['tab'])); ?>">
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('Ir para %s', 'bastionwp'),
                                    (string) $wizard_next_step['title']
                                )
                            );
                            ?>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                    <?php else : ?>
                        <p><?php echo esc_html__('Todas as etapas obrigatórias estão prontas. Você pode concluir a configuração inicial.', 'bastionwp'); ?></p>
                    <?php endif; ?>
                </section>

                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-title">
                        <span class="bastionwp-overview-card-icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
                        <div>
                            <h2><?php echo esc_html__('Concluir configuração inicial', 'bastionwp'); ?></h2>
                        </div>
                    </div>

                    <?php if (!empty($wizard_state['completed'])) : ?>
                        <p><?php echo esc_html__('A configuração inicial já está marcada como concluída. Você pode reabrir o assistente para uma nova revisão.', 'bastionwp'); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="bastionwp_reopen_wizard">
                            <?php wp_nonce_field('bastionwp_reopen_wizard'); ?>
                            <?php submit_button(__('Reabrir assistente', 'bastionwp'), 'secondary', 'submit', false, ['class' => 'bastionwp-wizard-side-submit']); ?>
                        </form>
                    <?php else : ?>
                        <p>
                            <?php echo $wizard_progress['can_complete']
                                ? esc_html__('As etapas obrigatórias estão prontas. Registre a configuração como concluída.', 'bastionwp')
                                : esc_html__('Conclua primeiro as etapas obrigatórias marcadas com Atenção ou Erro para finalizar a configuração.', 'bastionwp'); ?>
                        </p>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="bastionwp_complete_wizard">
                            <?php wp_nonce_field('bastionwp_complete_wizard'); ?>
                            <?php submit_button(
                                __('Marcar como concluído', 'bastionwp'),
                                'primary',
                                'submit',
                                false,
                                $wizard_progress['can_complete']
                                    ? ['class' => 'bastionwp-wizard-side-submit']
                                    : ['disabled' => 'disabled', 'class' => 'bastionwp-wizard-side-submit']
                            ); ?>
                        </form>

                        <?php if (!$wizard_progress['can_complete']) : ?>
                            <div class="bastionwp-wizard-pending-note">
                                <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        _n(
                                            'Falta resolver %d item para concluir a configuração inicial.',
                                            'Faltam resolver %d itens para concluir a configuração inicial.',
                                            $wizard_pending_total,
                                            'bastionwp'
                                        ),
                                        $wizard_pending_total
                                    )
                                );
                                ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="bastionwp-card">
                    <div class="bastionwp-overview-section-title">
                        <span class="bastionwp-overview-card-icon dashicons dashicons-admin-links" aria-hidden="true"></span>
                        <div>
                            <span class="bastionwp-eyebrow"><?php echo esc_html__('Site Kit', 'bastionwp'); ?></span>
                            <h2><?php echo esc_html__('Acesso de usuários do cliente', 'bastionwp'); ?></h2>
                        </div>
                    </div>

                    <p>
                        <?php echo esc_html__('O BastionWP corrige o link do Site Kit para usar a rota administrativa válida. A visualização dos dados continua dependendo do Dashboard Sharing do próprio Site Kit.', 'bastionwp'); ?>
                    </p>

                    <a class="button bastionwp-wizard-side-action" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
                        <?php echo esc_html__('Revisar acesso ao Site Kit', 'bastionwp'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                    </a>
                </section>
            </aside>
        </div>
    <?php elseif ($tab === 'access') : ?>
        <div class="bastionwp-grid bastionwp-page-access">
            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Acesso técnico', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Developer Principal', 'bastionwp'); ?></h2>
                <p><?php echo esc_html__('O Developer possui acesso técnico ao BastionWP e fica protegido contra alterações feitas por outros usuários.', 'bastionwp'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_access">
                    <?php wp_nonce_field('bastionwp_save_access'); ?>

                    <label class="bastionwp-field-label" for="developer_user_id"><?php echo esc_html__('Usuário Developer', 'bastionwp'); ?></label>
                    <select id="developer_user_id" name="developer_user_id" class="regular-text">
                        <?php foreach ($administrators as $administrator) : ?>
                            <option value="<?php echo esc_attr((string) $administrator->ID); ?>"
                                <?php selected(in_array((int) $administrator->ID, $developer_ids, true)); ?>>
                                <?php echo esc_html($administrator->display_name . ' (' . $administrator->user_login . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="description"><?php echo esc_html__('Somente usuários Administradores podem ser definidos como Developer nesta versão.', 'bastionwp'); ?></p>

                    <?php submit_button(__('Salvar Developer', 'bastionwp')); ?>
                </form>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Cliente', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Gerenciador do Cliente', 'bastionwp'); ?></h2>
                <p><?php echo esc_html__('Converta um usuário existente para a role restrita do BastionWP. Ele poderá gerenciar conteúdo, mas não infraestrutura técnica.', 'bastionwp'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_access">
                    <?php wp_nonce_field('bastionwp_save_access'); ?>

                    <label class="bastionwp-field-label" for="client_user_id"><?php echo esc_html__('Usuário do cliente', 'bastionwp'); ?></label>
                    <select id="client_user_id" name="client_user_id" class="regular-text">
                        <option value="0"><?php echo esc_html__('Selecione um usuário', 'bastionwp'); ?></option>
                        <?php foreach ($client_candidates as $candidate) : ?>
                            <option value="<?php echo esc_attr((string) $candidate->ID); ?>">
                                <?php echo esc_html($candidate->display_name . ' (' . $candidate->user_login . ') — ' . implode(', ', $candidate->roles)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="description">
                        <?php echo esc_html__('A conversão substitui a role atual desse usuário por Gerenciador do Cliente.', 'bastionwp'); ?>
                    </p>

                    <?php submit_button(__('Converter para Gerenciador do Cliente', 'bastionwp'), 'secondary'); ?>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Acesso individual', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Menus e áreas permitidas por usuário', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Cada Gerenciador do Cliente possui sua própria configuração. Selecione o usuário e defina exatamente quais áreas adicionais aparecerão para ele.', 'bastionwp'); ?>
                </p>

                <?php if (empty($client_managers)) : ?>
                    <div class="bastionwp-callout">
                        <strong><?php echo esc_html__('Nenhum Gerenciador do Cliente encontrado.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Primeiro converta um usuário na área acima. Depois ele aparecerá aqui para receber uma política individual.', 'bastionwp'); ?>
                    </div>
                <?php else : ?>
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-user-selector">
                        <input type="hidden" name="page" value="bastionwp">
                        <input type="hidden" name="tab" value="access">

                        <label class="bastionwp-field-label" for="access_user">
                            <?php echo esc_html__('Usuário que será configurado', 'bastionwp'); ?>
                        </label>

                        <div class="bastionwp-inline-control">
                            <select id="access_user" name="access_user" class="regular-text">
                                <?php foreach ($client_managers as $managed_user) : ?>
                                    <option
                                        value="<?php echo esc_attr((string) $managed_user->ID); ?>"
                                        <?php selected($selected_access_user_id, (int) $managed_user->ID); ?>
                                    >
                                        <?php echo esc_html($managed_user->display_name . ' (' . $managed_user->user_login . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php submit_button(__('Carregar usuário', 'bastionwp'), 'secondary', 'submit', false); ?>
                        </div>
                    </form>

                    <?php if ($selected_access_user) : ?>
                        <div class="bastionwp-selected-user">
                            <strong><?php echo esc_html($selected_access_user->display_name); ?></strong>
                            <span>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __('Usuário #%d · %s', 'bastionwp'),
                                        (int) $selected_access_user->ID,
                                        $selected_access_user->user_login
                                    )
                                );
                                ?>
                            </span>
                        </div>

                        <div class="bastionwp-active-access">
                            <h3><?php echo esc_html__('Ativos para este usuário', 'bastionwp'); ?></h3>

                            <?php if ($client_access_mode !== 'custom') : ?>
                                <p class="description">
                                    <?php echo esc_html__('Nenhum menu adicional ativo. Este usuário está em Bloqueio total.', 'bastionwp'); ?>
                                </p>
                            <?php elseif (empty($client_active_groups)) : ?>
                                <div class="bastionwp-callout bastionwp-callout-warning">
                                    <strong><?php echo esc_html__('Nenhum menu adicional salvo para este usuário.', 'bastionwp'); ?></strong>
                                    <?php echo esc_html__('Marque os menus abaixo e salve novamente.', 'bastionwp'); ?>
                                </div>
                            <?php else : ?>
                                <div class="bastionwp-active-tags">
                                    <?php foreach ($client_active_groups as $active_group) : ?>
                                        <span class="bastionwp-active-tag">
                                            <?php echo esc_html((string) ($active_group['label'] ?? $active_group['top_slug'] ?? __('Menu', 'bastionwp'))); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="bastionwp_save_menu_access">
                            <input type="hidden" name="access_user_id" value="<?php echo esc_attr((string) $selected_access_user_id); ?>">
                            <?php wp_nonce_field('bastionwp_save_menu_access'); ?>

                            <div class="bastionwp-mode-grid">
                                <label class="bastionwp-mode-card">
                                    <input
                                        type="radio"
                                        name="client_access_mode"
                                        value="strict"
                                        <?php checked($client_access_mode, 'strict'); ?>
                                    >
                                    <strong><?php echo esc_html__('Bloqueio total', 'bastionwp'); ?></strong>
                                    <span><?php echo esc_html__('Mantém somente as áreas editoriais básicas desse usuário. Menus adicionais ficam bloqueados.', 'bastionwp'); ?></span>
                                </label>

                                <label class="bastionwp-mode-card">
                                    <input
                                        type="radio"
                                        name="client_access_mode"
                                        value="custom"
                                        <?php checked($client_access_mode, 'custom'); ?>
                                    >
                                    <strong><?php echo esc_html__('Personalizado para este usuário', 'bastionwp'); ?></strong>
                                    <span><?php echo esc_html__('Libera somente os menus adicionais marcados abaixo para o usuário selecionado.', 'bastionwp'); ?></span>
                                </label>
                            </div>

                            <div class="bastionwp-callout">
                                <strong><?php echo esc_html__('Sempre bloqueados:', 'bastionwp'); ?></strong>
                                <?php echo esc_html__('Plugins, Temas, Ferramentas, Configurações, Usuários, Atualizações e BastionWP.', 'bastionwp'); ?>
                            </div>

                            <h3><?php echo esc_html__('Menus adicionais detectados', 'bastionwp'); ?></h3>
                            <p class="description">
                                <?php echo esc_html__('Os menus abaixo foram detectados e registrados pelo BastionWP no painel do Developer. A seleção é salva individualmente no usuário e permanece marcada quando ele for carregado novamente.', 'bastionwp'); ?>
                            </p>

                            <?php $selected_ids = array_keys($client_allowed_groups); ?>

                            <?php if (empty($menu_catalog)) : ?>
                                <p><?php echo esc_html__('Nenhum menu adicional liberável foi detectado neste site.', 'bastionwp'); ?></p>
                            <?php else : ?>
                                <div class="bastionwp-menu-list">
                                    <?php foreach ($menu_catalog as $menu_id => $menu_item) : ?>
                                        <label class="bastionwp-menu-option">
                                            <input
                                                type="checkbox"
                                                name="allowed_menus[]"
                                                value="<?php echo esc_attr($menu_id); ?>"
                                                <?php checked(in_array($menu_id, $selected_ids, true)); ?>
                                            >
                                            <span>
                                                <strong><?php echo esc_html($menu_item['label']); ?></strong>
                                                <small>
                                                    <?php
                                                    $caps = !empty($menu_item['capabilities'])
                                                        ? implode(', ', $menu_item['capabilities'])
                                                        : __('nenhuma capability identificada', 'bastionwp');

                                                    echo esc_html(
                                                        sprintf(
                                                            __('%1$s · Capability: %2$s', 'bastionwp'),
                                                            $menu_item['top_slug'],
                                                            $caps
                                                        )
                                                    );
                                                    ?>
                                                </small>

                                                <?php if (!empty($menu_item['native_permissions_only'])) : ?>
                                                    <small class="bastionwp-native-permissions-note">
                                                        <?php echo esc_html__('Requer também autorização nativa do plugin. No Site Kit, compartilhe os serviços com a role Gerenciador do Cliente pelo Dashboard Sharing.', 'bastionwp'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="bastionwp-callout bastionwp-callout-warning">
                                <strong><?php echo esc_html__('Site Kit possui permissão própria.', 'bastionwp'); ?></strong>
                                <?php echo esc_html__('Selecionar Site Kit no BastionWP autoriza a área na política do Bastion, mas o Google exige que o acesso view-only seja compartilhado também dentro do Site Kit com a role Gerenciador do Cliente. O BastionWP não contorna essa proteção do Google.', 'bastionwp'); ?>
                            </div>

                            <?php submit_button(__('Salvar acessos deste usuário', 'bastionwp')); ?>
                        </form>

                        <p class="description">
                            <?php echo esc_html__('Plugins que salvam configurações exclusivamente por AJAX, REST API ou options.php ainda podem exigir compatibilidade específica. Nesses casos o menu deve aparecer e abrir, mas alguma ação interna pode continuar bloqueada até receber um adaptador seguro.', 'bastionwp'); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Permissões', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('O que o Gerenciador do Cliente pode fazer', 'bastionwp'); ?></h2>

                <div class="bastionwp-permissions">
                    <div>
                        <h3><?php echo esc_html__('Permitido', 'bastionwp'); ?></h3>
                        <ul>
                            <li><?php echo esc_html__('Criar e editar páginas', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Criar e editar posts', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Publicar e excluir conteúdo', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Enviar arquivos para a biblioteca de mídia', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Gerenciar categorias e comentários', 'bastionwp'); ?></li>
                        </ul>
                    </div>
                    <div>
                        <h3><?php echo esc_html__('Bloqueado', 'bastionwp'); ?></h3>
                        <ul>
                            <li><?php echo esc_html__('Instalar, ativar, editar ou excluir plugins', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Instalar, trocar, editar ou excluir temas', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Atualizar WordPress, plugins ou temas', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Criar, excluir ou promover usuários', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Alterar configurações técnicas do WordPress', 'bastionwp'); ?></li>
                            <li><?php echo esc_html__('Editar ou excluir a conta Developer', 'bastionwp'); ?></li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    <?php elseif ($tab === 'requests') : ?>
        <div class="bastionwp-grid bastionwp-page-requests">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Acesso temporário', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Solicitações de privilégios administrativos', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Gerenciadores do Cliente podem solicitar acesso administrativo temporário para configurar plugins. Você decide se aprova ou nega e escolhe a duração.', 'bastionwp'); ?>
                </p>
                <div class="bastionwp-callout">
                    <strong><?php echo esc_html__('Proteções que permanecem ativas:', 'bastionwp'); ?></strong>
                    <?php echo esc_html__('Plugins, Temas, Usuários, Atualizações, BastionWP, Wordfence e Code Snippets continuam protegidos. O acesso expira automaticamente.', 'bastionwp'); ?>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <?php if (empty($temp_admin_requests)) : ?>
                    <p><?php echo esc_html__('Nenhuma solicitação registrada.', 'bastionwp'); ?></p>
                <?php else : ?>
                    <div class="bastionwp-request-list">
                        <?php foreach ($temp_admin_requests as $request) : ?>
                            <?php
                            $request_user = get_userdata((int) ($request['user_id'] ?? 0));
                            $request_status = (string) ($request['status'] ?? '');
                            $status_labels = [
                                'pending'  => __('Pendente', 'bastionwp'),
                                'approved' => __('Aprovada', 'bastionwp'),
                                'denied'   => __('Negada', 'bastionwp'),
                                'expired'  => __('Expirada', 'bastionwp'),
                                'revoked'  => __('Encerrada', 'bastionwp'),
                            ];
                            ?>
                            <div class="bastionwp-request-card">
                                <div class="bastionwp-request-head">
                                    <div>
                                        <strong><?php echo esc_html($request_user ? $request_user->display_name : '#' . (int) ($request['user_id'] ?? 0)); ?></strong>
                                        <small><?php echo esc_html($request_user ? $request_user->user_login : ''); ?></small>
                                    </div>
                                    <span class="bastionwp-request-status bastionwp-request-<?php echo esc_attr($request_status); ?>">
                                        <?php echo esc_html($status_labels[$request_status] ?? $request_status); ?>
                                    </span>
                                </div>

                                <dl>
                                    <div>
                                        <dt><?php echo esc_html__('Solicitado em', 'bastionwp'); ?></dt>
                                        <dd><?php echo esc_html(wp_date('d/m/Y H:i', (int) ($request['requested_at'] ?? 0))); ?></dd>
                                    </div>
                                    <div>
                                        <dt><?php echo esc_html__('Motivo', 'bastionwp'); ?></dt>
                                        <dd><?php echo esc_html((string) (($request['reason'] ?? '') !== '' ? $request['reason'] : __('Não informado', 'bastionwp'))); ?></dd>
                                    </div>
                                    <?php if ($request_status === 'approved') : ?>
                                        <div>
                                            <dt><?php echo esc_html__('Expira em', 'bastionwp'); ?></dt>
                                            <dd><?php echo esc_html(wp_date('d/m/Y H:i', (int) ($request['expires_at'] ?? 0))); ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>

                                <?php if ($request_status === 'pending') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-request-actions">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>

                                        <select name="duration">
                                            <?php foreach ($temp_admin_durations as $duration_value => $duration_label) : ?>
                                                <option value="<?php echo esc_attr((string) $duration_value); ?>"><?php echo esc_html($duration_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="button button-primary" name="decision" value="approve">
                                            <?php echo esc_html__('Aprovar', 'bastionwp'); ?>
                                        </button>
                                        <button type="submit" class="button" name="decision" value="deny">
                                            <?php echo esc_html__('Negar', 'bastionwp'); ?>
                                        </button>
                                    </form>
                                <?php elseif ($request_status === 'approved') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>
                                        <button type="submit" class="button" name="decision" value="revoke">
                                            <?php echo esc_html__('Encerrar agora', 'bastionwp'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php elseif ($tab === 'hardening') : ?>
        <div class="bastionwp-grid bastionwp-page-hardening" id="bastionwp-hardening-root">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Segurança do ambiente', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Perfil de Hardening', 'bastionwp'); ?></h2>
                <p id="bastionwp-hardening-profile-description">
                    <?php echo esc_html($current_hardening_ui['description'] ?? __('Escolha o perfil de acordo com o estágio do site.', 'bastionwp')); ?>
                </p>

                <?php if ($hardening_profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) : ?>
                    <div class="bastionwp-callout bastionwp-callout-warning">
                        <strong><?php echo esc_html__('Hardening ainda não configurado.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Escolha um perfil abaixo. Para um site publicado, a recomendação padrão é Produção.', 'bastionwp'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_hardening">
                    <?php wp_nonce_field('bastionwp_save_hardening'); ?>

                    <div class="bastionwp-hardening-profiles">
                        <?php foreach ($hardening_profiles as $profile_key => $profile_data) : ?>
                            <label class="bastionwp-hardening-profile">
                                <input
                                    type="radio"
                                    name="hardening_profile"
                                    value="<?php echo esc_attr($profile_key); ?>"
                                    <?php checked($hardening_profile, $profile_key); ?>
                                >
                                <span>
                                    <strong><?php echo esc_html($profile_data['label']); ?></strong>
                                    <small><?php echo esc_html($profile_data['description']); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php submit_button(__('Aplicar perfil', 'bastionwp')); ?>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Ajustes adicionais', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Controles independentes do perfil', 'bastionwp'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_hardening_overrides">
                    <?php wp_nonce_field('bastionwp_save_hardening_overrides'); ?>

                    <div class="bastionwp-switch-list">
                        <label class="bastionwp-switch-row">
                            <span>
                                <strong><?php echo esc_html__('Desabilitar comentários', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Fecha comentários e trackbacks, remove suporte dos tipos de conteúdo e oculta o menu Comentários.', 'bastionwp'); ?></small>
                            </span>
                            <input type="checkbox" name="disable_comments" value="1" <?php checked(!empty($hardening_effective['disable_comments'])); ?>>
                        </label>

                        <label class="bastionwp-switch-row">
                            <span>
                                <strong><?php echo esc_html__('Ocultar menu Painel do Gerenciador do Cliente', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Ao entrar no /wp-admin, o cliente será direcionado para Páginas. Produção e Produção Bloqueada usam esta opção como padrão quando não há override manual.', 'bastionwp'); ?></small>
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
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Resumo', 'bastionwp'); ?></span>
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
                <ul class="bastionwp-summary-list" id="bastionwp-hardening-summary-list">
                    <?php foreach (($current_hardening_ui['summary'] ?? []) as $summary_item) : ?>
                        <li><?php echo esc_html($summary_item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Proteções efetivas', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Regras do perfil selecionado', 'bastionwp'); ?></h2>

                <div class="bastionwp-effective-rules">
                    <div class="bastionwp-effective-rule" data-hardening-rule="block_file_editors">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('Editor de arquivos', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_file_editors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_file_editors']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_file_editors']) ? esc_html__('Editor de arquivos de plugins e temas ficará indisponível.', 'bastionwp') : esc_html__('Editor de arquivos permanecerá disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_xmlrpc">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('XML-RPC', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['disable_xmlrpc']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['disable_xmlrpc']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['disable_xmlrpc']) ? esc_html__('Requisições XML-RPC serão recusadas.', 'bastionwp') : esc_html__('XML-RPC continuará disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_application_passwords">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('Application Passwords', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['disable_application_passwords']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['disable_application_passwords']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['disable_application_passwords']) ? esc_html__('Application Passwords não poderão ser usadas.', 'bastionwp') : esc_html__('Application Passwords continuarão disponíveis.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="hide_wordpress_version">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('Versão WordPress no HTML', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['hide_wordpress_version']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['hide_wordpress_version']) ? esc_html__('Ocultada', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['hide_wordpress_version']) ? esc_html__('A versão do WordPress será ocultada no HTML.', 'bastionwp') : esc_html__('A saída padrão do WordPress será mantida.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="generic_login_errors">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('Erros de login', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['generic_login_errors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['generic_login_errors']) ? esc_html__('Mensagem genérica', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['generic_login_errors']) ? esc_html__('Erros de login exibirão texto genérico.', 'bastionwp') : esc_html__('Erros padrão do WordPress serão mantidos.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_public_rest_users">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('REST / usuários públicos', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_public_rest_users']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_public_rest_users']) ? esc_html__('Bloqueado sem login', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_public_rest_users']) ? esc_html__('A listagem pública de usuários pela REST API será bloqueada.', 'bastionwp') : esc_html__('A REST API seguirá o comportamento padrão do WordPress.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_manual_infrastructure_changes">
                        <div class="bastionwp-effective-rule-head">
                            <strong><?php echo esc_html__('Alterações manuais de plugins/temas/core', 'bastionwp'); ?></strong>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas ao Developer', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? esc_html__('Alterações manuais de plugins, temas e core serão bloqueadas.', 'bastionwp') : esc_html__('Manutenção manual continuará disponível ao Developer.', 'bastionwp'); ?></small>
                    </div>
                </div>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Compatibilidade', 'bastionwp'); ?></span>
                <h2 id="bastionwp-hardening-compatibility-title">
                    <?php echo esc_html($current_hardening_ui['compatibilityTitle'] ?? __('Compatibilidade', 'bastionwp')); ?>
                </h2>
                <ul class="bastionwp-summary-list" id="bastionwp-hardening-compatibility-list">
                    <?php foreach (($current_hardening_ui['compatibility'] ?? []) as $compatibility_item) : ?>
                        <li><?php echo esc_html($compatibility_item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Diagnóstico', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Estado atual', 'bastionwp'); ?></h2>

                <div class="bastionwp-diagnostics">
                    <?php foreach ($hardening_diagnostics as $diagnostic) : ?>
                        <div class="bastionwp-diagnostic-row">
                            <span class="bastionwp-diagnostic-state bastionwp-diagnostic-<?php echo esc_attr($diagnostic['status']); ?>"></span>
                            <div>
                                <strong><?php echo esc_html($diagnostic['label']); ?></strong>
                                <?php if (!empty($diagnostic['help'])) : ?>
                                    <small><?php echo esc_html($diagnostic['help']); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="bastionwp-diagnostic-action">
                                <span><?php echo esc_html($diagnostic['value']); ?></span>
                                <?php if (($diagnostic['action'] ?? '') === 'fix_display_errors') : ?>
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

                <div class="bastionwp-callout">
                    <strong><?php echo esc_html__('Proteções que dependem do servidor', 'bastionwp'); ?></strong>
                    <?php echo esc_html__('Bloqueio de execução PHP em uploads, directory listing e regras específicas de Apache/LiteSpeed/Nginx ainda não são escritos automaticamente nesta versão. Serão tratados com detecção do servidor para evitar quebrar o site.', 'bastionwp'); ?>
                </div>
            </section>
        </div>
    <?php elseif ($tab === 'integrations') : ?>
        <div class="bastionwp-grid bastionwp-page-integrations">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Segurança especializada', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Wordfence', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('O BastionWP usa o Wordfence como ferramenta externa para firewall, scanner de malware, vulnerabilidades e segurança de login. O código do Wordfence não é incluído dentro do BastionWP.', 'bastionwp'); ?>
                </p>

                <div class="bastionwp-wordfence-status">
                    <div>
                        <span><?php echo esc_html__('Instalação', 'bastionwp'); ?></span>
                        <strong><?php echo $wordfence_status['installed'] ? esc_html__('Instalado', 'bastionwp') : esc_html__('Não instalado', 'bastionwp'); ?></strong>
                    </div>
                    <div>
                        <span><?php echo esc_html__('Ativação', 'bastionwp'); ?></span>
                        <strong><?php echo $wordfence_status['active'] ? esc_html__('Ativo', 'bastionwp') : esc_html__('Inativo', 'bastionwp'); ?></strong>
                    </div>
                    <div>
                        <span><?php echo esc_html__('Versão instalada', 'bastionwp'); ?></span>
                        <strong><?php echo $wordfence_status['version'] !== '' ? esc_html($wordfence_status['version']) : esc_html__('—', 'bastionwp'); ?></strong>
                    </div>
                    <div>
                        <span><?php echo esc_html__('Auto-update', 'bastionwp'); ?></span>
                        <strong><?php echo $wordfence_status['auto_update'] ? esc_html__('Ativado', 'bastionwp') : esc_html__('Desativado', 'bastionwp'); ?></strong>
                    </div>
                    <div>
                        <span><?php echo esc_html__('WAF carregado', 'bastionwp'); ?></span>
                        <strong><?php echo $wordfence_status['waf_loaded'] ? esc_html__('Detectado', 'bastionwp') : esc_html__('Não detectado nesta requisição', 'bastionwp'); ?></strong>
                    </div>
                </div>

                <?php if (!$wordfence_status['installed']) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_wordfence_install">
                        <?php wp_nonce_field('bastionwp_wordfence_install'); ?>
                        <?php submit_button(__('Instalar e ativar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?>
                    </form>
                    <p class="description">
                        <?php echo esc_html__('A instalação utiliza o pacote oficial disponibilizado pelo WordPress.org. Depois da ativação, conclua o assistente/licença diretamente no Wordfence.', 'bastionwp'); ?>
                    </p>
                <?php elseif (!$wordfence_status['active']) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_wordfence_activate">
                        <?php wp_nonce_field('bastionwp_wordfence_activate'); ?>
                        <?php submit_button(__('Ativar Wordfence', 'bastionwp'), 'primary', 'submit', false); ?>
                    </form>
                <?php else : ?>
                    <p>
                        <a class="button button-primary" href="<?php echo esc_url($this->wordfence->get_admin_url()); ?>">
                            <?php echo esc_html__('Abrir Wordfence', 'bastionwp'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </section>

            <?php if ($wordfence_status['installed']) : ?>
                <section class="bastionwp-card">
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Atualizações', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Wordfence sempre atualizado', 'bastionwp'); ?></h2>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_wordfence_auto_update">
                        <?php wp_nonce_field('bastionwp_wordfence_auto_update'); ?>

                        <label class="bastionwp-checkbox-line">
                            <input
                                type="checkbox"
                                name="wordfence_auto_update"
                                value="1"
                                <?php checked($wordfence_status['auto_update']); ?>
                            >
                            <span>
                                <strong><?php echo esc_html__('Atualizar Wordfence automaticamente', 'bastionwp'); ?></strong>
                                <small><?php echo esc_html__('Usa o mecanismo nativo de auto-update do WordPress.', 'bastionwp'); ?></small>
                            </span>
                        </label>

                        <?php submit_button(__('Salvar', 'bastionwp'), 'secondary', 'submit', false); ?>
                    </form>
                </section>

                <section class="bastionwp-card">
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('WAF', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Firewall do Wordfence', 'bastionwp'); ?></h2>

                    <?php if ($wordfence_status['waf_loaded']) : ?>
                        <div class="bastionwp-callout bastionwp-callout-success">
                            <?php echo esc_html__('A camada WAF do Wordfence foi detectada nesta requisição.', 'bastionwp'); ?>
                        </div>
                    <?php else : ?>
                        <div class="bastionwp-callout bastionwp-callout-warning">
                            <?php echo esc_html__('O WAF não foi detectado como carregado nesta requisição. Abra o Wordfence e revise a configuração do Firewall.', 'bastionwp'); ?>
                        </div>
                    <?php endif; ?>

                    <p class="description">
                        <?php echo esc_html__('O BastionWP não altera automaticamente arquivos de bootstrap do firewall. A otimização do WAF continua sendo feita pelo fluxo oficial do Wordfence.', 'bastionwp'); ?>
                    </p>
                </section>
            <?php endif; ?>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Checklist', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Configuração recomendada', 'bastionwp'); ?></h2>

                <div class="bastionwp-checklist">
                    <label><input type="checkbox" disabled <?php checked($wordfence_status['active']); ?>> <span><?php echo esc_html__('Wordfence instalado e ativo', 'bastionwp'); ?></span></label>
                    <label><input type="checkbox" disabled <?php checked($wordfence_status['auto_update']); ?>> <span><?php echo esc_html__('Atualizações automáticas ativas', 'bastionwp'); ?></span></label>
                    <label><input type="checkbox" disabled <?php checked($wordfence_status['waf_loaded']); ?>> <span><?php echo esc_html__('WAF detectado nesta requisição', 'bastionwp'); ?></span></label>
                    <label><input type="checkbox" disabled> <span><?php echo esc_html__('Executar e revisar o primeiro scan completo', 'bastionwp'); ?></span></label>
                    <label><input type="checkbox" disabled> <span><?php echo esc_html__('Revisar alertas por e-mail no Wordfence', 'bastionwp'); ?></span></label>
                    <label><input type="checkbox" disabled> <span><?php echo esc_html__('Ativar 2FA para contas técnicas quando aplicável', 'bastionwp'); ?></span></label>
                </div>

                <div class="bastionwp-callout">
                    <strong><?php echo esc_html__('Área exclusiva do Developer.', 'bastionwp'); ?></strong>
                    <?php echo esc_html__('Wordfence não aparece no seletor de menus liberáveis para Gerenciadores do Cliente e suas rotas administrativas são bloqueadas pelo Bastion Core.', 'bastionwp'); ?>
                </div>
            </section>
        </div>
    <?php elseif ($tab === 'diagnostics') : ?>
        <div class="bastionwp-grid bastionwp-page-diagnostics">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Visão consolidada', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Diagnóstico do BastionWP', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Este relatório reúne o estado do plugin, Bastion Core, ambiente WordPress, atualizações, hardening e Wordfence sem incluir senhas, tokens ou outras credenciais.', 'bastionwp'); ?>
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
                <h2><?php echo esc_html__('Estado atual', 'bastionwp'); ?></h2>

                <div class="bastionwp-diagnostic-list">
                    <?php foreach (($diagnostics_report['checks'] ?? []) as $check) : ?>
                        <div class="bastionwp-diagnostic-item">
                            <span class="bastionwp-diagnostic-state bastionwp-diagnostic-<?php echo esc_attr($check['status']); ?>"></span>
                            <div>
                                <strong><?php echo esc_html($check['label']); ?></strong>
                                <small><?php echo esc_html($check['description']); ?></small>
                            </div>
                            <span class="bastionwp-diagnostic-value"><?php echo esc_html($check['value']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    <?php elseif ($tab === 'logs') : ?>
        <div class="bastionwp-grid bastionwp-page-logs">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Auditoria', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Logs do BastionWP', 'bastionwp'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('Retenção automática: até %1$d dias ou %2$d eventos. Senhas, tokens, cookies, chaves de API e nonces não são armazenados.', 'bastionwp'),
                            BastionWP_Logger::retention_days(),
                            BastionWP_Logger::max_rows()
                        )
                    );
                    ?>
                </p>

                <div class="bastionwp-log-actions">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_export_logs">
                        <?php wp_nonce_field('bastionwp_export_logs'); ?>
                        <?php submit_button(__('Exportar CSV', 'bastionwp'), 'secondary', 'submit', false); ?>
                    </form>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Deseja realmente limpar o histórico de logs do BastionWP?', 'bastionwp')); ?>');">
                        <input type="hidden" name="action" value="bastionwp_clear_logs">
                        <?php wp_nonce_field('bastionwp_clear_logs'); ?>
                        <?php submit_button(__('Limpar logs', 'bastionwp'), 'delete', 'submit', false); ?>
                    </form>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-log-filters">
                    <input type="hidden" name="page" value="bastionwp">
                    <input type="hidden" name="tab" value="logs">

                    <label>
                        <span><?php echo esc_html__('Nível', 'bastionwp'); ?></span>
                        <select name="log_level">
                            <option value=""><?php echo esc_html__('Todos', 'bastionwp'); ?></option>
                            <?php foreach (['info', 'success', 'warning', 'error'] as $level) : ?>
                                <option value="<?php echo esc_attr($level); ?>" <?php selected($log_filters['level'], $level); ?>>
                                    <?php echo esc_html(ucfirst($level)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span><?php echo esc_html__('Evento', 'bastionwp'); ?></span>
                        <select name="log_event">
                            <option value=""><?php echo esc_html__('Todos', 'bastionwp'); ?></option>
                            <?php foreach ($log_event_types as $event_type) : ?>
                                <option value="<?php echo esc_attr($event_type); ?>" <?php selected($log_filters['event_type'], $event_type); ?>>
                                    <?php echo esc_html($event_type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <?php submit_button(__('Filtrar', 'bastionwp'), 'secondary', 'submit', false); ?>
                </form>

                <?php if (empty($log_rows)) : ?>
                    <p><?php echo esc_html__('Nenhum log encontrado para os filtros selecionados.', 'bastionwp'); ?></p>
                <?php else : ?>
                    <div class="bastionwp-log-table-wrap">
                        <table class="widefat striped bastionwp-log-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Data', 'bastionwp'); ?></th>
                                    <th><?php echo esc_html__('Nível', 'bastionwp'); ?></th>
                                    <th><?php echo esc_html__('Evento', 'bastionwp'); ?></th>
                                    <th><?php echo esc_html__('Usuário', 'bastionwp'); ?></th>
                                    <th><?php echo esc_html__('Mensagem', 'bastionwp'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($log_rows as $log_row) : ?>
                                    <?php
                                    $log_user = !empty($log_row['user_id'])
                                        ? get_userdata((int) $log_row['user_id'])
                                        : false;
                                    $log_user_label = $log_user
                                        ? $log_user->display_name . ' (#' . (int) $log_row['user_id'] . ')'
                                        : ((int) $log_row['user_id'] > 0 ? '#' . (int) $log_row['user_id'] : __('Sistema', 'bastionwp'));
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html(get_date_from_gmt((string) $log_row['event_time'], 'd/m/Y H:i:s')); ?></td>
                                        <td><span class="bastionwp-log-level bastionwp-log-<?php echo esc_attr($log_row['level']); ?>"><?php echo esc_html($log_row['level']); ?></span></td>
                                        <td><code><?php echo esc_html($log_row['event_type']); ?></code></td>
                                        <td><?php echo esc_html($log_user_label); ?></td>
                                        <td>
                                            <?php echo esc_html($log_row['message']); ?>
                                            <?php if (!empty($log_row['context'])) : ?>
                                                <details>
                                                    <summary><?php echo esc_html__('Detalhes', 'bastionwp'); ?></summary>
                                                    <pre><?php echo esc_html($log_row['context']); ?></pre>
                                                </details>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $log_total_pages = max(1, (int) ceil($log_total / $log_per_page));
                    if ($log_total_pages > 1) :
                        $pagination_base = add_query_arg(
                            [
                                'page'      => 'bastionwp',
                                'tab'       => 'logs',
                                'log_level' => $log_filters['level'],
                                'log_event' => $log_filters['event_type'],
                                'log_page'  => '%#%',
                            ],
                            admin_url('admin.php')
                        );
                        ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <?php
                                echo wp_kses_post(
                                    paginate_links(
                                        [
                                            'base'      => $pagination_base,
                                            'format'    => '',
                                            'current'   => $log_page,
                                            'total'     => $log_total_pages,
                                            'prev_text' => '‹',
                                            'next_text' => '›',
                                        ]
                                    )
                                );
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    <?php else : ?>
        <div class="bastionwp-grid bastionwp-page-updates">
            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Fonte de atualização', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('GitHub Releases', 'bastionwp'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_update_settings">
                    <?php wp_nonce_field('bastionwp_save_update_settings'); ?>

                    <label class="bastionwp-field-label" for="github_owner"><?php echo esc_html__('Proprietário do repositório', 'bastionwp'); ?></label>
                    <input
                        type="text"
                        id="github_owner"
                        name="github_owner"
                        class="regular-text"
                        value="<?php echo esc_attr((string) $update_settings['owner']); ?>"
                        placeholder="seu-usuario-github"
                    >

                    <label class="bastionwp-field-label" for="github_repo"><?php echo esc_html__('Nome do repositório', 'bastionwp'); ?></label>
                    <input
                        type="text"
                        id="github_repo"
                        name="github_repo"
                        class="regular-text"
                        value="<?php echo esc_attr((string) $update_settings['repo']); ?>"
                        placeholder="bastionwp"
                    >

                    <label class="bastionwp-field-label" for="update_channel"><?php echo esc_html__('Canal de atualização', 'bastionwp'); ?></label>
                    <select id="update_channel" name="update_channel">
                        <option value="stable" <?php selected($update_settings['channel'], 'stable'); ?>>
                            <?php echo esc_html__('Estável', 'bastionwp'); ?>
                        </option>
                        <option value="beta" <?php selected($update_settings['channel'], 'beta'); ?>>
                            <?php echo esc_html__('Beta / Pré-lançamentos', 'bastionwp'); ?>
                        </option>
                    </select>

                    <label class="bastionwp-checkbox-line">
                        <input type="checkbox" name="auto_update" value="1" <?php checked($auto_update_enabled); ?>>
                        <span>
                            <strong><?php echo esc_html__('Atualizar BastionWP automaticamente', 'bastionwp'); ?></strong>
                            <small><?php echo esc_html__('Usa o mecanismo nativo de atualização automática do WordPress.', 'bastionwp'); ?></small>
                        </span>
                    </label>

                    <?php submit_button(__('Salvar configurações', 'bastionwp')); ?>
                </form>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Status', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Atualizações', 'bastionwp'); ?></h2>

                <dl>
                    <div>
                        <dt><?php echo esc_html__('Versão instalada', 'bastionwp'); ?></dt>
                        <dd><?php echo esc_html(BASTIONWP_VERSION); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Atualização automática', 'bastionwp'); ?></dt>
                        <dd><?php echo $auto_update_enabled ? esc_html__('Ativada', 'bastionwp') : esc_html__('Desativada', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Canal', 'bastionwp'); ?></dt>
                        <dd><?php echo $update_settings['channel'] === 'beta' ? esc_html__('Beta', 'bastionwp') : esc_html__('Estável', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('GitHub', 'bastionwp'); ?></dt>
                        <dd><?php echo $update_status['configured'] ? esc_html__('Configurado', 'bastionwp') : esc_html__('Não configurado', 'bastionwp'); ?></dd>
                    </div>
                    <?php if (!empty($update_status['release']['version'])) : ?>
                        <div>
                            <dt><?php echo esc_html__('Última Release encontrada', 'bastionwp'); ?></dt>
                            <dd><?php echo esc_html((string) $update_status['release']['version']); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <?php if (!empty($update_status['error'])) : ?>
                    <div class="bastionwp-callout bastionwp-callout-error">
                        <?php echo esc_html($update_status['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_check_updates">
                    <?php wp_nonce_field('bastionwp_check_updates'); ?>
                    <?php submit_button(__('Verificar atualizações agora', 'bastionwp'), 'secondary', 'submit', false); ?>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Publicação', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Como o BastionWP encontra uma versão', 'bastionwp'); ?></h2>

                <div class="bastionwp-update-flow">
                    <code>GitHub Release</code>
                    <span>→</span>
                    <code>Tag vX.Y.Z</code>
                    <span>→</span>
                    <code>Asset bastionwp-X.Y.Z.zip</code>
                    <span>→</span>
                    <code>WordPress Update</code>
                </div>

                <p>
                    <?php echo esc_html__('O arquivo ZIP anexado à Release precisa conter a pasta bastionwp na raiz. Não use o ZIP automático “Source code” do GitHub como pacote de atualização.', 'bastionwp'); ?>
                </p>

                <p>
                    <?php echo esc_html__('Após uma atualização do plugin, o Bastion Core é sincronizado automaticamente no próximo carregamento do WordPress.', 'bastionwp'); ?>
                </p>
            </section>
        </div>
    <?php endif; ?>
</div>
