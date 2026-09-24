<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Admin
{
    private BastionWP_MU_Installer $mu_installer;
    private BastionWP_Users $users;
    private BastionWP_Update_Manager $update_manager;
    private BastionWP_Hardening $hardening;
    private BastionWP_Wordfence_Integration $wordfence;
    private BastionWP_Diagnostics $diagnostics;
    private BastionWP_Wizard $wizard;

    public function __construct(
        BastionWP_MU_Installer $mu_installer,
        BastionWP_Users $users,
        BastionWP_Update_Manager $update_manager,
        BastionWP_Hardening $hardening,
        BastionWP_Wordfence_Integration $wordfence,
        BastionWP_Diagnostics $diagnostics,
        BastionWP_Wizard $wizard
    ) {
        $this->mu_installer = $mu_installer;
        $this->users = $users;
        $this->update_manager = $update_manager;
        $this->hardening = $hardening;
        $this->wordfence = $wordfence;
        $this->diagnostics = $diagnostics;
        $this->wizard = $wizard;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_menu', [$this, 'capture_menu_catalog'], 9998);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_bastionwp_repair_core', [$this, 'handle_repair_core']);
        add_action('admin_post_bastionwp_save_access', [$this, 'handle_save_access']);
        add_action('admin_post_bastionwp_save_menu_access', [$this, 'handle_save_menu_access']);
        add_action('admin_post_bastionwp_save_hardening', [$this, 'handle_save_hardening']);
        add_action('admin_post_bastionwp_wordfence_install', [$this, 'handle_wordfence_install']);
        add_action('admin_post_bastionwp_wordfence_activate', [$this, 'handle_wordfence_activate']);
        add_action('admin_post_bastionwp_wordfence_auto_update', [$this, 'handle_wordfence_auto_update']);
        add_action('admin_post_bastionwp_clear_logs', [$this, 'handle_clear_logs']);
        add_action('admin_post_bastionwp_export_logs', [$this, 'handle_export_logs']);
        add_action('admin_post_bastionwp_export_diagnostics', [$this, 'handle_export_diagnostics']);
        add_action('admin_post_bastionwp_complete_wizard', [$this, 'handle_complete_wizard']);
        add_action('admin_post_bastionwp_reopen_wizard', [$this, 'handle_reopen_wizard']);
        add_action('admin_post_bastionwp_temp_admin_decision', [$this, 'handle_temp_admin_decision']);
        add_action('admin_post_bastionwp_save_hardening_overrides', [$this, 'handle_save_hardening_overrides']);
        add_action('admin_post_bastionwp_fix_display_errors', [$this, 'handle_fix_display_errors']);
        add_action('admin_notices', [$this, 'activation_notice']);
    }

    public function register_menu(): void
    {
        if (!BastionWP_Users::is_developer() && !empty(BastionWP_Users::get_developer_ids())) {
            return;
        }

        add_menu_page(
            __('BastionWP', 'bastionwp'),
            __('BastionWP', 'bastionwp'),
            'manage_options',
            'bastionwp',
            [$this, 'render_page'],
            'dashicons-shield-alt',
            80
        );
    }

    public function capture_menu_catalog(): void
    {
        // O catálogo precisa ser capturado em uma requisição administrativa
        // normal, depois que os plugins registraram seus menus.
        // Não fazemos isso em admin-post.php.
        if (!BastionWP_Users::is_developer()) {
            return;
        }

        BastionWP_Menu_Access::refresh_catalog_snapshot();
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_bastionwp') {
            return;
        }

        wp_enqueue_style(
            'bastionwp-admin',
            BASTIONWP_URL . 'admin/css/admin.css',
            [],
            BASTIONWP_VERSION
        );

        wp_enqueue_style(
            'bastionwp-design-tokens',
            BASTIONWP_URL . 'assets/admin/css/tokens.css',
            ['bastionwp-admin'],
            BASTIONWP_VERSION
        );

        wp_enqueue_style(
            'bastionwp-design-system',
            BASTIONWP_URL . 'assets/admin/css/design-system.css',
            ['bastionwp-design-tokens'],
            BASTIONWP_VERSION
        );

        wp_enqueue_style(
            'bastionwp-design-pages',
            BASTIONWP_URL . 'assets/admin/css/pages.css',
            ['bastionwp-design-system'],
            BASTIONWP_VERSION
        );

        wp_enqueue_script(
            'bastionwp-admin-script',
            BASTIONWP_URL . 'admin/js/admin.js',
            [],
            BASTIONWP_VERSION,
            true
        );

        wp_enqueue_script(
            'bastionwp-design-system-script',
            BASTIONWP_URL . 'assets/admin/js/design-system.js',
            ['bastionwp-admin-script'],
            BASTIONWP_VERSION,
            true
        );

        wp_localize_script(
            'bastionwp-admin-script',
            'BastionWPHardeningData',
            [
                'currentProfile' => BastionWP_Hardening::get_profile(),
                'profiles'       => $this->get_hardening_ui_profiles(),
            ]
        );
    }

    public function render_page(): void
    {
        $this->assert_developer_access();

        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';

        if (!in_array($tab, ['overview', 'wizard', 'access', 'requests', 'hardening', 'integrations', 'diagnostics', 'system', 'logs', 'updates'], true)) {
            $tab = 'overview';
        }

        if (in_array($tab, ['logs', 'updates'], true)) {
            $tab = 'system';
        }

        $core_status = $this->mu_installer->get_status();
        $install_error = get_option('bastionwp_core_install_error', '');
        $developer_ids = BastionWP_Users::get_developer_ids();
        $administrators = BastionWP_Users::get_administrators();
        $client_candidates = BastionWP_Users::get_client_candidates();
        $client_managers = BastionWP_Users::get_client_managers();
        $menu_catalog = BastionWP_Menu_Access::get_catalog_snapshot();
        if (empty($menu_catalog)) {
            $menu_catalog = BastionWP_Menu_Access::refresh_catalog_snapshot();
        }

        $selected_access_user_id = isset($_GET['access_user'])
            ? absint(wp_unslash($_GET['access_user']))
            : 0;

        if (
            $selected_access_user_id <= 0
            || !BastionWP_Users::is_client_manager_user_id($selected_access_user_id)
        ) {
            $selected_access_user_id = !empty($client_managers)
                ? (int) $client_managers[0]->ID
                : 0;
        }

        $selected_access_user = $selected_access_user_id > 0
            ? get_userdata($selected_access_user_id)
            : false;

        $client_access_mode = $selected_access_user_id > 0
            ? BastionWP_Menu_Access::get_user_mode($selected_access_user_id)
            : BastionWP_Menu_Access::MODE_STRICT;

        $client_allowed_groups = $selected_access_user_id > 0
            ? BastionWP_Menu_Access::get_user_allowed_groups($selected_access_user_id)
            : [];

        $client_active_groups = $selected_access_user_id > 0
            ? BastionWP_Menu_Access::get_user_active_groups($selected_access_user_id)
            : [];

        $temp_admin_requests = BastionWP_Temporary_Admin::get_all();
        $temp_admin_durations = BastionWP_Temporary_Admin::durations();

        $hardening_profiles = BastionWP_Hardening::get_profiles();
        $hardening_profile = BastionWP_Hardening::get_profile();
        $hardening_effective = $this->hardening->get_effective_settings();
        $hardening_diagnostics = $this->hardening->get_diagnostics();
        $hardening_ui_profiles = $this->get_hardening_ui_profiles();
        $current_hardening_ui = $hardening_ui_profiles[$hardening_profile]
            ?? reset($hardening_ui_profiles);

        $wordfence_status = $this->wordfence->get_status();

        $update_settings = BastionWP_Update_Manager::get_settings();
        $update_status = $this->update_manager->get_status();
        $auto_update_enabled = BastionWP_Update_Manager::is_auto_update_enabled();

        $diagnostics_report = $this->diagnostics->get_report();
        $wizard_steps = $this->wizard->get_steps();
        $wizard_progress = $this->wizard->get_progress();
        $wizard_state = $this->wizard->get_state();

        $log_filters = [
            'level'      => isset($_GET['log_level']) ? sanitize_key(wp_unslash($_GET['log_level'])) : '',
            'event_type' => isset($_GET['log_event']) ? sanitize_key(wp_unslash($_GET['log_event'])) : '',
            'user_id'    => isset($_GET['log_user']) ? absint(wp_unslash($_GET['log_user'])) : 0,
        ];
        $log_page = isset($_GET['log_page']) ? max(1, absint(wp_unslash($_GET['log_page']))) : 1;
        $log_per_page = 50;
        $log_total = BastionWP_Logger::count_logs($log_filters);
        $log_rows = BastionWP_Logger::get_logs(
            $log_filters,
            $log_per_page,
            ($log_page - 1) * $log_per_page
        );
        $log_event_types = BastionWP_Logger::get_event_types();

        require BASTIONWP_DIR . 'admin/views/dashboard.php';
    }


    private function get_hardening_ui_profiles(): array
    {
        $profiles = [];
        $labels = BastionWP_Hardening::get_profiles();

        foreach ($labels as $profile_key => $profile_data) {
            $profiles[$profile_key] = [
                'label'              => (string) $profile_data['label'],
                'description'        => (string) $profile_data['description'],
                'settings'           => $this->hardening->get_effective_settings($profile_key),
                'summary'            => $this->get_hardening_summary_items($profile_key),
                'compatibilityTitle' => $this->get_hardening_compatibility_title($profile_key),
                'compatibility'      => $this->get_hardening_compatibility_items($profile_key),
            ];
        }

        return $profiles;
    }

    private function get_hardening_summary_items(string $profile): array
    {
        switch ($profile) {
            case BastionWP_Hardening::PROFILE_DEVELOPMENT:
                return [
                    __('Mantém o ambiente menos restritivo para desenvolvimento ativo.', 'bastionwp'),
                    __('Não bloqueia XML-RPC nem Application Passwords.', 'bastionwp'),
                    __('Não aplica bloqueio manual de plugins, temas e core.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_STAGING:
                return [
                    __('Bloqueia o editor de arquivos de plugins e temas.', 'bastionwp'),
                    __('Oculta a versão do WordPress no HTML e usa erros de login genéricos.', 'bastionwp'),
                    __('Mantém XML-RPC e Application Passwords disponíveis para testes de integração.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_PRODUCTION:
                return [
                    __('Bloqueia XML-RPC e Application Passwords.', 'bastionwp'),
                    __('Reduz exposição pública e suprime exibição de erros quando possível.', 'bastionwp'),
                    __('Mantém a manutenção manual disponível ao Developer.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_LOCKED:
                return [
                    __('Aplica todas as proteções de Produção.', 'bastionwp'),
                    __('Bloqueia alterações manuais de plugins, temas e WordPress core.', 'bastionwp'),
                    __('Mantém apenas atualizações automáticas em background.', 'bastionwp'),
                ];
        }

        return [];
    }

    private function get_hardening_compatibility_title(string $profile): string
    {
        switch ($profile) {
            case BastionWP_Hardening::PROFILE_DEVELOPMENT:
                return __('Antes de usar Desenvolvimento', 'bastionwp');
            case BastionWP_Hardening::PROFILE_STAGING:
                return __('Antes de usar Staging', 'bastionwp');
            case BastionWP_Hardening::PROFILE_PRODUCTION:
                return __('Antes de usar Produção', 'bastionwp');
            case BastionWP_Hardening::PROFILE_LOCKED:
                return __('Antes de usar Produção Bloqueada', 'bastionwp');
        }

        return __('Compatibilidade', 'bastionwp');
    }

    private function get_hardening_compatibility_items(string $profile): array
    {
        switch ($profile) {
            case BastionWP_Hardening::PROFILE_DEVELOPMENT:
                return [
                    __('Use somente enquanto o site estiver em desenvolvimento ativo.', 'bastionwp'),
                    __('Não é recomendado para site publicado, porque mantém maior superfície de exposição.', 'bastionwp'),
                    __('Troque para Produção antes da entrega final ao cliente.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_STAGING:
                return [
                    __('Indicado para homologação e testes antes de publicar.', 'bastionwp'),
                    __('XML-RPC e Application Passwords continuam ativos para validar integrações.', 'bastionwp'),
                    __('Se tudo estiver validado, avance para Produção.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_PRODUCTION:
                return [
                    __('Desabilitar XML-RPC ou Application Passwords pode afetar integrações externas.', 'bastionwp'),
                    __('Confirme se o site depende desses recursos antes de aplicar este perfil.', 'bastionwp'),
                    __('A manutenção manual ainda permanece disponível para o Developer.', 'bastionwp'),
                ];

            case BastionWP_Hardening::PROFILE_LOCKED:
                return [
                    __('Bloqueia alterações manuais de plugins, temas e WordPress core.', 'bastionwp'),
                    __('Para manutenção manual, volte temporariamente para Produção.', 'bastionwp'),
                    __('Atualizações automáticas em background continuam permitidas.', 'bastionwp'),
                ];
        }

        return [];
    }

    public function handle_repair_core(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_repair_core');

        $result = $this->mu_installer->install_or_repair();

        $args = ['page' => 'bastionwp'];

        if (is_wp_error($result)) {
            BastionWP_Logger::log(
                'core_repair_failed',
                __('Falha ao reparar o Bastion Core.', 'bastionwp'),
                'error',
                ['error' => $result->get_error_message()]
            );
            $args['bastionwp_core'] = 'error';
            set_transient(
                'bastionwp_core_action_message_' . get_current_user_id(),
                $result->get_error_message(),
                60
            );
        } else {
            BastionWP_Logger::log(
                'core_repaired',
                __('Bastion Core reparado/sincronizado.', 'bastionwp'),
                'success'
            );
            delete_option('bastionwp_core_install_error');
            $args['bastionwp_core'] = 'success';
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public function handle_save_access(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_save_access');

        $developer_user_id = isset($_POST['developer_user_id'])
            ? absint(wp_unslash($_POST['developer_user_id']))
            : 0;

        $client_user_id = isset($_POST['client_user_id'])
            ? absint(wp_unslash($_POST['client_user_id']))
            : 0;

        if ($developer_user_id > 0 && !BastionWP_Users::set_primary_developer($developer_user_id)) {
            $this->set_access_message(
                'error',
                __('Não foi possível definir o Developer. O usuário selecionado precisa ser Administrador.', 'bastionwp')
            );
            $this->redirect_access();
        }

        if ($client_user_id > 0 && !BastionWP_Users::assign_client_manager($client_user_id)) {
            $this->set_access_message(
                'error',
                __('Não foi possível converter o usuário para Gerenciador do Cliente.', 'bastionwp')
            );
            $this->redirect_access();
        }

        BastionWP_Logger::log(
            'access_roles_updated',
            __('Configurações de usuários administrativos alteradas.', 'bastionwp'),
            'success',
            [
                'developer_user_id' => $developer_user_id,
                'client_user_id'    => $client_user_id,
            ]
        );

        $this->set_access_message(
            'success',
            __('Configurações de acesso salvas com sucesso.', 'bastionwp')
        );

        if (!BastionWP_Users::is_developer()) {
            wp_safe_redirect(admin_url());
            exit;
        }

        $this->redirect_access();
    }

    public function handle_save_menu_access(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_save_menu_access');

        $user_id = isset($_POST['access_user_id'])
            ? absint(wp_unslash($_POST['access_user_id']))
            : 0;

        if (
            $user_id <= 0
            || !BastionWP_Users::is_client_manager_user_id($user_id)
        ) {
            $this->set_access_message(
                'error',
                __('Selecione um Gerenciador do Cliente válido para configurar os acessos.', 'bastionwp')
            );
            $this->redirect_access();
        }

        $mode = isset($_POST['client_access_mode'])
            ? sanitize_key(wp_unslash($_POST['client_access_mode']))
            : BastionWP_Menu_Access::MODE_STRICT;

        $selected = isset($_POST['allowed_menus']) && is_array($_POST['allowed_menus'])
            ? array_map('sanitize_key', wp_unslash($_POST['allowed_menus']))
            : [];

        $saved = BastionWP_Menu_Access::save_user_configuration(
            $user_id,
            $mode,
            $selected
        );

        if (!$saved) {
            $this->set_access_message(
                'error',
                __('Não foi possível salvar a política individual desse usuário.', 'bastionwp')
            );
            $this->redirect_access($user_id);
        }

        $user = get_userdata($user_id);
        $name = $user ? $user->display_name : __('usuário', 'bastionwp');

        BastionWP_Logger::log(
            'user_menu_policy_updated',
            sprintf(__('Acessos de %s atualizados.', 'bastionwp'), $name),
            'success',
            [
                'target_user_id' => $user_id,
                'mode'           => $mode,
                'selected_count' => count($selected),
            ]
        );

        $this->set_access_message(
            'success',
            sprintf(
                __('Acessos de %s atualizados com sucesso.', 'bastionwp'),
                $name
            )
        );

        $this->redirect_access($user_id);
    }

    public function handle_save_hardening(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_save_hardening');

        $profile = isset($_POST['hardening_profile'])
            ? sanitize_key(wp_unslash($_POST['hardening_profile']))
            : BastionWP_Hardening::PROFILE_UNCONFIGURED;

        $previous_profile = BastionWP_Hardening::get_profile();
        $saved = BastionWP_Hardening::save_profile($profile);

        BastionWP_Logger::log(
            'hardening_profile_changed',
            $saved
                ? __('Perfil de hardening alterado.', 'bastionwp')
                : __('Falha ao alterar perfil de hardening.', 'bastionwp'),
            $saved ? 'success' : 'error',
            [
                'previous_profile' => $previous_profile,
                'new_profile'      => $profile,
            ]
        );

        set_transient(
            'bastionwp_hardening_message_' . get_current_user_id(),
            [
                'type' => $saved ? 'success' : 'error',
                'text' => $saved
                    ? __('Perfil de hardening aplicado. As novas regras valem a partir desta requisição e dos próximos acessos.', 'bastionwp')
                    : __('Não foi possível salvar o perfil de hardening.', 'bastionwp'),
            ],
            60
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'hardening'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_save_hardening_overrides(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_save_hardening_overrides');

        $overrides = [
            'disable_comments' => isset($_POST['disable_comments']),
            'hide_client_dashboard' => isset($_POST['hide_client_dashboard']),
            'force_suppress_display_errors' => isset($_POST['force_suppress_display_errors']),
        ];

        $saved = BastionWP_Hardening::save_overrides($overrides);

        BastionWP_Logger::log(
            'hardening_overrides_changed',
            __('Ajustes adicionais de hardening alterados.', 'bastionwp'),
            $saved ? 'success' : 'error',
            $overrides
        );

        set_transient(
            'bastionwp_hardening_message_' . get_current_user_id(),
            [
                'type' => $saved ? 'success' : 'error',
                'text' => $saved
                    ? __('Ajustes adicionais de hardening salvos.', 'bastionwp')
                    : __('Não foi possível salvar os ajustes adicionais.', 'bastionwp'),
            ],
            60
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'hardening'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_fix_display_errors(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_fix_display_errors');

        $saved = BastionWP_Hardening::enable_force_suppress_display_errors();

        BastionWP_Logger::log(
            'display_errors_suppression_enabled',
            __('Supressão de display_errors ativada pelo BastionWP.', 'bastionwp'),
            $saved ? 'success' : 'error'
        );

        set_transient(
            'bastionwp_hardening_message_' . get_current_user_id(),
            [
                'type' => $saved ? 'success' : 'error',
                'text' => $saved
                    ? __('A exibição de erros foi configurada para ser suprimida pelo BastionWP. Para correção definitiva, revise também WP_DEBUG_DISPLAY no wp-config.php.', 'bastionwp')
                    : __('Não foi possível aplicar a supressão de erros.', 'bastionwp'),
            ],
            60
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'hardening'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_temp_admin_decision(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_temp_admin_decision');

        $request_id = isset($_POST['request_id'])
            ? sanitize_text_field(wp_unslash($_POST['request_id']))
            : '';

        $decision = isset($_POST['decision'])
            ? sanitize_key(wp_unslash($_POST['decision']))
            : '';

        $success = false;
        $context = ['request_id' => $request_id, 'decision' => $decision];

        if ($decision === 'approve') {
            $duration = isset($_POST['duration']) ? absint(wp_unslash($_POST['duration'])) : 0;
            $context['duration'] = $duration;
            $success = BastionWP_Temporary_Admin::approve(
                $request_id,
                $duration,
                get_current_user_id()
            );
        } elseif ($decision === 'deny') {
            $success = BastionWP_Temporary_Admin::deny(
                $request_id,
                get_current_user_id()
            );
        } elseif ($decision === 'revoke') {
            $success = BastionWP_Temporary_Admin::revoke(
                $request_id,
                get_current_user_id()
            );
        }

        BastionWP_Logger::log(
            'temp_admin_decision',
            $success
                ? __('Solicitação administrativa temporária atualizada.', 'bastionwp')
                : __('Falha ao atualizar solicitação administrativa temporária.', 'bastionwp'),
            $success ? 'success' : 'error',
            $context
        );

        set_transient(
            'bastionwp_request_message_' . get_current_user_id(),
            [
                'type' => $success ? 'success' : 'error',
                'text' => $success
                    ? __('Solicitação atualizada com sucesso.', 'bastionwp')
                    : __('Não foi possível atualizar a solicitação. Ela pode já ter expirado ou sido processada.', 'bastionwp'),
            ],
            60
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'requests'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_wordfence_install(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_wordfence_install');

        $result = $this->wordfence->install_and_activate();

        BastionWP_Logger::log(
            'wordfence_install',
            is_wp_error($result)
                ? __('Falha ao instalar/ativar Wordfence.', 'bastionwp')
                : __('Wordfence instalado/ativado pelo BastionWP.', 'bastionwp'),
            is_wp_error($result) ? 'error' : 'success',
            is_wp_error($result) ? ['error' => $result->get_error_message()] : []
        );

        $this->set_integration_message_from_result(
            $result,
            __('Wordfence instalado e ativado. A atualização automática também foi ativada.', 'bastionwp')
        );

        $this->redirect_integrations();
    }

    public function handle_wordfence_activate(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_wordfence_activate');

        $result = $this->wordfence->activate();

        BastionWP_Logger::log(
            'wordfence_activate',
            is_wp_error($result)
                ? __('Falha ao ativar Wordfence.', 'bastionwp')
                : __('Wordfence ativado pelo BastionWP.', 'bastionwp'),
            is_wp_error($result) ? 'error' : 'success',
            is_wp_error($result) ? ['error' => $result->get_error_message()] : []
        );

        $this->set_integration_message_from_result(
            $result,
            __('Wordfence ativado. A atualização automática também foi ativada.', 'bastionwp')
        );

        $this->redirect_integrations();
    }

    public function handle_wordfence_auto_update(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_wordfence_auto_update');

        $enabled = isset($_POST['wordfence_auto_update']);
        $this->wordfence->set_auto_update_enabled($enabled);

        BastionWP_Logger::log(
            'wordfence_auto_update_changed',
            $enabled
                ? __('Auto-update do Wordfence ativado.', 'bastionwp')
                : __('Auto-update do Wordfence desativado.', 'bastionwp'),
            'success',
            ['enabled' => $enabled]
        );

        set_transient(
            'bastionwp_integration_message_' . get_current_user_id(),
            [
                'type' => 'success',
                'text' => $enabled
                    ? __('Atualização automática do Wordfence ativada.', 'bastionwp')
                    : __('Atualização automática do Wordfence desativada.', 'bastionwp'),
            ],
            60
        );

        $this->redirect_integrations();
    }

    private function set_integration_message_from_result($result, string $success_message): void
    {
        if (is_wp_error($result)) {
            $message = [
                'type' => 'error',
                'text' => $result->get_error_message(),
            ];
        } else {
            $message = [
                'type' => 'success',
                'text' => $success_message,
            ];
        }

        set_transient(
            'bastionwp_integration_message_' . get_current_user_id(),
            $message,
            60
        );
    }

    private function redirect_integrations(): void
    {
        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'integrations'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_complete_wizard(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_complete_wizard');

        $progress = $this->wizard->get_progress();

        if (empty($progress['can_complete'])) {
            set_transient(
                'bastionwp_wizard_message_' . get_current_user_id(),
                [
                    'type' => 'error',
                    'text' => __('Ainda existem etapas obrigatórias pendentes. Revise os itens marcados com Atenção ou Erro.', 'bastionwp'),
                ],
                60
            );
        } else {
            $this->wizard->mark_completed(get_current_user_id());

            BastionWP_Logger::log(
                'wizard_completed',
                __('Assistente inicial do BastionWP concluído.', 'bastionwp'),
                'success'
            );

            set_transient(
                'bastionwp_wizard_message_' . get_current_user_id(),
                [
                    'type' => 'success',
                    'text' => __('Configuração inicial marcada como concluída.', 'bastionwp'),
                ],
                60
            );
        }

        $this->redirect_wizard();
    }

    public function handle_reopen_wizard(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_reopen_wizard');

        $this->wizard->reopen();

        BastionWP_Logger::log(
            'wizard_reopened',
            __('Assistente inicial do BastionWP reaberto.', 'bastionwp'),
            'info'
        );

        set_transient(
            'bastionwp_wizard_message_' . get_current_user_id(),
            [
                'type' => 'success',
                'text' => __('Assistente reaberto para revisão.', 'bastionwp'),
            ],
            60
        );

        $this->redirect_wizard();
    }

    private function redirect_wizard(): void
    {
        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'wizard'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_clear_logs(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_clear_logs');

        $cleared = BastionWP_Logger::clear();

        if ($cleared) {
            BastionWP_Logger::log(
                'logs_cleared',
                __('Histórico de logs limpo pelo Developer.', 'bastionwp'),
                'warning'
            );
        }

        set_transient(
            'bastionwp_logs_message_' . get_current_user_id(),
            [
                'type' => $cleared ? 'success' : 'error',
                'text' => $cleared
                    ? __('Logs limpos. Um novo registro desta limpeza foi criado.', 'bastionwp')
                    : __('Não foi possível limpar os logs.', 'bastionwp'),
            ],
            60
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'bastionwp', 'tab' => 'system'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handle_export_logs(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_export_logs');

        $rows = BastionWP_Logger::get_logs([], 200, 0);

        BastionWP_Logger::log(
            'logs_exported',
            __('Logs exportados em CSV.', 'bastionwp'),
            'info',
            ['exported_rows' => count($rows)]
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="bastionwp-logs-' .
            gmdate('Y-m-d-His') .
            '.csv"'
        );

        $output = fopen('php://output', 'w');

        if ($output === false) {
            wp_die(
                esc_html__('Não foi possível iniciar a exportação.', 'bastionwp'),
                esc_html__('Erro de exportação', 'bastionwp'),
                ['response' => 500]
            );
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv(
            $output,
            ['ID', 'Data UTC', 'Nível', 'Evento', 'Usuário', 'Mensagem', 'Contexto'],
            ';'
        );

        foreach ($rows as $row) {
            fputcsv(
                $output,
                [
                    (string) $row['id'],
                    (string) $row['event_time'],
                    (string) $row['level'],
                    (string) $row['event_type'],
                    (string) $row['user_id'],
                    (string) $row['message'],
                    (string) $row['context'],
                ],
                ';'
            );
        }

        fclose($output);
        exit;
    }

    public function handle_export_diagnostics(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_export_diagnostics');

        $report = $this->diagnostics->get_report();

        BastionWP_Logger::log(
            'diagnostics_exported',
            __('Relatório de diagnóstico exportado.', 'bastionwp'),
            'info'
        );

        nocache_headers();
        header('Content-Type: application/json; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="bastionwp-diagnostico-' .
            gmdate('Y-m-d-His') .
            '.json"'
        );

        echo wp_json_encode(
            $report,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    public function activation_notice(): void
    {
        if (!current_user_can('manage_options') || !get_transient('bastionwp_activated')) {
            return;
        }

        delete_transient('bastionwp_activated');

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    __('BastionWP %s ativado. Abra o painel BastionWP para revisar a instalação.', 'bastionwp'),
                    BASTIONWP_VERSION
                )
            )
        );
    }

    private function assert_developer_access(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('Você não possui permissão administrativa para acessar o BastionWP.', 'bastionwp'),
                esc_html__('Acesso negado', 'bastionwp'),
                ['response' => 403]
            );
        }

        $developers = BastionWP_Users::get_developer_ids();

        if (!empty($developers) && !BastionWP_Users::is_developer()) {
            wp_die(
                esc_html__('Somente um usuário Developer autorizado pode acessar o BastionWP.', 'bastionwp'),
                esc_html__('Acesso protegido pelo BastionWP', 'bastionwp'),
                ['response' => 403]
            );
        }
    }

    private function set_access_message(string $type, string $text): void
    {
        set_transient(
            'bastionwp_access_message_' . get_current_user_id(),
            ['type' => $type, 'text' => $text],
            60
        );
    }

    private function redirect_access(int $user_id = 0): void
    {
        $args = [
            'page' => 'bastionwp',
            'tab'  => 'access',
        ];

        if ($user_id > 0) {
            $args['access_user'] = $user_id;
        }

        wp_safe_redirect(
            add_query_arg(
                $args,
                admin_url('admin.php')
            )
        );
        exit;
    }
}
