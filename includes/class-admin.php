<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Admin
{
    private BastionWP_MU_Installer $mu_installer;
    private BastionWP_Users $users;
    private BastionWP_Update_Manager $update_manager;

    public function __construct(
        BastionWP_MU_Installer $mu_installer,
        BastionWP_Users $users,
        BastionWP_Update_Manager $update_manager
    ) {
        $this->mu_installer = $mu_installer;
        $this->users = $users;
        $this->update_manager = $update_manager;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_bastionwp_repair_core', [$this, 'handle_repair_core']);
        add_action('admin_post_bastionwp_save_access', [$this, 'handle_save_access']);
        add_action('admin_post_bastionwp_save_menu_access', [$this, 'handle_save_menu_access']);
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
    }

    public function render_page(): void
    {
        $this->assert_developer_access();

        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';

        if (!in_array($tab, ['overview', 'access', 'updates'], true)) {
            $tab = 'overview';
        }

        $core_status = $this->mu_installer->get_status();
        $install_error = get_option('bastionwp_core_install_error', '');
        $developer_ids = BastionWP_Users::get_developer_ids();
        $administrators = BastionWP_Users::get_administrators();
        $client_candidates = BastionWP_Users::get_client_candidates();
        $client_managers = BastionWP_Users::get_client_managers();
        $menu_catalog = BastionWP_Menu_Access::build_catalog();

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

        $update_settings = BastionWP_Update_Manager::get_settings();
        $update_status = $this->update_manager->get_status();
        $auto_update_enabled = BastionWP_Update_Manager::is_auto_update_enabled();

        require BASTIONWP_DIR . 'admin/views/dashboard.php';
    }

    public function handle_repair_core(): void
    {
        $this->assert_developer_access();
        check_admin_referer('bastionwp_repair_core');

        $result = $this->mu_installer->install_or_repair();

        $args = ['page' => 'bastionwp'];

        if (is_wp_error($result)) {
            $args['bastionwp_core'] = 'error';
            set_transient(
                'bastionwp_core_action_message_' . get_current_user_id(),
                $result->get_error_message(),
                60
            );
        } else {
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

        $this->set_access_message(
            'success',
            sprintf(
                __('Acessos de %s atualizados com sucesso.', 'bastionwp'),
                $name
            )
        );

        $this->redirect_access($user_id);
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
