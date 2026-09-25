<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Update_Manager
{
    public const SETTINGS_OPTION = 'bastionwp_update_settings';

    private BastionWP_MU_Installer $mu_installer;
    private static bool $internal_update_running = false;

    public function __construct(BastionWP_MU_Installer $mu_installer)
    {
        $this->mu_installer = $mu_installer;

        add_filter('update_plugins_github.com', [$this, 'filter_update'], 10, 4);
        add_filter('auto_update_plugin', [$this, 'filter_auto_update_plugin'], 20, 2);
        add_action('upgrader_process_complete', [$this, 'mark_core_sync_pending'], 10, 2);
        add_filter('upgrader_source_selection', [$this, 'validate_update_source'], 10, 4);
        add_action('admin_post_bastionwp_save_update_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_bastionwp_check_updates', [$this, 'handle_manual_check']);
        add_action('admin_post_bastionwp_install_update', [$this, 'handle_install_update']);
    }

    public static function get_settings(): array
    {
        $defaults = [
            'owner'   => 'BUSSINGUER',
            'repo'    => 'bastionwp',
            'channel' => 'stable',
        ];

        $saved = get_option(self::SETTINGS_OPTION, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        $settings = wp_parse_args($saved, $defaults);
        if (trim((string) $settings['owner']) === '') {
            $settings['owner'] = $defaults['owner'];
        }
        if (trim((string) $settings['repo']) === '') {
            $settings['repo'] = $defaults['repo'];
        }

        return $settings;
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

        if (is_multisite() && !current_user_can('manage_network_options')) {
            return;
        }

        update_site_option('auto_update_plugins', $plugins);
    }

    public static function enable_auto_update_by_default(): void
    {
        self::set_auto_update_enabled(true);
    }

    public static function is_internal_update_running(): bool
    {
        return self::$internal_update_running;
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

        if (self::is_auto_update_enabled()) {
            $this->schedule_background_auto_update();
        }

        return [
            'id'           => BASTIONWP_UPDATE_URI,
            'slug'         => BASTIONWP_SLUG,
            'version'      => (string) $release['version'],
            'url'          => (string) $release['url'],
            'package'      => (string) $release['package'],
            'requires_php' => !empty($release['requires_php']) ? (string) $release['requires_php'] : BASTIONWP_MIN_PHP,
            'requires'     => !empty($release['requires_wp']) ? (string) $release['requires_wp'] : BASTIONWP_MIN_WP,
            'autoupdate'   => self::is_auto_update_enabled(),
        ];
    }

    public function filter_auto_update_plugin($update, $item)
    {
        if (!self::is_auto_update_enabled()) {
            return $update;
        }

        $slug = is_object($item) && isset($item->slug)
            ? (string) $item->slug
            : '';

        $plugin = is_object($item) && isset($item->plugin)
            ? (string) $item->plugin
            : '';

        if ($slug === BASTIONWP_SLUG || $plugin === BASTIONWP_BASENAME) {
            return true;
        }

        return $update;
    }

    private function schedule_background_auto_update(): void
    {
        // O mecanismo nativo do WordPress decide e executa o background update.
        // Este evento apenas garante uma nova tentativa logo após o BastionWP
        // detectar uma Release nova, em vez de depender exclusivamente do
        // próximo ciclo normal de atualização.
        if (!wp_next_scheduled('wp_maybe_auto_update')) {
            wp_schedule_single_event(time() + 60, 'wp_maybe_auto_update');
        }
    }

    public function mark_core_sync_pending($upgrader, array $options): void
    {
        if (($options['action'] ?? '') !== 'update' || ($options['type'] ?? '') !== 'plugin') {
            return;
        }

        $plugins = [];

        if (!empty($options['plugin']) && is_string($options['plugin'])) {
            $plugins[] = $options['plugin'];
        }

        if (!empty($options['plugins']) && is_array($options['plugins'])) {
            $plugins = array_merge($plugins, array_map('strval', $options['plugins']));
        }

        $plugins = array_values(array_unique($plugins));

        if (!in_array(BASTIONWP_BASENAME, $plugins, true)) {
            return;
        }

        update_option('bastionwp_core_sync_pending', 1, false);

        $disk_version = BASTIONWP_VERSION;
        if (is_readable(BASTIONWP_FILE)) {
            $headers = get_file_data(BASTIONWP_FILE, ['Version' => 'Version'], 'plugin');
            if (!empty($headers['Version'])) {
                $disk_version = sanitize_text_field((string) $headers['Version']);
            }
        }

        BastionWP_Logger::log(
            'bastionwp_plugin_updated',
            __('Atualização do plugin BastionWP concluída pelo WordPress.', 'bastionwp'),
            'success',
            ['version_on_disk' => $disk_version]
        );
    }

    public function validate_update_source($source, string $remote_source, $upgrader, array $hook_extra)
    {
        if (is_wp_error($source)) {
            return $source;
        }

        $plugin = isset($hook_extra['plugin']) ? (string) $hook_extra['plugin'] : '';
        $plugins = isset($hook_extra['plugins']) && is_array($hook_extra['plugins'])
            ? array_map('strval', $hook_extra['plugins'])
            : [];

        if ($plugin !== BASTIONWP_BASENAME && !in_array(BASTIONWP_BASENAME, $plugins, true)) {
            return $source;
        }

        $source_path = untrailingslashit((string) $source);

        if (basename($source_path) !== BASTIONWP_SLUG) {
            return new WP_Error(
                'bastionwp_invalid_package_root',
                __('O pacote de atualização foi rejeitado: a raiz deve ser exatamente bastionwp/.', 'bastionwp')
            );
        }

        $main = trailingslashit($source_path) . 'bastionwp.php';

        if (!is_readable($main)) {
            return new WP_Error(
                'bastionwp_invalid_package_main',
                __('O pacote de atualização foi rejeitado porque não contém bastionwp/bastionwp.php.', 'bastionwp')
            );
        }

        $headers = get_file_data(
            $main,
            [
                'Name'      => 'Plugin Name',
                'Version'   => 'Version',
                'UpdateURI' => 'Update URI',
            ],
            'plugin'
        );

        if (
            trim((string) ($headers['Name'] ?? '')) !== 'BastionWP'
            || empty($headers['Version'])
            || trim((string) ($headers['UpdateURI'] ?? '')) !== BASTIONWP_UPDATE_URI
        ) {
            return new WP_Error(
                'bastionwp_invalid_package_identity',
                __('O pacote de atualização não corresponde à identidade esperada do BastionWP.', 'bastionwp')
            );
        }

        if (version_compare((string) $headers['Version'], BASTIONWP_VERSION, '<')) {
            return new WP_Error(
                'bastionwp_update_downgrade_blocked',
                __('O pacote de atualização é mais antigo que a versão instalada e foi bloqueado.', 'bastionwp')
            );
        }

        return $source;
    }

    public function handle_save_settings(): void
    {
        $this->assert_developer();
        check_admin_referer('bastionwp_save_update_settings');

        $risk_unlocked = class_exists('BastionWP_Admin') && BastionWP_Admin::is_risk_zone_unlocked_for_current_user();
        if (!$risk_unlocked) {
            wp_die(
                esc_html__('Desbloqueie a Zona de risco antes de alterar a fonte de atualização.', 'bastionwp'),
                esc_html__('Zona de risco bloqueada', 'bastionwp'),
                ['response' => 403, 'back_link' => true]
            );
        }

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

        $current = self::get_settings();
        $source_changed = $owner !== (string) $current['owner'] || $repo !== (string) $current['repo'];
        $source_unlocked = isset($_POST['source_unlocked']) && (string) wp_unslash($_POST['source_unlocked']) === '1';

        if ($source_changed && (!$source_unlocked || !$risk_unlocked)) {
            wp_die(
                esc_html__('A fonte de atualização está bloqueada. Desbloqueie a Zona de risco antes de alterar proprietário ou repositório.', 'bastionwp'),
                esc_html__('Fonte de atualização protegida', 'bastionwp'),
                ['response' => 403]
            );
        }

        update_option(
            self::SETTINGS_OPTION,
            [
                'owner'   => $owner,
                'repo'    => $repo,
                'channel' => $channel,
            ],
            false
        );

        $auto_update_enabled = isset($_POST['auto_update']);
        self::set_auto_update_enabled($auto_update_enabled);

        BastionWP_Logger::log(
            'update_settings_changed',
            __('Configurações de atualização do BastionWP alteradas.', 'bastionwp'),
            'success',
            [
                'owner'       => $owner,
                'repo'        => $repo,
                'channel'     => $channel,
                'auto_update' => $auto_update_enabled,
            ]
        );

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

        BastionWP_Logger::log(
            'manual_update_check',
            is_wp_error($release)
                ? __('Verificação manual de atualização falhou.', 'bastionwp')
                : __('Verificação manual de atualização concluída.', 'bastionwp'),
            is_wp_error($release) ? 'warning' : 'info',
            is_wp_error($release)
                ? ['error' => $release->get_error_message()]
                : ['latest_version' => (string) $release['version']]
        );

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

    public function handle_install_update(): void
    {
        $this->assert_developer();
        check_admin_referer('bastionwp_install_update');

        $result = $this->install_latest_available();

        if (is_wp_error($result)) {
            $message = ['type' => 'error', 'text' => $result->get_error_message()];
        } elseif (($result['status'] ?? '') === 'updated') {
            delete_option('bastionwp_dismissed_update_notification');
            $message = [
                'type' => 'success',
                'text' => sprintf(__('BastionWP atualizado para %s.', 'bastionwp'), (string) ($result['version'] ?? '')),
            ];
        } else {
            $message = ['type' => 'success', 'text' => __('O BastionWP já está na versão mais recente.', 'bastionwp')];
        }

        set_transient('bastionwp_update_message_' . get_current_user_id(), $message, 90);
        $this->redirect_updates();
    }

    public function install_latest_available()
    {
        $provider = $this->provider();
        $provider->clear_cache();
        $release = $provider->get_latest_release(true);

        if (is_wp_error($release)) {
            return $release;
        }

        $version = (string) ($release['version'] ?? '');
        if ($version === '' || version_compare($version, BASTIONWP_VERSION, '<=')) {
            return ['status' => 'current', 'version' => BASTIONWP_VERSION];
        }

        if (empty($release['package'])) {
            return new WP_Error('bastionwp_update_package_missing', __('A Release encontrada não possui pacote instalável válido.', 'bastionwp'));
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $transient = get_site_transient('update_plugins');
        if (!is_object($transient)) {
            $transient = new stdClass();
        }
        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = [];
        }

        $item = new stdClass();
        $item->id = BASTIONWP_UPDATE_URI;
        $item->slug = BASTIONWP_SLUG;
        $item->plugin = BASTIONWP_BASENAME;
        $item->new_version = $version;
        $item->url = (string) ($release['url'] ?? '');
        $item->package = (string) $release['package'];
        $item->requires_php = !empty($release['requires_php']) ? (string) $release['requires_php'] : BASTIONWP_MIN_PHP;
        $item->requires = !empty($release['requires_wp']) ? (string) $release['requires_wp'] : BASTIONWP_MIN_WP;
        $transient->response[BASTIONWP_BASENAME] = $item;
        set_site_transient('update_plugins', $transient);

        self::$internal_update_running = true;
        try {
            $skin = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            $updated = $upgrader->upgrade(BASTIONWP_BASENAME);
        } finally {
            self::$internal_update_running = false;
        }

        if (is_wp_error($updated)) {
            return $updated;
        }
        if (!$updated) {
            $errors = $skin->get_errors();
            if (is_wp_error($errors) && $errors->has_errors()) {
                return $errors;
            }
            return new WP_Error('bastionwp_update_failed', __('O WordPress não conseguiu concluir a atualização do BastionWP.', 'bastionwp'));
        }

        update_option('bastionwp_core_sync_pending', 1, false);
        delete_site_transient('update_plugins');

        BastionWP_Logger::log(
            'bastionwp_self_update',
            sprintf(__('Atualização interna do BastionWP executada para %s.', 'bastionwp'), $version),
            'success',
            ['target_version' => $version]
        );

        return ['status' => 'updated', 'version' => $version];
    }

    public function get_status(): array
    {
        $settings = self::get_settings();
        $provider = $this->provider();

        if (!$provider->is_configured()) {
            return [
                'configured'      => false,
                'release'         => null,
                'error'           => '',
                'installed'       => BASTIONWP_VERSION,
                'latest_version'  => '',
                'update_available'=> false,
            ];
        }

        $release = $provider->get_latest_release();

        if (is_wp_error($release)) {
            return [
                'configured'      => true,
                'release'         => null,
                'error'           => $release->get_error_message(),
                'installed'       => BASTIONWP_VERSION,
                'latest_version'  => '',
                'update_available'=> false,
            ];
        }

        $latest = (string) ($release['version'] ?? '');

        return [
            'configured'       => true,
            'release'          => $release,
            'error'            => '',
            'installed'        => BASTIONWP_VERSION,
            'latest_version'   => $latest,
            'update_available' => $latest !== '' && version_compare($latest, BASTIONWP_VERSION, '>'),
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
        if (is_multisite()) {
            wp_die(
                esc_html__('BastionWP 0.9.9.3 ainda é homologado somente para instalações WordPress single-site. Alterações de atualização foram bloqueadas no multisite.', 'bastionwp'),
                esc_html__('Multisite não homologado', 'bastionwp'),
                ['response' => 403]
            );
        }

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
                    'tab'  => 'system',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
