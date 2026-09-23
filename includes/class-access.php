<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Access
{
    private BastionWP_Users $users;
    private bool $resolving_capability = false;

    public function __construct(BastionWP_Users $users)
    {
        $this->users = $users;

        add_action('admin_menu', [$this, 'apply_client_menu_policy'], 9999);
        add_action('admin_init', [$this, 'block_client_routes'], 1);
        add_filter('user_has_cap', [$this, 'grant_allowed_capabilities'], 20, 4);

        add_filter('map_meta_cap', [$this, 'protect_developer_accounts'], 20, 4);
        add_filter('user_row_actions', [$this, 'filter_developer_row_actions'], 20, 2);
    }

    public function apply_client_menu_policy(): void
    {
        $user_id = $this->current_client_manager_id();

        if ($user_id <= 0) {
            return;
        }

        BastionWP_Menu_Access::apply_menu_visibility($user_id);
    }

    public function block_client_routes(): void
    {
        $user_id = $this->current_client_manager_id();

        if ($user_id <= 0) {
            return;
        }

        if (BastionWP_Menu_Access::is_critical_request()) {
            $this->deny();
        }

        if (!BastionWP_Menu_Access::is_current_request_allowed($user_id)) {
            $this->deny();
        }
    }

    public function grant_allowed_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if ($this->resolving_capability) {
            return $allcaps;
        }

        // NÃO usar user_can/current_user_can/WP_User::has_cap aqui.
        // Estamos dentro de user_has_cap.
        if (
            !$user->exists()
            || empty($allcaps[BastionWP_Users::CLIENT_MARKER_CAP])
        ) {
            return $allcaps;
        }

        $user_id = (int) $user->ID;

        if ($user_id <= 0) {
            return $allcaps;
        }

        $this->resolving_capability = true;

        try {
            // 1) Durante admin_menu, permite que plugins selecionados registrem
            //    suas páginas e callbacks.
            $allcaps = BastionWP_Menu_Access::grant_menu_build_capabilities(
                $allcaps,
                $user_id
            );

            // 2) Fora da construção do menu, libera apenas capabilities da
            //    rota explicitamente selecionada para esse usuário.
            $allcaps = BastionWP_Menu_Access::grant_route_capabilities(
                $allcaps,
                $user_id
            );

            return $allcaps;
        } finally {
            $this->resolving_capability = false;
        }
    }

    public function protect_developer_accounts(array $caps, string $cap, int $user_id, array $args): array
    {
        if (BastionWP_Users::is_developer($user_id)) {
            return $caps;
        }

        if (!in_array($cap, ['edit_user', 'delete_user', 'remove_user', 'promote_user'], true)) {
            return $caps;
        }

        $target_user_id = isset($args[0]) ? absint($args[0]) : 0;

        if ($target_user_id > 0 && BastionWP_Users::is_developer($target_user_id)) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    public function filter_developer_row_actions(array $actions, WP_User $user): array
    {
        if (BastionWP_Users::is_developer()) {
            return $actions;
        }

        if (!BastionWP_Users::is_developer((int) $user->ID)) {
            return $actions;
        }

        unset($actions['edit'], $actions['delete'], $actions['remove'], $actions['resetpassword']);

        return $actions;
    }

    private function current_client_manager_id(): int
    {
        $user = wp_get_current_user();

        if (!$user->exists()) {
            return 0;
        }

        // Fora de user_has_cap, usamos a role para evitar disparar
        // consultas de capability desnecessárias.
        if (!in_array(BastionWP_Users::CLIENT_ROLE, (array) $user->roles, true)) {
            return 0;
        }

        return (int) $user->ID;
    }

    private function deny(): void
    {
        global $pagenow;

        BastionWP_Logger::log(
            'client_route_blocked',
            __('Tentativa de acesso a área bloqueada para Gerenciador do Cliente.', 'bastionwp'),
            'warning',
            [
                'route' => sanitize_file_name((string) $pagenow),
                'page'  => isset($_GET['page'])
                    ? sanitize_text_field(wp_unslash($_GET['page']))
                    : '',
            ]
        );

        wp_die(
            esc_html__('Seu usuário não possui permissão para acessar esta área. O acesso é controlado individualmente pelo Developer no BastionWP.', 'bastionwp'),
            esc_html__('Acesso bloqueado pelo BastionWP', 'bastionwp'),
            ['response' => 403, 'back_link' => true]
        );
    }
}
