<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Logger
{
    private const DB_VERSION = '1';
    private const DB_VERSION_OPTION = 'bastionwp_log_db_version';
    private const LAST_CLEANUP_OPTION = 'bastionwp_log_last_cleanup';
    private const RETENTION_DAYS = 90;
    private const MAX_ROWS = 5000;

    public static function install_schema(): void
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
            message text NOT NULL,
            context longtext NULL,
            PRIMARY KEY  (id),
            KEY event_time (event_time),
            KEY event_type (event_type),
            KEY level (level),
            KEY user_id (user_id)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
    }

    public static function maybe_install_schema(): void
    {
        if ((string) get_option(self::DB_VERSION_OPTION, '') !== self::DB_VERSION) {
            self::install_schema();
        }
    }

    public static function table_exists(): bool
    {
        global $wpdb;

        $table = self::table_name();
        $found = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $table)
        );

        return $found === $table;
    }

    public static function log(
        string $event_type,
        string $message,
        string $level = 'info',
        array $context = [],
        ?int $user_id = null
    ): bool {
        global $wpdb;

        self::maybe_install_schema();

        if (!self::table_exists()) {
            return false;
        }

        $allowed_levels = ['info', 'success', 'warning', 'error'];

        if (!in_array($level, $allowed_levels, true)) {
            $level = 'info';
        }

        $event_type = sanitize_key($event_type);
        $message = sanitize_text_field($message);
        $user_id = $user_id === null ? get_current_user_id() : absint($user_id);
        $safe_context = self::sanitize_context($context);

        $inserted = $wpdb->insert(
            self::table_name(),
            [
                'event_time' => current_time('mysql', true),
                'level'      => $level,
                'event_type' => $event_type,
                'user_id'    => $user_id,
                'message'    => $message,
                'context'    => empty($safe_context)
                    ? null
                    : wp_json_encode($safe_context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        self::maybe_cleanup();

        return $inserted !== false;
    }

    public static function get_logs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        global $wpdb;

        if (!self::table_exists()) {
            return [];
        }

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

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY id DESC LIMIT %%d OFFSET %%d',
            self::table_name(),
            implode(' AND ', $where)
        );

        $values[] = $limit;
        $values[] = $offset;

        $prepared = $wpdb->prepare($sql, ...$values);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public static function count_logs(array $filters = []): int
    {
        global $wpdb;

        if (!self::table_exists()) {
            return 0;
        }

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

        $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::table_name());

        if ($count > self::MAX_ROWS) {
            $delete_count = $count - self::MAX_ROWS;

            $wpdb->query(
                $wpdb->prepare(
                    'DELETE FROM ' . self::table_name() . ' ORDER BY id ASC LIMIT %d',
                    $delete_count
                )
            );
        }

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

    private static function sanitize_context(array $context): array
    {
        $safe = [];

        foreach ($context as $key => $value) {
            $key_string = sanitize_key((string) $key);

            if (
                preg_match(
                    '/(?:pass|password|token|secret|api[_-]?key|license|cookie|authorization|nonce)/i',
                    $key_string
                )
            ) {
                $safe[$key_string] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $safe[$key_string] = self::sanitize_context($value);
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $safe[$key_string] = $value;
                continue;
            }

            if (is_scalar($value)) {
                $safe[$key_string] = sanitize_text_field((string) $value);
            }
        }

        return $safe;
    }
}
