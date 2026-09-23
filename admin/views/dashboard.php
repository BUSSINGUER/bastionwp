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

$is_ssl = is_ssl();
?>
<div class="wrap bastionwp-wrap">
    <div class="bastionwp-heading">
        <div>
            <h1><?php echo esc_html__('BastionWP', 'bastionwp'); ?></h1>
            <p><?php echo esc_html__('Controle e proteção para WordPress', 'bastionwp'); ?></p>
        </div>
        <span class="bastionwp-version"><?php echo esc_html('v' . BASTIONWP_VERSION); ?></span>
    </div>

    <nav class="nav-tab-wrapper bastionwp-tabs">
        <a class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=overview')); ?>">
            <?php echo esc_html__('Visão Geral', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'access' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>">
            <?php echo esc_html__('Acessos', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'hardening' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=hardening')); ?>">
            <?php echo esc_html__('Hardening', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'integrations' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=integrations')); ?>">
            <?php echo esc_html__('Integrações', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=diagnostics')); ?>">
            <?php echo esc_html__('Diagnóstico', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'logs' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=logs')); ?>">
            <?php echo esc_html__('Logs', 'bastionwp'); ?>
        </a>
        <a class="nav-tab <?php echo $tab === 'updates' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=updates')); ?>">
            <?php echo esc_html__('Atualizações', 'bastionwp'); ?>
        </a>
    </nav>

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

    <?php if ($tab === 'overview') : ?>
        <div class="bastionwp-grid">
            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Fundação', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Bastion Core', 'bastionwp'); ?></h2>
                <p class="bastionwp-status bastionwp-status-<?php echo esc_attr($status_key); ?>">
                    <?php echo esc_html($status_label); ?>
                </p>
                <p><?php echo esc_html($core_message_map[$status_key] ?? $core_status['message']); ?></p>
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

                <?php if ($status_key !== 'ok') : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bastionwp_repair_core">
                        <?php wp_nonce_field('bastionwp_repair_core'); ?>
                        <?php submit_button(__('Instalar / Reparar Core', 'bastionwp'), 'primary', 'submit', false); ?>
                    </form>
                <?php endif; ?>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Ambiente', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('WordPress', 'bastionwp'); ?></h2>
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
                        <dd><?php echo $is_ssl ? esc_html__('Ativo', 'bastionwp') : esc_html__('Não detectado', 'bastionwp'); ?></dd>
                    </div>
                </dl>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Controle de acesso', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Developer', 'bastionwp'); ?></h2>
                <?php if (!empty($developer_ids)) : ?>
                    <?php $developer = get_userdata((int) $developer_ids[0]); ?>
                    <p class="bastionwp-status bastionwp-status-ok"><?php echo esc_html__('Protegido', 'bastionwp'); ?></p>
                    <p>
                        <?php
                        echo $developer
                            ? esc_html($developer->display_name . ' (' . $developer->user_login . ')')
                            : esc_html__('Usuário configurado não encontrado.', 'bastionwp');
                        ?>
                    </p>
                <?php else : ?>
                    <p class="bastionwp-status bastionwp-status-outdated"><?php echo esc_html__('Não configurado', 'bastionwp'); ?></p>
                <?php endif; ?>
                <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=bastionwp&tab=access')); ?>"><?php echo esc_html__('Gerenciar acessos', 'bastionwp'); ?></a></p>
            </section>

            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Versão 0.8.1', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Controle de usuários e permissões', 'bastionwp'); ?></h2>
                <ul class="bastionwp-checklist">
                    <li><?php echo esc_html__('Developer Principal identificado por ID interno', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Role Gerenciador do Cliente criada', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Capabilities técnicas removidas do cliente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Rotas técnicas bloqueadas para Client Manager', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Conta Developer protegida contra edição/exclusão por não Developers', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Interface e descrição do plugin em português-BR', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Controle granular dos menus liberados para o cliente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Sistema de atualização via GitHub Releases', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Políticas de menus configuradas individualmente por usuário', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Correção do registro de menus de plugins com capabilities próprias', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Atualização automática usando o mecanismo nativo do WordPress', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Perfis de hardening por ambiente', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Produção Bloqueada com alterações manuais de infraestrutura restritas', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Integração operacional com Wordfence', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Logs de auditoria do BastionWP', 'bastionwp'); ?></li>
                    <li><?php echo esc_html__('Diagnóstico consolidado e exportável', 'bastionwp'); ?></li>
                </ul>
            </section>
        </div>
    <?php elseif ($tab === 'access') : ?>
        <div class="bastionwp-grid">
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
    <?php elseif ($tab === 'hardening') : ?>
        <div class="bastionwp-grid" id="bastionwp-hardening-root">
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
                            <strong><?php echo esc_html($diagnostic['label']); ?></strong>
                            <span><?php echo esc_html($diagnostic['value']); ?></span>
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
        <div class="bastionwp-grid">
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
        <div class="bastionwp-grid">
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
        <div class="bastionwp-grid">
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
        <div class="bastionwp-grid">
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
