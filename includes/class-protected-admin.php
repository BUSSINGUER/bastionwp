<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Administrador Protegido.
 *
 * Este nível usa a role Administrator real do WordPress por escolha explícita
 * do Developer. O BastionWP não mascara esse privilégio: em vez disso aplica
 * bloqueios adicionais selecionáveis por usuário.
 */
final class BastionWP_Protected_Admin
{
    public const ENABLED_META = 'bastionwp_protected_admin';
    public const POLICY_META = 'bastionwp_protected_admin_policy';

    public function __construct()
    {
        add_filter('user_has_cap', [$this, 'filter_capabilities'], 9500, 4);
        add_filter('map_meta_cap', [$this, 'protect_other_administrators'], 15, 4);
        add_filter('rest_pre_dispatch', [$this, 'block_restricted_rest'], 1, 3);
        add_action('admin_init', [$this, 'block_restricted_admin_routes'], 2);
        add_action('admin_menu', [$this, 'hide_restricted_menus'], 9999);
    }

    public static function default_policy(): array
    {
        return [
            'block_file_editor'       => true,
            'block_code_tools'        => true,
            'block_plugin_install'    => false,
            'block_plugin_delete'     => false,
            'block_plugin_activation' => false,
            'block_theme_install'     => false,
            'block_theme_delete'      => false,
            'block_theme_activation'  => false,
            'block_users'             => false,
            'block_updates'           => false,
            'protect_bastion'         => true,
            'protect_other_admins'    => true,
        ];
    }

    public static function is_user(int $user_id): bool
    {
        if ($user_id <= 0 || BastionWP_Users::is_developer($user_id)) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user || !in_array('administrator', (array) $user->roles, true)) {
            return false;
        }

        return (bool) get_user_meta($user_id, self::ENABLED_META, true);
    }

    public static function enable_user(int $user_id): bool
    {
        if ($user_id <= 0 || BastionWP_Users::is_developer($user_id)) {
            return false;
        }

        $user = new WP_User($user_id);
        if (!$user->exists()) {
            return false;
        }

        $user->set_role('administrator');
        $user = new WP_User($user_id);

        // Mantém o nível previsível: Administrator padrão + política Bastion.
        foreach ((array) $user->caps as $capability => $granted) {
            if ($capability === 'administrator') {
                continue;
            }
            $user->remove_cap((string) $capability);
        }

        update_user_meta($user_id, self::ENABLED_META, 1);
        if (get_user_meta($user_id, self::POLICY_META, true) === '') {
            update_user_meta($user_id, self::POLICY_META, self::default_policy());
        }

        delete_user_meta($user_id, BastionWP_Menu_Access::USER_MODE_META);
        delete_user_meta($user_id, BastionWP_Menu_Access::USER_ALLOWED_META);

        return true;
    }

    public static function disable_user(int $user_id): void
    {
        delete_user_meta($user_id, self::ENABLED_META);
        delete_user_meta($user_id, self::POLICY_META);
    }

    public static function get_policy(int $user_id): array
    {
        $saved = get_user_meta($user_id, self::POLICY_META, true);
        if (!is_array($saved)) {
            $saved = [];
        }

        $policy = self::default_policy();
        foreach ($policy as $key => $default) {
            if (array_key_exists($key, $saved)) {
                $policy[$key] = (bool) $saved[$key];
            }
        }

        return $policy;
    }

    public static function save_policy(int $user_id, array $policy): bool
    {
        if (!self::is_user($user_id)) {
            return false;
        }

        $clean = [];
        foreach (self::default_policy() as $key => $default) {
            $clean[$key] = !empty($policy[$key]);
        }

        $updated = update_user_meta($user_id, self::POLICY_META, $clean);
        return $updated !== false || self::get_policy($user_id) === $clean;
    }

    public static function get_users(): array
    {
        return get_users([
            'role'       => 'administrator',
            'meta_key'   => self::ENABLED_META,
            'meta_value' => '1',
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        ]);
    }

    public function filter_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if (!$user->exists() || !self::is_user((int) $user->ID)) {
            return $allcaps;
        }

        $policy = self::get_policy((int) $user->ID);
        $blocked = [];

        if (!empty($policy['block_file_editor'])) {
            $blocked = array_merge($blocked, ['edit_plugins', 'edit_themes', 'edit_files']);
        }
        if (!empty($policy['block_plugin_install'])) {
            $blocked[] = 'install_plugins';
        }
        if (!empty($policy['block_plugin_delete'])) {
            $blocked[] = 'delete_plugins';
        }
        if (!empty($policy['block_plugin_activation'])) {
            $blocked = array_merge($blocked, ['activate_plugins', 'deactivate_plugins']);
        }
        if (!empty($policy['block_theme_install'])) {
            $blocked[] = 'install_themes';
        }
        if (!empty($policy['block_theme_delete'])) {
            $blocked[] = 'delete_themes';
        }
        if (!empty($policy['block_theme_activation'])) {
            $blocked[] = 'switch_themes';
        }
        if (!empty($policy['block_users'])) {
            $blocked = array_merge($blocked, [
                'create_users', 'edit_users', 'delete_users', 'promote_users',
                'remove_users', 'list_users',
            ]);
        }
        if (!empty($policy['block_updates'])) {
            $blocked = array_merge($blocked, ['update_core', 'update_plugins', 'update_themes']);
        }

        foreach (array_values(array_unique($blocked)) as $capability) {
            $allcaps[$capability] = false;
        }

        return $allcaps;
    }

    public function protect_other_administrators(array $caps, string $cap, int $user_id, array $args): array
    {
        if (!self::is_user($user_id)) {
            return $caps;
        }

        $policy = self::get_policy($user_id);
        if (empty($policy['protect_other_admins'])) {
            return $caps;
        }

        if (!in_array($cap, ['edit_user', 'delete_user', 'remove_user', 'promote_user'], true)) {
            return $caps;
        }

        $target_user_id = isset($args[0]) ? absint($args[0]) : 0;
        if ($target_user_id <= 0 || $target_user_id === $user_id) {
            return $caps;
        }

        $target = get_userdata($target_user_id);
        if ($target && in_array('administrator', (array) $target->roles, true)) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    public function block_restricted_admin_routes(): void
    {
        $user_id = get_current_user_id();
        if (!self::is_user($user_id)) {
            return;
        }

        $policy = self::get_policy($user_id);
        if (empty($policy['block_code_tools'])) {
            return;
        }

        global $pagenow;
        $pagenow = (string) $pagenow;

        if (in_array($pagenow, ['plugin-editor.php', 'theme-editor.php'], true)) {
            $this->deny(__('O editor de código está bloqueado pela política deste Administrador Protegido.', 'bastionwp'));
        }

        if ($pagenow === 'admin.php') {
            $page = isset($_GET['page']) ? strtolower(sanitize_text_field(wp_unslash($_GET['page']))) : '';
            if ($page === 'snippets' || str_starts_with($page, 'code-snippets')) {
                $this->deny(__('Ferramentas de execução de PHP estão bloqueadas para este usuário.', 'bastionwp'));
            }
        }

        if (in_array($pagenow, ['admin-ajax.php', 'admin-post.php'], true)) {
            $action = isset($_REQUEST['action']) ? strtolower(sanitize_key(wp_unslash($_REQUEST['action']))) : '';
            if ($action !== '' && str_contains($action, 'snippet')) {
                $this->deny(__('A operação de execução de PHP foi bloqueada pela política BastionWP.', 'bastionwp'));
            }
        }
    }

    public function block_restricted_rest($result, WP_REST_Server $server, WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        if (!self::is_user($user_id)) {
            return $result;
        }

        $policy = self::get_policy($user_id);
        if (empty($policy['block_code_tools'])) {
            return $result;
        }

        $route = strtolower((string) $request->get_route());
        if (str_contains($route, 'code-snippets')) {
            return new WP_Error(
                'bastionwp_protected_admin_code_tools_blocked',
                __('Ferramentas de execução de PHP estão bloqueadas para este Administrador Protegido.', 'bastionwp'),
                ['status' => 403]
            );
        }

        return $result;
    }

    public function hide_restricted_menus(): void
    {
        $user_id = get_current_user_id();
        if (!self::is_user($user_id)) {
            return;
        }

        $policy = self::get_policy($user_id);
        if (!empty($policy['block_code_tools'])) {
            remove_menu_page('snippets');
            remove_menu_page('code-snippets');
        }
    }

    private function deny(string $message): void
    {
        wp_die(
            esc_html($message),
            esc_html__('Ação bloqueada pelo BastionWP', 'bastionwp'),
            ['response' => 403, 'back_link' => true]
        );
    }
}
