<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Hardening
{
    public const SETTINGS_OPTION = 'bastionwp_settings';
    public const OWNERSHIP_OPTION = 'bastionwp_hardening_ownership';

    public const PROFILE_UNCONFIGURED = 'unconfigured';
    public const PROFILE_DEVELOPMENT = 'development';
    public const PROFILE_STAGING = 'staging';
    public const PROFILE_PRODUCTION = 'production';
    public const PROFILE_LOCKED = 'production_locked';

    private bool $resolving_capabilities = false;

    public function __construct()
    {
        $settings = self::get_effective_settings();

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

        if ($settings['suppress_display_errors'] || $settings['force_suppress_display_errors']) {
            @ini_set('display_errors', '0');
        }

        if ($settings['disable_comments']) {
            add_action('init', [$this, 'disable_comment_support'], 100);
            add_action('admin_menu', [$this, 'hide_comments_menu'], 9999);
            add_action('admin_bar_menu', [$this, 'hide_comments_admin_bar'], 9999);
            add_filter('comments_open', '__return_false', 100);
            add_filter('pings_open', '__return_false', 100);
            add_filter('pre_option_default_comment_status', [$this, 'closed_comment_status']);
            add_filter('pre_option_default_ping_status', [$this, 'closed_comment_status']);
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
                'label'       => __('Homologação', 'bastionwp'),
                'description' => __('Proteção moderada para validar o site antes da publicação.', 'bastionwp'),
            ],
            self::PROFILE_PRODUCTION => [
                'label'       => __('Proteção Recomendada', 'bastionwp'),
                'description' => __('Proteção alta recomendada para sites publicados, mantendo manutenção técnica do Developer.', 'bastionwp'),
            ],
            self::PROFILE_LOCKED => [
                'label'       => __('Proteção Máxima', 'bastionwp'),
                'description' => __('Perfil mais restritivo, com alterações manuais de plugins, temas e core bloqueadas.', 'bastionwp'),
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

        return in_array($profile, self::valid_profile_keys(), true)
            ? $profile
            : self::PROFILE_UNCONFIGURED;
    }

    public static function save_profile(string $profile): bool
    {
        if (!in_array($profile, self::valid_profile_keys(), true)) {
            return false;
        }

        $settings = self::raw_settings();
        $settings['profile'] = $profile;
        $settings['setup_complete'] = true;
        $settings['profile_updated_at'] = time();
        $settings['profile_updated_by'] = get_current_user_id();

        return self::persist_settings($settings);
    }

    public static function save_overrides(array $overrides): bool
    {
        $settings = self::raw_settings();

        foreach ([
            'disable_comments',
            'hide_client_dashboard',
            'force_suppress_display_errors',
        ] as $key) {
            $settings[$key] = !empty($overrides[$key]);
        }

        return self::persist_settings($settings);
    }

    public static function enable_force_suppress_display_errors(): bool
    {
        $settings = self::raw_settings();
        $settings['force_suppress_display_errors'] = true;

        return self::persist_settings($settings);
    }

    public static function get_effective_settings(?string $profile = null, bool $respect_ownership = true): array
    {
        $profile = $profile ?: self::get_profile();

        $base = [
            'block_file_editors'                  => false,
            'disable_xmlrpc'                      => false,
            'disable_application_passwords'       => false,
            'hide_wordpress_version'              => false,
            'generic_login_errors'                => false,
            'block_public_rest_users'             => false,
            'suppress_display_errors'             => false,
            'block_manual_infrastructure_changes' => false,
            'disable_comments'                    => false,
            'hide_client_dashboard'               => false,
            'force_suppress_display_errors'       => false,
        ];

        if ($profile === self::PROFILE_STAGING) {
            $base = array_merge($base, [
                'block_file_editors'      => true,
                'hide_wordpress_version'  => true,
                'generic_login_errors'    => true,
                'block_public_rest_users' => true,
            ]);
        }

        if ($profile === self::PROFILE_PRODUCTION) {
            $base = array_merge($base, [
                'block_file_editors'            => true,
                'disable_xmlrpc'                => true,
                'disable_application_passwords' => true,
                'hide_wordpress_version'        => true,
                'generic_login_errors'          => true,
                'block_public_rest_users'       => true,
                'suppress_display_errors'       => true,
                'hide_client_dashboard'         => true,
            ]);
        }

        if ($profile === self::PROFILE_LOCKED) {
            $base = array_merge($base, [
                'block_file_editors'                  => true,
                'disable_xmlrpc'                      => true,
                'disable_application_passwords'       => true,
                'hide_wordpress_version'              => true,
                'generic_login_errors'                => true,
                'block_public_rest_users'             => true,
                'suppress_display_errors'             => true,
                'block_manual_infrastructure_changes' => true,
                'hide_client_dashboard'               => true,
            ]);
        }

        $raw = self::raw_settings();

        foreach ([
            'disable_comments',
            'hide_client_dashboard',
            'force_suppress_display_errors',
        ] as $override_key) {
            if (array_key_exists($override_key, $raw)) {
                $base[$override_key] = (bool) $raw[$override_key];
            }
        }

        if (!$respect_ownership) {
            return $base;
        }

        $ownership = self::get_ownership_decisions();
        $ownership_map = [
            'xmlrpc' => 'disable_xmlrpc',
            'application_passwords' => 'disable_application_passwords',
            'file_editors' => 'block_file_editors',
            'display_errors' => 'suppress_display_errors',
        ];

        foreach ($ownership_map as $ownership_key => $setting_key) {
            if (($ownership[$ownership_key] ?? '') !== 'external') {
                continue;
            }

            // Uma decisão antiga de "proteção externa" não pode deixar a regra
            // efetivamente aberta se aquela proteção desaparecer. Só cedemos a
            // responsabilidade quando a proteção externa continua detectável.
            $external_effective = false;
            if ($ownership_key === 'xmlrpc') {
                $external_effective = self::find_external_hook_source_path('xmlrpc_enabled') !== ''
                    && !(bool) apply_filters('xmlrpc_enabled', true);
            } elseif ($ownership_key === 'application_passwords') {
                $external_effective = self::find_external_hook_source_path('wp_is_application_passwords_available') !== ''
                    && function_exists('wp_is_application_passwords_available')
                    && !wp_is_application_passwords_available();
            } elseif ($ownership_key === 'file_editors') {
                $external_effective = (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
                    || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS);
            } elseif ($ownership_key === 'display_errors') {
                $external_effective = !filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);
            }

            if ($external_effective) {
                $base[$setting_key] = false;
                if ($ownership_key === 'display_errors') {
                    $base['force_suppress_display_errors'] = false;
                }
            }
        }

        return $base;
    }

    public static function get_ownership_decisions(): array
    {
        $saved = get_option(self::OWNERSHIP_OPTION, []);
        return is_array($saved) ? $saved : [];
    }

    public static function save_ownership_decisions(array $choices): bool
    {
        $clean = [];
        foreach (['xmlrpc', 'application_passwords', 'file_editors', 'display_errors'] as $key) {
            $value = isset($choices[$key]) ? sanitize_key((string) $choices[$key]) : 'bastion';
            $clean[$key] = in_array($value, ['bastion', 'external'], true) ? $value : 'bastion';
        }

        return update_option(self::OWNERSHIP_OPTION, $clean, false) || self::get_ownership_decisions() === $clean;
    }

    public function get_preflight_report(): array
    {
        $effective_xmlrpc = (bool) apply_filters('xmlrpc_enabled', true);
        $app_passwords = function_exists('wp_is_application_passwords_available')
            ? (bool) wp_is_application_passwords_available()
            : true;
        $file_editors_blocked = (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
            || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS);
        $display_errors = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($display_errors === null) {
            $display_errors = (string) ini_get('display_errors') !== '0'
                && strtolower((string) ini_get('display_errors')) !== 'off';
        }

        return [
            [
                'key' => 'xmlrpc',
                'label' => __('XML-RPC', 'bastionwp'),
                'protected' => !$effective_xmlrpc,
                'source' => $this->detect_hook_source('xmlrpc_enabled'),
                'description' => __('Verifica se outra camada já desabilita os métodos XML-RPC antes do BastionWP assumir essa regra.', 'bastionwp'),
            ],
            [
                'key' => 'application_passwords',
                'label' => __('Application Passwords', 'bastionwp'),
                'protected' => !$app_passwords,
                'source' => $this->detect_hook_source('wp_is_application_passwords_available'),
                'description' => __('Verifica filtros externos que já indisponibilizam Application Passwords.', 'bastionwp'),
            ],
            [
                'key' => 'file_editors',
                'label' => __('Editor de arquivos', 'bastionwp'),
                'protected' => $file_editors_blocked,
                'source' => $file_editors_blocked ? __('wp-config.php / constantes do WordPress', 'bastionwp') : __('Nenhuma regra externa detectada', 'bastionwp'),
                'description' => __('Detecta DISALLOW_FILE_EDIT ou DISALLOW_FILE_MODS antes de aplicar a proteção do BastionWP.', 'bastionwp'),
            ],
            [
                'key' => 'display_errors',
                'label' => __('Exibição de erros PHP', 'bastionwp'),
                'protected' => !$display_errors,
                'source' => !$display_errors ? __('PHP / wp-config.php / servidor', 'bastionwp') : __('Nenhuma supressão externa efetiva detectada', 'bastionwp'),
                'description' => __('Verifica o estado efetivo de display_errors sem editar automaticamente arquivos externos.', 'bastionwp'),
            ],
        ];
    }

    private function detect_hook_source(string $hook): string
    {
        $source = self::find_external_hook_source_path($hook);
        return $source !== ''
            ? $source
            : __('Origem não identificada com segurança', 'bastionwp');
    }

    /**
     * Retorna somente uma origem externa realmente atribuível.
     * Callbacks genéricos (__return_false etc.) não possuem autoria confiável
     * e o próprio BastionWP os utiliza, portanto nunca contam como origem.
     */
    private static function find_external_hook_source_path(string $hook): string
    {
        global $wp_filter;
        if (empty($wp_filter[$hook]) || !isset($wp_filter[$hook]->callbacks)) {
            return '';
        }

        $sources = [];
        foreach ((array) $wp_filter[$hook]->callbacks as $callbacks) {
            foreach ((array) $callbacks as $callback_data) {
                $callback = $callback_data['function'] ?? null;
                try {
                    if (is_array($callback) && isset($callback[0], $callback[1])) {
                        $reflection = new ReflectionMethod($callback[0], (string) $callback[1]);
                    } elseif (is_string($callback) && function_exists($callback)) {
                        if (in_array($callback, ['__return_false', '__return_true', '__return_zero', '__return_empty_string'], true)) {
                            continue;
                        }
                        $reflection = new ReflectionFunction($callback);
                    } else {
                        continue;
                    }

                    $file = $reflection->getFileName();
                    if (!$file || str_contains((string) $file, 'class-hardening.php')) {
                        continue;
                    }
                    $sources[] = str_replace('\\', '/', wp_normalize_path((string) $file));
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        $sources = array_values(array_unique($sources));
        if (empty($sources)) {
            return '';
        }

        $first = $sources[0];
        if (defined('WP_CONTENT_DIR') && str_starts_with($first, wp_normalize_path(WP_CONTENT_DIR))) {
            $first = 'wp-content' . substr($first, strlen(wp_normalize_path(WP_CONTENT_DIR)));
        }

        return $first;
    }

    private static function preflight_source_is_identified(string $source): bool
    {
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($source) : strtolower($source);
        return $normalized !== ''
            && !str_contains($normalized, 'nenhuma origem')
            && !str_contains($normalized, 'origem não identificada')
            && !str_contains($normalized, 'origem nao identificada');
    }

    public function get_rule_state(string $key, ?string $profile = null): array
    {
        $profile = $profile ?: self::get_profile();
        $policy = self::get_effective_settings($profile, false);
        $runtime = self::get_effective_settings($profile, true);
        $ownership = self::get_ownership_decisions();
        $preflight = $this->get_preflight_report();
        $external = null;

        foreach ($preflight as $item) {
            if (($item['key'] ?? '') === $key) {
                $external = $item;
                break;
            }
        }

        $map = [
            'xmlrpc' => 'disable_xmlrpc',
            'application_passwords' => 'disable_application_passwords',
            'file_editors' => 'block_file_editors',
            'display_errors' => 'suppress_display_errors',
        ];

        $setting = $map[$key] ?? '';
        $policy_blocks = $setting !== '' && !empty($policy[$setting]);
        $bastion_active = $setting !== '' && !empty($runtime[$setting]);
        $external_selected = ($ownership[$key] ?? '') === 'external';
        $external_source = is_array($external) ? (string) ($external['source'] ?? '') : '';
        $external_protected = is_array($external)
            && !empty($external['protected'])
            && self::preflight_source_is_identified($external_source);

        $protected = $bastion_active || ($external_selected && $external_protected);
        $manager = $bastion_active
            ? 'bastion'
            : (($external_selected && $external_protected) ? 'external' : 'none');

        return [
            'policy_blocks' => $policy_blocks,
            'protected' => $protected,
            'manager' => $manager,
            'external_selected' => $external_selected,
            'external_detected' => $external_protected,
            'source' => $external_source,
        ];
    }

    public function get_diagnostics(): array
    {
        $profile = self::get_profile();
        $settings = self::get_effective_settings($profile);
        $xmlrpc_state = $this->get_rule_state('xmlrpc', $profile);
        $app_password_state = $this->get_rule_state('application_passwords', $profile);

        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $wp_debug_display_configured = defined('WP_DEBUG_DISPLAY')
            ? (bool) WP_DEBUG_DISPLAY
            : null;

        $effective_display_errors = filter_var(
            ini_get('display_errors'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($effective_display_errors === null) {
            $effective_display_errors = (string) ini_get('display_errors') !== '0'
                && strtolower((string) ini_get('display_errors')) !== 'off';
        }

        return [
            [
                'key'    => 'profile',
                'label'  => __('Perfil de hardening', 'bastionwp'),
                'status' => $profile === self::PROFILE_UNCONFIGURED ? 'warning' : 'ok',
                'value'  => $profile === self::PROFILE_UNCONFIGURED
                    ? __('Não configurado', 'bastionwp')
                    : (self::get_profiles()[$profile]['label'] ?? $profile),
                'help'   => '',
            ],
            [
                'key'    => 'https',
                'label'  => __('HTTPS', 'bastionwp'),
                'status' => is_ssl() ? 'ok' : 'warning',
                'value'  => is_ssl() ? __('Ativo', 'bastionwp') : __('Não detectado', 'bastionwp'),
                'help'   => is_ssl() ? '' : __('Ative SSL/HTTPS no servidor e force HTTPS no domínio.', 'bastionwp'),
            ],
            [
                'key'    => 'wp_debug',
                'label'  => __('WP_DEBUG', 'bastionwp'),
                'status' => (
                    in_array($profile, [self::PROFILE_PRODUCTION, self::PROFILE_LOCKED], true)
                    && $wp_debug
                ) ? 'warning' : 'ok',
                'value'  => $wp_debug ? __('Ativo', 'bastionwp') : __('Desativado', 'bastionwp'),
                'help'   => $wp_debug
                    ? __('Para correção definitiva em produção, defina WP_DEBUG como false no wp-config.php.', 'bastionwp')
                    : '',
            ],
            [
                'key'    => 'display_errors',
                'label'  => __('WP_DEBUG_DISPLAY / display_errors', 'bastionwp'),
                'status' => $effective_display_errors ? 'warning' : 'ok',
                'value'  => $effective_display_errors
                    ? __('Exibição efetiva está ativa', 'bastionwp')
                    : (
                        $wp_debug_display_configured === true
                            ? __('Suprimida pelo BastionWP; wp-config.php ainda define WP_DEBUG_DISPLAY=true', 'bastionwp')
                            : __('Suprimida', 'bastionwp')
                    ),
                'help'   => $effective_display_errors
                    ? __('Use “Corrigir agora” para suprimir a exibição em runtime. Para correção definitiva, defina WP_DEBUG_DISPLAY como false no wp-config.php.', 'bastionwp')
                    : (
                        $wp_debug_display_configured === true
                            ? __('O site está protegido em runtime. Para eliminar o alerta de configuração, altere WP_DEBUG_DISPLAY para false no wp-config.php.', 'bastionwp')
                            : ''
                    ),
                'action' => $effective_display_errors ? 'fix_display_errors' : '',
            ],
            [
                'key'    => 'xmlrpc',
                'label'  => __('XML-RPC', 'bastionwp'),
                'status' => ($xmlrpc_state['policy_blocks'] && !$xmlrpc_state['protected']) ? 'warning' : 'ok',
                'value'  => $xmlrpc_state['protected']
                    ? ($xmlrpc_state['manager'] === 'external'
                        ? __('Bloqueado — proteção externa detectada', 'bastionwp')
                        : __('Bloqueado pelo BastionWP', 'bastionwp'))
                    : ($xmlrpc_state['policy_blocks']
                        ? __('Proteção esperada, mas não detectada', 'bastionwp')
                        : __('Permitido pelo perfil', 'bastionwp')),
                'help'   => $xmlrpc_state['manager'] === 'external' && !empty($xmlrpc_state['source'])
                    ? sprintf(__('Origem detectada: %s', 'bastionwp'), $xmlrpc_state['source'])
                    : '',
            ],
            [
                'key'    => 'application_passwords',
                'label'  => __('Application Passwords', 'bastionwp'),
                'status' => ($app_password_state['policy_blocks'] && !$app_password_state['protected']) ? 'warning' : 'ok',
                'value'  => $app_password_state['protected']
                    ? ($app_password_state['manager'] === 'external'
                        ? __('Bloqueadas — proteção externa detectada', 'bastionwp')
                        : __('Bloqueadas pelo BastionWP', 'bastionwp'))
                    : ($app_password_state['policy_blocks']
                        ? __('Proteção esperada, mas não detectada', 'bastionwp')
                        : __('Permitidas pelo perfil', 'bastionwp')),
                'help'   => $app_password_state['manager'] === 'external' && !empty($app_password_state['source'])
                    ? sprintf(__('Origem detectada: %s', 'bastionwp'), $app_password_state['source'])
                    : '',
            ],
            [
                'key'    => 'comments',
                'label'  => __('Comentários', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['disable_comments']
                    ? __('Desativados pelo BastionWP', 'bastionwp')
                    : __('Permitidos', 'bastionwp'),
                'help'   => '',
            ],
            [
                'key'    => 'client_dashboard',
                'label'  => __('Menu Painel para Cliente Protegido', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['hide_client_dashboard']
                    ? __('Oculto', 'bastionwp')
                    : __('Visível', 'bastionwp'),
                'help'   => '',
            ],
            [
                'key'    => 'file_editors',
                'label'  => __('Editor de arquivos de tema/plugin', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['block_file_editors']
                    ? __('Bloqueado pelo BastionWP', 'bastionwp')
                    : __('Permitido pelo perfil', 'bastionwp'),
                'help'   => '',
            ],
            [
                'key'    => 'manual_infrastructure',
                'label'  => __('Alterações manuais de infraestrutura', 'bastionwp'),
                'status' => 'ok',
                'value'  => $settings['block_manual_infrastructure_changes']
                    ? __('Bloqueadas', 'bastionwp')
                    : __('Permitidas ao Developer', 'bastionwp'),
                'help'   => '',
            ],
        ];
    }

    public function disable_comment_support(): void
    {
        foreach (get_post_types([], 'names') as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
            }

            if (post_type_supports($post_type, 'trackbacks')) {
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    public function hide_comments_menu(): void
    {
        remove_menu_page('edit-comments.php');
    }

    public function hide_comments_admin_bar(WP_Admin_Bar $admin_bar): void
    {
        $admin_bar->remove_node('comments');
    }

    public function closed_comment_status(): string
    {
        return 'closed';
    }

    public function filter_xmlrpc_methods(array $methods): array
    {
        // Quando o perfil desativa XML-RPC, nenhum método fica disponível.
        // O endpoint pode responder com fault, mas não há método operacional.
        return [];
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

    private static function valid_profile_keys(): array
    {
        return [
            self::PROFILE_DEVELOPMENT,
            self::PROFILE_STAGING,
            self::PROFILE_PRODUCTION,
            self::PROFILE_LOCKED,
        ];
    }

    private static function persist_settings(array $settings): bool
    {
        $current = self::raw_settings();

        if ($current === $settings) {
            return true;
        }

        $updated = update_option(self::SETTINGS_OPTION, $settings, false);

        if ($updated) {
            return true;
        }

        return self::raw_settings() === $settings;
    }

    private static function raw_settings(): array
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        return is_array($settings) ? $settings : [];
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
        if (class_exists('BastionWP_Update_Manager') && BastionWP_Update_Manager::is_internal_update_running()) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return true;
        }

        return defined('DOING_CRON') && DOING_CRON;
    }
}
