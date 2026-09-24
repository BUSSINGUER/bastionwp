<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Activator
{
    public static function activate(): void
    {
        self::assert_environment();

        add_option('bastionwp_version', BASTIONWP_VERSION, '', false);

        add_option(
            'bastionwp_settings',
            [
                'setup_complete' => false,
                'profile'        => 'unconfigured',
            ],
            '',
            false
        );

        add_option('bastionwp_developers', [], '', false);
        add_option('bastionwp_client_access_mode', 'strict', '', false);
        add_option('bastionwp_client_allowed_menus', [], '', false);
        add_option('bastionwp_update_settings', ['owner' => '', 'repo' => 'bastionwp', 'channel' => 'stable'], '', false);

        BastionWP_Users::register_client_manager_role();
        BastionWP_Users::ensure_initial_developer();
        BastionWP_Users::sync_developer_capabilities();

        if (class_exists('BastionWP_Menu_Access')) {
            BastionWP_Menu_Access::migrate_legacy_configuration();
        }

        if (class_exists('BastionWP_Logger')) {
            BastionWP_Logger::install_schema();
        }
    }

    private static function assert_environment(): void
    {
        global $wp_version;

        if (is_multisite()) {
            deactivate_plugins(plugin_basename(BASTIONWP_FILE), true);

            wp_die(
                esc_html__('BastionWP 0.9.8 ainda é homologado somente para instalações WordPress single-site.', 'bastionwp'),
                esc_html__('Multisite não homologado', 'bastionwp'),
                ['back_link' => true]
            );
        }

        if (version_compare(PHP_VERSION, BASTIONWP_MIN_PHP, '<')) {
            deactivate_plugins(plugin_basename(BASTIONWP_FILE));

            wp_die(
                esc_html(
                    sprintf(
                        __('O BastionWP requer PHP %1$s ou superior. Versão atual: %2$s.', 'bastionwp'),
                        BASTIONWP_MIN_PHP,
                        PHP_VERSION
                    )
                ),
                esc_html__('Ativação do BastionWP bloqueada', 'bastionwp'),
                ['back_link' => true]
            );
        }

        if (version_compare((string) $wp_version, BASTIONWP_MIN_WP, '<')) {
            deactivate_plugins(plugin_basename(BASTIONWP_FILE));

            wp_die(
                esc_html(
                    sprintf(
                        __('O BastionWP requer WordPress %1$s ou superior. Versão atual: %2$s.', 'bastionwp'),
                        BASTIONWP_MIN_WP,
                        (string) $wp_version
                    )
                ),
                esc_html__('Ativação do BastionWP bloqueada', 'bastionwp'),
                ['back_link' => true]
            );
        }
    }
}
