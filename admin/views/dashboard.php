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
        'icon' => 'dashicons-admin-customizer',
        'title' => __('Assistente BastionWP', 'bastionwp'),
        'description' => __('Revise a configuração inicial do plugin e acompanhe o progresso das áreas essenciais.', 'bastionwp'),
        'status' => __('Configuração guiada', 'bastionwp'),
        'status_class' => 'info',
    ],
    'access' => [
        'icon' => 'dashicons-groups',
        'title' => __('Controle de acessos', 'bastionwp'),
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
        'title' => __('Perfil de Hardening', 'bastionwp'),
        'description' => __('Ajuste o nível de segurança do ambiente WordPress de acordo com cada fase do projeto.', 'bastionwp'),
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
        'description' => __('Centralize as verificações de WordPress, servidor, hardening e integrações em um só lugar.', 'bastionwp'),
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
            <a class="button bastionwp-button bastionwp-button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
                <span class="dashicons dashicons-chart-area" aria-hidden="true"></span>
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
            <span class="dashicons dashicons-admin-home" aria-hidden="true"></span><span><?php echo esc_html__('Visão Geral', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'wizard' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=wizard')); ?>">
            <span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span><span><?php echo esc_html__('Assistente', 'bastionwp'); ?></span>
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
            <span class="dashicons dashicons-chart-area" aria-hidden="true"></span><span><?php echo esc_html__('Status do Sistema', 'bastionwp'); ?></span>
        </a>
        <a class="nav-tab <?php echo $tab === 'system' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>">
            <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span><span><?php echo esc_html__('Sistema', 'bastionwp'); ?></span>
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
                    <?php echo esc_html__('Versões do WordPress/PHP e HTTPS exibidos conforme a verificação desta requisição.', 'bastionwp'); ?>
                </p>
            </section>

            <section class="bastionwp-card bastionwp-overview-summary-card">
                <div class="bastionwp-overview-card-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-groups" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Controle de Acesso', 'bastionwp'); ?></h2>
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
                    <li><?php echo esc_html__('Privilégios temporários de configuração', 'bastionwp'); ?></li>
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
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>">
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
                    <div><span class="dashicons dashicons-shield"></span><strong><?php echo esc_html(BastionWP_Hardening::get_profiles()[BastionWP_Hardening::get_profile()]['label'] ?? __('Não configurado', 'bastionwp')); ?></strong><small><?php echo esc_html__('perfil de Hardening', 'bastionwp'); ?></small></div>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_reopen_wizard">
                    <?php wp_nonce_field('bastionwp_reopen_wizard'); ?>
                    <?php submit_button(__('Reabrir Assistente em modo foco', 'bastionwp'), 'primary', 'submit', false); ?>
                </form>
            </section>
        </div>
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
                    <span class="bastionwp-focus-hero-icon dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Bem-vindo', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Configure uma base de segurança previsível para este WordPress', 'bastionwp'); ?></h2>
                    <p><?php echo esc_html__('O BastionWP organiza acessos do cliente, Hardening, integrações, auditoria e atualizações sem depender apenas de esconder menus. O Assistente revisa o ambiente antes de aplicar mudanças importantes.', 'bastionwp'); ?></p>
                    <ul class="bastionwp-focus-benefits">
                        <li><span class="dashicons dashicons-lock"></span><?php echo esc_html__('Protege áreas técnicas contra alterações acidentais.', 'bastionwp'); ?></li>
                        <li><span class="dashicons dashicons-groups"></span><?php echo esc_html__('Converte usuários do cliente para uma política controlada.', 'bastionwp'); ?></li>
                        <li><span class="dashicons dashicons-shield"></span><?php echo esc_html__('Aplica Hardening somente depois de verificar conflitos conhecidos.', 'bastionwp'); ?></li>
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
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-groups"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 3', 'bastionwp'); ?></span><h2><?php echo esc_html__('Quais usuários serão gerenciados pelo cliente?', 'bastionwp'); ?></h2><p><?php echo esc_html__('O Developer não aparece nesta lista. Marque os usuários que devem receber a role Gerenciador do Cliente e as políticas BastionWP.', 'bastionwp'); ?></p></div></div>
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
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-admin-network"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 4', 'bastionwp'); ?></span><h2><?php echo esc_html__('Permissões dos usuários convertidos', 'bastionwp'); ?></h2><p><?php echo esc_html__('Escolha Bloqueio total ou Personalizado. Menus que exigem privilégios administrativos técnicos só podem ser liberados por uma integração/adaptador validado.', 'bastionwp'); ?></p></div></div>
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
                                            <label class="bastionwp-menu-option bastionwp-menu-option-switch <?php echo $needs_adapter ? 'requires-adapter' : ''; ?>"><span class="bastionwp-menu-option-copy"><strong><?php echo esc_html($menu_item['label']); ?></strong><?php if ($needs_adapter) : ?><small><?php echo esc_html__('Compatibilidade BastionWP necessária para delegação segura.', 'bastionwp'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=integrations')); ?>"><?php echo esc_html__('Ver Integrações', 'bastionwp'); ?></a></small><?php else : ?><small><?php echo esc_html__('Delegação compatível com a política atual.', 'bastionwp'); ?></small><?php endif; ?></span><input class="bastionwp-menu-switch" type="checkbox" name="menus_<?php echo esc_attr((string)$uid); ?>[]" value="<?php echo esc_attr($menu_id); ?>" <?php checked($selected); ?> <?php disabled($needs_adapter); ?>></label>
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
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-shield"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 5', 'bastionwp'); ?></span><h2><?php echo esc_html__('Preflight de Hardening', 'bastionwp'); ?></h2><p><?php echo esc_html__('Antes de aplicar o perfil, o BastionWP verifica proteções já efetivas e informa a origem quando consegue identificá-la com segurança.', 'bastionwp'); ?></p></div></div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_wizard_hardening"><?php wp_nonce_field('bastionwp_wizard_hardening'); ?>
                        <div class="bastionwp-hardening-profiles bastionwp-hardening-profiles-row">
                            <?php foreach ($hardening_profiles as $profile_key=>$profile_data) : ?><label class="bastionwp-hardening-profile bastionwp-hardening-profile-modern"><input type="radio" name="hardening_profile" value="<?php echo esc_attr($profile_key); ?>" <?php checked($profile_key, BastionWP_Hardening::PROFILE_PRODUCTION); ?>><span class="bastionwp-hardening-profile-icon dashicons dashicons-shield"></span><span class="bastionwp-hardening-profile-copy"><strong><?php echo esc_html($profile_data['label']); ?></strong><small><?php echo esc_html($profile_data['description']); ?></small></span><span class="bastionwp-hardening-radio-visual"></span></label><?php endforeach; ?>
                        </div>
                        <div class="bastionwp-preflight-list">
                            <?php foreach ($hardening_preflight as $preflight) : $default_owner = !empty($preflight['protected']) ? 'external' : 'bastion'; ?>
                                <article class="bastionwp-preflight-item"><div><span class="bastionwp-diagnostic-state <?php echo !empty($preflight['protected']) ? 'bastionwp-diagnostic-ok' : 'bastionwp-diagnostic-warning'; ?>"></span><div><strong><?php echo esc_html($preflight['label']); ?></strong><small><?php echo esc_html($preflight['description']); ?></small><code><?php echo esc_html(sprintf(__('Origem detectada: %s', 'bastionwp'), $preflight['source'])); ?></code></div></div><div class="bastionwp-preflight-choices"><label class="<?php echo empty($preflight['protected']) ? 'is-disabled' : ''; ?>"><input type="radio" name="ownership[<?php echo esc_attr($preflight['key']); ?>]" value="external" <?php checked($default_owner,'external'); ?> <?php disabled(empty($preflight['protected'])); ?>><?php echo empty($preflight['protected']) ? esc_html__('Nenhuma proteção externa detectada', 'bastionwp') : esc_html__('Manter configuração existente', 'bastionwp'); ?></label><label><input type="radio" name="ownership[<?php echo esc_attr($preflight['key']); ?>]" value="bastion" <?php checked($default_owner,'bastion'); ?>><?php echo esc_html__('Gerenciar também pelo BastionWP', 'bastionwp'); ?></label></div></article>
                            <?php endforeach; ?>
                        </div>
                        <div class="bastionwp-callout"><strong><?php echo esc_html__('O BastionWP não edita silenciosamente configurações de outros plugins ou arquivos externos.', 'bastionwp'); ?></strong> <?php echo esc_html__('Quando você escolhe “Manter configuração existente”, o Bastion deixa aquela regra sob responsabilidade da origem detectada.', 'bastionwp'); ?></div>
                        <div class="bastionwp-focus-footer-actions"><?php submit_button(__('Aplicar Hardening e revisar', 'bastionwp'), 'primary', 'submit', false); ?></div>
                    </form>
                </section>

            <?php else : ?>
                <section class="bastionwp-focus-card">
                    <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-yes-alt"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Etapa 6', 'bastionwp'); ?></span><h2><?php echo esc_html__('Revisão final', 'bastionwp'); ?></h2><p><?php echo esc_html__('Confira o resultado antes de encerrar o modo foco.', 'bastionwp'); ?></p></div></div>
                    <div class="bastionwp-wizard-review-grid">
                        <div><span><?php echo esc_html__('Bastion Core', 'bastionwp'); ?></span><strong><?php echo esc_html($core_status['status']); ?></strong></div>
                        <div><span><?php echo esc_html__('Gerenciadores do Cliente', 'bastionwp'); ?></span><strong><?php echo esc_html((string) count(BastionWP_Users::get_client_managers())); ?></strong></div>
                        <div><span><?php echo esc_html__('Hardening', 'bastionwp'); ?></span><strong><?php echo esc_html($hardening_profiles[BastionWP_Hardening::get_profile()]['label'] ?? __('Não configurado', 'bastionwp')); ?></strong></div>
                        <div><span><?php echo esc_html__('Wordfence', 'bastionwp'); ?></span><strong><?php echo !empty($wordfence_status['active']) ? esc_html__('Ativo', 'bastionwp') : esc_html__('Revisar depois', 'bastionwp'); ?></strong></div>
                        <div><span><?php echo esc_html__('Diagnóstico', 'bastionwp'); ?></span><strong><?php echo esc_html(sprintf(__('%1$d erros · %2$d atenções', 'bastionwp'), (int)($diagnostics_report['summary']['error']??0), (int)($diagnostics_report['summary']['warning']??0))); ?></strong></div>
                    </div>
                    <?php if (empty($wizard_progress['can_complete'])) : ?><div class="bastionwp-callout bastionwp-callout-warning"><strong><?php echo esc_html__('Ainda existem itens obrigatórios para revisar.', 'bastionwp'); ?></strong> <?php echo esc_html__('Você pode sair e corrigir depois; o Assistente continuará disponível.', 'bastionwp'); ?></div><?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-focus-footer-actions"><input type="hidden" name="action" value="bastionwp_complete_wizard"><?php wp_nonce_field('bastionwp_complete_wizard'); ?><?php submit_button(__('Concluir configuração inicial', 'bastionwp'), 'primary', 'submit', false, empty($wizard_progress['can_complete']) ? ['disabled'=>'disabled'] : []); ?></form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>


<?php elseif ($tab === 'access') : ?>
    <?php $access_section = isset($_GET['access_section']) && sanitize_key(wp_unslash($_GET['access_section'])) === 'permissions' ? 'permissions' : 'users'; ?>
    <div class="bastionwp-access-accordion" data-bastionwp-access-accordion>
        <details class="bastionwp-access-section" <?php echo $access_section === 'users' ? 'open' : ''; ?>>
            <summary><span class="bastionwp-overview-card-icon dashicons dashicons-groups"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Usuários', 'bastionwp'); ?></span><strong><?php echo esc_html__('Ver usuários e aplicar proteção', 'bastionwp'); ?></strong><small><?php echo esc_html__('Lista todos os usuários do site, exceto o Developer.', 'bastionwp'); ?></small></div><span class="dashicons dashicons-arrow-down-alt2"></span></summary>
            <div class="bastionwp-access-section-body">
                <div class="bastionwp-access-flow-note"><strong><?php echo esc_html__('Fluxo', 'bastionwp'); ?></strong><span><?php echo esc_html__('Revise os usuários → converta quem precisa ser gerenciado → abra Permissões para definir Bloqueio total ou Personalizado.', 'bastionwp'); ?></span></div>
                <div class="bastionwp-access-users-list-lines">
                    <?php foreach ($site_users as $site_user) : $is_managed=BastionWP_Users::is_client_manager_user_id((int)$site_user->ID); $mode=$is_managed?BastionWP_Menu_Access::get_user_mode((int)$site_user->ID):''; $active=$is_managed?BastionWP_Menu_Access::get_user_active_groups((int)$site_user->ID):[]; ?>
                        <article class="bastionwp-access-user-line">
                            <div class="bastionwp-access-user-line-person"><?php echo get_avatar($site_user->ID,46); ?><div><strong><?php echo esc_html($site_user->display_name); ?></strong><small><?php echo esc_html($site_user->user_login . ' · ' . implode(', ', $site_user->roles)); ?></small></div></div>
                            <div class="bastionwp-access-user-line-status">
                                <?php if ($is_managed) : ?><span class="bastionwp-access-role-pill is-client"><span class="bastionwp-status-dot"></span><?php echo esc_html__('Gerenciador do Cliente', 'bastionwp'); ?></span><strong><?php echo $mode==='custom'?esc_html__('Personalizado','bastionwp'):esc_html__('Bloqueio total','bastionwp'); ?></strong><?php else : ?><span class="bastionwp-access-role-pill is-unmanaged"><?php echo esc_html__('Não gerenciado', 'bastionwp'); ?></span><strong><?php echo esc_html__('Política BastionWP não aplicada', 'bastionwp'); ?></strong><?php endif; ?>
                            </div>
                            <div class="bastionwp-access-user-line-menus">
                                <?php if ($is_managed && $mode==='custom' && !empty($active)) : ?><span><?php echo esc_html__('Menus habilitados', 'bastionwp'); ?></span><div class="bastionwp-active-tags"><?php foreach (array_slice($active,0,4) as $group) : ?><span class="bastionwp-active-tag"><span class="dashicons dashicons-yes-alt"></span><?php echo esc_html($group['label']??$group['top_slug']); ?></span><?php endforeach; ?></div><?php elseif ($is_managed) : ?><small><?php echo esc_html__('Nenhum menu adicional.', 'bastionwp'); ?></small><?php endif; ?>
                            </div>
                            <div class="bastionwp-access-user-line-action">
                                <?php if (!$is_managed) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_save_access"><input type="hidden" name="client_user_id" value="<?php echo esc_attr((string)$site_user->ID); ?>"><?php wp_nonce_field('bastionwp_save_access'); ?><button class="button button-primary" type="submit"><?php echo esc_html__('Aplicar proteção', 'bastionwp'); ?></button></form><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>

        <details class="bastionwp-access-section" <?php echo $access_section === 'permissions' ? 'open' : ''; ?>>
            <summary><span class="bastionwp-overview-card-icon dashicons dashicons-admin-network"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Permissões', 'bastionwp'); ?></span><strong><?php echo esc_html__('Selecionar política e menus', 'bastionwp'); ?></strong><small><?php echo esc_html__('Configure somente usuários que já foram convertidos em Gerenciador do Cliente.', 'bastionwp'); ?></small></div><span class="dashicons dashicons-arrow-down-alt2"></span></summary>
            <div class="bastionwp-access-section-body">
                <?php if (empty($client_managers)) : ?><div class="bastionwp-empty-state"><span class="dashicons dashicons-groups"></span><p><?php echo esc_html__('Nenhum Gerenciador do Cliente disponível. Converta um usuário na seção Usuários.', 'bastionwp'); ?></p></div>
                <?php else : ?>
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-access-permission-selector"><input type="hidden" name="page" value="bastionwp"><input type="hidden" name="tab" value="access"><input type="hidden" name="access_section" value="permissions"><div><label class="bastionwp-field-label" for="access_user"><?php echo esc_html__('Usuário', 'bastionwp'); ?></label><select id="access_user" name="access_user"><?php foreach ($client_managers as $managed_user) : ?><option value="<?php echo esc_attr((string)$managed_user->ID); ?>" <?php selected($selected_access_user_id,(int)$managed_user->ID); ?>><?php echo esc_html($managed_user->display_name . ' (' . $managed_user->user_login . ')'); ?></option><?php endforeach; ?></select><?php submit_button(__('Selecionar usuário', 'bastionwp'),'secondary','submit',false); ?></div><?php if ($selected_access_user) : ?><div class="bastionwp-access-selected-inline"><?php echo get_avatar($selected_access_user->ID,46); ?><div><strong><?php echo esc_html($selected_access_user->display_name); ?></strong><small><?php echo esc_html__('Gerenciador do Cliente', 'bastionwp'); ?></small></div></div><?php endif; ?></form>

                    <?php if ($selected_access_user) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-bastionwp-permission-form><input type="hidden" name="action" value="bastionwp_save_menu_access"><input type="hidden" name="access_user_id" value="<?php echo esc_attr((string)$selected_access_user_id); ?>"><?php wp_nonce_field('bastionwp_save_menu_access'); ?>
                        <h3><?php echo esc_html__('Tipo de permissão', 'bastionwp'); ?></h3>
                        <div class="bastionwp-mode-grid bastionwp-mode-grid-friendly"><label class="bastionwp-mode-card bastionwp-mode-card-strict"><input type="radio" name="client_access_mode" value="strict" <?php checked($client_access_mode,'strict'); ?>><span class="bastionwp-mode-icon dashicons dashicons-lock"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Bloqueio total', 'bastionwp'); ?></strong><span><?php echo esc_html__('Mantém somente as áreas editoriais básicas.', 'bastionwp'); ?></span></span></label><label class="bastionwp-mode-card bastionwp-mode-card-custom"><input type="radio" name="client_access_mode" value="custom" <?php checked($client_access_mode,'custom'); ?>><span class="bastionwp-mode-icon dashicons dashicons-admin-generic"></span><span class="bastionwp-mode-copy"><strong><?php echo esc_html__('Personalizado para este usuário', 'bastionwp'); ?></strong><span><?php echo esc_html__('Permite escolher menus compatíveis abaixo.', 'bastionwp'); ?></span></span></label></div>
                        <div class="bastionwp-access-menu-lockable <?php echo $client_access_mode!=='custom'?'is-locked':''; ?>" data-bastionwp-menu-lockable><div class="bastionwp-menu-lock-overlay"><span class="dashicons dashicons-lock"></span><strong><?php echo esc_html__('Menus adicionais bloqueados', 'bastionwp'); ?></strong><small><?php echo esc_html__('Selecione “Personalizado para este usuário” para liberar esta área.', 'bastionwp'); ?></small></div><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-admin-plugins"></span><div><h3><?php echo esc_html__('Menus adicionais detectados', 'bastionwp'); ?></h3><p><?php echo esc_html__('Ative apenas recursos compatíveis. Plugins que exigem privilégios técnicos precisam de integração específica para serem delegados com segurança.', 'bastionwp'); ?></p></div></div><div class="bastionwp-menu-list bastionwp-menu-list-switches"><?php $selected_ids=array_keys($client_allowed_groups); foreach ($menu_catalog as $menu_id=>$menu_item) : $needs_adapter=!empty($menu_item['requires_adapter']); ?><label class="bastionwp-menu-option bastionwp-menu-option-switch <?php echo $needs_adapter?'requires-adapter':''; ?>"><span class="bastionwp-menu-option-copy"><strong><?php echo esc_html($menu_item['label']); ?></strong><?php if ($needs_adapter) : ?><small><?php echo esc_html__('Compatibilidade BastionWP necessária.'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=integrations')); ?>"><?php echo esc_html__('Abrir Integrações', 'bastionwp'); ?></a></small><?php else : ?><small><?php echo !empty($menu_item['native_permissions_only'])?esc_html__('Usa permissões nativas do próprio plugin.','bastionwp'):esc_html__('Compatível com delegação segura.','bastionwp'); ?></small><?php endif; ?></span><input class="bastionwp-menu-switch" type="checkbox" name="allowed_menus[]" value="<?php echo esc_attr($menu_id); ?>" <?php checked(in_array($menu_id,$selected_ids,true)); ?> <?php disabled($needs_adapter); ?>></label><?php endforeach; ?></div></div>
                        <div class="bastionwp-access-savebar"><span class="description"><?php echo esc_html__('As mudanças serão aplicadas somente ao usuário selecionado.', 'bastionwp'); ?></span><?php submit_button(__('Salvar permissões', 'bastionwp'),'primary','submit',false); ?></div>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </details>
    </div>

<?php elseif ($tab === 'requests') : ?>
    <?php
    $request_counts = [
        'all'      => count($temp_admin_requests),
        'pending'  => 0,
        'approved' => 0,
        'expired'  => 0,
    ];
    $latest_request_at = 0;

    foreach ($temp_admin_requests as $request_item) {
        $request_status_tmp = (string) ($request_item['status'] ?? '');
        if (isset($request_counts[$request_status_tmp])) {
            $request_counts[$request_status_tmp]++;
        }
        $latest_request_at = max($latest_request_at, (int) ($request_item['requested_at'] ?? 0));
    }

    $request_status_filter = isset($_GET['request_status']) ? sanitize_key(wp_unslash($_GET['request_status'])) : 'all';
    if (!in_array($request_status_filter, ['all', 'pending', 'approved', 'expired', 'denied', 'revoked'], true)) {
        $request_status_filter = 'all';
    }
    $request_search = isset($_GET['request_search']) ? sanitize_text_field(wp_unslash($_GET['request_search'])) : '';
    $request_detail_id = isset($_GET['request_detail']) ? sanitize_text_field(wp_unslash($_GET['request_detail'])) : '';
    $request_sort = isset($_GET['request_sort']) ? sanitize_key(wp_unslash($_GET['request_sort'])) : 'recent';

    $filtered_requests = [];
    foreach ($temp_admin_requests as $request_item) {
        $req_user = get_userdata((int) ($request_item['user_id'] ?? 0));
        $req_text_raw = trim(($req_user ? $req_user->display_name . ' ' . $req_user->user_login : '') . ' ' . (string) ($request_item['reason'] ?? ''));
        $req_text = function_exists('mb_strtolower') ? mb_strtolower($req_text_raw) : strtolower($req_text_raw);
        $matches_status = $request_status_filter === 'all' || (string) ($request_item['status'] ?? '') === $request_status_filter;
        $search_normalized = function_exists('mb_strtolower') ? mb_strtolower($request_search) : strtolower($request_search);
        $matches_search = $request_search === '' || str_contains($req_text, $search_normalized);
        if ($matches_status && $matches_search) {
            $filtered_requests[] = $request_item;
        }
    }
    if ($request_sort === 'older') {
        usort($filtered_requests, static fn($a, $b) => ((int) ($a['requested_at'] ?? 0)) <=> ((int) ($b['requested_at'] ?? 0)));
    }
    $selected_request = $request_detail_id !== '' ? BastionWP_Temporary_Admin::get_request($request_detail_id) : null;
    $selected_request_logs = [];
    $selected_request_logs_truncated = false;
    if ($selected_request && !empty($selected_request['approved_at'])) {
        $time_start = (int) ($selected_request['approved_at'] ?? 0);
        $time_end = (int) ($selected_request['revoked_at'] ?? 0);
        if ($time_end <= 0) {
            $time_end = (int) ($selected_request['expires_at'] ?? 0);
        }
        if ($time_end <= 0) {
            $time_end = time();
        }

        $request_filters = [
            'request_id' => (string) ($selected_request['id'] ?? ''),
            'start_time' => $time_start,
            'end_time'   => $time_end,
        ];
        $request_total = BastionWP_Logger::count_logs($request_filters);
        $selected_request_logs = BastionWP_Logger::get_logs($request_filters, 1000, 0);

        if (empty($selected_request_logs)) {
            $legacy_filters = [
                'user_id'    => (int) ($selected_request['user_id'] ?? 0),
                'start_time' => $time_start,
                'end_time'   => $time_end,
            ];
            $request_total = BastionWP_Logger::count_logs($legacy_filters);
            $selected_request_logs = BastionWP_Logger::get_logs($legacy_filters, 1000, 0);
        }

        $selected_request_logs_truncated = $request_total > count($selected_request_logs);
    }
    ?>
    <div class="bastionwp-grid bastionwp-page-requests bastionwp-page-requests-modern">
        <section class="bastionwp-card bastionwp-card-wide bastionwp-request-stats-card">
            <div class="bastionwp-request-stats-grid">
                <article class="bastionwp-request-stat-item is-pending">
                    <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Pendentes', 'bastionwp'); ?></strong><small><?php echo esc_html__('Aguardando sua análise', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['pending']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-active">
                    <span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Ativas', 'bastionwp'); ?></strong><small><?php echo esc_html__('Acessos em andamento', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['approved']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-expired">
                    <span class="dashicons dashicons-backup" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Expiradas', 'bastionwp'); ?></strong><small><?php echo esc_html__('Acessos finalizados', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['expired']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-latest">
                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Última solicitação', 'bastionwp'); ?></strong><small><?php echo $latest_request_at ? esc_html(sprintf(__('Em %s', 'bastionwp'), wp_date('d/m/Y H:i', $latest_request_at))) : esc_html__('Nenhum registro', 'bastionwp'); ?></small></div>
                    <b><?php echo $latest_request_at ? esc_html(human_time_diff($latest_request_at, time()) . ' ' . __('atrás', 'bastionwp')) : '—'; ?></b>
                </article>
            </div>
        </section>

        <section class="bastionwp-card bastionwp-card-wide bastionwp-request-board">
            <div class="bastionwp-overview-section-head bastionwp-request-board-head">
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-list-view" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Solicitações de privilégios temporários', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('Gerenciadores do Cliente podem solicitar privilégios temporários de configuração. Você aprova, nega ou encerra e pode revisar os eventos que o próprio BastionWP registrou durante o período.', 'bastionwp'); ?></p>
                    </div>
                </div>
                <a class="button bastionwp-button bastionwp-button-secondary" href="#bastionwp-request-howto"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><?php echo esc_html__('Como funciona?', 'bastionwp'); ?></a>
            </div>

            <div class="bastionwp-callout bastionwp-callout-warning">
                <strong><?php echo esc_html__('Proteções que permanecem ativas:', 'bastionwp'); ?></strong>
                <?php echo esc_html__('Capabilities técnicas, rotas críticas, Code Snippets, Wordfence e infraestrutura continuam protegidos. O acesso não transforma o cliente em Administrador e expira automaticamente.', 'bastionwp'); ?>
            </div>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-request-toolbar">
                <input type="hidden" name="page" value="bastionwp">
                <input type="hidden" name="tab" value="requests">
                <input type="hidden" name="request_status" value="<?php echo esc_attr($request_status_filter); ?>">
                <div class="bastionwp-request-filter-pills">
                    <?php
                    $request_filters = [
                        'all' => ['label' => __('Todas', 'bastionwp'), 'count' => $request_counts['all']],
                        'pending' => ['label' => __('Pendentes', 'bastionwp'), 'count' => $request_counts['pending']],
                        'approved' => ['label' => __('Ativas', 'bastionwp'), 'count' => $request_counts['approved']],
                        'expired' => ['label' => __('Expiradas', 'bastionwp'), 'count' => $request_counts['expired']],
                        'denied' => ['label' => __('Negadas', 'bastionwp'), 'count' => count(array_filter($temp_admin_requests, static fn($item) => ($item['status'] ?? '') === 'denied'))],
                        'revoked' => ['label' => __('Encerradas', 'bastionwp'), 'count' => count(array_filter($temp_admin_requests, static fn($item) => ($item['status'] ?? '') === 'revoked'))],
                    ];
                    foreach ($request_filters as $filter_key => $filter_item) :
                    ?>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'bastionwp', 'tab' => 'requests', 'request_status' => $filter_key, 'request_search' => $request_search, 'request_sort' => $request_sort], admin_url('admin.php'))); ?>" class="bastionwp-request-pill <?php echo $request_status_filter === $filter_key ? 'is-active' : ''; ?>">
                            <span><?php echo esc_html($filter_item['label']); ?></span>
                            <b><?php echo esc_html((string) $filter_item['count']); ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="bastionwp-request-toolbar-search">
                    <label class="screen-reader-text" for="request_search"><?php echo esc_html__('Buscar solicitação', 'bastionwp'); ?></label>
                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                    <input type="text" id="request_search" name="request_search" value="<?php echo esc_attr($request_search); ?>" placeholder="<?php echo esc_attr__('Buscar solicitações por usuário ou motivo...', 'bastionwp'); ?>">
                </div>
                <select name="request_sort">
                    <option value="recent" <?php selected($request_sort, 'recent'); ?>><?php echo esc_html__('Mais recentes', 'bastionwp'); ?></option>
                    <option value="older" <?php selected($request_sort, 'older'); ?>><?php echo esc_html__('Mais antigas', 'bastionwp'); ?></option>
                </select>
                <button type="submit" class="button"><?php echo esc_html__('Aplicar filtros', 'bastionwp'); ?></button>
            </form>

            <?php if (empty($filtered_requests)) : ?>
                <div class="bastionwp-empty-state">
                    <span class="dashicons dashicons-archive" aria-hidden="true"></span>
                    <p><?php echo esc_html__('Nenhuma solicitação encontrada para os filtros selecionados.', 'bastionwp'); ?></p>
                </div>
            <?php else : ?>
                <div class="bastionwp-request-list bastionwp-request-list-modern">
                    <?php foreach ($filtered_requests as $request) : ?>
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
                        <article class="bastionwp-request-row-card">
                            <div class="bastionwp-request-user">
                                <span class="bastionwp-request-user-avatar"><?php echo esc_html(function_exists('mb_substr') ? mb_strtoupper(mb_substr($request_user ? $request_user->display_name : 'U', 0, 1)) : strtoupper(substr($request_user ? $request_user->display_name : 'U', 0, 1))); ?></span>
                                <div>
                                    <strong><?php echo esc_html($request_user ? $request_user->display_name : '#' . (int) ($request['user_id'] ?? 0)); ?></strong>
                                    <small><?php echo esc_html($request_user ? $request_user->user_login : ''); ?></small>
                                    <span class="bastionwp-request-status bastionwp-request-<?php echo esc_attr($request_status); ?>"><?php echo esc_html($status_labels[$request_status] ?? $request_status); ?></span>
                                </div>
                            </div>
                            <div class="bastionwp-request-info-col">
                                <strong><?php echo esc_html__('Informações da solicitação', 'bastionwp'); ?></strong>
                                <dl>
                                    <div><dt><?php echo esc_html__('Solicitado em', 'bastionwp'); ?></dt><dd><?php echo esc_html(wp_date('d/m/Y H:i', (int) ($request['requested_at'] ?? 0))); ?></dd></div>
                                    <div><dt><?php echo esc_html__('Motivo', 'bastionwp'); ?></dt><dd><?php echo esc_html((string) (($request['reason'] ?? '') !== '' ? $request['reason'] : __('Não informado', 'bastionwp'))); ?></dd></div>
                                </dl>
                            </div>
                            <div class="bastionwp-request-info-col">
                                <strong><?php echo esc_html__('Status do acesso', 'bastionwp'); ?></strong>
                                <p><?php echo esc_html($status_labels[$request_status] ?? $request_status); ?></p>
                                <small>
                                    <?php
                                    if ($request_status === 'approved') {
                                        echo esc_html(sprintf(__('Expira em %s', 'bastionwp'), wp_date('d/m/Y H:i', (int) ($request['expires_at'] ?? 0))));
                                    } elseif ($request_status === 'expired') {
                                        echo esc_html__('O acesso foi encerrado automaticamente após o período definido.', 'bastionwp');
                                    } elseif ($request_status === 'denied') {
                                        echo esc_html__('A solicitação foi negada pelo Developer.', 'bastionwp');
                                    } elseif ($request_status === 'revoked') {
                                        echo esc_html__('O acesso foi encerrado manualmente antes do prazo.', 'bastionwp');
                                    } else {
                                        echo esc_html__('Aguardando análise do Developer.', 'bastionwp');
                                    }
                                    ?>
                                </small>
                            </div>
                            <div class="bastionwp-request-actions-modern">
                                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'bastionwp', 'tab' => 'requests', 'request_status' => $request_status_filter, 'request_search' => $request_search, 'request_sort' => $request_sort, 'request_detail' => (string) $request['id']], admin_url('admin.php'))); ?>#bastionwp-request-details">
                                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php echo esc_html__('Ver detalhes', 'bastionwp'); ?>
                                </a>
                                <?php if ($request_status === 'pending') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-request-actions">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>
                                        <select name="duration"><?php foreach ($temp_admin_durations as $duration_value => $duration_label) : ?><option value="<?php echo esc_attr((string) $duration_value); ?>"><?php echo esc_html($duration_label); ?></option><?php endforeach; ?></select>
                                        <button type="submit" class="button button-primary" name="decision" value="approve"><?php echo esc_html__('Aprovar', 'bastionwp'); ?></button>
                                        <button type="submit" class="button" name="decision" value="deny"><?php echo esc_html__('Negar', 'bastionwp'); ?></button>
                                    </form>
                                <?php elseif ($request_status === 'approved') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-request-actions">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>
                                        <button type="submit" class="button button-primary" name="decision" value="revoke"><?php echo esc_html__('Encerrar agora', 'bastionwp'); ?></button>
                                    </form>
                                <?php elseif (in_array($request_status, ['expired', 'revoked', 'denied'], true)) : ?>
                                    <span class="button disabled"><?php echo esc_html__('Sem ações', 'bastionwp'); ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="bastionwp-card bastionwp-card-wide" id="bastionwp-request-details">
            <div class="bastionwp-overview-section-title">
                <span class="bastionwp-overview-card-icon dashicons dashicons-media-text" aria-hidden="true"></span>
                <div>
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Detalhes', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Eventos registrados pelo BastionWP durante o período', 'bastionwp'); ?></h2>
                    <p><?php echo esc_html__('O BastionWP apresenta somente eventos que ele próprio registrou durante a janela aprovada. Este histórico não monitora arquivos e não prova que nenhuma outra alteração ocorreu fora dos eventos auditados.', 'bastionwp'); ?></p>
                </div>
            </div>

            <?php if (!$selected_request) : ?>
                <p><?php echo esc_html__('Selecione uma solicitação em “Ver detalhes” para abrir o histórico relacionado.', 'bastionwp'); ?></p>
            <?php else : ?>
                <?php $selected_request_user = get_userdata((int) ($selected_request['user_id'] ?? 0)); ?>
                <div class="bastionwp-request-detail-meta">
                    <div><strong><?php echo esc_html__('Usuário', 'bastionwp'); ?></strong><span><?php echo esc_html($selected_request_user ? $selected_request_user->display_name : '#' . (int) ($selected_request['user_id'] ?? 0)); ?></span></div>
                    <div><strong><?php echo esc_html__('Período aprovado', 'bastionwp'); ?></strong><span><?php echo !empty($selected_request['approved_at']) ? esc_html(wp_date('d/m/Y H:i', (int) $selected_request['approved_at'])) : '—'; ?></span></div>
                    <div><strong><?php echo esc_html__('Encerramento', 'bastionwp'); ?></strong><span><?php echo !empty($selected_request['expires_at']) ? esc_html(wp_date('d/m/Y H:i', (int) $selected_request['expires_at'])) : '—'; ?></span></div>
                </div>
                <?php if (empty($selected_request_logs)) : ?>
                    <div class="bastionwp-callout">
                        <strong><?php echo esc_html__('Nenhum evento do BastionWP encontrado para este período.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Nenhum evento compatível foi registrado pelo BastionWP dentro do período. Isso não significa que nenhuma alteração tenha ocorrido em plugins, arquivos ou serviços externos.', 'bastionwp'); ?>
                    </div>
                <?php else : ?>
                    <?php if ($selected_request_logs_truncated) : ?>
                        <div class="bastionwp-callout bastionwp-callout-warning">
                            <?php echo esc_html__('Há mais eventos do que o limite exibido nesta tela. Consulte Sistema > Logs para o histórico completo e exportação.', 'bastionwp'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="bastionwp-request-detail-loglist">
                        <?php foreach ($selected_request_logs as $request_log_row) : ?>
                            <article class="bastionwp-request-log-item">
                                <div>
                                    <strong><?php echo esc_html(get_date_from_gmt((string) $request_log_row['event_time'], 'd/m/Y H:i:s')); ?></strong>
                                    <small><code><?php echo esc_html($request_log_row['event_type']); ?></code> · <?php echo esc_html($request_log_row['level']); ?></small>
                                </div>
                                <p><?php echo esc_html($request_log_row['message']); ?></p>
                                <?php if (!empty($request_log_row['context'])) : ?><details><summary><?php echo esc_html__('Contexto do log', 'bastionwp'); ?></summary><pre><?php echo esc_html($request_log_row['context']); ?></pre></details><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="bastionwp-card bastionwp-card-wide" id="bastionwp-request-howto">
            <div class="bastionwp-callout bastionwp-callout-info">
                <strong><?php echo esc_html__('Proteções técnicas mantidas durante o acesso temporário', 'bastionwp'); ?></strong>
                <?php echo esc_html__('Mesmo durante o acesso temporário de configuração, capabilities técnicas e rotas críticas permanecem bloqueadas. O acesso expira automaticamente e não desfaz alterações legítimas já realizadas durante a janela.', 'bastionwp'); ?>
            </div>
        </section>
    </div>

    <?php elseif ($tab === 'hardening') : ?>
        <?php
        $hardening_icon_map = [
            BastionWP_Hardening::PROFILE_DEVELOPMENT => 'dashicons-editor-code',
            BastionWP_Hardening::PROFILE_STAGING     => 'dashicons-admin-tools',
            BastionWP_Hardening::PROFILE_PRODUCTION  => 'dashicons-admin-site-alt3',
            BastionWP_Hardening::PROFILE_LOCKED      => 'dashicons-lock',
        ];
        ?>

        <div class="bastionwp-hardening-subnav" aria-label="<?php echo esc_attr__('Navegação interna do Hardening', 'bastionwp'); ?>">
            <a href="#bastionwp-hardening-profile-section" class="is-active">
                <span class="dashicons dashicons-shield" aria-hidden="true"></span>
                <?php echo esc_html__('Perfil de Hardening', 'bastionwp'); ?>
            </a>
            <a href="#bastionwp-hardening-additional-section">
                <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                <?php echo esc_html__('Ajustes adicionais', 'bastionwp'); ?>
            </a>
        </div>

        <div class="bastionwp-grid bastionwp-page-hardening" id="bastionwp-hardening-root">
            <section class="bastionwp-card bastionwp-card-wide bastionwp-hardening-profile-section" id="bastionwp-hardening-profile-section">
                <?php if ($hardening_profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) : ?>
                    <div class="bastionwp-callout bastionwp-callout-warning">
                        <strong><?php echo esc_html__('Hardening ainda não configurado.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Escolha um perfil abaixo. Para um site publicado, a recomendação padrão é Produção.', 'bastionwp'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bastionwp_save_hardening">
                    <?php wp_nonce_field('bastionwp_save_hardening'); ?>

                    <div class="bastionwp-hardening-profiles bastionwp-hardening-profiles-row">
                        <?php foreach ($hardening_profiles as $profile_key => $profile_data) : ?>
                            <label class="bastionwp-hardening-profile bastionwp-hardening-profile-modern">
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

                                    <?php if ($hardening_profile === $profile_key) : ?>
                                        <span class="bastionwp-hardening-current-pill"><?php echo esc_html__('Perfil atual', 'bastionwp'); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="bastionwp-hardening-radio-visual" aria-hidden="true"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="bastionwp-hardening-profile-actions">
                        <?php submit_button(__('Aplicar perfil', 'bastionwp'), 'primary', 'submit', false); ?>
                        <a class="button" href="#bastionwp-hardening-rules">
                            <span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
                            <?php echo esc_html__('Comparar perfil', 'bastionwp'); ?>
                        </a>
                    </div>
                </form>
            </section>

            <section class="bastionwp-card bastionwp-hardening-rules-card" id="bastionwp-hardening-rules">
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

                <div class="bastionwp-effective-rules">
                    <div class="bastionwp-effective-rule" data-hardening-rule="block_file_editors">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-media-code" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Editor de arquivos', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_file_editors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_file_editors']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_file_editors']) ? esc_html__('Editor de arquivos de plugins e temas ficará indisponível.', 'bastionwp') : esc_html__('Editor de arquivos permanecerá disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_xmlrpc">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-share" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('XML-RPC', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['disable_xmlrpc']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['disable_xmlrpc']) ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['disable_xmlrpc']) ? esc_html__('Os métodos XML-RPC do WordPress ficam indisponíveis; o endpoint ainda pode responder com uma mensagem de falha.', 'bastionwp') : esc_html__('XML-RPC continuará disponível.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="disable_application_passwords">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Application Passwords', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['disable_application_passwords']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['disable_application_passwords']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['disable_application_passwords']) ? esc_html__('Application Passwords não poderão ser usadas.', 'bastionwp') : esc_html__('Application Passwords continuarão disponíveis.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="hide_wordpress_version">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Versão WordPress no HTML', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['hide_wordpress_version']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['hide_wordpress_version']) ? esc_html__('Ocultada', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['hide_wordpress_version']) ? esc_html__('A versão do WordPress será ocultada no HTML.', 'bastionwp') : esc_html__('A saída padrão do WordPress será mantida.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="generic_login_errors">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Erros de login', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['generic_login_errors']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['generic_login_errors']) ? esc_html__('Mensagem genérica', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['generic_login_errors']) ? esc_html__('Erros de login exibirão texto genérico.', 'bastionwp') : esc_html__('Erros padrão do WordPress serão mantidos.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_public_rest_users">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-rest-api" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('REST / usuários públicos', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_public_rest_users']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_public_rest_users']) ? esc_html__('Bloqueado sem login', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_public_rest_users']) ? esc_html__('A listagem pública de usuários pela REST API será bloqueada.', 'bastionwp') : esc_html__('A REST API seguirá o comportamento padrão do WordPress.', 'bastionwp'); ?></small>
                    </div>

                    <div class="bastionwp-effective-rule" data-hardening-rule="block_manual_infrastructure_changes">
                        <div class="bastionwp-effective-rule-head">
                            <div class="bastionwp-rule-title">
                                <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
                                <strong><?php echo esc_html__('Alterações manuais de plugins/temas/core', 'bastionwp'); ?></strong>
                            </div>
                            <span class="bastionwp-rule-badge <?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed'; ?>">
                                <?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas ao Developer', 'bastionwp'); ?>
                            </span>
                        </div>
                        <small class="bastionwp-rule-helper"><?php echo !empty($hardening_effective['block_manual_infrastructure_changes']) ? esc_html__('Alterações manuais de plugins, temas e core serão bloqueadas.', 'bastionwp') : esc_html__('Manutenção manual continuará disponível ao Developer.', 'bastionwp'); ?></small>
                    </div>
                </div>
            </section>

            <section class="bastionwp-card bastionwp-hardening-summary-card">
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

            <section class="bastionwp-card bastionwp-card-wide bastionwp-hardening-additional-section" id="bastionwp-hardening-additional-section">
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-admin-generic" aria-hidden="true"></span>
                    <div>
                        <span class="bastionwp-eyebrow"><?php echo esc_html__('Ajustes adicionais', 'bastionwp'); ?></span>
                        <h2><?php echo esc_html__('Controles independentes do perfil', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('Ajustes que podem ser ativados ou desativados sem trocar o perfil de Hardening.', 'bastionwp'); ?></p>
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
                                <strong><?php echo esc_html__('Ocultar menu Painel do Gerenciador do Cliente', 'bastionwp'); ?></strong>
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
            </section>
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
            'access_compatibility' => __('Bloqueada para clientes', 'bastionwp'),
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
            'access_compatibility' => __('Compatibilidade específica ainda não disponível', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'yoastseo' => [
            'label' => 'Yoast SEO',
            'icon' => 'dashicons-chart-line',
            'status' => is_plugin_active('wordpress-seo/wp-seo.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('wordpress-seo/wp-seo.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade específica ainda não disponível', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'elementor' => [
            'label' => 'Elementor',
            'icon' => 'dashicons-screenoptions',
            'status' => is_plugin_active('elementor/elementor.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('elementor/elementor.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade específica ainda não disponível', 'bastionwp'),
            'access_class' => 'pending',
        ],
        'sitekit' => [
            'label' => 'Site Kit Google',
            'icon' => 'dashicons-chart-bar',
            'status' => is_plugin_active('google-site-kit/google-site-kit.php') ? __('Plugin ativo', 'bastionwp') : __('Não ativo', 'bastionwp'),
            'status_class' => is_plugin_active('google-site-kit/google-site-kit.php') ? 'success' : 'muted',
            'access_compatibility' => __('Compatibilidade BastionWP disponível', 'bastionwp'),
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
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Painel lateral', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Integrações disponíveis', 'bastionwp'); ?></h2>
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
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Delegação ao cliente', 'bastionwp'); ?></span><strong><?php echo esc_html__('Bloqueada por segurança', 'bastionwp'); ?></strong></div>
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
                        'description' => __('Use esta integração para revisar disponibilidade da REST API e compatibilidade com Application Passwords e XML-RPC bloqueado pelo hardening.', 'bastionwp'),
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
                    <div class="bastionwp-integration-kpi"><span><?php echo esc_html__('Delegação ao cliente', 'bastionwp'); ?></span><strong><?php echo esc_html($selected_integration['access_compatibility']); ?></strong></div>
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
                            <li><?php echo esc_html__('Usar o hardening para revisar compatibilidades quando necessário.', 'bastionwp'); ?></li>
                        </ul>
                    </section>
                    <section class="bastionwp-card">
                        <h3><?php echo esc_html__('Observações do BastionWP', 'bastionwp'); ?></h3>
                        <div class="bastionwp-callout"><strong><?php echo esc_html__('Status atual:', 'bastionwp'); ?></strong> <?php echo esc_html($integration_doc['status']); ?></div>
                        <p><?php echo esc_html__('O BastionWP também usa esta área como catálogo de compatibilidade para delegação segura. Site Kit possui integração própria; plugins que exigem capabilities administrativas continuam bloqueados até existir um adaptador validado.', 'bastionwp'); ?></p>
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
    $system_view = isset($_GET['system_view']) && sanitize_key(wp_unslash($_GET['system_view'])) === 'logs' ? 'logs' : 'overview';
    $recent_logs = BastionWP_Logger::get_logs([], 10, 0);
    $has_update = !empty($update_status['update_available']);
    $source_owner = (string) ($update_settings['owner'] ?? 'BUSSINGUER');
    $source_repo = (string) ($update_settings['repo'] ?? 'bastionwp');
    ?>
    <nav class="bastionwp-system-subnav"><a class="<?php echo $system_view==='overview'?'is-active':''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system')); ?>"><span class="dashicons dashicons-dashboard"></span><?php echo esc_html__('Visão geral', 'bastionwp'); ?></a><a class="<?php echo $system_view==='logs'?'is-active':''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=system&system_view=logs')); ?>"><span class="dashicons dashicons-media-text"></span><?php echo esc_html__('Logs', 'bastionwp'); ?></a><a href="#bastionwp-risk-zone"><span class="dashicons dashicons-warning"></span><?php echo esc_html__('Zona de risco', 'bastionwp'); ?></a></nav>

    <?php if ($system_view === 'logs') : ?>
        <div class="bastionwp-grid bastionwp-page-system-modern">
            <section class="bastionwp-card bastionwp-card-wide"><div class="bastionwp-overview-section-head"><div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon dashicons dashicons-media-text"></span><div><h2><?php echo esc_html__('Logs do BastionWP', 'bastionwp'); ?></h2><p><?php echo esc_html__('Histórico completo de eventos registrados pelo BastionWP.', 'bastionwp'); ?></p></div></div><div class="bastionwp-log-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_export_logs"><?php wp_nonce_field('bastionwp_export_logs'); ?><?php submit_button(__('Exportar CSV','bastionwp'),'secondary','submit',false); ?></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Deseja realmente limpar os logs?', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_clear_logs"><?php wp_nonce_field('bastionwp_clear_logs'); ?><?php submit_button(__('Limpar logs','bastionwp'),'delete','submit',false); ?></form></div></div>
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-log-filters"><input type="hidden" name="page" value="bastionwp"><input type="hidden" name="tab" value="system"><input type="hidden" name="system_view" value="logs"><label><span><?php echo esc_html__('Nível','bastionwp'); ?></span><select name="log_level"><option value=""><?php echo esc_html__('Todos','bastionwp'); ?></option><?php foreach(['info','success','warning','error'] as $level): ?><option value="<?php echo esc_attr($level); ?>" <?php selected($log_filters['level'],$level); ?>><?php echo esc_html(ucfirst($level)); ?></option><?php endforeach; ?></select></label><label><span><?php echo esc_html__('Evento','bastionwp'); ?></span><select name="log_event"><option value=""><?php echo esc_html__('Todos','bastionwp'); ?></option><?php foreach($log_event_types as $event_type): ?><option value="<?php echo esc_attr($event_type); ?>" <?php selected($log_filters['event_type'],$event_type); ?>><?php echo esc_html($event_type); ?></option><?php endforeach; ?></select></label><?php submit_button(__('Filtrar','bastionwp'),'secondary','submit',false); ?></form>
                <?php if (empty($log_rows)) : ?><div class="bastionwp-empty-state"><p><?php echo esc_html__('Nenhum log encontrado.', 'bastionwp'); ?></p></div><?php else : ?><div class="bastionwp-log-table-wrap"><table class="widefat striped bastionwp-log-table"><thead><tr><th><?php echo esc_html__('Data','bastionwp'); ?></th><th><?php echo esc_html__('Nível','bastionwp'); ?></th><th><?php echo esc_html__('Evento','bastionwp'); ?></th><th><?php echo esc_html__('Usuário','bastionwp'); ?></th><th><?php echo esc_html__('Mensagem','bastionwp'); ?></th></tr></thead><tbody><?php foreach($log_rows as $log_row): $log_user=!empty($log_row['user_id'])?get_userdata((int)$log_row['user_id']):false; ?><tr><td><?php echo esc_html(get_date_from_gmt((string)$log_row['event_time'],'d/m/Y H:i:s')); ?></td><td><span class="bastionwp-log-level bastionwp-log-<?php echo esc_attr($log_row['level']); ?>"><?php echo esc_html($log_row['level']); ?></span></td><td><code><?php echo esc_html($log_row['event_type']); ?></code></td><td><?php echo esc_html($log_user?$log_user->display_name:__('Sistema','bastionwp')); ?></td><td><?php echo esc_html($log_row['message']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php $log_total_pages=max(1,(int)ceil($log_total/$log_per_page)); if($log_total_pages>1): $pagination_base=add_query_arg(['page'=>'bastionwp','tab'=>'system','system_view'=>'logs','log_level'=>$log_filters['level'],'log_event'=>$log_filters['event_type'],'log_page'=>'%#%'],admin_url('admin.php')); ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(['base'=>$pagination_base,'format'=>'','current'=>$log_page,'total'=>$log_total_pages,'prev_text'=>'‹','next_text'=>'›'])); ?></div></div><?php endif; endif; ?>
            </section>
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

            <section class="bastionwp-card bastionwp-card-wide bastionwp-system-risk-zone" id="bastionwp-risk-zone">
                <div class="bastionwp-overview-section-title"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-warning"></span><div><span class="bastionwp-eyebrow"><?php echo esc_html__('Zona de risco','bastionwp'); ?></span><h2><?php echo esc_html__('Configurações sensíveis do sistema','bastionwp'); ?></h2><p><?php echo esc_html__('Estas opções ficam bloqueadas para evitar alterações acidentais e concentrar o handoff técnico em um único lugar.', 'bastionwp'); ?></p></div></div>
                <div class="bastionwp-risk-two-column">
                    <article class="bastionwp-risk-card" data-bastionwp-risk-zone><div class="bastionwp-risk-card-head"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-lock"></span><div><strong><?php echo esc_html__('Developer Principal','bastionwp'); ?></strong><small><?php echo esc_html__('Conta técnica que administra o BastionWP.','bastionwp'); ?></small></div></div><button type="button" class="button bastionwp-risk-unlock" data-bastionwp-risk-unlock><span class="dashicons dashicons-lock"></span><?php echo esc_html__('Desbloquear alteração','bastionwp'); ?></button><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-bastionwp-risk-form><input type="hidden" name="action" value="bastionwp_save_access"><?php wp_nonce_field('bastionwp_save_access'); ?><fieldset disabled data-bastionwp-risk-fieldset><label class="bastionwp-field-label" for="developer_user_id"><?php echo esc_html__('Usuário Developer','bastionwp'); ?></label><select id="developer_user_id" name="developer_user_id"><?php foreach($administrators as $administrator): ?><option value="<?php echo esc_attr((string)$administrator->ID); ?>" <?php selected(in_array((int)$administrator->ID,$developer_ids,true)); ?>><?php echo esc_html($administrator->display_name.' ('.$administrator->user_login.')'); ?></option><?php endforeach; ?></select><?php submit_button(__('Salvar Developer','bastionwp'),'primary'); ?></fieldset></form></article>
                    <article class="bastionwp-risk-card" data-bastionwp-source-lock data-owner="<?php echo esc_attr($source_owner); ?>" data-repo="<?php echo esc_attr($source_repo); ?>"><div class="bastionwp-risk-card-head"><span class="bastionwp-overview-card-icon bastionwp-risk-icon dashicons dashicons-admin-links"></span><div><strong><?php echo esc_html__('Fonte de atualização','bastionwp'); ?></strong><small><?php echo esc_html__('Provider usado para localizar Releases oficiais.','bastionwp'); ?></small></div></div><button type="button" class="button" data-bastionwp-source-unlock><span class="dashicons dashicons-lock"></span><?php echo esc_html__('Desbloquear configuração','bastionwp'); ?></button><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bastionwp_save_update_settings"><input type="hidden" name="source_unlocked" value="0" data-source-unlocked><?php wp_nonce_field('bastionwp_save_update_settings'); ?><fieldset disabled data-bastionwp-source-fieldset><label class="bastionwp-field-label" for="github_owner"><?php echo esc_html__('Proprietário','bastionwp'); ?></label><input type="text" id="github_owner" name="github_owner" value="********"><label class="bastionwp-field-label" for="github_repo"><?php echo esc_html__('Repositório','bastionwp'); ?></label><input type="text" id="github_repo" name="github_repo" value="********"><label class="bastionwp-field-label" for="update_channel"><?php echo esc_html__('Canal','bastionwp'); ?></label><select id="update_channel" name="update_channel"><option value="stable" <?php selected($update_settings['channel'],'stable'); ?>><?php echo esc_html__('Estável','bastionwp'); ?></option><option value="beta" <?php selected($update_settings['channel'],'beta'); ?>><?php echo esc_html__('Beta','bastionwp'); ?></option></select><label class="bastionwp-checkbox-line"><input type="checkbox" name="auto_update" value="1" <?php checked($auto_update_enabled); ?>><span><strong><?php echo esc_html__('Atualização automática','bastionwp'); ?></strong></span></label><?php submit_button(__('Salvar fonte','bastionwp'),'primary'); ?></fieldset></form></article>
                </div>
                <div class="bastionwp-plugin-exit-card"><div><span class="dashicons dashicons-exit"></span><div><strong><?php echo esc_html__('Handoff ou encerramento do BastionWP','bastionwp'); ?></strong><p><?php echo esc_html__('A desativação padrão na tela Plugins fica oculta e bloqueada. Use estas ações para desligar o Core de forma controlada ou remover o plugin durante uma troca de desenvolvedor.', 'bastionwp'); ?></p></div></div><div class="bastionwp-plugin-exit-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Desativar o BastionWP e remover o Bastion Core? As configurações e logs serão preservados.', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_system_deactivate"><?php wp_nonce_field('bastionwp_system_deactivate'); ?><button class="button" type="submit"><?php echo esc_html__('Desativar BastionWP','bastionwp'); ?></button></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Remover o BastionWP do site? Os Gerenciadores do Cliente serão convertidos para a role escolhida.', 'bastionwp')); ?>');"><input type="hidden" name="action" value="bastionwp_system_remove"><?php wp_nonce_field('bastionwp_system_remove'); ?><label><?php echo esc_html__('Role após remoção','bastionwp'); ?><select name="replacement_role"><option value="editor"><?php echo esc_html__('Editor','bastionwp'); ?></option><option value="administrator"><?php echo esc_html__('Administrador','bastionwp'); ?></option><option value="author"><?php echo esc_html__('Autor','bastionwp'); ?></option><option value="subscriber"><?php echo esc_html__('Assinante','bastionwp'); ?></option></select></label><label class="bastionwp-checkbox-line"><input type="checkbox" name="cleanup_data" value="1"><span><?php echo esc_html__('Remover também opções, logs e role do BastionWP','bastionwp'); ?></span></label><button class="button bastionwp-danger-button" type="submit"><?php echo esc_html__('Remover BastionWP do site','bastionwp'); ?></button></form></div></div>
            </section>
        </div>
    <?php endif; ?>

    <?php else : ?>
        <div class="bastionwp-grid"><section class="bastionwp-card bastionwp-card-wide"><p><?php echo esc_html__('Área não encontrada.', 'bastionwp'); ?></p></section></div>
    <?php endif; ?>
</div>
