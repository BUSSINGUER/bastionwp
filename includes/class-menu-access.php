<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controle de acesso administrativo por usuário.
 *
 * V0.5.0:
 * - Não existem perfis compartilhados.
 * - Cada Gerenciador do Cliente possui sua própria política.
 * - O catálogo é capturado no contexto do Developer.
 * - Capabilities de plugins selecionados são concedidas apenas:
 *   a) enquanto o WordPress/plugins constroem o admin_menu; e
 *   b) quando o usuário está dentro de uma rota explicitamente liberada.
 *
 * Isso permite que plugins que exigem manage_options para registrar suas
 * páginas apareçam, sem transformar manage_options em uma capability global
 * do Gerenciador do Cliente.
 */
final class BastionWP_Menu_Access
{
    public const USER_MODE_META = 'bastionwp_access_mode';
    public const USER_ALLOWED_META = 'bastionwp_allowed_menus';

    public const MODE_STRICT = 'strict';
    public const MODE_CUSTOM = 'custom';

    private const LEGACY_MODE_OPTION = 'bastionwp_client_access_mode';
    private const LEGACY_ALLOWED_OPTION = 'bastionwp_client_allowed_menus';
    private const MIGRATION_OPTION = 'bastionwp_access_migrated_050';
    private const CATALOG_OPTION = 'bastionwp_menu_catalog_snapshot';

    public static function get_user_mode(int $user_id): string
    {
        $mode = (string) get_user_meta($user_id, self::USER_MODE_META, true);

        return in_array($mode, [self::MODE_STRICT, self::MODE_CUSTOM], true)
            ? $mode
            : self::MODE_STRICT;
    }

    public static function get_user_allowed_groups(int $user_id): array
    {
        $groups = get_user_meta($user_id, self::USER_ALLOWED_META, true);

        return is_array($groups) ? $groups : [];
    }

    public static function save_user_configuration(int $user_id, string $mode, array $selected_ids): bool
    {
        if (!BastionWP_Users::is_client_manager_user_id($user_id)) {
            return false;
        }

        if (BastionWP_Users::is_developer($user_id)) {
            return false;
        }

        $mode = in_array($mode, [self::MODE_STRICT, self::MODE_CUSTOM], true)
            ? $mode
            : self::MODE_STRICT;

        update_user_meta($user_id, self::USER_MODE_META, $mode);

        if ($mode === self::MODE_STRICT) {
            update_user_meta($user_id, self::USER_ALLOWED_META, []);
            return true;
        }

        // admin-post.php não monta $menu/$submenu como uma página normal do wp-admin.
        // Por isso o salvamento deve usar o snapshot capturado previamente
        // no painel do Developer, e nunca reconstruir o catálogo aqui.
        $catalog = self::get_catalog_snapshot();
        $allowed = [];

        foreach ($selected_ids as $id) {
            $id = sanitize_key((string) $id);

            if (isset($catalog[$id])) {
                $allowed[$id] = $catalog[$id];
            }
        }

        update_user_meta($user_id, self::USER_ALLOWED_META, $allowed);

        return true;
    }

    /**
     * Migra a política global antiga para os Client Managers já existentes.
     *
     * Se havia um único usuário de teste com JoinChat/Site Kit marcados,
     * ele preservará essa seleção na primeira execução da V0.5.0.
     */
    public static function migrate_legacy_configuration(): void
    {
        if (get_option(self::MIGRATION_OPTION, false)) {
            return;
        }

        $legacy_mode = get_option(self::LEGACY_MODE_OPTION, self::MODE_STRICT);
        $legacy_mode = in_array($legacy_mode, [self::MODE_STRICT, self::MODE_CUSTOM], true)
            ? $legacy_mode
            : self::MODE_STRICT;

        $legacy_groups = get_option(self::LEGACY_ALLOWED_OPTION, []);
        $legacy_groups = is_array($legacy_groups) ? $legacy_groups : [];

        foreach (BastionWP_Users::get_client_managers() as $user) {
            $user_id = (int) $user->ID;

            if (get_user_meta($user_id, self::USER_MODE_META, true) === '') {
                update_user_meta($user_id, self::USER_MODE_META, $legacy_mode);
            }

            if (get_user_meta($user_id, self::USER_ALLOWED_META, true) === '') {
                update_user_meta($user_id, self::USER_ALLOWED_META, $legacy_groups);
            }
        }

        update_option(self::MIGRATION_OPTION, 1, false);
    }

    public static function refresh_catalog_snapshot(): array
    {
        $catalog = self::build_catalog();

        if (!empty($catalog)) {
            update_option(self::CATALOG_OPTION, $catalog, false);
        }

        return $catalog;
    }

    public static function get_catalog_snapshot(): array
    {
        $catalog = get_option(self::CATALOG_OPTION, []);

        return is_array($catalog) ? $catalog : [];
    }

    /**
     * Retorna os grupos efetivamente ativos para um usuário.
     */
    public static function get_user_active_groups(int $user_id): array
    {
        if (self::get_user_mode($user_id) !== self::MODE_CUSTOM) {
            return [];
        }

        return self::get_user_allowed_groups($user_id);
    }

    private static function resolve_entry_slug(string $top_slug, array $submenu_items): string
    {
        foreach ($submenu_items as $subitem) {
            if (!is_array($subitem) || !isset($subitem[2])) {
                continue;
            }

            $subslug = (string) $subitem[2];

            if ($subslug !== '') {
                return $subslug;
            }
        }

        return $top_slug;
    }

    private static function is_site_kit_group(string $slug): bool
    {
        return strtolower($slug) === 'googlesitekit-dashboard';
    }

    private static function site_kit_routes(): array
    {
        return [
            [
                'path'        => 'admin.php',
                'query'       => ['page' => 'googlesitekit-dashboard'],
                'capability'  => 'googlesitekit_view_dashboard',
                'source_slug' => 'googlesitekit-dashboard',
                'parent_slug' => '',
            ],
            [
                'path'        => 'admin.php',
                'query'       => ['page' => 'googlesitekit-splash'],
                'capability'  => 'googlesitekit_view_splash',
                'source_slug' => 'googlesitekit-splash',
                'parent_slug' => 'googlesitekit-dashboard',
            ],
        ];
    }

    /**
     * Catálogo de menus detectado no painel do Developer.
     *
     * Guarda:
     * - nome;
     * - slug;
     * - capability;
     * - rotas do menu/submenus;
     * - capabilities relacionadas.
     */
    public static function build_catalog(): array
    {
        global $menu, $submenu;

        if (!is_array($menu)) {
            return [];
        }

        $catalog = [];

        foreach ($menu as $item) {
            if (!is_array($item) || !isset($item[0], $item[1], $item[2])) {
                continue;
            }

            $label = self::clean_label((string) $item[0]);
            $capability = sanitize_key((string) $item[1]);
            $slug = (string) $item[2];

            if ($label === '' || $slug === '' || str_starts_with($slug, 'separator')) {
                continue;
            }

            if (self::is_safe_core_slug($slug) || self::is_critical_slug($slug)) {
                continue;
            }

            $id = self::make_group_id($slug);
            $routes = [];
            $capabilities = [];
            $submenu_items = isset($submenu[$slug]) && is_array($submenu[$slug])
                ? $submenu[$slug]
                : [];

            if ($capability !== '') {
                $capabilities[] = $capability;
            }

            $route = self::route_from_slug($slug, $capability);

            if ($route !== null) {
                $routes[] = $route;
                $routes = array_merge($routes, self::expand_route($route));
            }

            if (!empty($submenu_items)) {
                foreach ($submenu_items as $subitem) {
                    if (!is_array($subitem) || !isset($subitem[0], $subitem[1], $subitem[2])) {
                        continue;
                    }

                    $subcap = sanitize_key((string) $subitem[1]);
                    $subslug = (string) $subitem[2];

                    if ($subcap !== '') {
                        $capabilities[] = $subcap;
                    }

                    $subroute = self::route_from_slug($subslug, $subcap, $slug);

                    if ($subroute !== null) {
                        $routes[] = $subroute;
                        $routes = array_merge($routes, self::expand_route($subroute));
                    }
                }
            }

            $native_permissions_only = self::is_site_kit_group($slug);

            if ($native_permissions_only) {
                // O Site Kit possui um modelo próprio de autenticação e Dashboard Sharing.
                // O BastionWP não deve contornar esse modelo com capabilities sintéticas.
                $routes = self::site_kit_routes();
            }

            $catalog[$id] = [
                'id'                      => $id,
                'label'                   => $label,
                'top_slug'                => $slug,
                'entry_slug'              => $slug,
                'capability'              => $capability,
                'capabilities'            => array_values(
                    array_filter(
                        array_unique(array_map('sanitize_key', $capabilities))
                    )
                ),
                'routes'                  => self::deduplicate_routes($routes),
                'native_permissions_only' => $native_permissions_only,
            ];
        }

        uasort(
            $catalog,
            static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label'])
        );

        return $catalog;
    }

    /**
     * Durante admin_menu, concede temporariamente as capabilities dos menus
     * selecionados para que plugins terceiros registrem callbacks e páginas.
     *
     * Fora de admin_menu, essas capabilities NÃO são concedidas por esta regra.
     */
    public static function grant_menu_build_capabilities(array $allcaps, int $user_id): array
    {
        if (!doing_action('admin_menu')) {
            return $allcaps;
        }

        if (self::get_user_mode($user_id) !== self::MODE_CUSTOM) {
            return $allcaps;
        }

        foreach (self::get_user_allowed_groups($user_id) as $group) {
            if (!empty($group['native_permissions_only'])) {
                continue;
            }

            $capabilities = isset($group['capabilities']) && is_array($group['capabilities'])
                ? $group['capabilities']
                : [];

            // Compatibilidade com configurações salvas nas versões anteriores.
            if (empty($capabilities) && !empty($group['capability'])) {
                $capabilities[] = $group['capability'];
            }

            foreach ($capabilities as $capability) {
                $capability = sanitize_key((string) $capability);

                if ($capability !== '') {
                    $allcaps[$capability] = true;
                }
            }
        }

        return $allcaps;
    }

    /**
     * Na rota autorizada, libera as capabilities associadas somente enquanto
     * aquela página está sendo processada.
     */
    public static function grant_route_capabilities(array $allcaps, int $user_id): array
    {
        $group = self::current_request_matching_group($user_id);

        if ($group === null) {
            return $allcaps;
        }

        if (!empty($group['native_permissions_only'])) {
            return $allcaps;
        }

        $capabilities = isset($group['capabilities']) && is_array($group['capabilities'])
            ? $group['capabilities']
            : [];

        if (empty($capabilities) && !empty($group['capability'])) {
            $capabilities[] = $group['capability'];
        }

        foreach ($capabilities as $capability) {
            $capability = sanitize_key((string) $capability);

            if ($capability !== '') {
                $allcaps[$capability] = true;
            }
        }

        return $allcaps;
    }

    public static function is_current_request_allowed(int $user_id): bool
    {
        if (self::is_safe_editorial_request()) {
            return true;
        }

        if (self::is_async_or_action_endpoint()) {
            // Não concede capabilities extras aqui.
            // A própria ação AJAX/admin-post ainda precisa passar pela sua
            // autorização nativa. Adaptadores específicos podem ser criados
            // futuramente para plugins que necessitem disso.
            return true;
        }

        return self::current_request_matching_group($user_id) !== null;
    }

    public static function is_critical_request(): bool
    {
        global $pagenow;

        $pagenow = (string) $pagenow;

        $blocked = [
            'plugins.php',
            'plugin-install.php',
            'plugin-editor.php',
            'themes.php',
            'theme-install.php',
            'theme-editor.php',
            'tools.php',
            'options-general.php',
            'options-writing.php',
            'options-reading.php',
            'options-discussion.php',
            'options-media.php',
            'options-permalink.php',
            'options-privacy.php',
            'users.php',
            'user-new.php',
            'update-core.php',
        ];

        if (in_array($pagenow, $blocked, true)) {
            return true;
        }

        if ($pagenow === 'admin.php') {
            $page_raw = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
            $page = strtolower($page_raw);

            if (
                $page === 'bastionwp'
                || str_starts_with($page, 'wordfence')
                || $page === 'wfls'
                || str_starts_with($page, 'wfls_')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ajusta somente a apresentação dos menus depois que todos os plugins
     * tiveram oportunidade de registrá-los.
     */
    public static function apply_menu_visibility(int $user_id): void
    {
        global $menu, $submenu;

        if (!is_array($menu)) {
            return;
        }

        $mode = self::get_user_mode($user_id);
        $allowed = $mode === self::MODE_CUSTOM
            ? self::get_user_allowed_groups($user_id)
            : [];

        $selected_slugs = [];
        $selected_groups = [];

        foreach ($allowed as $group) {
            if (!empty($group['top_slug'])) {
                $top_slug = (string) $group['top_slug'];
                $selected_slugs[] = $top_slug;
                $selected_groups[$top_slug] = $group;
            }
        }

        $selected_slugs = array_values(array_unique($selected_slugs));

        foreach ($menu as $index => &$item) {
            if (!is_array($item) || !isset($item[2])) {
                continue;
            }

            $slug = (string) $item[2];

            if ($slug === '' || str_starts_with($slug, 'separator')) {
                continue;
            }

            if (self::is_safe_core_slug($slug)) {
                continue;
            }

            if (self::is_critical_slug($slug)) {
                unset($menu[$index]);
                unset($submenu[$slug]);
                continue;
            }

            if ($mode === self::MODE_CUSTOM && in_array($slug, $selected_slugs, true)) {
                $selected_group = $selected_groups[$slug] ?? [];

                if (!empty($selected_group['native_permissions_only'])) {
                    // Plugins com modelo próprio de permissões (Site Kit nesta versão)
                    // permanecem totalmente sob o controle do plugin de origem.
                    // Não alteramos slug nem capability do menu.
                    continue;
                }

                // Para plugins genéricos, depois do registro do callback usando a
                // capability original, reduzimos somente a capability visual.
                $item[1] = 'read';

                if (isset($submenu[$slug]) && is_array($submenu[$slug])) {
                    foreach ($submenu[$slug] as &$subitem) {
                        if (!is_array($subitem) || !isset($subitem[1], $subitem[2])) {
                            continue;
                        }

                        if (self::submenu_slug_is_allowed($slug, (string) $subitem[2], $allowed)) {
                            $subitem[1] = 'read';
                        } else {
                            $subitem[1] = 'do_not_allow';
                        }
                    }
                    unset($subitem);
                }

                continue;
            }

            unset($menu[$index]);
            unset($submenu[$slug]);
        }
        unset($item);
    }

    public static function safe_core_slugs(): array
    {
        return [
            'index.php',
            'edit.php',
            'upload.php',
            'edit.php?post_type=page',
            'edit-comments.php',
            'profile.php',
        ];
    }

    public static function critical_slugs(): array
    {
        return [
            'plugins.php',
            'plugin-install.php',
            'plugin-editor.php',
            'themes.php',
            'theme-install.php',
            'theme-editor.php',
            'tools.php',
            'options-general.php',
            'users.php',
            'user-new.php',
            'update-core.php',
            'bastionwp',
        ];
    }

    public static function is_safe_core_slug(string $slug): bool
    {
        return in_array($slug, self::safe_core_slugs(), true);
    }

    public static function is_critical_slug(string $slug): bool
    {
        if (in_array($slug, self::critical_slugs(), true)) {
            return true;
        }

        $normalized = strtolower($slug);

        // Wordfence é uma área técnica e nunca deve ser delegável ao
        // Gerenciador do Cliente pelo seletor de menus.
        if (
            str_starts_with($normalized, 'wordfence')
            || $normalized === 'wfls'
            || str_starts_with($normalized, 'wfls_')
        ) {
            return true;
        }

        return false;
    }

    private static function current_request_matching_group(int $user_id): ?array
    {
        if (self::get_user_mode($user_id) !== self::MODE_CUSTOM) {
            return null;
        }

        foreach (self::get_user_allowed_groups($user_id) as $group) {
            if (empty($group['routes']) || !is_array($group['routes'])) {
                continue;
            }

            foreach ($group['routes'] as $route) {
                if (self::route_matches_request($route)) {
                    return $group;
                }
            }
        }

        return null;
    }

    private static function submenu_slug_is_allowed(string $parent, string $slug, array $allowed): bool
    {
        foreach ($allowed as $group) {
            if (($group['top_slug'] ?? '') !== $parent || empty($group['routes'])) {
                continue;
            }

            foreach ($group['routes'] as $route) {
                if (($route['source_slug'] ?? '') === $slug) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function route_from_slug(string $slug, string $capability, string $parent = ''): ?array
    {
        if ($slug === '' || preg_match('#^https?://#i', $slug)) {
            return null;
        }

        $path = $slug;
        $query = [];

        if (str_contains($slug, '?')) {
            [$path, $query_string] = explode('?', $slug, 2);
            parse_str($query_string, $query);
        } elseif (!str_ends_with($slug, '.php')) {
            $path = 'admin.php';
            $query = ['page' => $slug];
        }

        $safe_query = [];

        foreach ($query as $key => $value) {
            if (is_scalar($value)) {
                $safe_query[sanitize_key((string) $key)] = sanitize_text_field((string) $value);
            }
        }

        return [
            'path'        => sanitize_file_name($path),
            'query'       => $safe_query,
            'capability'  => $capability,
            'source_slug' => $slug,
            'parent_slug' => $parent,
        ];
    }

    private static function expand_route(array $route): array
    {
        $expanded = [];

        if (
            ($route['path'] ?? '') === 'edit.php'
            && !empty($route['query']['post_type'])
            && !in_array($route['query']['post_type'], ['post', 'page'], true)
        ) {
            $post_type = sanitize_key((string) $route['query']['post_type']);

            $expanded[] = [
                'path'        => 'post-new.php',
                'query'       => ['post_type' => $post_type],
                'capability'  => $route['capability'],
                'source_slug' => $route['source_slug'],
                'parent_slug' => $route['parent_slug'],
            ];
        }

        return $expanded;
    }

    private static function route_matches_request(array $route): bool
    {
        global $pagenow;

        if (($route['path'] ?? '') !== (string) $pagenow) {
            return false;
        }

        $query = isset($route['query']) && is_array($route['query']) ? $route['query'] : [];

        foreach ($query as $key => $expected) {
            $actual = isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';

            if ((string) $actual !== (string) $expected) {
                return false;
            }
        }

        return true;
    }

    private static function is_safe_editorial_request(): bool
    {
        global $pagenow;

        $pagenow = (string) $pagenow;

        if (in_array(
            $pagenow,
            ['index.php', 'upload.php', 'media-new.php', 'edit-comments.php', 'comment.php', 'profile.php'],
            true
        )) {
            return true;
        }

        if (in_array($pagenow, ['edit.php', 'post-new.php'], true)) {
            $post_type = isset($_GET['post_type'])
                ? sanitize_key(wp_unslash($_GET['post_type']))
                : 'post';

            return in_array($post_type, ['post', 'page'], true);
        }

        if ($pagenow === 'post.php') {
            $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

            if ($post_id <= 0) {
                return true;
            }

            return in_array(get_post_type($post_id), ['post', 'page'], true);
        }

        return false;
    }

    private static function is_async_or_action_endpoint(): bool
    {
        global $pagenow;

        return in_array(
            (string) $pagenow,
            ['admin-ajax.php', 'async-upload.php', 'admin-post.php'],
            true
        );
    }

    private static function clean_label(string $label): string
    {
        $label = wp_strip_all_tags($label);
        $label = preg_replace('/\s+\d+\s*$/', '', $label);

        return trim((string) $label);
    }

    private static function make_group_id(string $slug): string
    {
        return 'menu_' . substr(hash('sha256', $slug), 0, 16);
    }

    private static function deduplicate_routes(array $routes): array
    {
        $unique = [];

        foreach ($routes as $route) {
            $key = md5(
                wp_json_encode([
                    $route['path'] ?? '',
                    $route['query'] ?? [],
                    $route['capability'] ?? '',
                    $route['source_slug'] ?? '',
                ])
            );

            $unique[$key] = $route;
        }

        return array_values($unique);
    }
}
