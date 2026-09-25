<?php

if (!defined('ABSPATH')) {
    exit;
}

interface BastionWP_Auth_Provider
{
    public function verify(int $user_id, string $secret): bool;

    public function get_label(): string;
}

final class BastionWP_Local_WordPress_Auth_Provider implements BastionWP_Auth_Provider
{
    public function verify(int $user_id, string $secret): bool
    {
        $user = get_userdata($user_id);
        if (!$user instanceof WP_User || $secret === '') {
            return false;
        }

        return wp_check_password($secret, (string) $user->user_pass, $user_id);
    }

    public function get_label(): string
    {
        return __('Senha atual do WordPress', 'bastionwp');
    }
}

/**
 * Sessão adicional do BastionWP para ações do Developer.
 *
 * A sessão é vinculada ao token da sessão WordPress atual. Ela não substitui
 * a autenticação do WordPress; adiciona uma reautenticação curta para o
 * painel BastionWP e seus handlers sensíveis.
 */
final class BastionWP_Auth_Manager
{
    public const USER_META = 'bastionwp_auth_sessions';
    public const IDLE_TIMEOUT = 15 * MINUTE_IN_SECONDS;
    public const HARD_TIMEOUT = 60 * MINUTE_IN_SECONDS;
    private const FAILURE_PREFIX = 'bwp_auth_fail_';
    private const BLOCK_PREFIX = 'bwp_auth_block_';

    private BastionWP_Auth_Provider $provider;

    public function __construct(?BastionWP_Auth_Provider $provider = null)
    {
        $this->provider = $provider ?: new BastionWP_Local_WordPress_Auth_Provider();

        add_action('wp_ajax_bastionwp_auth_touch', [$this, 'handle_ajax_touch']);
    }

    public function get_provider_label(): string
    {
        return $this->provider->get_label();
    }

    public function authenticate(string $secret): true|WP_Error
    {
        $user_id = get_current_user_id();
        if ($user_id <= 0 || !BastionWP_Users::is_developer($user_id)) {
            return new WP_Error('bastionwp_auth_identity', __('Somente o Developer pode autenticar uma sessão BastionWP.', 'bastionwp'));
        }

        $session_key = $this->current_session_key();
        if ($session_key === '') {
            return new WP_Error('bastionwp_auth_wp_session', __('Não foi possível vincular a autenticação à sessão atual do WordPress. Faça login novamente.', 'bastionwp'));
        }

        $block_key = self::BLOCK_PREFIX . $this->failure_key($user_id, $session_key);
        if (get_transient($block_key)) {
            return new WP_Error('bastionwp_auth_rate_limited', __('Muitas tentativas de autenticação. Aguarde alguns minutos e tente novamente.', 'bastionwp'));
        }

        if (!$this->provider->verify($user_id, $secret)) {
            $this->record_failure($user_id, $session_key);
            return new WP_Error('bastionwp_auth_invalid_secret', __('A senha informada não corresponde à conta Developer atual.', 'bastionwp'));
        }

        $now = time();
        $sessions = $this->get_sessions($user_id);
        $sessions = $this->purge_expired_sessions($sessions, $now);
        $sessions[$session_key] = [
            'authenticated_at' => $now,
            'last_activity'    => $now,
            'hard_expires_at'  => $now + self::HARD_TIMEOUT,
        ];

        if (count($sessions) > 5) {
            uasort($sessions, static fn(array $a, array $b): int => ((int) ($b['last_activity'] ?? 0)) <=> ((int) ($a['last_activity'] ?? 0)));
            $sessions = array_slice($sessions, 0, 5, true);
        }

        update_user_meta($user_id, self::USER_META, $sessions);
        $stored_sessions = $this->get_sessions($user_id);
        if (empty($stored_sessions[$session_key]) || !is_array($stored_sessions[$session_key])) {
            return new WP_Error(
                'bastionwp_auth_persist_failed',
                __('A senha foi validada, mas o BastionWP não conseguiu criar a sessão protegida. Tente novamente ou verifique a gravação de user_meta.', 'bastionwp')
            );
        }

        $this->clear_failures($user_id, $session_key);

        BastionWP_Logger::log(
            'bastionwp_auth_session_started',
            __('Sessão protegida do BastionWP autenticada.', 'bastionwp'),
            'success',
            ['idle_timeout' => self::IDLE_TIMEOUT, 'hard_timeout' => self::HARD_TIMEOUT],
            $user_id
        );

        return true;
    }

    /**
     * @return array{authenticated:bool,reason:string,idle_remaining:int,hard_remaining:int,idle_expires_at:int,hard_expires_at:int}
     */
    public function get_state(bool $touch = false): array
    {
        $empty = [
            'authenticated' => false,
            'reason' => 'missing',
            'idle_remaining' => 0,
            'hard_remaining' => 0,
            'idle_expires_at' => 0,
            'hard_expires_at' => 0,
        ];

        $user_id = get_current_user_id();
        if ($user_id <= 0 || !BastionWP_Users::is_developer($user_id)) {
            $empty['reason'] = 'identity';
            return $empty;
        }

        $session_key = $this->current_session_key();
        if ($session_key === '') {
            $empty['reason'] = 'wordpress_session';
            return $empty;
        }

        $sessions = $this->get_sessions($user_id);
        if (empty($sessions[$session_key]) || !is_array($sessions[$session_key])) {
            return $empty;
        }

        $entry = $sessions[$session_key];
        $now = time();
        $authenticated_at = (int) ($entry['authenticated_at'] ?? 0);
        $last_activity = (int) ($entry['last_activity'] ?? 0);
        $hard_expires = (int) ($entry['hard_expires_at'] ?? ($authenticated_at + self::HARD_TIMEOUT));
        $idle_expires = $last_activity + self::IDLE_TIMEOUT;

        if ($hard_expires <= $now) {
            unset($sessions[$session_key]);
            update_user_meta($user_id, self::USER_META, $sessions);
            $empty['reason'] = 'hard_expired';
            return $empty;
        }

        if ($idle_expires <= $now) {
            unset($sessions[$session_key]);
            update_user_meta($user_id, self::USER_META, $sessions);
            $empty['reason'] = 'idle_expired';
            return $empty;
        }

        if ($touch) {
            $last_activity = $now;
            $idle_expires = min($now + self::IDLE_TIMEOUT, $hard_expires);
            $sessions[$session_key]['last_activity'] = $now;
            update_user_meta($user_id, self::USER_META, $sessions);
        }

        return [
            'authenticated' => true,
            'reason' => 'ok',
            'idle_remaining' => max(0, $idle_expires - $now),
            'hard_remaining' => max(0, $hard_expires - $now),
            'idle_expires_at' => $idle_expires,
            'hard_expires_at' => $hard_expires,
        ];
    }

    public function is_authenticated(bool $touch = false): bool
    {
        return !empty($this->get_state($touch)['authenticated']);
    }

    public function lock_current(): void
    {
        $user_id = get_current_user_id();
        $session_key = $this->current_session_key();
        if ($user_id <= 0 || $session_key === '') {
            return;
        }

        $sessions = $this->get_sessions($user_id);
        unset($sessions[$session_key]);
        update_user_meta($user_id, self::USER_META, $sessions);
        delete_transient('bastionwp_risk_unlocked_' . $user_id);

        BastionWP_Logger::log(
            'bastionwp_auth_session_locked',
            __('Sessão protegida do BastionWP bloqueada manualmente.', 'bastionwp'),
            'info',
            [],
            $user_id
        );
    }

    public function handle_ajax_touch(): void
    {
        if (!is_user_logged_in() || !BastionWP_Users::is_developer()) {
            wp_send_json_error(['message' => __('Acesso negado.', 'bastionwp')], 403);
        }

        check_ajax_referer('bastionwp_auth_touch', 'nonce');
        $state = $this->get_state(true);
        if (empty($state['authenticated'])) {
            wp_send_json_error(['message' => __('Sua sessão BastionWP expirou.', 'bastionwp'), 'state' => $state], 401);
        }

        wp_send_json_success(['state' => $state]);
    }

    private function current_session_key(): string
    {
        if (!function_exists('wp_get_session_token')) {
            return '';
        }

        $token = (string) wp_get_session_token();
        if ($token === '') {
            return '';
        }

        return substr(hash_hmac('sha256', $token, wp_salt('auth')), 0, 48);
    }

    private function get_sessions(int $user_id): array
    {
        $sessions = get_user_meta($user_id, self::USER_META, true);
        return is_array($sessions) ? $sessions : [];
    }

    private function purge_expired_sessions(array $sessions, int $now): array
    {
        foreach ($sessions as $key => $entry) {
            if (!is_array($entry)) {
                unset($sessions[$key]);
                continue;
            }
            $authenticated_at = (int) ($entry['authenticated_at'] ?? 0);
            $last_activity = (int) ($entry['last_activity'] ?? 0);
            $hard_expires = (int) ($entry['hard_expires_at'] ?? ($authenticated_at + self::HARD_TIMEOUT));
            if ($hard_expires <= $now || ($last_activity + self::IDLE_TIMEOUT) <= $now) {
                unset($sessions[$key]);
            }
        }
        return $sessions;
    }

    private function failure_key(int $user_id, string $session_key): string
    {
        return substr(hash_hmac('sha256', $user_id . '|' . $session_key, wp_salt('auth')), 0, 24);
    }

    private function record_failure(int $user_id, string $session_key): void
    {
        $suffix = $this->failure_key($user_id, $session_key);
        $key = self::FAILURE_PREFIX . $suffix;
        $count = (int) get_transient($key) + 1;
        set_transient($key, $count, 10 * MINUTE_IN_SECONDS);
        if ($count >= 5) {
            set_transient(self::BLOCK_PREFIX . $suffix, 1, 5 * MINUTE_IN_SECONDS);
        }

        BastionWP_Logger::log(
            'bastionwp_auth_failed',
            __('Falha ao autenticar a sessão protegida do BastionWP.', 'bastionwp'),
            'warning',
            ['attempt' => $count],
            $user_id
        );
    }

    private function clear_failures(int $user_id, string $session_key): void
    {
        $suffix = $this->failure_key($user_id, $session_key);
        delete_transient(self::FAILURE_PREFIX . $suffix);
        delete_transient(self::BLOCK_PREFIX . $suffix);
    }
}
