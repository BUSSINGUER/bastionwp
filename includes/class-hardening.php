<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hardening por perfil de ambiente.
 *
 * Esta classe evita editar wp-config.php e arquivos de servidor automaticamente.
 * O foco da V0.6.0 é aplicar proteções reversíveis por hooks/capabilities nativas.
 */
final class BastionWP_Hardening
{
    public const SETTINGS_OPTION = 'bastionwp_settings';

    public const PROFILE_UNCONFIGURED = 'unconfigured';
    public const PROFILE_DEVELOPMENT = 'development';
    public const PROFILE_STAGING = 'staging';
    public const PROFILE_PRODUCTION = 'production';
    public const PROFILE_LOCKED = 'production_locked';

    private bool $resolving_capabilities = false;

    public function __construct()
    {
        $settings = $this->get_effective_settings();

        if ($settings['hide_wordpress_version']) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        if ($settings['disable_xmlrpc']) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('xmlrpc_methods', [$this, 'filter_xmlrpc_methods']);
            add_filter('wp_headers', [$this, 'filter_pingback_header']);
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
        }

        if ($settings['disable_application_passwords']) {
            add_filter('wp_is_application_passwords_available', '__return_false');
        }

        if ($settings['generic_login_errors']) {
            add_filter('login_errors', [$this, 'generic_login_error']);
        }

        if ($settings['block_public_rest_users']) {
            add_filter('rest_pre_dispatch', [$this, 'block_public_user_enumeration'], 10, 3);
        }

        if ($settings['suppress_display_errors']) {
            @ini_set('display_errors', '0');
        }

        if ($settings['block_file_editors'] || $settings['block_manual_infrastructure_changes']) {
            add_filter('user_has_cap', [$this, 'filter_infrastructure_capabilities'], 30, 4);
        }
    }

    public static function get_profiles(): array
    {
        return [
            self::PROFILE_DEVELOPMENT => [
                'label'       => __('Desenvolvimento', 'bastionwp'),
                'description' => __('Menos restritivo. Indicado somente durante desenvolvimento ativo.', 'bastionwp'),
            ],
            self::PROFILE_STAGING => [
                'label'       => __('Staging', 'bastionwp'),
                'description' => __('Bloqueia editores de código e reduz exposição, mantendo integrações disponíveis para testes.', 'bastionwp'),
            ],
            self::PROFILE_PRODUCTION => [
                'label'       => __('Produção', 'bastionwp'),
                'description' => __('Perfil recomendado para sites publicados. Reduz superfícies de ataque sem bloquear manutenção técnica.', 'bastionwp'),
            ],
            self::PROFILE_LOCKED => [
                'label'       => __('Produção Bloqueada', 'bastionwp'),
                'description' => __('Produção com alterações manuais de plugins, temas e core bloqueadas até o Developer trocar o perfil.', 'bastionwp'),
            ],
        ];
    }

    public static function get_profile(): string
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        if (!is_array($settings)) {
            return self::PROFILE_UNCONFIGURED;
        }

        $profile = isset($settings['profile'])
            ? sanitize_key((string) $settings['profile'])
            : self::PROFILE_UNCONFIGURED;

        return array_key_exists($profile, self::get_profiles())
            ? $profile
            : self::PROFILE_UNCONFIGURED;
    }

    public static function save_profile(string $profile): bool
    {
        if (!array_key_exists($profile, self::get_profiles())) {
            return false;
        }

        $settings = get_option(self::SETTINGS_OPTION, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['profile'] = $profile;
        $settings['setup_complete'] = true;
        $settings['profile_updated_at'] = time();
        $settings['profile_updated_by'] = get_current_user_id();

        return update_option(self::SETTINGS_OPTION, $settings, false);
    }

    public function get_effective_settings(?string $profile = null): array
    {
        $profile = $profile ?: self::get_profile();

        $base = [
            'block_file_editors'                 => false,
            'disable_xmlrpc'                     => false,
            'disable_application_passwords'      => false,
            'hide_wordpress_version'             => false,
            'generic_login_errors'               => false,
            'block_public_rest_users'            => false,
            'suppress_display_errors'            => false,
            'block_manual_infrastructure_changes'=> false,
        ];

        if ($profile === self::PROFILE_STAGING) {
            return array_merge($base, [
                'block_file_editors'      => true,
                'hide_wordpress_version'  => true,
                'generic_login_errors'    => true,
                'block_public_rest_users' => true,
            ]);
        }

        if ($profile === self::PROFILE_PRODUCTION) {
            return array_merge($base, [
                'block_file_editors'            => true,
                'disable_xmlrpc'                => true,
                'disable_application_passwords' => true,
                'hide_wordpress_version'        => true,
                'generic_login_errors'          => true,
                'block_public_rest_users'       => true,
                'suppress_display_errors'       => true,
            ]);
        }

        if ($profile === self::PROFILE_LOCKED) {
            return array_merge($base, [
                'block_file_editors'                  => true,
                'disable_xmlrpc'                      => true,
                'disable_application_passwords'       => true,
                'hide_wordpress_version'              => true,
                'generic_login_errors'                => true,
                'block_public_rest_users'             => true,
                'suppress_display_errors'             => true,
                'block_manual_infrastructure_changes' => true,
            ]);
        }

        return $base;
    }

    public function get_diagnostics(): array
    {
        $profile = self::get_profile();
        $settings = $this->get_effective_settings($profile);

        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $wp_debug_display = defined('WP_DEBUG_DISPLAY')
            ? (bool) WP_DEBUG_DISPLAY
            : filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);

        $checks = [
            [
                'label'  => __('Perfil de hardening', 'bastionwp'),
                'status' => $profile === self::PROFILE_UNCONFIGURED ? 'warning' : 'ok',
                'value'  => $profile === self::PROFILE_UNCONFIGURED
                    ? __('Não configurado', 'bastionwp')
                    : (self::get_profiles()[$profile]['label'] ?? $profile),
            ],
            [
                'label'  => __('HTTPS', 'bastionwp'),
                'status' => is_ssl() ? 'ok' : 'warning',
                'value'  => is_ssl() ? __('Ativo', 'bastionwp') : __('Não detectado', 'bastionwp'),
            ],
            [
                'label'  => __('WP_DEBUG', 'bastionwp'),
                'status' => (
                    in_array($profile, [self::PROFILE_PRODUCTION, self::PROFILE_LOCKED], true)
                    && $wp_debug
                ) ? 'warning' : 'ok',
                'value'  => $wp_debug ? __('Ativo', 'bastionwp') : __('Desativado', 'bastionwp'),
            ],
            [
                'label'  => __('Exibição de erros PHP', 'bastionwp'),
                'status' => (
                    in_array($profile, [self::PROFILE_PRODUCTION, self::PROFILE_LOCKED], true)
                    && $wp_debug_display
                ) ? 'warning' : 'ok',
                'value'  => $wp_debug_display ? __('Pode estar ativa', 'bastionwp') : __('Suprimida', 'bastionwp'),
            ],
            [
                'label'  => __('XML-RPC', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['disable_xmlrpc']
                    ? __('Bloqueado pelo perfil', 'bastionwp')
                    : __('Permitido pelo perfil', 'bastionwp'),
            ],
            [
                'label'  => __('Application Passwords', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['disable_application_passwords']
                    ? __('Bloqueadas pelo perfil', 'bastionwp')
                    : __('Permitidas pelo perfil', 'bastionwp'),
            ],
            [
                'label'  => __('Editor de arquivos de tema/plugin', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['block_file_editors']
                    ? __('Bloqueado pelo BastionWP', 'bastionwp')
                    : __('Permitido pelo perfil', 'bastionwp'),
            ],
            [
                'label'  => __('Alterações manuais de infraestrutura', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['block_manual_infrastructure_changes']
                    ? __('Bloqueadas', 'bastionwp')
                    : __('Permitidas ao Developer', 'bastionwp'),
            ],
        ];

        return $checks;
    }

    public function filter_xmlrpc_methods(array $methods): array
    {
        unset(
            $methods['pingback.ping'],
            $methods['pingback.extensions.getPingbacks']
        );

        return $methods;
    }

    public function filter_pingback_header(array $headers): array
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'x-pingback') {
                unset($headers[$key]);
            }
        }

        return $headers;
    }

    public function generic_login_error(): string
    {
        return __('Não foi possível realizar o login com os dados informados.', 'bastionwp');
    }

    public function block_public_user_enumeration($result, $server, $request)
    {
        if (is_user_logged_in()) {
            return $result;
        }

        if (!is_object($request) || !method_exists($request, 'get_route')) {
            return $result;
        }

        $route = (string) $request->get_route();

        if (
            preg_match('#^/wp/v2/users(?:/|$)#', $route)
            || preg_match('#^/wp/v2/users/me(?:/|$)#', $route)
        ) {
            return new WP_Error(
                'bastionwp_rest_users_blocked',
                __('A listagem pública de usuários está desativada.', 'bastionwp'),
                ['status' => 401]
            );
        }

        return $result;
    }

    public function filter_infrastructure_capabilities(
        array $allcaps,
        array $caps,
        array $args,
        WP_User $user
    ): array {
        if ($this->resolving_capabilities || !$user->exists()) {
            return $allcaps;
        }

        $settings = $this->get_effective_settings();

        $this->resolving_capabilities = true;

        try {
            if ($settings['block_file_editors']) {
                $allcaps['edit_plugins'] = false;
                $allcaps['edit_themes'] = false;
            }

            if (
                $settings['block_manual_infrastructure_changes']
                && !$this->is_background_update_context()
            ) {
                foreach ($this->locked_capabilities() as $capability) {
                    $allcaps[$capability] = false;
                }
            }

            return $allcaps;
        } finally {
            $this->resolving_capabilities = false;
        }
    }

    private function locked_capabilities(): array
    {
        return [
            'install_plugins',
            'activate_plugins',
            'delete_plugins',
            'update_plugins',
            'edit_plugins',
            'install_themes',
            'switch_themes',
            'delete_themes',
            'update_themes',
            'edit_themes',
            'update_core',
        ];
    }

    private function is_background_update_context(): bool
    {
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return true;
        }

        return defined('DOING_CRON') && DOING_CRON;
    }
}
