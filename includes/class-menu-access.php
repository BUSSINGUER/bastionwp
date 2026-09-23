<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Catálogo e políticas de acesso aos menus administrativos.
 *
 * O modelo da V0.3.0 é:
 * - "Bloqueio total": somente áreas editoriais básicas.
 * - "Personalizado": áreas editoriais básicas + menus explicitamente liberados.
 *
 * Menus críticos de infraestrutura nunca são liberados por esta tela.
 */
final class BastionWP_Menu_Access
{
    public const MODE_OPTION = 'bastionwp_client_access_mode';
    public const ALLOWED_OPTION = 'bastionwp_client_allowed_menus';

    public const MODE_STRICT = 'strict';
    public const MODE_CUSTOM = 'custom';

    public static function get_mode(): string
    {
        $mode = get_option(self::MODE_OPTION, self::MODE_STRICT);

        return in_array($mode, [self::MODE_STRICT, self::MODE_CUSTOM], true)
            ? $mode
            : self::MODE_STRICT;
    }

    public static function get_allowed_groups(): array
    {
        $groups = get_option(self::ALLOWED_OPTION, []);

        return is_array($groups) ? $groups : [];
    }

    public static function save_configuration(string $mode, array $selected_ids): void
    {
        $mode = in_array($mode, [self::MODE_STRICT, self::MODE_CUSTOM], true)
            ? $mode
            : self::MODE_STRICT;

        update_option(self::MODE_OPTION, $mode, false);

        if ($mode === self::MODE_STRICT) {
            update_option(self::ALLOWED_OPTION, [], false);
            return;
        }

        $catalog = self::build_catalog();
        $allowed = [];

        foreach ($selected_ids as $id) {
            $id = sanitize_key((string) $id);

            if (isset($catalog[$id])) {
                $allowed[$id] = $catalog[$id];
            }
        }

        update_option(self::ALLOWED_OPTION, $allowed, false);
    }

    /**
     * Gera catálogo dos menus detectados no wp-admin do Developer.
     *
     * O catálogo guarda as rotas e capabilities no momento do salvamento.
     * Isso permite que o Bastion Core aplique a whitelist mesmo sem a UI.
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
            $route = self::route_from_slug($slug, $capability);

            if ($route !== null) {
                $routes[] = $route;
                $routes = array_merge($routes, self::expand_route($route));
            }

            if (isset($submenu[$slug]) && is_array($submenu[$slug])) {
                foreach ($submenu[$slug] as $subitem) {
                    if (!is_array($subitem) || !isset($subitem[0], $subitem[1], $subitem[2])) {
                        continue;
                    }

                    $subcap = sanitize_key((string) $subitem[1]);
                    $subslug = (string) $subitem[2];
                    $subroute = self::route_from_slug($subslug, $subcap, $slug);

                    if ($subroute !== null) {
                        $routes[] = $subroute;
                        $routes = array_merge($routes, self::expand_route($subroute));
                    }
                }
            }

            $routes = self::deduplicate_routes($routes);

            $catalog[$id] = [
                'id'         => $id,
                'label'      => $label,
                'top_slug'   => $slug,
                'capability' => $capability,
                'routes'     => $routes,
            ];
        }

        uasort(
            $catalog,
            static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label'])
        );

        return $catalog;
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
        return in_array($slug, self::critical_slugs(), true);
    }

    public static function current_request_matches_allowed_route(): ?array
    {
        if (self::get_mode() !== self::MODE_CUSTOM) {
            return null;
        }

        foreach (self::get_allowed_groups() as $group) {
            if (empty($group['routes']) || !is_array($group['routes'])) {
                continue;
            }

            foreach ($group['routes'] as $route) {
                if (self::route_matches_request($route)) {
                    return $route;
                }
            }
        }

        return null;
    }

    public static function is_current_request_allowed(): bool
    {
        if (self::is_safe_editorial_request()) {
            return true;
        }

        if (self::is_async_or_action_endpoint()) {
            // O endpoint em si não concede capability. A ação interna ainda
            // precisa passar pelas verificações do WordPress/plugin.
            return true;
        }

        return self::current_request_matches_allowed_route() !== null;
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
            $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

            if ($page === 'bastionwp') {
                return true;
            }
        }

        return false;
    }

    public static function selected_top_slugs(): array
    {
        $slugs = [];

        foreach (self::get_allowed_groups() as $group) {
            if (!empty($group['top_slug'])) {
                $slugs[] = (string) $group['top_slug'];
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Ajusta os arrays globais de menu apenas para UX.
     * O controle real de rota/capability ocorre separadamente.
     */
    public static function apply_menu_visibility(): void
    {
        global $menu, $submenu;

        if (!is_array($menu)) {
            return;
        }

        $mode = self::get_mode();
        $allowed = $mode === self::MODE_CUSTOM ? self::get_allowed_groups() : [];
        $selected_slugs = self::selected_top_slugs();

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
                // Torna o item visível. A capability real só é concedida
                // quando a rota explicitamente autorizada estiver aberta.
                $item[1] = 'read';

                if (isset($submenu[$slug]) && is_array($submenu[$slug])) {
                    foreach ($submenu[$slug] as &$subitem) {
                        if (is_array($subitem) && isset($subitem[1], $subitem[2])) {
                            if (self::submenu_slug_is_allowed($slug, (string) $subitem[2], $allowed)) {
                                $subitem[1] = 'read';
                            } else {
                                $subitem[1] = 'do_not_allow';
                            }
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

    public static function grant_route_scoped_capabilities(array $allcaps): array
    {
        $route = self::current_request_matches_allowed_route();

        if ($route === null || empty($route['capability'])) {
            return $allcaps;
        }

        $capability = sanitize_key((string) $route['capability']);

        if ($capability !== '') {
            $allcaps[$capability] = true;
        }

        return $allcaps;
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

        if (in_array($pagenow, ['index.php', 'upload.php', 'media-new.php', 'edit-comments.php', 'comment.php', 'profile.php'], true)) {
            return true;
        }

        if ($pagenow === 'edit.php') {
            $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : 'post';

            return in_array($post_type, ['post', 'page'], true);
        }

        if ($pagenow === 'post-new.php') {
            $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : 'post';

            return in_array($post_type, ['post', 'page'], true);
        }

        if ($pagenow === 'post.php') {
            $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

            if ($post_id <= 0) {
                return true;
            }

            $post_type = get_post_type($post_id);

            return in_array($post_type, ['post', 'page'], true);
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
            $key = md5(wp_json_encode([
                $route['path'] ?? '',
                $route['query'] ?? [],
                $route['capability'] ?? '',
                $route['source_slug'] ?? '',
            ]));

            $unique[$key] = $route;
        }

        return array_values($unique);
    }
}
