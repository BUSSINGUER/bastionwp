<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Users
{
    public const DEVELOPER_CAP = 'bastionwp_manage';
    public const CLIENT_MARKER_CAP = 'bastionwp_client_manager';
    public const CLIENT_ROLE = 'bastion_client_manager';
    public const DEVELOPERS_OPTION = 'bastionwp_developers';

    public function __construct()
    {
        add_action('init', [self::class, 'register_client_manager_role'], 5);
    }

    public static function register_client_manager_role(): void
    {
        $caps = [
            'read'                   => true,
            'upload_files'           => true,
            'edit_posts'             => true,
            'edit_others_posts'      => true,
            'edit_published_posts'   => true,
            'publish_posts'          => true,
            'delete_posts'           => true,
            'delete_others_posts'    => true,
            'delete_published_posts' => true,
            'read_private_posts'     => true,
            'edit_pages'             => true,
            'edit_others_pages'      => true,
            'edit_published_pages'   => true,
            'publish_pages'          => true,
            'delete_pages'           => true,
            'delete_others_pages'    => true,
            'delete_published_pages' => true,
            'read_private_pages'     => true,
            'manage_categories'      => true,
            'moderate_comments'      => true,
            self::CLIENT_MARKER_CAP  => true,
        ];

        $role = get_role(self::CLIENT_ROLE);

        if (!$role) {
            add_role(
                self::CLIENT_ROLE,
                __('Gerenciador do Cliente', 'bastionwp'),
                $caps
            );
            return;
        }

        foreach ($caps as $capability => $grant) {
            if ($grant) {
                $role->add_cap($capability);
            }
        }

        // Capabilities técnicas que nunca devem fazer parte desta role.
        foreach (self::forbidden_client_capabilities() as $capability) {
            $role->remove_cap($capability);
        }
    }

    public static function forbidden_client_capabilities(): array
    {
        return [
            'manage_options',
            'update_core',
            'update_plugins',
            'update_themes',
            'install_plugins',
            'activate_plugins',
            'edit_plugins',
            'delete_plugins',
            'install_themes',
            'switch_themes',
            'edit_themes',
            'delete_themes',
            'create_users',
            'edit_users',
            'delete_users',
            'promote_users',
            'remove_users',
        ];
    }

    public static function get_developer_ids(): array
    {
        $ids = get_option(self::DEVELOPERS_OPTION, []);

        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_map('absint', $ids);
        $ids = array_values(array_filter(array_unique($ids)));

        return $ids;
    }

    public static function is_developer(?int $user_id = null): bool
    {
        $user_id = $user_id ?: get_current_user_id();

        if ($user_id <= 0) {
            return false;
        }

        return in_array($user_id, self::get_developer_ids(), true);
    }

    public static function set_primary_developer(int $user_id): bool
    {
        $user = get_userdata($user_id);

        if (!$user || !user_can($user, 'manage_options')) {
            return false;
        }

        $previous_ids = self::get_developer_ids();

        foreach ($previous_ids as $previous_id) {
            if ($previous_id === $user_id) {
                continue;
            }

            $previous = new WP_User($previous_id);
            $previous->remove_cap(self::DEVELOPER_CAP);
        }

        update_option(self::DEVELOPERS_OPTION, [$user_id], false);

        $user->add_cap(self::DEVELOPER_CAP);

        return true;
    }

    public static function ensure_initial_developer(): void
    {
        if (!empty(self::get_developer_ids())) {
            return;
        }

        $current_user_id = get_current_user_id();

        if ($current_user_id > 0 && current_user_can('manage_options')) {
            self::set_primary_developer($current_user_id);
        }
    }

    public static function sync_developer_capabilities(): void
    {
        foreach (self::get_developer_ids() as $developer_id) {
            $user = new WP_User($developer_id);

            if ($user->exists()) {
                $user->add_cap(self::DEVELOPER_CAP);
            }
        }
    }

    public static function assign_client_manager(int $user_id): bool
    {
        if (self::is_developer($user_id)) {
            return false;
        }

        $user = new WP_User($user_id);

        if (!$user->exists()) {
            return false;
        }

        $user->set_role(self::CLIENT_ROLE);

        return true;
    }

    public static function get_administrators(): array
    {
        return get_users([
            'role'    => 'administrator',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ]);
    }

    public static function get_client_candidates(): array
    {
        $developers = self::get_developer_ids();

        return get_users([
            'exclude' => $developers,
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ]);
    }
}
