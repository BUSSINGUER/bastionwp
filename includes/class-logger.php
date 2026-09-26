<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Logger
{
    private const DB_VERSION = '2';
    private const DB_VERSION_OPTION = 'bastionwp_log_db_version';
    private const LAST_CLEANUP_OPTION = 'bastionwp_log_last_cleanup';
    private const RETENTION_DAYS = 90;
    private const MAX_ROWS = 5000;
    private const CRON_HOOK = 'bastionwp_log_cleanup';
    private static ?bool $schema_valid_cache = null;

    public static function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [self::class, 'delete_old_logs']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function install_schema(): bool
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_time datetime NOT NULL,
            level varchar(20) NOT NULL DEFAULT 'info',
            event_type varchar(100) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            target_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            request_id varchar(64) NOT NULL DEFAULT '',
            message text NOT NULL,
            context longtext NULL,
            PRIMARY KEY  (id),
            KEY event_time (event_time),
            KEY event_type (event_type),
            KEY level (level),
            KEY user_id (user_id),
            KEY target_user_id (target_user_id),
            KEY request_id (request_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::table_exists() || !self::schema_is_valid()) {
            self::$schema_valid_cache = false;
            return false;
        }

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
        self::$schema_valid_cache = true;
        return true;
    }

    public static function maybe_install_schema(): bool
    {
        if (self::$schema_valid_cache === true) {
            return true;
        }

        $valid = self::table_exists() && self::schema_is_valid();

        if (
            (string) get_option(self::DB_VERSION_OPTION, '') !== self::DB_VERSION
            || !$valid
        ) {
            return self::install_schema();
        }

        self::$schema_valid_cache = true;
        return true;
    }

    public static function table_exists(): bool
    {
        global $wpdb;

        $table = self::table_name();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        return $found === $table;
    }

    public static function schema_is_valid(): bool
    {
        global $wpdb;

        if (!self::table_exists()) {
            return false;
        }

        $wpdb->last_error = '';
        $wpdb->get_results(
            'SELECT id, event_time, level, event_type, user_id, target_user_id, request_id, message, context FROM ' .
            self::table_name() .
            ' LIMIT 1',
            ARRAY_A
        );

        return $wpdb->last_error === '';
    }

    public static function log(
        string $event_type,
        string $message,
        string $level = 'info',
        array $context = [],
        ?int $user_id = null,
        string $request_id = '',
        ?int $target_user_id = null
    ): bool {
        global $wpdb;

        if (!self::maybe_install_schema()) {
            update_option('bastionwp_log_last_error', 'schema_unavailable', false);
            return false;
        }

        $allowed_levels = ['info', 'success', 'warning', 'error'];
        if (!in_array($level, $allowed_levels, true)) {
            $level = 'info';
        }

        $event_type = sanitize_key($event_type);
        $message = self::sanitize_message($message);
        $user_id = $user_id === null ? get_current_user_id() : absint($user_id);

        if ($request_id === '' && isset($context['request_id'])) {
            $request_id = sanitize_text_field((string) $context['request_id']);
        }

        if (
            $request_id === ''
            && $user_id > 0
            && class_exists('BastionWP_Users')
            && class_exists('BastionWP_Temporary_Admin')
            && BastionWP_Users::is_client_manager_user_id($user_id)
        ) {
            $active_request = BastionWP_Temporary_Admin::get_active_for_user($user_id);
            if ($active_request) {
                $request_id = (string) ($active_request['id'] ?? '');
                $target_user_id = $target_user_id ?? $user_id;
            }
        }

        if ($target_user_id === null && isset($context['target_user_id'])) {
            $target_user_id = absint($context['target_user_id']);
        }

        $request_id = substr(sanitize_text_field($request_id), 0, 64);
        $target_user_id = absint($target_user_id ?? 0);
        $safe_context = self::sanitize_context($context);

        $inserted = $wpdb->insert(
            self::table_name(),
            [
                'event_time'     => current_time('mysql', true),
                'level'          => $level,
                'event_type'     => $event_type,
                'user_id'        => $user_id,
                'target_user_id' => $target_user_id,
                'request_id'     => $request_id,
                'message'        => $message,
                'context'        => empty($safe_context)
                    ? null
                    : wp_json_encode($safe_context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            update_option('bastionwp_log_last_error', sanitize_text_field((string) $wpdb->last_error), false);
            return false;
        }

        delete_option('bastionwp_log_last_error');
        self::enforce_max_rows();
        self::maybe_cleanup();

        return true;
    }

    public static function get_logs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        global $wpdb;

        if (!self::table_exists() || !self::schema_is_valid()) {
            return [];
        }

        [$where, $values] = self::build_where($filters);
        $limit = max(1, min(1000, $limit));
        $offset = max(0, $offset);

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY id DESC LIMIT %%d OFFSET %%d',
            self::table_name(),
            implode(' AND ', $where)
        );

        $values[] = $limit;
        $values[] = $offset;
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$values), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public static function count_logs(array $filters = []): int
    {
        global $wpdb;

        if (!self::table_exists() || !self::schema_is_valid()) {
            return 0;
        }

        [$where, $values] = self::build_where($filters);
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s',
            self::table_name(),
            implode(' AND ', $where)
        );

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, ...$values);
        }

        return (int) $wpdb->get_var($sql);
    }

    public static function get_event_types(): array
    {
        global $wpdb;

        if (!self::table_exists()) {
            return [];
        }

        $rows = $wpdb->get_col(
            'SELECT DISTINCT event_type FROM ' . self::table_name() . ' ORDER BY event_type ASC'
        );

        return is_array($rows) ? array_values(array_filter(array_map('sanitize_key', $rows))) : [];
    }

    public static function clear(): bool
    {
        global $wpdb;

        if (!self::table_exists()) {
            return true;
        }

        return $wpdb->query('TRUNCATE TABLE ' . self::table_name()) !== false;
    }

    public static function delete_old_logs(): void
    {
        global $wpdb;

        if (!self::table_exists()) {
            return;
        }

        $cutoff = gmdate('Y-m-d H:i:s', time() - (self::RETENTION_DAYS * DAY_IN_SECONDS));
        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . self::table_name() . ' WHERE event_time < %s',
                $cutoff
            )
        );

        self::enforce_max_rows();
        update_option(self::LAST_CLEANUP_OPTION, time(), false);
    }

    public static function retention_days(): int
    {
        return self::RETENTION_DAYS;
    }

    public static function max_rows(): int
    {
        return self::MAX_ROWS;
    }

    private static function build_where(array $filters): array
    {
        $where = ['1=1'];
        $values = [];

        if (!empty($filters['level'])) {
            $where[] = 'level = %s';
            $values[] = sanitize_key((string) $filters['level']);
        }
        if (!empty($filters['event_type'])) {
            $where[] = 'event_type = %s';
            $values[] = sanitize_key((string) $filters['event_type']);
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = absint($filters['user_id']);
        }
        if (!empty($filters['target_user_id'])) {
            $where[] = 'target_user_id = %d';
            $values[] = absint($filters['target_user_id']);
        }
        if (!empty($filters['request_id'])) {
            $where[] = 'request_id = %s';
            $values[] = substr(sanitize_text_field((string) $filters['request_id']), 0, 64);
        }
        if (!empty($filters['start_time'])) {
            $where[] = 'event_time >= %s';
            $values[] = self::normalize_time_filter($filters['start_time']);
        }
        if (!empty($filters['end_time'])) {
            $where[] = 'event_time <= %s';
            $values[] = self::normalize_time_filter($filters['end_time']);
        }

        return [$where, $values];
    }

    private static function normalize_time_filter($value): string
    {
        if (is_numeric($value)) {
            return gmdate('Y-m-d H:i:s', (int) $value);
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : '1970-01-01 00:00:00';
    }

    private static function enforce_max_rows(): void
    {
        global $wpdb;

        $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::table_name());
        if ($count <= self::MAX_ROWS) {
            return;
        }

        $delete_count = $count - self::MAX_ROWS;
        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . self::table_name() . ' ORDER BY id ASC LIMIT %d',
                $delete_count
            )
        );
    }

    private static function maybe_cleanup(): void
    {
        $last = (int) get_option(self::LAST_CLEANUP_OPTION, 0);
        if ($last <= 0 || (time() - $last) >= DAY_IN_SECONDS) {
            self::delete_old_logs();
        }
    }

    private static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'bastionwp_logs';
    }

    private static function sanitize_message(string $message): string
    {
        $message = sanitize_text_field($message);
        $message = preg_replace('/(bearer\\s+)[a-z0-9._~+\\/=-]+/i', '$1[redacted]', $message) ?? $message;
        $message = preg_replace('/((?:password|token|secret|api[_-]?key|nonce)\\s*[=:]\\s*)[^\\s,;]+/i', '$1[redacted]', $message) ?? $message;
        return function_exists('mb_substr') ? mb_substr($message, 0, 2000) : substr($message, 0, 2000);
    }

    private static function sanitize_context(array $context, int $depth = 0): array
    {
        if ($depth > 3) {
            return ['truncated' => true];
        }

        $safe = [];
        $count = 0;

        foreach ($context as $key => $value) {
            if (++$count > 40) {
                $safe['truncated'] = true;
                break;
            }

            $key_string = sanitize_key((string) $key);
            if ($key_string === '') {
                continue;
            }

            if (preg_match('/(?:pass|password|token|secret|api[_-]?key|license|cookie|authorization|nonce)/i', $key_string)) {
                $safe[$key_string] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $safe[$key_string] = self::sanitize_context($value, $depth + 1);
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $safe[$key_string] = $value;
                continue;
            }

            if (is_scalar($value)) {
                $text = self::sanitize_message((string) $value);
                $safe[$key_string] = function_exists('mb_substr') ? mb_substr($text, 0, 1000) : substr($text, 0, 1000);
            }
        }

        return $safe;
    }
}
