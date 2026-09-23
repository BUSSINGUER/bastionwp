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
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Versão 0.6.0', 'bastionwp'); ?></span>
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
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

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
        <div class="bastionwp-grid">
            <section class="bastionwp-card bastionwp-card-wide">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Segurança do ambiente', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Perfil de Hardening', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Escolha o perfil de acordo com o estágio do site. A alteração é reversível e não edita automaticamente wp-config.php nem arquivos do servidor.', 'bastionwp'); ?>
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
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Proteções efetivas', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Regras do perfil atual', 'bastionwp'); ?></h2>
                <dl>
                    <div>
                        <dt><?php echo esc_html__('Editor de arquivos', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['block_file_editors'] ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('XML-RPC', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['disable_xmlrpc'] ? esc_html__('Bloqueado', 'bastionwp') : esc_html__('Permitido', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Application Passwords', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['disable_application_passwords'] ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Versão WordPress no HTML', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['hide_wordpress_version'] ? esc_html__('Ocultada', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Erros de login', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['generic_login_errors'] ? esc_html__('Mensagem genérica', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('REST / usuários públicos', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['block_public_rest_users'] ? esc_html__('Bloqueado sem login', 'bastionwp') : esc_html__('Padrão WordPress', 'bastionwp'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo esc_html__('Alterações manuais de plugins/temas/core', 'bastionwp'); ?></dt>
                        <dd><?php echo $hardening_effective['block_manual_infrastructure_changes'] ? esc_html__('Bloqueadas', 'bastionwp') : esc_html__('Permitidas ao Developer', 'bastionwp'); ?></dd>
                    </div>
                </dl>
            </section>

            <section class="bastionwp-card">
                <span class="bastionwp-eyebrow"><?php echo esc_html__('Compatibilidade', 'bastionwp'); ?></span>
                <h2><?php echo esc_html__('Antes de usar Produção', 'bastionwp'); ?></h2>
                <p>
                    <?php echo esc_html__('Desabilitar XML-RPC ou Application Passwords pode afetar integrações externas que dependam desses recursos.', 'bastionwp'); ?>
                </p>
                <p>
                    <?php echo esc_html__('Produção Bloqueada impede alterações manuais de plugins, temas e WordPress. Para manutenção, volte temporariamente para Produção.', 'bastionwp'); ?>
                </p>
                <p>
                    <?php echo esc_html__('Atualizações automáticas em background continuam permitidas em Produção Bloqueada.', 'bastionwp'); ?>
                </p>
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
