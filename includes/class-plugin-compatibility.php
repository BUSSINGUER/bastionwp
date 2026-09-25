<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibilidade BastionWP para plugins administrativos.
 *
 * O objetivo não é conceder capabilities administrativas globalmente.
 * Quando o Developer habilita a compatibilidade de um grupo detectado, o
 * BastionWP libera somente as capabilities originais daquele grupo e apenas
 * enquanto a requisição pertence ao mesmo plugin (admin page, AJAX,
 * admin-post ou REST). Operações de infraestrutura continuam fora desse
 * mecanismo e exigem Administrador Protegido.
 */
final class BastionWP_Plugin_Compatibility
{
    public const OPTION = 'bastionwp_plugin_compatibility';

    /** @var array<string,bool> */
    private static array $rest_context = [];

    public function __construct()
    {
        add_filter('user_has_cap', [$this, 'grant_scoped_capabilities'], 9000, 4);
        add_filter('rest_request_before_callbacks', [$this, 'enter_rest_context'], 1, 3);
        add_filter('rest_request_after_callbacks', [$this, 'leave_rest_context'], 9999, 3);
    }

    public static function get_rules(): array
    {
        $rules = get_option(self::OPTION, []);
        return is_array($rules) ? $rules : [];
    }

    public static function is_enabled(string $group_id): bool
    {
        $rules = self::get_rules();
        return !empty($rules[sanitize_key($group_id)]['enabled']);
    }

    public static function can_enable(array $group): bool
    {
        if (empty($group['requires_adapter'])) {
            return false;
        }

        if (!empty($group['native_permissions_only'])) {
            return false;
        }

        if (empty($group['plugin_root'])) {
            return false;
        }

        foreach ((array) ($group['capabilities'] ?? []) as $capability) {
            if (self::is_never_delegable_capability((string) $capability)) {
                return false;
            }
        }

        return true;
    }

    public static function set_enabled(string $group_id, bool $enabled): bool
    {
        $group_id = sanitize_key($group_id);
        $catalog = BastionWP_Menu_Access::get_catalog_snapshot();

        if ($group_id === '' || empty($catalog[$group_id]) || !self::can_enable($catalog[$group_id])) {
            return false;
        }

        $rules = self::get_rules();
        if ($enabled) {
            $group = $catalog[$group_id];
            $rules[$group_id] = [
                'enabled'      => true,
                'plugin_root'  => sanitize_text_field((string) ($group['plugin_root'] ?? '')),
                'plugin_file'  => sanitize_text_field((string) ($group['plugin_file'] ?? '')),
                'label'        => sanitize_text_field((string) ($group['label'] ?? '')),
                'updated_at'   => current_time('mysql'),
                'updated_by'   => get_current_user_id(),
            ];
        } else {
            unset($rules[$group_id]);

            // Remove a seleção deste grupo dos Clientes Protegidos para não
            // deixar um menu visualmente liberado sem a compatibilidade ativa.
            foreach (BastionWP_Users::get_client_managers() as $client_user) {
                $user_id = (int) $client_user->ID;
                $allowed = BastionWP_Menu_Access::get_user_allowed_groups($user_id);
                if (isset($allowed[$group_id])) {
                    unset($allowed[$group_id]);
                    update_user_meta($user_id, BastionWP_Menu_Access::USER_ALLOWED_META, $allowed);
                }
            }
        }

        update_option(self::OPTION, $rules, false);
        return true;
    }

    public static function compatibility_label(array $group): string
    {
        if (!empty($group['native_permissions_only'])) {
            return __('Permissão nativa do plugin', 'bastionwp');
        }
        if (empty($group['requires_adapter'])) {
            return __('Delegação segura genérica', 'bastionwp');
        }
        if (self::is_enabled((string) ($group['id'] ?? ''))) {
            return __('Compatibilidade BastionWP habilitada', 'bastionwp');
        }
        if (self::can_enable($group)) {
            return __('Compatibilidade BastionWP disponível', 'bastionwp');
        }
        return __('Requer Administrador Protegido', 'bastionwp');
    }

    public static function enrich_group(array $group, string $top_slug, array $submenu_items): array
    {
        $origin = self::detect_menu_origin($top_slug, $submenu_items);
        $group['plugin_root'] = $origin['plugin_root'];
        $group['plugin_file'] = $origin['plugin_file'];
        return $group;
    }

    public static function is_capability_allowed_for_request(int $user_id, string $capability): bool
    {
        if ($user_id <= 0 || self::is_never_delegable_capability($capability)) {
            return false;
        }

        if (!BastionWP_Users::is_client_manager_user_id($user_id)) {
            return false;
        }

        if (BastionWP_Menu_Access::get_user_mode($user_id) !== BastionWP_Menu_Access::MODE_CUSTOM) {
            return false;
        }

        foreach (BastionWP_Menu_Access::get_user_allowed_groups($user_id) as $group) {
            $group_id = sanitize_key((string) ($group['id'] ?? ''));
            if ($group_id === '' || !self::is_enabled($group_id)) {
                continue;
            }

            $capabilities = array_map('sanitize_key', (array) ($group['capabilities'] ?? []));
            if (!in_array(sanitize_key($capability), $capabilities, true)) {
                continue;
            }

            if (self::request_belongs_to_group($group)) {
                return true;
            }
        }

        return false;
    }

    public function grant_scoped_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if (!$user->exists() || !in_array(BastionWP_Users::CLIENT_ROLE, (array) $user->roles, true)) {
            return $allcaps;
        }

        foreach ((array) $caps as $capability) {
            $capability = sanitize_key((string) $capability);
            if ($capability !== '' && self::is_capability_allowed_for_request((int) $user->ID, $capability)) {
                $allcaps[$capability] = true;
            }
        }

        return $allcaps;
    }

    public function enter_rest_context($response, array $handler, WP_REST_Request $request)
    {
        self::$rest_context = [];

        $user_id = get_current_user_id();
        if ($user_id <= 0 || !BastionWP_Users::is_client_manager_user_id($user_id)) {
            return $response;
        }

        $callbacks = [];
        if (!empty($handler['callback'])) {
            $callbacks[] = $handler['callback'];
        }
        if (!empty($handler['permission_callback'])) {
            $callbacks[] = $handler['permission_callback'];
        }

        foreach (BastionWP_Menu_Access::get_user_allowed_groups($user_id) as $group) {
            $group_id = sanitize_key((string) ($group['id'] ?? ''));
            $plugin_root = (string) ($group['plugin_root'] ?? '');
            if ($group_id === '' || $plugin_root === '' || !self::is_enabled($group_id)) {
                continue;
            }

            foreach ($callbacks as $callback) {
                if (self::callback_belongs_to_plugin($callback, $plugin_root)) {
                    self::$rest_context[$group_id] = true;
                    break;
                }
            }
        }

        return $response;
    }

    public function leave_rest_context($response, array $handler, WP_REST_Request $request)
    {
        self::$rest_context = [];
        return $response;
    }

    private static function request_belongs_to_group(array $group): bool
    {
        $group_id = sanitize_key((string) ($group['id'] ?? ''));
        $plugin_root = (string) ($group['plugin_root'] ?? '');

        if ($group_id === '' || $plugin_root === '') {
            return false;
        }

        if (!empty(self::$rest_context[$group_id])) {
            return true;
        }

        if (doing_action('admin_menu')) {
            return self::backtrace_belongs_to_plugin($plugin_root);
        }

        global $pagenow;
        $pagenow = (string) $pagenow;

        if (in_array($pagenow, ['admin-ajax.php', 'admin-post.php'], true)) {
            $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';
            if ($action === '') {
                return false;
            }

            $hook = $pagenow === 'admin-ajax.php' ? 'wp_ajax_' . $action : 'admin_post_' . $action;
            return self::hook_belongs_to_plugin($hook, $plugin_root);
        }

        foreach ((array) ($group['routes'] ?? []) as $route) {
            if (self::route_matches_request((array) $route)) {
                return true;
            }
        }

        return false;
    }

    private static function detect_menu_origin(string $top_slug, array $submenu_items): array
    {
        $hooks = [];
        if (function_exists('get_plugin_page_hookname')) {
            $hooks[] = get_plugin_page_hookname($top_slug, '');
            foreach ($submenu_items as $subitem) {
                if (is_array($subitem) && isset($subitem[2])) {
                    $hooks[] = get_plugin_page_hookname((string) $subitem[2], $top_slug);
                }
            }
        }

        foreach (array_filter(array_unique($hooks)) as $hook) {
            $file = self::first_plugin_file_for_hook($hook);
            if ($file !== '') {
                return self::plugin_origin_from_file($file);
            }
        }

        return ['plugin_root' => '', 'plugin_file' => ''];
    }

    private static function first_plugin_file_for_hook(string $hook): string
    {
        global $wp_filter;
        if (empty($wp_filter[$hook]) || !($wp_filter[$hook] instanceof WP_Hook)) {
            return '';
        }

        foreach ($wp_filter[$hook]->callbacks as $callbacks) {
            foreach ($callbacks as $callback_data) {
                $file = self::callback_file($callback_data['function'] ?? null);
                if ($file !== '' && self::path_is_plugin_file($file)) {
                    return $file;
                }
            }
        }
        return '';
    }

    private static function hook_belongs_to_plugin(string $hook, string $plugin_root): bool
    {
        global $wp_filter;
        if (empty($wp_filter[$hook]) || !($wp_filter[$hook] instanceof WP_Hook)) {
            return false;
        }

        foreach ($wp_filter[$hook]->callbacks as $callbacks) {
            foreach ($callbacks as $callback_data) {
                if (self::callback_belongs_to_plugin($callback_data['function'] ?? null, $plugin_root)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function callback_belongs_to_plugin($callback, string $plugin_root): bool
    {
        $file = self::callback_file($callback);
        if ($file === '') {
            return false;
        }

        $origin = self::plugin_origin_from_file($file);
        return $origin['plugin_root'] !== '' && hash_equals($origin['plugin_root'], $plugin_root);
    }

    private static function callback_file($callback): string
    {
        try {
            if (is_string($callback) && function_exists($callback)) {
                return (new ReflectionFunction($callback))->getFileName() ?: '';
            }
            if (is_array($callback) && count($callback) === 2) {
                return (new ReflectionMethod($callback[0], (string) $callback[1]))->getFileName() ?: '';
            }
            if ($callback instanceof Closure) {
                return (new ReflectionFunction($callback))->getFileName() ?: '';
            }
            if (is_object($callback) && is_callable($callback)) {
                return (new ReflectionMethod($callback, '__invoke'))->getFileName() ?: '';
            }
        } catch (ReflectionException $e) {
            return '';
        }
        return '';
    }

    private static function plugin_origin_from_file(string $file): array
    {
        $file = wp_normalize_path($file);
        $plugin_dir = trailingslashit(wp_normalize_path(WP_PLUGIN_DIR));
        if (!str_starts_with($file, $plugin_dir)) {
            return ['plugin_root' => '', 'plugin_file' => ''];
        }

        $relative = ltrim(substr($file, strlen($plugin_dir)), '/');
        $parts = explode('/', $relative);
        $root = count($parts) > 1 ? $parts[0] : $relative;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_file = '';
        foreach (array_keys(get_plugins()) as $candidate) {
            if ($candidate === $relative || str_starts_with($candidate, $root . '/')) {
                $plugin_file = $candidate;
                break;
            }
        }

        return [
            'plugin_root' => sanitize_text_field($root),
            'plugin_file' => sanitize_text_field($plugin_file),
        ];
    }

    private static function path_is_plugin_file(string $file): bool
    {
        return str_starts_with(wp_normalize_path($file), trailingslashit(wp_normalize_path(WP_PLUGIN_DIR)));
    }

    private static function backtrace_belongs_to_plugin(string $plugin_root): bool
    {
        $plugin_base = trailingslashit(wp_normalize_path(WP_PLUGIN_DIR));
        $needle = $plugin_base . $plugin_root;
        $directory_needle = trailingslashit($needle);
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 18) as $frame) {
            $file = isset($frame['file']) ? wp_normalize_path((string) $frame['file']) : '';
            if ($file !== '' && ($file === $needle || str_starts_with($file, $directory_needle))) {
                return true;
            }
        }
        return false;
    }

    private static function route_matches_request(array $route): bool
    {
        global $pagenow;
        if (($route['path'] ?? '') !== (string) $pagenow) {
            return false;
        }
        foreach ((array) ($route['query'] ?? []) as $key => $expected) {
            $actual = isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
            if ((string) $actual !== (string) $expected) {
                return false;
            }
        }
        return true;
    }

    private static function is_never_delegable_capability(string $capability): bool
    {
        return in_array(sanitize_key($capability), [
            BastionWP_Users::DEVELOPER_CAP,
            'install_plugins', 'activate_plugins', 'deactivate_plugins', 'delete_plugins', 'edit_plugins',
            'install_themes', 'switch_themes', 'delete_themes', 'edit_themes',
            'update_core', 'update_plugins', 'update_themes',
            'create_users', 'edit_users', 'delete_users', 'promote_users', 'remove_users',
            'unfiltered_html', 'unfiltered_upload', 'edit_files',
            'manage_network', 'manage_network_options', 'manage_network_plugins', 'manage_network_themes', 'manage_network_users',
            'setup_network', 'delete_site',
        ], true);
    }
}
