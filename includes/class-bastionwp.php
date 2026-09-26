<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP
{
    private static ?BastionWP $instance = null;

    private BastionWP_Users $users;
    private BastionWP_Auth_Manager $auth_manager;
    private BastionWP_Temporary_Admin $temporary_admin;
    private BastionWP_Access $access;
    private BastionWP_Protected_Admin $protected_admin;
    private BastionWP_Plugin_Compatibility $plugin_compatibility;
    private BastionWP_Security_Controls $security_controls;
    private BastionWP_MU_Installer $mu_installer;
    private BastionWP_Update_Manager $update_manager;
    private BastionWP_Hardening $hardening;
    private BastionWP_Wordfence_Integration $wordfence;
    private BastionWP_Diagnostics $diagnostics;
    private BastionWP_Wizard $wizard;
    private BastionWP_Admin $admin;

    public static function instance(): BastionWP
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->load_dependencies();

        BastionWP_Logger::register_hooks();

        $this->users = new BastionWP_Users();
        $this->auth_manager = new BastionWP_Auth_Manager();
        $this->temporary_admin = new BastionWP_Temporary_Admin();
        $this->access = new BastionWP_Access($this->users);
        $this->protected_admin = new BastionWP_Protected_Admin();
        $this->plugin_compatibility = new BastionWP_Plugin_Compatibility();
        $this->security_controls = new BastionWP_Security_Controls();
        $this->mu_installer = new BastionWP_MU_Installer();
        $this->update_manager = new BastionWP_Update_Manager($this->mu_installer, $this->auth_manager);
        $this->hardening = new BastionWP_Hardening();
        $this->wordfence = new BastionWP_Wordfence_Integration();
        $this->diagnostics = new BastionWP_Diagnostics(
            $this->mu_installer,
            $this->hardening,
            $this->wordfence
        );
        $this->wizard = new BastionWP_Wizard(
            $this->mu_installer,
            $this->hardening,
            $this->wordfence,
            $this->diagnostics
        );
        $this->admin = new BastionWP_Admin(
            $this->mu_installer,
            $this->users,
            $this->update_manager,
            $this->hardening,
            $this->wordfence,
            $this->diagnostics,
            $this->wizard,
            $this->auth_manager
        );

        add_action('init', [$this, 'load_textdomain'], 0);
        add_action('init', [$this, 'run_version_migrations'], 2);
    }

    private function load_dependencies(): void
    {
        require_once BASTIONWP_DIR . 'includes/class-logger.php';
        require_once BASTIONWP_DIR . 'includes/class-activator.php';
        require_once BASTIONWP_DIR . 'includes/class-users.php';
        require_once BASTIONWP_DIR . 'includes/class-auth-manager.php';
        require_once BASTIONWP_DIR . 'includes/class-menu-access.php';
        require_once BASTIONWP_DIR . 'includes/class-temporary-admin.php';
        require_once BASTIONWP_DIR . 'includes/class-access.php';
        require_once BASTIONWP_DIR . 'includes/class-protected-admin.php';
        require_once BASTIONWP_DIR . 'includes/class-plugin-compatibility.php';
        require_once BASTIONWP_DIR . 'includes/class-security-controls.php';
        require_once BASTIONWP_DIR . 'integrations/class-backup.php';
        require_once BASTIONWP_DIR . 'includes/class-mu-installer.php';
        require_once BASTIONWP_DIR . 'integrations/class-github-provider.php';
        require_once BASTIONWP_DIR . 'includes/class-update-manager.php';
        require_once BASTIONWP_DIR . 'includes/class-hardening.php';
        require_once BASTIONWP_DIR . 'integrations/class-wordfence-integration.php';
        require_once BASTIONWP_DIR . 'includes/class-diagnostics.php';
        require_once BASTIONWP_DIR . 'includes/class-wizard.php';
        require_once BASTIONWP_DIR . 'includes/class-admin.php';
    }

    public static function activate(): void
    {
        require_once BASTIONWP_DIR . 'includes/class-logger.php';
        require_once BASTIONWP_DIR . 'includes/class-users.php';
        require_once BASTIONWP_DIR . 'includes/class-activator.php';
        require_once BASTIONWP_DIR . 'integrations/class-backup.php';
        require_once BASTIONWP_DIR . 'includes/class-mu-installer.php';
        require_once BASTIONWP_DIR . 'integrations/class-github-provider.php';
        require_once BASTIONWP_DIR . 'includes/class-update-manager.php';

        BastionWP_Activator::activate();
        BastionWP_Logger::install_schema();
        BastionWP_Update_Manager::enable_auto_update_by_default();

        $installer = new BastionWP_MU_Installer();
        $result = $installer->install_or_repair();

        if (is_wp_error($result)) {
            update_option('bastionwp_core_install_error', $result->get_error_message(), false);
        } else {
            delete_option('bastionwp_core_install_error');
        }

        set_transient('bastionwp_activated', 1, 60);
    }

    public static function deactivate(): void
    {
        // Bastion Core permanece instalado de propósito.
        if (class_exists('BastionWP_Security_Controls')) {
            wp_clear_scheduled_hook(BastionWP_Security_Controls::CRON_HOOK);
        }
        if (class_exists('BastionWP_Logger')) {
            BastionWP_Logger::unschedule();
        }
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'bastionwp',
            false,
            dirname(plugin_basename(BASTIONWP_FILE)) . '/languages'
        );
    }

    public function run_version_migrations(): void
    {
        $stored = (string) get_option('bastionwp_version', '');
        $pending_core_sync = (bool) get_option('bastionwp_core_sync_pending', false);

        if ($stored === BASTIONWP_VERSION && !$pending_core_sync) {
            return;
        }

        BastionWP_Logger::maybe_install_schema();
        BastionWP_Users::register_client_manager_role();
        BastionWP_Users::sync_developer_capabilities();
        BastionWP_Users::sanitize_existing_client_managers();
        BastionWP_Menu_Access::migrate_legacy_configuration();
        BastionWP_Temporary_Admin::migrate_active_index();

        if (class_exists('BastionWP_Config_Backup')) {
            $backup_migration = BastionWP_Config_Backup::migrate_legacy_storage();
            if (is_wp_error($backup_migration)) {
                update_option('bastionwp_backup_storage_error', $backup_migration->get_error_message(), false);
                BastionWP_Logger::log(
                    'config_snapshot_storage_warning',
                    __('O armazenamento privado de snapshots precisa de atenção.', 'bastionwp'),
                    'warning',
                    ['error' => $backup_migration->get_error_message()]
                );
            } else {
                delete_option('bastionwp_backup_storage_error');
            }
        }

        $core_result = $this->mu_installer->install_or_repair();

        if (is_wp_error($core_result)) {
            update_option('bastionwp_core_install_error', $core_result->get_error_message(), false);
            update_option('bastionwp_core_sync_pending', 1, false);
            return;
        }

        delete_option('bastionwp_core_install_error');
        delete_option('bastionwp_core_sync_pending');
        update_option('bastionwp_version', BASTIONWP_VERSION, false);
        BastionWP_Logger::log(
            'bastionwp_version_migrated',
            sprintf(__('BastionWP atualizado para %s.', 'bastionwp'), BASTIONWP_VERSION),
            'success',
            ['previous_version' => $stored, 'new_version' => BASTIONWP_VERSION]
        );
    }
}
