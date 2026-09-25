<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controles HTTP, monitoramento local e alertas de segurança.
 *
 * Limites deliberados:
 * - WAF de borda e rate limiting real de edge não são simulados pelo PHP.
 * - tráfego bloqueado antes do WordPress não é visível ao BastionWP.
 * - cabeçalhos podem ser sobrescritos por servidor/CDN e por isso o painel
 *   diferencia configuração local de estado verificável.
 */
final class BastionWP_Security_Controls
{
    public const OPTION = 'bastionwp_security_controls';
    public const ALERTS_OPTION = 'bastionwp_security_alerts';
    public const FILE_BASELINE_OPTION = 'bastionwp_php_integrity_baseline';
    public const DNS_BASELINE_OPTION = 'bastionwp_dns_baseline';
    public const TLS_STATUS_OPTION = 'bastionwp_tls_status';
    public const REST_INVENTORY_OPTION = 'bastionwp_rest_inventory';
    public const CSP_REPORTS_OPTION = 'bastionwp_csp_reports';
    public const TRAFFIC_OPTION = 'bastionwp_traffic_window';
    public const MONITOR_STATUS_OPTION = 'bastionwp_monitor_status';
    public const CRON_HOOK = 'bastionwp_security_monitor';

    public function __construct()
    {
        add_action('send_headers', [$this, 'apply_frontend_headers'], 20);
        add_action('admin_init', [$this, 'apply_private_nocache'], 0);
        add_action('login_init', [$this, 'apply_private_nocache'], 0);
        add_filter('authenticate', [$this, 'enforce_login_rate_limit'], 99, 3);
        add_action('wp_login_failed', [$this, 'record_failed_login'], 10, 2);
        add_action('wp_login', [$this, 'clear_login_failures'], 10, 2);
        add_action('set_user_role', [$this, 'detect_new_administrator'], 20, 3);
        add_action('upgrader_process_complete', [$this, 'detect_plugin_install'], 20, 2);
        add_action('shutdown', [$this, 'record_local_traffic']);
        add_action('rest_api_init', [$this, 'register_csp_report_endpoint'], 5);
        add_action('rest_api_init', [$this, 'capture_rest_inventory'], 9999);
        add_filter('rest_pre_dispatch', [$this, 'enforce_rest_policy'], 20, 3);
        add_action(self::CRON_HOOK, [self::class, 'run_monitoring_cycle']);
        add_action('init', [self::class, 'ensure_schedule'], 20);
    }

    public static function defaults(): array
    {
        return [
            'login_rate_limit'      => true,
            'login_limit'           => 10,
            'login_window_minutes'  => 15,
            'login_block_minutes'   => 30,
            'rest_mode'             => 'observe',
            'rest_allowed_namespaces' => [],
            'headers_enabled'       => false,
            'hsts_mode'             => 'off',
            'csp_mode'              => 'report_only',
            'csp_policy'            => "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self';",
            'nosniff'               => true,
            'referrer_policy'       => 'strict-origin-when-cross-origin',
            'permissions_policy'    => 'camera=(), microphone=(), geolocation=(), payment=()',
            'x_frame_options'       => true,
            'remove_powered_by'     => true,
            'remove_xss_header'     => true,
            'private_nocache'       => true,
            'integrity_monitor'     => false,
            'dns_monitor'           => true,
            'tls_monitor'           => true,
            'traffic_monitor'       => false,
            'traffic_threshold'     => 800,
            'error_threshold'       => 40,
            'custom_headers_enabled' => false,
            'custom_headers'         => '',
            'custom_rest_auth_namespaces' => [],
            'custom_admin_css'       => '',
        ];
    }

    public static function get_settings(): array
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return array_replace(self::defaults(), $saved);
    }

    public static function save_settings(array $input): bool
    {
        $defaults = self::defaults();
        $clean = $defaults;

        foreach ([
            'login_rate_limit','headers_enabled','nosniff','x_frame_options','remove_powered_by',
            'remove_xss_header','private_nocache','integrity_monitor','dns_monitor','tls_monitor','traffic_monitor',
            'custom_headers_enabled'
        ] as $key) {
            $clean[$key] = !empty($input[$key]);
        }

        $clean['login_limit'] = max(3, min(100, absint($input['login_limit'] ?? $defaults['login_limit'])));
        $clean['login_window_minutes'] = max(1, min(120, absint($input['login_window_minutes'] ?? $defaults['login_window_minutes'])));
        $clean['login_block_minutes'] = max(1, min(1440, absint($input['login_block_minutes'] ?? $defaults['login_block_minutes'])));
        $clean['traffic_threshold'] = max(50, min(100000, absint($input['traffic_threshold'] ?? $defaults['traffic_threshold'])));
        $clean['error_threshold'] = max(5, min(10000, absint($input['error_threshold'] ?? $defaults['error_threshold'])));

        $rest_mode = sanitize_key((string) ($input['rest_mode'] ?? 'observe'));
        $clean['rest_mode'] = in_array($rest_mode, ['observe', 'recommended', 'allowlist'], true) ? $rest_mode : 'observe';
        $clean['rest_allowed_namespaces'] = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($input['rest_allowed_namespaces'] ?? [])))));

        $hsts_mode = sanitize_key((string) ($input['hsts_mode'] ?? 'off'));
        $clean['hsts_mode'] = in_array($hsts_mode, ['off', 'test', 'validated', 'production'], true) ? $hsts_mode : 'off';
        $csp_mode = sanitize_key((string) ($input['csp_mode'] ?? 'report_only'));
        $clean['csp_mode'] = in_array($csp_mode, ['off', 'report_only', 'enforce'], true) ? $csp_mode : 'report_only';
        $clean['csp_policy'] = trim(sanitize_textarea_field((string) ($input['csp_policy'] ?? $defaults['csp_policy'])));
        $clean['referrer_policy'] = sanitize_text_field((string) ($input['referrer_policy'] ?? $defaults['referrer_policy']));
        $clean['permissions_policy'] = sanitize_text_field((string) ($input['permissions_policy'] ?? $defaults['permissions_policy']));
        $clean['custom_headers'] = self::sanitize_custom_headers((string) ($input['custom_headers'] ?? ''));
        $custom_rest = $input['custom_rest_auth_namespaces'] ?? [];
        if (is_string($custom_rest)) {
            $custom_rest = preg_split('/[\r\n,]+/', $custom_rest) ?: [];
        }
        $clean['custom_rest_auth_namespaces'] = array_values(array_unique(array_filter(array_map(
            static fn($value): string => self::sanitize_namespace((string) $value),
            (array) $custom_rest
        ))));
        $clean['custom_admin_css'] = self::sanitize_admin_css((string) ($input['custom_admin_css'] ?? ''));

        if ($clean['rest_mode'] === 'allowlist') {
            $clean['rest_allowed_namespaces'] = array_values(array_unique(array_merge(
                self::get_required_rest_namespaces(),
                $clean['rest_allowed_namespaces']
            )));
        }

        update_option(self::OPTION, $clean, false);
        return true;
    }

    public static function save_custom_settings(array $input): bool
    {
        $settings = self::get_settings();
        $settings['custom_headers_enabled'] = !empty($input['custom_headers_enabled']);
        $settings['custom_headers'] = self::sanitize_custom_headers((string) ($input['custom_headers'] ?? ''));
        $custom_rest = $input['custom_rest_auth_namespaces'] ?? [];
        if (is_string($custom_rest)) {
            $custom_rest = preg_split('/[\r\n,]+/', $custom_rest) ?: [];
        }
        $settings['custom_rest_auth_namespaces'] = array_values(array_unique(array_filter(array_map(
            static fn($value): string => self::sanitize_namespace((string) $value),
            (array) $custom_rest
        ))));
        $settings['custom_admin_css'] = self::sanitize_admin_css((string) ($input['custom_admin_css'] ?? ''));
        return update_option(self::OPTION, $settings, false) || self::get_settings() === $settings;
    }

    public static function ensure_schedule(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'twicedaily', self::CRON_HOOK);
        }
    }

    public function apply_frontend_headers(): void
    {
        $settings = self::get_settings();
        if (!empty($settings['remove_powered_by']) && function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        if (!empty($settings['remove_xss_header']) && function_exists('header_remove')) {
            @header_remove('X-XSS-Protection');
        }
        if (!empty($settings['private_nocache']) && is_user_logged_in() && !headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        }

        if (!headers_sent() && !empty($settings['custom_headers_enabled']) && !empty($settings['custom_headers'])) {
            foreach (self::parse_custom_headers((string) $settings['custom_headers']) as $custom_header) {
                header($custom_header['name'] . ': ' . $custom_header['value'], true);
            }
        }

        if (empty($settings['headers_enabled']) || headers_sent()) {
            return;
        }

        if (!empty($settings['nosniff'])) {
            header('X-Content-Type-Options: nosniff', true);
        }
        if ($settings['referrer_policy'] !== '') {
            header('Referrer-Policy: ' . $settings['referrer_policy'], true);
        }
        if ($settings['permissions_policy'] !== '') {
            header('Permissions-Policy: ' . $settings['permissions_policy'], true);
        }
        if (!empty($settings['x_frame_options'])) {
            header('X-Frame-Options: SAMEORIGIN', true);
        }

        if (is_ssl()) {
            $hsts = [
                'test' => 300,
                'validated' => 2592000,
                'production' => 31536000,
            ];
            if (isset($hsts[$settings['hsts_mode']])) {
                header('Strict-Transport-Security: max-age=' . $hsts[$settings['hsts_mode']], true);
            }
        }

        if ($settings['csp_mode'] !== 'off' && $settings['csp_policy'] !== '') {
            $header = $settings['csp_mode'] === 'enforce'
                ? 'Content-Security-Policy'
                : 'Content-Security-Policy-Report-Only';
            $policy = preg_replace('/[\r\n]+/', ' ', (string) $settings['csp_policy']);
            $policy = trim((string) $policy);
            if ($settings['csp_mode'] === 'report_only' && !str_contains($policy, 'report-uri')) {
                $report_endpoint = esc_url_raw(rest_url('bastionwp/v1/csp-report'));
                $policy = rtrim($policy, '; ') . '; report-uri ' . $report_endpoint . ';';
            }
            header($header . ': ' . $policy, true);
        }
    }

    public function apply_private_nocache(): void
    {
        $settings = self::get_settings();
        if (!empty($settings['private_nocache']) && !headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        }

        // admin_init/login_init não passam pelo hook send_headers do frontend.
        // Reutiliza a mesma política para respostas administrativas e de login.
        $this->apply_frontend_headers();
    }

    public function enforce_login_rate_limit($user, string $username, string $password)
    {
        $settings = self::get_settings();
        if (empty($settings['login_rate_limit'])) {
            return $user;
        }

        $key = self::login_key($username);
        $ip_key = self::login_ip_key();
        if (get_transient('bwp_login_block_' . $key) || get_transient('bwp_login_ip_block_' . $ip_key)) {
            return new WP_Error(
                'bastionwp_login_rate_limited',
                __('Muitas tentativas de login. Aguarde alguns minutos antes de tentar novamente.', 'bastionwp')
            );
        }
        return $user;
    }

    public function record_failed_login(string $username, $error): void
    {
        $settings = self::get_settings();
        if (empty($settings['login_rate_limit'])) {
            return;
        }

        $key = self::login_key($username);
        $ip_key = self::login_ip_key();
        $ttl = ((int) $settings['login_window_minutes']) * MINUTE_IN_SECONDS;
        $block_ttl = ((int) $settings['login_block_minutes']) * MINUTE_IN_SECONDS;

        $counter_key = 'bwp_login_fail_' . $key;
        $count = (int) get_transient($counter_key) + 1;
        set_transient($counter_key, $count, $ttl);

        $ip_counter_key = 'bwp_login_ip_fail_' . $ip_key;
        $ip_count = (int) get_transient($ip_counter_key) + 1;
        set_transient($ip_counter_key, $ip_count, $ttl);

        if ($count >= (int) $settings['login_limit']) {
            set_transient('bwp_login_block_' . $key, 1, $block_ttl);
            self::create_alert(
                'login_failures_pair_' . $key,
                'warning',
                __('Muitas tentativas de login', 'bastionwp'),
                sprintf(__('O limite local de %d falhas para a mesma combinação de origem/usuário foi atingido.', 'bastionwp'), $count),
                ['count' => $count]
            );
        }

        $ip_limit = max((int) $settings['login_limit'] * 3, 15);
        if ($ip_count >= $ip_limit) {
            set_transient('bwp_login_ip_block_' . $ip_key, 1, $block_ttl);
            self::create_alert(
                'login_failures_ip_' . $ip_key,
                'warning',
                __('Possível credential stuffing', 'bastionwp'),
                sprintf(__('Uma mesma origem acumulou %d falhas de autenticação na janela monitorada.', 'bastionwp'), $ip_count),
                ['count' => $ip_count]
            );
        }
    }

    public function clear_login_failures(string $user_login, WP_User $user): void
    {
        $key = self::login_key($user_login);
        delete_transient('bwp_login_fail_' . $key);
        delete_transient('bwp_login_block_' . $key);
    }

    public function detect_new_administrator(int $user_id, string $role, array $old_roles): void
    {
        if ($role !== 'administrator' || in_array('administrator', $old_roles, true) || BastionWP_Users::is_developer($user_id)) {
            return;
        }

        $user = get_userdata($user_id);
        self::create_alert(
            'new_administrator_' . $user_id,
            'warning',
            __('Novo administrador', 'bastionwp'),
            sprintf(__('A conta %s recebeu a role Administrator.', 'bastionwp'), $user ? $user->display_name : ('#' . $user_id)),
            ['user_id' => $user_id, 'actor_user_id' => get_current_user_id()]
        );
    }

    public function detect_plugin_install($upgrader, array $options): void
    {
        if (in_array(($options['type'] ?? ''), ['plugin', 'theme', 'core'], true) && in_array(($options['action'] ?? ''), ['install', 'update'], true)) {
            // A próxima verificação cria uma nova baseline após uma manutenção
            // conhecida, evitando falsos positivos em atualizações legítimas.
            delete_option(self::FILE_BASELINE_OPTION);
        }

        if (($options['type'] ?? '') !== 'plugin' || ($options['action'] ?? '') !== 'install') {
            return;
        }
        $plugins = [];
        if (!empty($options['plugins']) && is_array($options['plugins'])) {
            $plugins = $options['plugins'];
        } elseif (!empty($options['plugin'])) {
            $plugins = [(string) $options['plugin']];
        }
        self::create_alert(
            'plugin_installed_' . substr(hash('sha256', wp_json_encode($plugins) . microtime(true)), 0, 12),
            'info',
            __('Plugin instalado', 'bastionwp'),
            !empty($plugins) ? sprintf(__('Plugin(s): %s', 'bastionwp'), implode(', ', array_map('sanitize_text_field', $plugins))) : __('Um novo plugin foi instalado no WordPress.', 'bastionwp'),
            ['plugins' => $plugins, 'actor_user_id' => get_current_user_id()]
        );
    }

    public function register_csp_report_endpoint(): void
    {
        register_rest_route('bastionwp/v1', '/csp-report', [
            'methods' => 'POST',
            'callback' => [$this, 'receive_csp_report'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function receive_csp_report(WP_REST_Request $request): WP_REST_Response
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        $rate_key = 'bwp_csp_report_' . substr(hash_hmac('sha256', $ip, wp_salt('auth')), 0, 20);
        $rate = (int) get_transient($rate_key);
        if ($rate >= 60) {
            return new WP_REST_Response(['received' => false], 429);
        }
        set_transient($rate_key, $rate + 1, HOUR_IN_SECONDS);

        $payload = $request->get_json_params();
        if (!is_array($payload) || empty($payload)) {
            $decoded = json_decode((string) $request->get_body(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }
        $report = isset($payload['csp-report']) && is_array($payload['csp-report'])
            ? $payload['csp-report']
            : $payload;

        $blocked_uri = esc_url_raw((string) ($report['blocked-uri'] ?? $report['blockedURL'] ?? ''));
        $violated = sanitize_text_field((string) ($report['violated-directive'] ?? $report['effective-directive'] ?? ''));
        $document = esc_url_raw((string) ($report['document-uri'] ?? $report['documentURL'] ?? ''));

        $origin = '';
        if ($blocked_uri !== '' && preg_match('#^https?://#i', $blocked_uri)) {
            $parts = wp_parse_url($blocked_uri);
            if (is_array($parts) && !empty($parts['host'])) {
                $origin = ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . (!empty($parts['port']) ? ':' . (int) $parts['port'] : '');
            }
        } elseif ($blocked_uri !== '') {
            $origin = sanitize_text_field($blocked_uri);
        }

        $reports = get_option(self::CSP_REPORTS_OPTION, []);
        if (!is_array($reports)) {
            $reports = [];
        }
        $key = substr(hash('sha256', $origin . '|' . $violated), 0, 24);
        $current = isset($reports[$key]) && is_array($reports[$key]) ? $reports[$key] : [];
        $reports[$key] = [
            'origin' => sanitize_text_field($origin),
            'directive' => $violated,
            'document' => $document,
            'count' => (int) ($current['count'] ?? 0) + 1,
            'last_seen' => current_time('mysql'),
        ];
        if (count($reports) > 100) {
            uasort($reports, static fn(array $a, array $b): int => strcmp((string) ($b['last_seen'] ?? ''), (string) ($a['last_seen'] ?? '')));
            $reports = array_slice($reports, 0, 100, true);
        }
        update_option(self::CSP_REPORTS_OPTION, $reports, false);

        return new WP_REST_Response(['received' => true], 204);
    }

    public static function get_csp_reports(): array
    {
        $reports = get_option(self::CSP_REPORTS_OPTION, []);
        if (!is_array($reports)) {
            return [];
        }
        uasort($reports, static fn(array $a, array $b): int => ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0)));
        return array_values($reports);
    }

    public function capture_rest_inventory(): void
    {
        $server = rest_get_server();
        $routes = $server->get_routes();
        $inventory = [];
        foreach ($routes as $route => $handlers) {
            $route = (string) $route;
            if (!preg_match('#^/([^/]+(?:/v\d+)?)#', $route, $m)) {
                continue;
            }
            $namespace = sanitize_text_field($m[1]);
            if ($namespace === '') {
                continue;
            }
            if (!isset($inventory[$namespace])) {
                $inventory[$namespace] = ['namespace' => $namespace, 'routes' => 0, 'source' => ''];
            }
            $inventory[$namespace]['routes']++;
            if ($inventory[$namespace]['source'] === '') {
                foreach ((array) $handlers as $handler) {
                    if (!is_array($handler) || empty($handler['callback'])) {
                        continue;
                    }
                    $source = self::callback_plugin_source($handler['callback']);
                    if ($source !== '') {
                        $inventory[$namespace]['source'] = $source;
                        break;
                    }
                }
            }
        }
        ksort($inventory);
        update_option(self::REST_INVENTORY_OPTION, array_values($inventory), false);
    }

    public function enforce_rest_policy($result, WP_REST_Server $server, WP_REST_Request $request)
    {
        $settings = self::get_settings();
        $mode = (string) $settings['rest_mode'];
        $route = (string) $request->get_route();
        $namespace = self::namespace_from_route($route);

        if (
            $namespace !== ''
            && !is_user_logged_in()
            && in_array($namespace, (array) ($settings['custom_rest_auth_namespaces'] ?? []), true)
        ) {
            return new WP_Error(
                'bastionwp_rest_custom_auth_required',
                __('Este namespace REST exige autenticação pela regra personalizada do BastionWP.', 'bastionwp'),
                ['status' => 401]
            );
        }

        if ($mode === 'observe') {
            return $result;
        }

        if ($namespace === '') {
            return $result;
        }

        $always_allowed = self::get_required_rest_namespaces();
        $allowed = array_values(array_unique(array_merge($always_allowed, (array) $settings['rest_allowed_namespaces'])));

        if ($mode === 'recommended') {
            // Mantém a REST disponível e protege somente enumeração pública de usuários.
            if (!is_user_logged_in() && str_starts_with($route, '/wp/v2/users')) {
                return new WP_Error('bastionwp_rest_users_blocked', __('Enumeração pública de usuários bloqueada pelo BastionWP.', 'bastionwp'), ['status' => 403]);
            }
            return $result;
        }

        if ($mode === 'allowlist' && !in_array($namespace, $allowed, true)) {
            return new WP_Error('bastionwp_rest_namespace_blocked', __('Este namespace REST não está na allowlist do BastionWP.', 'bastionwp'), ['status' => 403]);
        }

        return $result;
    }

    public function record_local_traffic(): void
    {
        $settings = self::get_settings();
        if (empty($settings['traffic_monitor']) || wp_doing_cron()) {
            return;
        }

        $window = (int) floor(time() / 600);
        $data = get_option(self::TRAFFIC_OPTION, []);
        if (!is_array($data)) {
            $data = [];
        }
        $bucket = $data[$window] ?? ['requests' => 0, '403' => 0, '404' => 0, '500' => 0];
        $bucket['requests']++;
        $status = (int) http_response_code();
        if ($status === 403) {
            $bucket['403']++;
        } elseif ($status === 404) {
            $bucket['404']++;
        } elseif ($status >= 500) {
            $bucket['500']++;
        }
        $data[$window] = $bucket;
        foreach (array_keys($data) as $key) {
            if ((int) $key < $window - 6) {
                unset($data[$key]);
            }
        }
        update_option(self::TRAFFIC_OPTION, $data, false);

        $errors = $bucket['403'] + $bucket['404'] + $bucket['500'];
        if ($bucket['requests'] >= (int) $settings['traffic_threshold']) {
            self::create_alert('traffic_spike_' . $window, 'warning', __('Aumento anormal de requisições', 'bastionwp'), sprintf(__('%d requisições chegaram ao WordPress em aproximadamente 10 minutos.', 'bastionwp'), $bucket['requests']), $bucket);
        }
        if ($errors >= (int) $settings['error_threshold']) {
            self::create_alert('http_errors_' . $window, 'warning', __('Muitos erros HTTP', 'bastionwp'), sprintf(__('%d respostas 403/404/5xx foram observadas pelo WordPress na janela atual.', 'bastionwp'), $errors), $bucket);
        }
    }

    public static function run_monitoring_cycle(): void
    {
        $settings = self::get_settings();
        $status = get_option(self::MONITOR_STATUS_OPTION, []);
        if (!is_array($status)) {
            $status = [];
        }

        if (!empty($settings['integrity_monitor'])) {
            self::check_php_integrity();
            $baseline = get_option(self::FILE_BASELINE_OPTION, []);
            $status['integrity'] = [
                'checked_at' => time(),
                'coverage' => is_array($baseline) ? count($baseline) : 0,
            ];
        }
        if (!empty($settings['dns_monitor'])) {
            self::check_dns();
            $baseline = get_option(self::DNS_BASELINE_OPTION, []);
            $status['dns'] = [
                'checked_at' => time(),
                'coverage' => is_array($baseline) ? count($baseline) : 0,
            ];
        }
        if (!empty($settings['tls_monitor'])) {
            self::check_tls();
            $tls = get_option(self::TLS_STATUS_OPTION, []);
            $status['tls'] = [
                'checked_at' => time(),
                'days' => is_array($tls) ? (int) ($tls['days'] ?? 0) : 0,
            ];
        }

        $status['cycle_at'] = time();
        update_option(self::MONITOR_STATUS_OPTION, $status, false);
    }

    public static function get_waf_status(): array
    {
        if (!function_exists('is_plugin_active')) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
        $wordfence_active = defined('WFWAF_VERSION') || class_exists('wordfence') || is_plugin_active('wordfence/wordfence.php');
        $cloudflare = !empty($_SERVER['HTTP_CF_RAY']) || !empty($_SERVER['HTTP_CF_CONNECTING_IP']);
        $scanner = $wordfence_active;

        return [
            'edge_detected' => $cloudflare,
            'edge_label' => $cloudflare ? 'Cloudflare proxy detectado' : 'WAF de borda não verificado',
            'local_waf' => $wordfence_active,
            'local_waf_label' => $wordfence_active ? 'Wordfence detectado' : 'Firewall WordPress não detectado',
            'scanner' => $scanner,
            'scanner_label' => $scanner ? 'Wordfence disponível para scan' : 'Mecanismo de scan não detectado',
        ];
    }

    public static function get_rest_inventory(): array
    {
        $inventory = get_option(self::REST_INVENTORY_OPTION, []);
        return is_array($inventory) ? $inventory : [];
    }

    public static function get_required_rest_namespaces(): array
    {
        return ['wp/v2', 'wp-site-health/v1', 'wp-block-editor/v1', 'oembed/1.0', 'batch/v1', 'bastionwp/v1'];
    }

    public static function get_monitoring_dashboard(): array
    {
        $settings = self::get_settings();
        $status = get_option(self::MONITOR_STATUS_OPTION, []);
        $status = is_array($status) ? $status : [];
        $baseline = get_option(self::FILE_BASELINE_OPTION, []);
        $traffic = get_option(self::TRAFFIC_OPTION, []);
        $traffic = is_array($traffic) ? $traffic : [];
        $current_bucket = !empty($traffic) ? end($traffic) : [];
        $current_bucket = is_array($current_bucket) ? $current_bucket : [];
        $tls = get_option(self::TLS_STATUS_OPTION, []);
        $tls = is_array($tls) ? $tls : [];
        $dns = get_option(self::DNS_BASELINE_OPTION, []);
        $dns = is_array($dns) ? $dns : [];

        return [
            'integrity' => [
                'enabled' => !empty($settings['integrity_monitor']),
                'state' => !empty($settings['integrity_monitor']) ? __('Ativo', 'bastionwp') : __('Desativado', 'bastionwp'),
                'checked_at' => (int) ($status['integrity']['checked_at'] ?? 0),
                'coverage' => is_array($baseline) ? count($baseline) : 0,
                'source' => __('Filesystem local / SHA-256', 'bastionwp'),
            ],
            'login' => [
                'enabled' => !empty($settings['login_rate_limit']),
                'state' => !empty($settings['login_rate_limit']) ? __('Proteção ativa', 'bastionwp') : __('Somente WordPress', 'bastionwp'),
                'checked_at' => 0,
                'coverage' => __('Falhas de login que chegam ao WordPress', 'bastionwp'),
                'source' => __('WordPress + BastionWP', 'bastionwp'),
            ],
            'traffic' => [
                'enabled' => !empty($settings['traffic_monitor']),
                'state' => !empty($settings['traffic_monitor']) ? __('Monitoramento parcial', 'bastionwp') : __('Desativado', 'bastionwp'),
                'checked_at' => 0,
                'coverage' => (int) ($current_bucket['requests'] ?? 0),
                'source' => __('Requisições que chegaram ao WordPress', 'bastionwp'),
            ],
            'http' => [
                'enabled' => !empty($settings['traffic_monitor']),
                'state' => !empty($settings['traffic_monitor']) ? __('Monitoramento parcial', 'bastionwp') : __('Desativado', 'bastionwp'),
                'checked_at' => 0,
                'coverage' => [
                    '403' => (int) ($current_bucket['403'] ?? 0),
                    '404' => (int) ($current_bucket['404'] ?? 0),
                    '500' => (int) ($current_bucket['500'] ?? 0),
                ],
                'source' => __('Respostas observadas pelo WordPress', 'bastionwp'),
            ],
            'dns' => [
                'enabled' => !empty($settings['dns_monitor']),
                'state' => !empty($settings['dns_monitor']) ? __('Monitorado', 'bastionwp') : __('Desativado', 'bastionwp'),
                'checked_at' => (int) ($status['dns']['checked_at'] ?? 0),
                'coverage' => count($dns),
                'source' => __('DNS público', 'bastionwp'),
            ],
            'tls' => [
                'enabled' => !empty($settings['tls_monitor']),
                'state' => !empty($settings['tls_monitor']) ? __('Monitorado', 'bastionwp') : __('Desativado', 'bastionwp'),
                'checked_at' => (int) ($tls['checked_at'] ?? ($status['tls']['checked_at'] ?? 0)),
                'coverage' => isset($tls['days']) ? (int) $tls['days'] : null,
                'source' => __('Certificado HTTPS público', 'bastionwp'),
            ],
        ];
    }

    public static function get_alerts(array $statuses = []): array
    {
        $alerts = get_option(self::ALERTS_OPTION, []);
        if (!is_array($alerts)) {
            return [];
        }
        $list = array_values($alerts);
        if (!empty($statuses)) {
            $list = array_values(array_filter($list, static fn(array $alert): bool => in_array((string) ($alert['status'] ?? 'open'), $statuses, true)));
        }
        usort($list, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $list;
    }

    public static function get_open_alerts(): array
    {
        return self::get_alerts(['open', 'acknowledged']);
    }

    public static function update_alert_status(string $alert_id, string $status): bool
    {
        $allowed = ['open', 'acknowledged', 'resolved', 'ignored'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $alerts = get_option(self::ALERTS_OPTION, []);
        if (!is_array($alerts) || empty($alerts[$alert_id])) {
            return false;
        }
        $alerts[$alert_id]['status'] = $status;
        $alerts[$alert_id]['status_at'] = current_time('mysql');
        $alerts[$alert_id]['status_by'] = get_current_user_id();
        update_option(self::ALERTS_OPTION, $alerts, false);
        return true;
    }

    public static function resolve_alert(string $alert_id): bool
    {
        return self::update_alert_status($alert_id, 'resolved');
    }

    public static function create_alert(string $id, string $severity, string $title, string $message, array $context = []): void
    {
        $id = sanitize_key($id);
        if ($id === '') {
            return;
        }
        $alerts = get_option(self::ALERTS_OPTION, []);
        if (!is_array($alerts)) {
            $alerts = [];
        }
        if (!empty($alerts[$id]) && ($alerts[$id]['status'] ?? '') === 'open') {
            return;
        }
        $alerts[$id] = [
            'id' => $id,
            'severity' => in_array($severity, ['info', 'warning', 'critical'], true) ? $severity : 'warning',
            'title' => sanitize_text_field($title),
            'message' => sanitize_text_field($message),
            'context' => $context,
            'status' => 'open',
            'created_at' => current_time('mysql'),
        ];
        if (count($alerts) > 200) {
            $alerts = array_slice($alerts, -200, null, true);
        }
        update_option(self::ALERTS_OPTION, $alerts, false);
        BastionWP_Logger::log('security_alert_created', $title . ': ' . $message, $severity === 'critical' ? 'error' : ($severity === 'warning' ? 'warning' : 'info'), ['alert_id' => $id] + $context);
    }

    private static function check_php_integrity(): void
    {
        $files = [];
        $roots = [ABSPATH, WP_PLUGIN_DIR, get_theme_root(), WPMU_PLUGIN_DIR];
        foreach (array_unique(array_filter($roots)) as $root) {
            if (!is_dir($root)) {
                continue;
            }
            try {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if (count($files) >= 5000) {
                        break 2;
                    }
                    /** @var SplFileInfo $file */
                    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                        continue;
                    }
                    $path = wp_normalize_path($file->getPathname());
                    if (str_contains($path, '/cache/') || str_contains($path, '/upgrade/')) {
                        continue;
                    }
                    $files[$path] = @hash_file('sha256', $path) ?: '';
                }
            } catch (UnexpectedValueException $e) {
                continue;
            }
        }

        $baseline = get_option(self::FILE_BASELINE_OPTION, []);
        if (!is_array($baseline) || empty($baseline)) {
            update_option(self::FILE_BASELINE_OPTION, $files, false);
            return;
        }

        $changes = [];
        foreach ($files as $path => $hash) {
            if (isset($baseline[$path]) && $baseline[$path] !== $hash) {
                $changes[] = $path;
            } elseif (!isset($baseline[$path])) {
                $changes[] = $path;
            }
        }
        foreach ($baseline as $path => $hash) {
            if (!isset($files[$path])) {
                $changes[] = $path . ' (removido)';
            }
        }

        if (!empty($changes)) {
            self::create_alert('php_integrity_' . substr(hash('sha256', implode('|', $changes)), 0, 12), 'critical', __('Arquivo PHP modificado', 'bastionwp'), sprintf(_n('%d alteração PHP detectada desde a baseline.', '%d alterações PHP detectadas desde a baseline.', count($changes), 'bastionwp'), count($changes)), ['files' => array_slice($changes, 0, 20)]);
            update_option(self::FILE_BASELINE_OPTION, $files, false);
        }

        $uploads = wp_get_upload_dir();
        if (empty($uploads['basedir']) || !is_dir($uploads['basedir'])) {
            return;
        }
        $found = [];
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads['basedir'], FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $found[] = wp_normalize_path($file->getPathname());
                    if (count($found) >= 20) {
                        break;
                    }
                }
            }
        } catch (UnexpectedValueException $e) {
            return;
        }
        if (!empty($found)) {
            self::create_alert('php_in_uploads_' . substr(hash('sha256', implode('|', $found)), 0, 12), 'critical', __('Arquivo PHP encontrado em uploads', 'bastionwp'), sprintf(_n('%d arquivo PHP encontrado na pasta de uploads.', '%d arquivos PHP encontrados na pasta de uploads.', count($found), 'bastionwp'), count($found)), ['files' => $found]);
        }
    }

    private static function check_dns(): void
    {
        if (!function_exists('dns_get_record')) {
            return;
        }
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!$host) {
            return;
        }
        $records = @dns_get_record($host, DNS_NS);
        if ((!is_array($records) || empty($records)) && substr_count($host, '.') >= 2) {
            $labels = explode('.', $host);
            array_shift($labels);
            $zone_candidate = implode('.', $labels);
            $records = @dns_get_record($zone_candidate, DNS_NS);
        }
        if (!is_array($records) || empty($records)) {
            return;
        }
        $nameservers = [];
        foreach ($records as $record) {
            if (!empty($record['target'])) {
                $nameservers[] = strtolower(rtrim((string) $record['target'], '.'));
            }
        }
        sort($nameservers);
        $baseline = get_option(self::DNS_BASELINE_OPTION, []);
        if (empty($baseline)) {
            update_option(self::DNS_BASELINE_OPTION, $nameservers, false);
            return;
        }
        $baseline = array_values((array) $baseline);
        sort($baseline);
        if ($baseline !== $nameservers) {
            self::create_alert('dns_nameserver_changed', 'critical', __('Nameserver/DNS alterado', 'bastionwp'), __('Os nameservers atuais diferem da baseline registrada pelo BastionWP.', 'bastionwp'), ['before' => $baseline, 'after' => $nameservers]);
            update_option(self::DNS_BASELINE_OPTION, $nameservers, false);
        }
    }

    private static function check_tls(): void
    {
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!$host || !function_exists('stream_socket_client') || !function_exists('openssl_x509_parse')) {
            return;
        }
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $client = @stream_socket_client('ssl://' . $host . ':443', $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context);
        if (!$client) {
            return;
        }
        $params = stream_context_get_params($client);
        fclose($client);
        if (empty($params['options']['ssl']['peer_certificate'])) {
            return;
        }
        $parsed = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (!is_array($parsed) || empty($parsed['validTo_time_t'])) {
            return;
        }
        $expires = (int) $parsed['validTo_time_t'];
        $days = (int) floor(($expires - time()) / DAY_IN_SECONDS);
        update_option(self::TLS_STATUS_OPTION, ['expires' => $expires, 'days' => $days, 'checked_at' => time()], false);
        if ($days <= 30) {
            self::create_alert('tls_expiry_' . gmdate('Ymd', $expires), $days <= 7 ? 'critical' : 'warning', __('Certificado próximo da expiração', 'bastionwp'), sprintf(__('O certificado público HTTPS expira em aproximadamente %d dias.', 'bastionwp'), max(0, $days)), ['days' => $days, 'expires' => $expires]);
        }
    }

    private static function sanitize_custom_headers(string $raw): string
    {
        $headers = self::parse_custom_headers($raw);
        $lines = [];
        foreach (array_slice($headers, 0, 10) as $header) {
            $lines[] = $header['name'] . ': ' . $header['value'];
        }
        return implode("\n", $lines);
    }

    private static function parse_custom_headers(string $raw): array
    {
        $reserved = [
            'set-cookie', 'location', 'content-length', 'host', 'server', 'x-powered-by',
            'strict-transport-security', 'content-security-policy', 'content-security-policy-report-only',
            'x-content-type-options', 'x-frame-options', 'referrer-policy', 'permissions-policy',
            'cache-control', 'connection', 'transfer-encoding',
        ];
        $result = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $line, 2));
            $lower = strtolower($name);
            if (!preg_match('/^[A-Za-z0-9-]{1,64}$/', $name) || in_array($lower, $reserved, true)) {
                continue;
            }
            $value = preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $value);
            $value = trim((string) $value);
            if ($value === '' || strlen($value) > 512) {
                continue;
            }
            $result[] = ['name' => $name, 'value' => $value];
        }
        return $result;
    }

    private static function sanitize_namespace(string $namespace): string
    {
        $namespace = trim($namespace, " \t\n\r\0\x0B/");
        return preg_match('#^[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)?$#', $namespace) ? $namespace : '';
    }

    private static function sanitize_admin_css(string $css): string
    {
        $css = substr($css, 0, 4000);
        $css = preg_replace('#</?style[^>]*>#i', '', $css);
        $css = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $css);
        return trim((string) $css);
    }

    private static function callback_plugin_source($callback): string
    {
        try {
            $file = '';
            if (is_string($callback) && function_exists($callback)) {
                $file = (new ReflectionFunction($callback))->getFileName() ?: '';
            } elseif (is_array($callback) && count($callback) === 2) {
                $file = (new ReflectionMethod($callback[0], (string) $callback[1]))->getFileName() ?: '';
            } elseif ($callback instanceof Closure) {
                $file = (new ReflectionFunction($callback))->getFileName() ?: '';
            } elseif (is_object($callback) && is_callable($callback)) {
                $file = (new ReflectionMethod($callback, '__invoke'))->getFileName() ?: '';
            }

            $file = wp_normalize_path($file);
            $plugin_dir = trailingslashit(wp_normalize_path(WP_PLUGIN_DIR));
            if ($file === '' || !str_starts_with($file, $plugin_dir)) {
                return '';
            }
            $relative = ltrim(substr($file, strlen($plugin_dir)), '/');
            $parts = explode('/', $relative);
            return sanitize_text_field(count($parts) > 1 ? $parts[0] : $relative);
        } catch (ReflectionException $e) {
            return '';
        }
    }

    private static function login_ip_key(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        return substr(hash_hmac('sha256', $ip, wp_salt('auth')), 0, 24);
    }

    private static function login_key(string $username): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        return substr(hash_hmac('sha256', strtolower(trim($username)) . '|' . $ip, wp_salt('auth')), 0, 24);
    }

    private static function namespace_from_route(string $route): string
    {
        if (preg_match('#^/([^/]+/v\d+)#', $route, $m)) {
            return sanitize_text_field($m[1]);
        }
        if (preg_match('#^/([^/]+)#', $route, $m)) {
            return sanitize_text_field($m[1]);
        }
        return '';
    }
}
