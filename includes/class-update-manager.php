<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Update_Manager
{
    public const SETTINGS_OPTION = 'bastionwp_update_settings';

    private BastionWP_MU_Installer $mu_installer;

    public function __construct(BastionWP_MU_Installer $mu_installer)
    {
        $this->mu_installer = $mu_installer;

        add_filter('update_plugins_github.com', [$this, 'filter_update'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'mark_core_sync_pending'], 10, 2);
        add_action('admin_post_bastionwp_save_update_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_bastionwp_check_updates', [$this, 'handle_manual_check']);
    }

    public static function get_settings(): array
    {
        $defaults = [
            'owner'   => '',
            'repo'    => 'bastionwp',
            'channel' => 'stable',
        ];

        $saved = get_option(self::SETTINGS_OPTION, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, $defaults);
    }

    public static function is_auto_update_enabled(): bool
    {
        $plugins = get_site_option('auto_update_plugins', []);

        return is_array($plugins) && in_array(BASTIONWP_BASENAME, $plugins, true);
    }

    public static function set_auto_update_enabled(bool $enabled): void
    {
        $plugins = get_site_option('auto_update_plugins', []);

        if (!is_array($plugins)) {
            $plugins = [];
        }

        $plugins = array_values(array_unique(array_map('strval', $plugins)));

        if ($enabled && !in_array(BASTIONWP_BASENAME, $plugins, true)) {
            $plugins[] = BASTIONWP_BASENAME;
        }

        if (!$enabled) {
            $plugins = array_values(
                array_filter(
                    $plugins,
                    static fn(string $plugin): bool => $plugin !== BASTIONWP_BASENAME
                )
            );
        }

        update_site_option('auto_update_plugins', $plugins);
    }

    public static function enable_auto_update_by_default(): void
    {
        self::set_auto_update_enabled(true);
    }

    public function filter_update($update, array $plugin_data, string $plugin_file, array $locales)
    {
        if ($plugin_file !== BASTIONWP_BASENAME) {
            return $update;
        }

        $provider = $this->provider();
        $release = $provider->get_latest_release();

        if (is_wp_error($release)) {
            return false;
        }

        if (version_compare((string) $release['version'], BASTIONWP_VERSION, '<=')) {
            return false;
        }

        return [
            'id'           => BASTIONWP_UPDATE_URI,
            'slug'         => BASTIONWP_SLUG,
            'version'      => (string) $release['version'],
            'url'          => (string) $release['url'],
            'package'      => (string) $release['package'],
            'requires_php' => BASTIONWP_MIN_PHP,
            'autoupdate'   => self::is_auto_update_enabled(),
        ];
    }

    public function mark_core_sync_pending($upgrader, array $options): void
    {
        if (($options['action'] ?? '') !== 'update' || ($options['type'] ?? '') !== 'plugin') {
            return;
        }

        $plugins = $options['plugins'] ?? [];

        if (!is_array($plugins)) {
            return;
        }

        if (in_array(BASTIONWP_BASENAME, $plugins, true)) {
            update_option('bastionwp_core_sync_pending', 1, false);
        }
    }

    public function handle_save_settings(): void
    {
        $this->assert_developer();
        check_admin_referer('bastionwp_save_update_settings');

        $owner = isset($_POST['github_owner'])
            ? sanitize_text_field(wp_unslash($_POST['github_owner']))
            : '';

        $repo = isset($_POST['github_repo'])
            ? sanitize_text_field(wp_unslash($_POST['github_repo']))
            : '';

        $channel = isset($_POST['update_channel'])
            ? sanitize_key(wp_unslash($_POST['update_channel']))
            : 'stable';

        $channel = in_array($channel, ['stable', 'beta'], true)
            ? $channel
            : 'stable';

        update_option(
            self::SETTINGS_OPTION,
            [
                'owner'   => $owner,
                'repo'    => $repo,
                'channel' => $channel,
            ],
            false
        );

        self::set_auto_update_enabled(isset($_POST['auto_update']));

        $this->provider()->clear_cache();
        delete_site_transient('update_plugins');

        set_transient(
            'bastionwp_update_message_' . get_current_user_id(),
            [
                'type' => 'success',
                'text' => __('Configurações de atualização salvas.', 'bastionwp'),
            ],
            60
        );

        $this->redirect_updates();
    }

    public function handle_manual_check(): void
    {
        $this->assert_developer();
        check_admin_referer('bastionwp_check_updates');

        $provider = $this->provider();
        $provider->clear_cache();

        $release = $provider->get_latest_release(true);

        if (is_wp_error($release)) {
            $message = [
                'type' => 'error',
                'text' => $release->get_error_message(),
            ];
        } elseif (version_compare((string) $release['version'], BASTIONWP_VERSION, '>')) {
            $message = [
                'type' => 'success',
                'text' => sprintf(
                    __('Nova versão encontrada: %s.', 'bastionwp'),
                    (string) $release['version']
                ),
            ];
        } else {
            $message = [
                'type' => 'success',
                'text' => sprintf(
                    __('Você já está na versão mais recente (%s).', 'bastionwp'),
                    BASTIONWP_VERSION
                ),
            ];
        }

        delete_site_transient('update_plugins');

        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }

        set_transient(
            'bastionwp_update_message_' . get_current_user_id(),
            $message,
            60
        );

        $this->redirect_updates();
    }

    public function get_status(): array
    {
        $settings = self::get_settings();
        $provider = $this->provider();

        if (!$provider->is_configured()) {
            return [
                'configured' => false,
                'release'    => null,
                'error'      => '',
            ];
        }

        $release = $provider->get_latest_release();

        if (is_wp_error($release)) {
            return [
                'configured' => true,
                'release'    => null,
                'error'      => $release->get_error_message(),
            ];
        }

        return [
            'configured' => true,
            'release'    => $release,
            'error'      => '',
        ];
    }

    private function provider(): BastionWP_GitHub_Provider
    {
        $settings = self::get_settings();

        return new BastionWP_GitHub_Provider(
            (string) $settings['owner'],
            (string) $settings['repo'],
            (string) $settings['channel']
        );
    }

    private function assert_developer(): void
    {
        if (
            !current_user_can('manage_options')
            || (
                !empty(BastionWP_Users::get_developer_ids())
                && !BastionWP_Users::is_developer()
            )
        ) {
            wp_die(
                esc_html__('Somente um Developer autorizado pode alterar as atualizações do BastionWP.', 'bastionwp'),
                esc_html__('Acesso negado', 'bastionwp'),
                ['response' => 403]
            );
        }
    }

    private function redirect_updates(): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'bastionwp',
                    'tab'  => 'updates',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
