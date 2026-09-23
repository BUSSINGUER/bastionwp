<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integração segura com o Wordfence.
 *
 * O BastionWP não copia código, regras, chaves ou configurações internas
 * não documentadas do Wordfence.
 *
 * Responsabilidades:
 * - detectar instalação e ativação;
 * - instalar o plugin oficial do WordPress.org por ação explícita do Developer;
 * - ativar o plugin;
 * - ativar/desativar auto-update nativo;
 * - apresentar checklist operacional.
 */
final class BastionWP_Wordfence_Integration
{
    public const PLUGIN_SLUG = 'wordfence';
    public const PLUGIN_FILE = 'wordfence/wordfence.php';

    public function get_status(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $installed = isset($plugins[self::PLUGIN_FILE]);
        $active = $installed && is_plugin_active(self::PLUGIN_FILE);

        $version = '';

        if ($installed && !empty($plugins[self::PLUGIN_FILE]['Version'])) {
            $version = sanitize_text_field((string) $plugins[self::PLUGIN_FILE]['Version']);
        }

        return [
            'installed'          => $installed,
            'active'             => $active,
            'version'            => $version,
            'waf_loaded'         => defined('WFWAF_VERSION'),
            'waf_bootstrap_file' => file_exists(ABSPATH . 'wordfence-waf.php'),
            'auto_update'        => $this->is_auto_update_enabled(),
        ];
    }

    public function install_and_activate()
    {
        if ($this->is_production_locked()) {
            return new WP_Error(
                'bastionwp_wordfence_locked',
                __('O perfil Produção Bloqueada impede instalações manuais. Altere temporariamente para Produção e tente novamente.', 'bastionwp')
            );
        }

        if (!current_user_can('install_plugins')) {
            return new WP_Error(
                'bastionwp_wordfence_no_install_cap',
                __('O usuário atual não possui permissão para instalar plugins.', 'bastionwp')
            );
        }

        $status = $this->get_status();

        if (!$status['installed']) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $api = plugins_api(
                'plugin_information',
                [
                    'slug'   => self::PLUGIN_SLUG,
                    'fields' => [
                        'sections' => false,
                    ],
                ]
            );

            if (is_wp_error($api)) {
                return $api;
            }

            if (empty($api->download_link)) {
                return new WP_Error(
                    'bastionwp_wordfence_no_download',
                    __('O WordPress.org não retornou um pacote de instalação para o Wordfence.', 'bastionwp')
                );
            }

            $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
            $installed = $upgrader->install((string) $api->download_link);

            if (is_wp_error($installed)) {
                return $installed;
            }

            if (!$installed) {
                return new WP_Error(
                    'bastionwp_wordfence_install_failed',
                    __('A instalação automática do Wordfence não foi concluída.', 'bastionwp')
                );
            }
        }

        if (!current_user_can('activate_plugins')) {
            return new WP_Error(
                'bastionwp_wordfence_no_activate_cap',
                __('O usuário atual não possui permissão para ativar plugins.', 'bastionwp')
            );
        }

        if (!is_plugin_active(self::PLUGIN_FILE)) {
            $result = activate_plugin(self::PLUGIN_FILE);

            if (is_wp_error($result)) {
                return $result;
            }
        }

        $this->set_auto_update_enabled(true);

        return true;
    }

    public function activate()
    {
        if ($this->is_production_locked()) {
            return new WP_Error(
                'bastionwp_wordfence_locked',
                __('O perfil Produção Bloqueada impede ativações manuais. Altere temporariamente para Produção e tente novamente.', 'bastionwp')
            );
        }

        if (!current_user_can('activate_plugins')) {
            return new WP_Error(
                'bastionwp_wordfence_no_activate_cap',
                __('O usuário atual não possui permissão para ativar plugins.', 'bastionwp')
            );
        }

        $status = $this->get_status();

        if (!$status['installed']) {
            return new WP_Error(
                'bastionwp_wordfence_not_installed',
                __('O Wordfence ainda não está instalado.', 'bastionwp')
            );
        }

        if ($status['active']) {
            return true;
        }

        $result = activate_plugin(self::PLUGIN_FILE);

        if (is_wp_error($result)) {
            return $result;
        }

        $this->set_auto_update_enabled(true);

        return true;
    }

    public function is_auto_update_enabled(): bool
    {
        $plugins = get_site_option('auto_update_plugins', []);

        return is_array($plugins) && in_array(self::PLUGIN_FILE, $plugins, true);
    }

    public function set_auto_update_enabled(bool $enabled): void
    {
        $plugins = get_site_option('auto_update_plugins', []);

        if (!is_array($plugins)) {
            $plugins = [];
        }

        $plugins = array_values(array_unique(array_map('strval', $plugins)));

        if ($enabled && !in_array(self::PLUGIN_FILE, $plugins, true)) {
            $plugins[] = self::PLUGIN_FILE;
        }

        if (!$enabled) {
            $plugins = array_values(
                array_filter(
                    $plugins,
                    static fn(string $plugin): bool => $plugin !== self::PLUGIN_FILE
                )
            );
        }

        update_site_option('auto_update_plugins', $plugins);
    }

    public function get_admin_url(): string
    {
        return admin_url('admin.php?page=Wordfence');
    }

    private function is_production_locked(): bool
    {
        return class_exists('BastionWP_Hardening')
            && BastionWP_Hardening::get_profile() === BastionWP_Hardening::PROFILE_LOCKED;
    }
}
