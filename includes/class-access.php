<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Access
{
    private BastionWP_Users $users;

    /**
     * Trava defensiva contra reentrada do filtro user_has_cap.
     * Mesmo que uma alteração futura provoque uma nova checagem de capability,
     * o filtro não deve entrar em recursão infinita.
     */
    private bool $resolving_route_capability = false;

    public function __construct(BastionWP_Users $users)
    {
        $this->users = $users;

        add_action('admin_menu', [$this, 'apply_client_menu_policy'], 9999);
        add_action('admin_init', [$this, 'block_client_routes'], 1);
        add_filter('user_has_cap', [$this, 'grant_allowed_route_capabilities'], 20, 4);

        add_filter('map_meta_cap', [$this, 'protect_developer_accounts'], 20, 4);
        add_filter('user_row_actions', [$this, 'filter_developer_row_actions'], 20, 2);
    }

    public function apply_client_menu_policy(): void
    {
        if (!$this->is_client_manager()) {
            return;
        }

        BastionWP_Menu_Access::apply_menu_visibility();
    }

    public function block_client_routes(): void
    {
        if (!$this->is_client_manager()) {
            return;
        }

        if (BastionWP_Menu_Access::is_critical_request()) {
            $this->deny();
        }

        if (!BastionWP_Menu_Access::is_current_request_allowed()) {
            $this->deny();
        }
    }

    public function grant_allowed_route_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if ($this->resolving_route_capability) {
            return $allcaps;
        }

        // IMPORTANTE:
        // Nunca chamar user_can(), current_user_can() ou equivalente aqui.
        // Este método já está dentro do filtro user_has_cap; fazer uma nova
        // consulta de capability aqui causaria recursão do próprio filtro.
        if (!$user->exists() || empty($allcaps[BastionWP_Users::CLIENT_MARKER_CAP])) {
            return $allcaps;
        }

        $this->resolving_route_capability = true;

        try {
            return BastionWP_Menu_Access::grant_route_scoped_capabilities($allcaps);
        } finally {
            $this->resolving_route_capability = false;
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

    private function is_client_manager(): bool
    {
        $user = wp_get_current_user();

        if (!$user->exists()) {
            return false;
        }

        // Fora do filtro user_has_cap, usar has_cap é seguro; ainda assim,
        // preferimos ler as capabilities já calculadas para reduzir hooks.
        return !empty($user->allcaps[BastionWP_Users::CLIENT_MARKER_CAP]);
    }

    private function deny(): void
    {
        wp_die(
            esc_html__('Seu perfil não possui permissão para acessar esta área. O acesso é controlado pelo Developer no BastionWP.', 'bastionwp'),
            esc_html__('Acesso bloqueado pelo BastionWP', 'bastionwp'),
            ['response' => 403, 'back_link' => true]
        );
    }
}
