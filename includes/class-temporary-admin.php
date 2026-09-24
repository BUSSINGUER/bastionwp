<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Temporary_Admin
{
    private const OPTION = 'bastionwp_temp_admin_requests';
    private const MAX_HISTORY = 500;
    private const ACTIVE_OPTION_PREFIX = 'bastionwp_temp_admin_active_';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    private bool $resolving_caps = false;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_client_request_menu'], 9997);
        add_action('admin_post_bastionwp_request_temp_admin', [$this, 'handle_client_request']);
        add_action('admin_notices', [$this, 'client_active_notice']);
        add_filter('user_has_cap', [$this, 'grant_temporary_capabilities'], 12, 4);
    }

    public static function migrate_active_index(): void
    {
        foreach (self::raw_all() as $request) {
            if (
                ($request['status'] ?? '') === self::STATUS_APPROVED
                && (int) ($request['expires_at'] ?? 0) > time()
                && (int) ($request['user_id'] ?? 0) > 0
            ) {
                update_option(
                    self::ACTIVE_OPTION_PREFIX . (int) $request['user_id'],
                    $request,
                    false
                );
            }
        }
    }

    public static function durations(): array
    {
        return [
            30  => __('30 minutos', 'bastionwp'),
            60  => __('1 hora', 'bastionwp'),
            120 => __('2 horas', 'bastionwp'),
        ];
    }

    public function register_client_request_menu(): void
    {
        if (!$this->is_current_client_manager()) {
            return;
        }

        add_menu_page(
            __('Acesso temporário', 'bastionwp'),
            __('Acesso temporário', 'bastionwp'),
            'read',
            'bastionwp-request-admin',
            [$this, 'render_client_request_page'],
            'dashicons-lock',
            99
        );
    }

    public function render_client_request_page(): void
    {
        if (!$this->is_current_client_manager()) {
            wp_die(
                esc_html__('Esta página é exclusiva para Gerenciadores do Cliente.', 'bastionwp'),
                esc_html__('Acesso não permitido', 'bastionwp'),
                ['response' => 403]
            );
        }

        $user_id = get_current_user_id();
        $active = self::get_active_for_user($user_id);
        $pending = self::get_pending_for_user($user_id);
        $message = isset($_GET['bastionwp_request'])
            ? sanitize_key(wp_unslash($_GET['bastionwp_request']))
            : '';

        echo '<div class="wrap bastionwp-client-admin-request">';
        echo '<h1>' . esc_html__('Acesso temporário', 'bastionwp') . '</h1>';
        echo '<div class="card" style="max-width:760px">';

        if ($message === 'sent') {
            echo '<div class="notice notice-success inline"><p>' .
                esc_html__('Solicitação enviada ao Developer responsável pelo site.', 'bastionwp') .
                '</p></div>';
        }

        if ($active) {
            $remaining = max(0, (int) $active['expires_at'] - time());
            $minutes = max(1, (int) ceil($remaining / 60));

            echo '<h2>' . esc_html__('Privilégios temporários de configuração ativos', 'bastionwp') . '</h2>';
            echo '<p>' . esc_html(
                sprintf(
                    _n(
                        'O acesso temporário expira em aproximadamente %d minuto.',
                        'O acesso temporário expira em aproximadamente %d minutos.',
                        $minutes,
                        'bastionwp'
                    ),
                    $minutes
                )
            ) . '</p>';
            echo '<p>' . esc_html__('O acesso é limitado a integrações e operações explicitamente suportadas; áreas técnicas críticas continuam protegidas.', 'bastionwp') . '</p>';
        } elseif ($pending) {
            echo '<h2>' . esc_html__('Solicitação aguardando aprovação', 'bastionwp') . '</h2>';
            echo '<p>' . esc_html__('A solicitação foi registrada. O Developer poderá aprovar por 30 minutos, 1 hora ou 2 horas, ou negar o pedido. A entrega do e-mail depende do servidor.', 'bastionwp') . '</p>';
        } else {
            echo '<h2>' . esc_html__('Solicitar privilégios temporários de configuração', 'bastionwp') . '</h2>';
            echo '<p>' . esc_html__('Use esta solicitação quando uma integração suportada exigir permissões adicionais para configuração. O BastionWP não transforma seu usuário em Administrador e o acesso expira automaticamente.', 'bastionwp') . '</p>';

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="bastionwp_request_temp_admin">';
            wp_nonce_field('bastionwp_request_temp_admin');

            echo '<p><label for="bastionwp_request_reason"><strong>' .
                esc_html__('Motivo da solicitação', 'bastionwp') .
                '</strong></label></p>';
            echo '<textarea id="bastionwp_request_reason" name="reason" rows="4" class="large-text" placeholder="' .
                esc_attr__('Ex.: configurar Google Site Kit, conectar Analytics, configurar integração do plugin...', 'bastionwp') .
                '"></textarea>';

            submit_button(__('Solicitar privilégios temporários de configuração', 'bastionwp'));
            echo '</form>';
        }

        echo '</div></div>';
    }

    public function handle_client_request(): void
    {
        if (!$this->is_current_client_manager()) {
            wp_die(
                esc_html__('Somente Gerenciadores do Cliente podem fazer esta solicitação.', 'bastionwp'),
                esc_html__('Acesso não permitido', 'bastionwp'),
                ['response' => 403]
            );
        }

        check_admin_referer('bastionwp_request_temp_admin');

        $user_id = get_current_user_id();

        if (self::get_active_for_user($user_id) || self::get_pending_for_user($user_id)) {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'bastionwp-request-admin'],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $reason = isset($_POST['reason'])
            ? sanitize_textarea_field(wp_unslash($_POST['reason']))
            : '';
        $reason = function_exists('mb_substr')
            ? mb_substr($reason, 0, 1000)
            : substr($reason, 0, 1000);

        $request = [
            'id'            => wp_generate_uuid4(),
            'user_id'       => $user_id,
            'status'        => self::STATUS_PENDING,
            'reason'        => $reason,
            'requested_at'  => time(),
            'approved_at'   => 0,
            'approved_by'   => 0,
            'duration'      => 0,
            'expires_at'    => 0,
            'denied_at'     => 0,
            'denied_by'     => 0,
            'revoked_at'    => 0,
            'revoked_by'    => 0,
            'email_sent'    => false,
        ];

        $requests = self::get_all();
        $requests[$request['id']] = $request;

        $email_sent = $this->notify_developers($request);
        $requests[$request['id']]['email_sent'] = $email_sent;

        self::save_all($requests);

        $user = get_userdata($user_id);

        BastionWP_Logger::log(
            'temp_admin_requested',
            __('Gerenciador do Cliente solicitou privilégios temporários de configuração.', 'bastionwp'),
            'warning',
            [
                'request_id' => $request['id'],
                'target_user_id' => $user_id,
                'target_user' => $user ? $user->user_login : '',
                'email_sent' => $email_sent,
            ],
            $user_id
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'bastionwp-request-admin',
                    'bastionwp_request' => 'sent',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public static function get_all(): array
    {
        $requests = get_option(self::OPTION, []);

        if (!is_array($requests)) {
            return [];
        }

        $changed = false;
        $expired = [];

        foreach ($requests as $id => &$request) {
            if (
                ($request['status'] ?? '') === self::STATUS_APPROVED
                && !empty($request['expires_at'])
                && (int) $request['expires_at'] <= time()
            ) {
                $request['status'] = self::STATUS_EXPIRED;
                $changed = true;
                $expired[] = [
                    'id'      => (string) $id,
                    'user_id' => (int) ($request['user_id'] ?? 0),
                ];
            }
        }
        unset($request);

        if ($changed) {
            self::save_all($requests);

            foreach ($expired as $expired_request) {
                if ($expired_request['user_id'] > 0) {
                    delete_option(self::ACTIVE_OPTION_PREFIX . $expired_request['user_id']);
                }

                BastionWP_Logger::log(
                    'temp_admin_expired',
                    __('Privilégios temporários expiraram automaticamente.', 'bastionwp'),
                    'info',
                    [
                        'request_id'      => $expired_request['id'],
                        'target_user_id'  => $expired_request['user_id'],
                    ],
                    0,
                    $expired_request['id'],
                    $expired_request['user_id']
                );
            }
        }

        uasort(
            $requests,
            static fn(array $a, array $b): int =>
                (int) ($b['requested_at'] ?? 0) <=> (int) ($a['requested_at'] ?? 0)
        );

        return $requests;
    }

    public static function get_request(string $request_id): ?array
    {
        $requests = self::get_all();

        return isset($requests[$request_id]) && is_array($requests[$request_id])
            ? $requests[$request_id]
            : null;
    }

    public static function get_pending_for_user(int $user_id): ?array
    {
        foreach (self::raw_all() as $request) {
            if (
                (int) ($request['user_id'] ?? 0) === $user_id
                && ($request['status'] ?? '') === self::STATUS_PENDING
            ) {
                return $request;
            }
        }

        return null;
    }

    public static function get_active_for_user(int $user_id): ?array
    {
        if ($user_id <= 0) {
            return null;
        }

        $active = get_option(self::ACTIVE_OPTION_PREFIX . $user_id, []);

        if (!is_array($active) || empty($active['id'])) {
            return null;
        }

        if (
            ($active['status'] ?? '') !== self::STATUS_APPROVED
            || (int) ($active['expires_at'] ?? 0) <= time()
        ) {
            delete_option(self::ACTIVE_OPTION_PREFIX . $user_id);
            self::mark_expired_in_history((string) ($active['id'] ?? ''), $user_id);
            return null;
        }

        return $active;
    }

    public static function is_active_for_user(int $user_id): bool
    {
        return self::get_active_for_user($user_id) !== null;
    }

    public static function approve(string $request_id, int $duration, int $developer_id): bool
    {
        if (!isset(self::durations()[$duration])) {
            return false;
        }

        $requests = self::get_all();

        if (
            empty($requests[$request_id])
            || ($requests[$request_id]['status'] ?? '') !== self::STATUS_PENDING
        ) {
            return false;
        }

        $requests[$request_id]['status'] = self::STATUS_APPROVED;
        $requests[$request_id]['approved_at'] = time();
        $requests[$request_id]['approved_by'] = absint($developer_id);
        $requests[$request_id]['duration'] = $duration;
        $requests[$request_id]['expires_at'] = time() + ($duration * MINUTE_IN_SECONDS);

        self::save_all($requests);
        update_option(
            self::ACTIVE_OPTION_PREFIX . (int) $requests[$request_id]['user_id'],
            $requests[$request_id],
            false
        );

        self::notify_requester(
            $requests[$request_id],
            sprintf(
                __('Sua solicitação foi aprovada por %s.', 'bastionwp'),
                self::durations()[$duration]
            )
        );

        return true;
    }

    public static function deny(string $request_id, int $developer_id): bool
    {
        $requests = self::get_all();

        if (
            empty($requests[$request_id])
            || ($requests[$request_id]['status'] ?? '') !== self::STATUS_PENDING
        ) {
            return false;
        }

        $requests[$request_id]['status'] = self::STATUS_DENIED;
        $requests[$request_id]['denied_at'] = time();
        $requests[$request_id]['denied_by'] = absint($developer_id);

        self::save_all($requests);

        self::notify_requester(
            $requests[$request_id],
            __('Sua solicitação de privilégios temporários de configuração foi negada.', 'bastionwp')
        );

        return true;
    }

    public static function revoke(string $request_id, int $developer_id): bool
    {
        $requests = self::get_all();

        if (
            empty($requests[$request_id])
            || ($requests[$request_id]['status'] ?? '') !== self::STATUS_APPROVED
        ) {
            return false;
        }

        $requests[$request_id]['status'] = self::STATUS_REVOKED;
        $requests[$request_id]['revoked_at'] = time();
        $requests[$request_id]['revoked_by'] = absint($developer_id);
        $requests[$request_id]['expires_at'] = time();

        self::save_all($requests);
        delete_option(self::ACTIVE_OPTION_PREFIX . (int) ($requests[$request_id]['user_id'] ?? 0));

        self::notify_requester(
            $requests[$request_id],
            __('Seus privilégios temporários de configuração foram encerrados pelo Developer.', 'bastionwp')
        );

        return true;
    }

    public function grant_temporary_capabilities(
        array $allcaps,
        array $caps,
        array $args,
        WP_User $user
    ): array {
        if ($this->resolving_caps || !$user->exists()) {
            return $allcaps;
        }

        $user_id = (int) $user->ID;

        if (
            !in_array(BastionWP_Users::CLIENT_ROLE, (array) $user->roles, true)
            || !self::is_active_for_user($user_id)
        ) {
            return $allcaps;
        }

        $this->resolving_caps = true;

        try {
            // A versão auditada NÃO copia a role Administrator. O acesso
            // temporário só concede capabilities de adaptadores conhecidos e
            // somente dentro do contexto correspondente.
            foreach (self::temporary_capabilities_for_current_request($user_id) as $capability) {
                if (!BastionWP_Users::is_forbidden_client_capability($capability)) {
                    $allcaps[$capability] = true;
                }
            }

            // O teto de segurança permanece explícito mesmo se outro plugin
            // tentar adicionar capabilities individuais ao usuário durante a
            // mesma requisição.
            foreach (self::blocked_temporary_capabilities() as $capability) {
                $allcaps[$capability] = false;
            }

            return $allcaps;
        } finally {
            $this->resolving_caps = false;
        }
    }

    public static function is_request_allowed_for_user(int $user_id): bool
    {
        if (!self::is_active_for_user($user_id)) {
            return false;
        }

        return self::is_site_kit_context($user_id);
    }

    public static function temporary_capabilities_for_current_request(int $user_id): array
    {
        if (!self::is_active_for_user($user_id)) {
            return [];
        }

        if (self::is_site_kit_context($user_id)) {
            return [
                'googlesitekit_setup',
                'googlesitekit_manage_options',
                'googlesitekit_view_dashboard',
                'googlesitekit_view_splash',
                'googlesitekit_view_posts_insights',
            ];
        }

        return [];
    }

    private static function is_site_kit_context(int $user_id): bool
    {
        if (!BastionWP_Menu_Access::user_has_site_kit_selected($user_id)) {
            return false;
        }

        global $pagenow;

        if ((string) $pagenow === 'admin.php') {
            $page = isset($_GET['page'])
                ? sanitize_key(wp_unslash($_GET['page']))
                : '';

            if (str_starts_with($page, 'googlesitekit-')) {
                return true;
            }
        }

        $route = isset($_GET['rest_route'])
            ? (string) wp_unslash($_GET['rest_route'])
            : '';

        if ($route === '' && isset($_SERVER['REQUEST_URI'])) {
            $route = (string) wp_unslash($_SERVER['REQUEST_URI']);
        }

        return stripos($route, 'google-site-kit') !== false;
    }

    public function client_active_notice(): void
    {
        if (!$this->is_current_client_manager()) {
            return;
        }

        $active = self::get_active_for_user(get_current_user_id());

        if (!$active) {
            return;
        }

        $remaining = max(0, (int) $active['expires_at'] - time());
        $minutes = max(1, (int) ceil($remaining / 60));

        echo '<div class="notice notice-warning"><p><strong>' .
            esc_html__('BastionWP:', 'bastionwp') .
            '</strong> ' .
            esc_html(
                sprintf(
                    _n(
                        'Privilégios temporários de configuração ativos por mais aproximadamente %d minuto.',
                        'Privilégios temporários de configuração ativos por mais aproximadamente %d minutos.',
                        $minutes,
                        'bastionwp'
                    ),
                    $minutes
                )
            ) .
            '</p></div>';
    }

    public static function blocked_temporary_capabilities(): array
    {
        return array_values(array_unique(BastionWP_Users::forbidden_client_capabilities()));
    }

    private function notify_developers(array $request): bool
    {
        $user = get_userdata((int) $request['user_id']);
        $emails = [];

        foreach (BastionWP_Users::get_developer_ids() as $developer_id) {
            $developer = get_userdata($developer_id);

            if ($developer && is_email($developer->user_email)) {
                $emails[] = $developer->user_email;
            }
        }

        if (empty($emails)) {
            $admin_email = (string) get_option('admin_email', '');

            if (is_email($admin_email)) {
                $emails[] = $admin_email;
            }
        }

        $emails = array_values(array_unique($emails));

        if (empty($emails)) {
            return false;
        }

        $subject = sprintf(
            '[BastionWP] %s — %s',
            __('Solicitação de privilégios temporários de configuração', 'bastionwp'),
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
        );

        $body = implode(
            "\n",
            [
                __('Uma nova solicitação foi criada.', 'bastionwp'),
                '',
                sprintf(__('Site: %s', 'bastionwp'), home_url('/')),
                sprintf(
                    __('Usuário: %1$s (%2$s)', 'bastionwp'),
                    $user ? $user->display_name : '#' . (int) $request['user_id'],
                    $user ? $user->user_login : ''
                ),
                sprintf(__('Motivo: %s', 'bastionwp'), $request['reason'] !== '' ? $request['reason'] : __('Não informado', 'bastionwp')),
                '',
                __('Aprovar ou negar no painel:', 'bastionwp'),
                admin_url('admin.php?page=bastionwp&tab=requests'),
            ]
        );

        $sent = true;

        foreach ($emails as $email) {
            if (!wp_mail($email, $subject, $body)) {
                $sent = false;
            }
        }

        return $sent;
    }

    private static function notify_requester(array $request, string $message): void
    {
        $user = get_userdata((int) ($request['user_id'] ?? 0));

        if (!$user || !is_email($user->user_email)) {
            return;
        }

        wp_mail(
            $user->user_email,
            sprintf(
                '[BastionWP] %s — %s',
                __('Solicitação de configuração temporária', 'bastionwp'),
                wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
            ),
            $message . "\n\n" . home_url('/')
        );
    }

    private static function mark_expired_in_history(string $request_id, int $user_id): void
    {
        if ($request_id === '') {
            return;
        }

        $requests = self::raw_all();

        if (!isset($requests[$request_id]) || !is_array($requests[$request_id])) {
            return;
        }

        if (($requests[$request_id]['status'] ?? '') !== self::STATUS_APPROVED) {
            return;
        }

        $requests[$request_id]['status'] = self::STATUS_EXPIRED;
        $requests[$request_id]['expires_at'] = min(
            (int) ($requests[$request_id]['expires_at'] ?? time()),
            time()
        );
        self::save_all($requests);

        BastionWP_Logger::log(
            'temp_admin_expired',
            __('Privilégios temporários expiraram automaticamente.', 'bastionwp'),
            'info',
            ['request_id' => $request_id, 'target_user_id' => $user_id],
            0,
            $request_id,
            $user_id
        );
    }

    private static function raw_all(): array
    {
        $requests = get_option(self::OPTION, []);
        return is_array($requests) ? $requests : [];
    }

    private static function save_all(array $requests): void
    {
        if (count($requests) > self::MAX_HISTORY) {
            uasort(
                $requests,
                static fn(array $a, array $b): int =>
                    (int) ($b['requested_at'] ?? 0) <=> (int) ($a['requested_at'] ?? 0)
            );
            $requests = array_slice($requests, 0, self::MAX_HISTORY, true);
        }

        update_option(self::OPTION, $requests, false);
    }

    private function is_current_client_manager(): bool
    {
        $user = wp_get_current_user();

        return $user->exists()
            && in_array(BastionWP_Users::CLIENT_ROLE, (array) $user->roles, true);
    }
}
