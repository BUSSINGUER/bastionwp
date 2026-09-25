<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Technical configuration snapshots used before sensitive file writes.
 *
 * This is intentionally not a full-site backup system. It only handles an
 * explicit allowlist of configuration files that BastionWP can safely restore.
 */
final class BastionWP_Config_Backup
{
    public const OPTION = 'bastionwp_config_snapshots';
    public const MAX_PER_TARGET = 5;

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function get_targets(): array
    {
        $targets = [];

        $wp_config = self::locate_wp_config();
        $targets['wp_config'] = self::target_definition(
            'wp_config',
            __('wp-config.php', 'bastionwp'),
            $wp_config,
            __('Configuração principal do WordPress. Pode conter credenciais e constantes sensíveis.', 'bastionwp')
        );

        $targets['htaccess'] = self::target_definition(
            'htaccess',
            __('.htaccess', 'bastionwp'),
            trailingslashit(ABSPATH) . '.htaccess',
            __('Regras Apache do diretório principal do WordPress.', 'bastionwp')
        );

        $targets['web_config'] = self::target_definition(
            'web_config',
            __('web.config', 'bastionwp'),
            trailingslashit(ABSPATH) . 'web.config',
            __('Regras IIS do diretório principal do WordPress.', 'bastionwp')
        );

        $mu_dir = defined('WPMU_PLUGIN_DIR') && WPMU_PLUGIN_DIR
            ? WPMU_PLUGIN_DIR
            : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';

        $targets['bastion_core'] = self::target_definition(
            'bastion_core',
            __('Bastion Core', 'bastionwp'),
            trailingslashit($mu_dir) . 'bastion-core.php',
            __('MU plugin responsável pelas proteções essenciais persistentes do BastionWP.', 'bastionwp')
        );

        return $targets;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function get_snapshots(): array
    {
        $items = get_option(self::OPTION, []);
        if (!is_array($items)) {
            return [];
        }

        $items = array_values(array_filter($items, static function ($item): bool {
            return is_array($item) && !empty($item['id']) && !empty($item['target_key']) && !empty($item['backup_file']);
        }));

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $items;
    }

    public static function get_snapshot(string $snapshot_id): ?array
    {
        foreach (self::get_snapshots() as $item) {
            if (hash_equals((string) $item['id'], $snapshot_id)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Creates a snapshot of a supported file.
     *
     * @return string|WP_Error Snapshot id on success.
     */
    public static function create_snapshot(string $target_key, string $reason = '', ?int $user_id = null)
    {
        $targets = self::get_targets();
        if (!isset($targets[$target_key])) {
            return new WP_Error('bastionwp_backup_target', __('Arquivo não permitido para snapshot técnico.', 'bastionwp'));
        }

        $target = $targets[$target_key];
        $source = (string) $target['path'];
        if ($source === '' || !is_file($source) || !is_readable($source)) {
            return new WP_Error('bastionwp_backup_missing', __('O arquivo selecionado não existe ou não pode ser lido.', 'bastionwp'));
        }

        $contents = file_get_contents($source);
        if ($contents === false) {
            return new WP_Error('bastionwp_backup_read', __('Não foi possível ler o arquivo para criar o snapshot.', 'bastionwp'));
        }

        $storage = self::ensure_storage_directory();
        if (is_wp_error($storage)) {
            return $storage;
        }

        $snapshot_id = wp_generate_uuid4();
        $safe_target = sanitize_key($target_key);
        $backup_name = $safe_target . '-' . gmdate('Ymd-His') . '-' . substr(hash('sha256', $snapshot_id), 0, 12) . '.snapshot';
        $backup_path = trailingslashit($storage) . $backup_name;
        $write = self::atomic_write($backup_path, $contents, false);
        if (is_wp_error($write)) {
            return $write;
        }

        @chmod($backup_path, 0600);

        $user_id = $user_id ?? get_current_user_id();
        $item = [
            'id'             => $snapshot_id,
            'target_key'     => $target_key,
            'target_label'   => (string) $target['label'],
            'backup_file'    => $backup_name,
            'sha256'         => hash('sha256', $contents),
            'size'           => strlen($contents),
            'bastion_version'=> defined('BASTIONWP_VERSION') ? BASTIONWP_VERSION : '',
            'user_id'        => (int) $user_id,
            'reason'         => sanitize_text_field($reason !== '' ? $reason : __('Snapshot manual', 'bastionwp')),
            'created_at'     => current_time('mysql', true),
        ];

        $items = self::get_snapshots();
        array_unshift($items, $item);
        self::save_snapshots(self::apply_retention($items));

        if (class_exists('BastionWP_Logger')) {
            BastionWP_Logger::log(
                'config_snapshot_created',
                sprintf(__('Snapshot criado para %s.', 'bastionwp'), (string) $target['label']),
                'success',
                ['snapshot_id' => $snapshot_id, 'target' => $target_key, 'sha256' => $item['sha256'], 'reason' => $item['reason']]
            );
        }

        return $snapshot_id;
    }

    /**
     * Creates a pre-write snapshot only when the target already exists.
     *
     * @return true|WP_Error
     */
    public static function before_sensitive_write(string $target_key, string $reason)
    {
        $targets = self::get_targets();
        if (!isset($targets[$target_key])) {
            return new WP_Error('bastionwp_backup_target', __('Arquivo sensível não reconhecido pelo sistema de snapshots.', 'bastionwp'));
        }

        if (empty($targets[$target_key]['exists'])) {
            return true;
        }

        $result = self::create_snapshot($target_key, $reason);
        return is_wp_error($result) ? $result : true;
    }

    /**
     * Restores a snapshot with an automatic snapshot of the current state.
     *
     * @return true|WP_Error
     */
    public static function restore_snapshot(string $snapshot_id)
    {
        $snapshot = self::get_snapshot($snapshot_id);
        if (!$snapshot) {
            return new WP_Error('bastionwp_backup_unknown', __('Snapshot não encontrado.', 'bastionwp'));
        }

        $targets = self::get_targets();
        $target_key = (string) $snapshot['target_key'];
        if (!isset($targets[$target_key])) {
            return new WP_Error('bastionwp_backup_target', __('O destino deste snapshot não é mais suportado.', 'bastionwp'));
        }

        $storage = self::get_storage_directory();
        $backup_path = trailingslashit($storage) . basename((string) $snapshot['backup_file']);
        if (!is_file($backup_path) || !is_readable($backup_path)) {
            return new WP_Error('bastionwp_backup_file_missing', __('O arquivo físico deste snapshot não está disponível.', 'bastionwp'));
        }

        $contents = file_get_contents($backup_path);
        if ($contents === false || !hash_equals((string) $snapshot['sha256'], hash('sha256', $contents))) {
            return new WP_Error('bastionwp_backup_integrity', __('A integridade do snapshot não pôde ser validada.', 'bastionwp'));
        }

        $target_path = (string) $targets[$target_key]['path'];
        if ($target_path === '') {
            return new WP_Error('bastionwp_backup_target_path', __('Não foi possível resolver o caminho do arquivo original.', 'bastionwp'));
        }

        if (is_file($target_path)) {
            $pre_restore = self::create_snapshot(
                $target_key,
                sprintf(__('Estado anterior à restauração do snapshot %s', 'bastionwp'), substr($snapshot_id, 0, 8))
            );
            if (is_wp_error($pre_restore)) {
                return $pre_restore;
            }
        }

        $write = self::atomic_write($target_path, $contents, true);
        if (is_wp_error($write)) {
            return $write;
        }

        $restored_hash = @hash_file('sha256', $target_path);
        if (!is_string($restored_hash) || !hash_equals((string) $snapshot['sha256'], $restored_hash)) {
            return new WP_Error('bastionwp_backup_restore_hash', __('O arquivo foi gravado, mas o hash final não corresponde ao snapshot.', 'bastionwp'));
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($target_path, true);
        }

        if (class_exists('BastionWP_Logger')) {
            BastionWP_Logger::log(
                'config_snapshot_restored',
                sprintf(__('Snapshot restaurado para %s.', 'bastionwp'), (string) $targets[$target_key]['label']),
                'warning',
                ['snapshot_id' => $snapshot_id, 'target' => $target_key, 'sha256' => $snapshot['sha256']]
            );
        }

        return true;
    }

    /**
     * @return true|WP_Error
     */
    public static function delete_snapshot(string $snapshot_id)
    {
        $items = self::get_snapshots();
        $found = null;
        $remaining = [];

        foreach ($items as $item) {
            if ($found === null && hash_equals((string) $item['id'], $snapshot_id)) {
                $found = $item;
                continue;
            }
            $remaining[] = $item;
        }

        if (!$found) {
            return new WP_Error('bastionwp_backup_unknown', __('Snapshot não encontrado.', 'bastionwp'));
        }

        $path = trailingslashit(self::get_storage_directory()) . basename((string) $found['backup_file']);
        if (is_file($path) && !@unlink($path)) {
            return new WP_Error('bastionwp_backup_delete', __('Não foi possível excluir o arquivo físico do snapshot.', 'bastionwp'));
        }

        self::save_snapshots($remaining);

        if (class_exists('BastionWP_Logger')) {
            BastionWP_Logger::log(
                'config_snapshot_deleted',
                sprintf(__('Snapshot excluído: %s.', 'bastionwp'), (string) ($found['target_label'] ?? $found['target_key'])),
                'info',
                ['snapshot_id' => $snapshot_id, 'target' => $found['target_key']]
            );
        }

        return true;
    }

    /**
     * @return array<string,mixed>|WP_Error
     */
    public static function compare_snapshot(string $snapshot_id)
    {
        $snapshot = self::get_snapshot($snapshot_id);
        if (!$snapshot) {
            return new WP_Error('bastionwp_backup_unknown', __('Snapshot não encontrado.', 'bastionwp'));
        }

        $targets = self::get_targets();
        $target_key = (string) $snapshot['target_key'];
        if (!isset($targets[$target_key])) {
            return new WP_Error('bastionwp_backup_target', __('Destino do snapshot não suportado.', 'bastionwp'));
        }

        $backup_path = trailingslashit(self::get_storage_directory()) . basename((string) $snapshot['backup_file']);
        $target_path = (string) $targets[$target_key]['path'];
        if (!is_file($backup_path) || !is_readable($backup_path)) {
            return new WP_Error('bastionwp_backup_file_missing', __('Arquivo do snapshot indisponível.', 'bastionwp'));
        }

        $backup_contents = (string) file_get_contents($backup_path);
        $current_contents = is_file($target_path) && is_readable($target_path)
            ? (string) file_get_contents($target_path)
            : '';

        $backup_lines = preg_split('/\R/', $backup_contents) ?: [];
        $current_lines = preg_split('/\R/', $current_contents) ?: [];
        $max = max(count($backup_lines), count($current_lines));
        $changes = [];

        for ($i = 0; $i < $max && count($changes) < 120; $i++) {
            $before = $backup_lines[$i] ?? null;
            $after = $current_lines[$i] ?? null;
            if ($before === $after) {
                continue;
            }
            $changes[] = [
                'line'   => $i + 1,
                'backup' => self::redact_sensitive_line($before),
                'current'=> self::redact_sensitive_line($after),
            ];
        }

        return [
            'snapshot'        => $snapshot,
            'target'          => $targets[$target_key],
            'current_exists'  => is_file($target_path),
            'current_sha256'  => is_file($target_path) ? (string) @hash_file('sha256', $target_path) : '',
            'same'            => is_file($target_path) && hash_equals((string) $snapshot['sha256'], (string) @hash_file('sha256', $target_path)),
            'changes'         => $changes,
            'changes_limited' => count($changes) >= 120,
        ];
    }

    /**
     * Removes all snapshot files and metadata. Used only by the explicit
     * "remove BastionWP data" flow.
     */
    public static function purge_all(): bool
    {
        $ok = true;
        $storage = self::get_storage_directory();

        foreach (self::get_snapshots() as $item) {
            $file = trailingslashit($storage) . basename((string) ($item['backup_file'] ?? ''));
            if (is_file($file) && !@unlink($file)) {
                $ok = false;
            }
        }

        delete_option(self::OPTION);

        if (is_dir($storage)) {
            $remaining = @scandir($storage);
            if (is_array($remaining) && count(array_diff($remaining, ['.', '..'])) === 0) {
                @rmdir($storage);
            }
        }

        return $ok;
    }

    public static function get_storage_status(): array
    {
        $dir = self::get_storage_directory();
        $root = wp_normalize_path(ABSPATH);
        $normalized = wp_normalize_path($dir);

        return [
            'directory' => $dir,
            'outside_document_root' => strpos($normalized, $root) !== 0,
            'writable' => is_dir($dir) && is_writable($dir),
        ];
    }

    private static function redact_sensitive_line(?string $line): ?string
    {
        if ($line === null) {
            return null;
        }

        if (preg_match('/(DB_PASSWORD|AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT|SECURE_AUTH_SALT|LOGGED_IN_SALT|NONCE_SALT|password|passwd|secret|token)/i', $line)) {
            return '[conteúdo sensível ocultado pelo BastionWP]';
        }

        return strlen($line) > 500 ? substr($line, 0, 500) . '…' : $line;
    }

    private static function target_definition(string $key, string $label, string $path, string $description): array
    {
        return [
            'key'         => $key,
            'label'       => $label,
            'path'        => $path,
            'description' => $description,
            'exists'      => $path !== '' && is_file($path),
            'readable'    => $path !== '' && is_readable($path),
            'writable'    => $path !== '' && ((is_file($path) && is_writable($path)) || (!file_exists($path) && is_writable(dirname($path)))),
        ];
    }

    private static function locate_wp_config(): string
    {
        $inside = trailingslashit(ABSPATH) . 'wp-config.php';
        if (is_file($inside)) {
            return $inside;
        }

        $parent = trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'wp-config.php';
        return is_file($parent) ? $parent : $inside;
    }

    private static function get_storage_directory(): string
    {
        $parent = dirname(untrailingslashit(ABSPATH));
        if ($parent !== '' && is_dir($parent) && is_writable($parent)) {
            return trailingslashit($parent) . '.bastionwp-private/backups';
        }

        return trailingslashit(WP_CONTENT_DIR) . '.bastionwp-private/backups';
    }

    /** @return string|WP_Error */
    private static function ensure_storage_directory()
    {
        $dir = self::get_storage_directory();
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            return new WP_Error('bastionwp_backup_storage', __('Não foi possível criar o diretório privado de snapshots.', 'bastionwp'));
        }

        @chmod(dirname($dir), 0700);
        @chmod($dir, 0700);

        $guards = [
            dirname($dir) . '/index.php' => "<?php\n// Silence is golden.\n",
            dirname($dir) . '/.htaccess' => "Order deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n",
            dirname($dir) . '/web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n",
        ];
        foreach ($guards as $file => $contents) {
            if (!file_exists($file)) {
                @file_put_contents($file, $contents, LOCK_EX);
            }
        }

        return $dir;
    }

    /**
     * @return true|WP_Error
     */
    private static function atomic_write(string $destination, string $contents, bool $preserve_permissions)
    {
        $directory = dirname($destination);
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            return new WP_Error('bastionwp_backup_directory', __('Não foi possível preparar o diretório de gravação.', 'bastionwp'));
        }
        if (!is_writable($directory)) {
            return new WP_Error('bastionwp_backup_permissions', __('O diretório de destino não possui permissão de escrita.', 'bastionwp'));
        }

        $mode = null;
        if ($preserve_permissions && is_file($destination)) {
            $mode = @fileperms($destination);
        }

        $tmp = @tempnam($directory, '.bwp-');
        if (!$tmp) {
            return new WP_Error('bastionwp_backup_temp', __('Não foi possível criar arquivo temporário para gravação atômica.', 'bastionwp'));
        }

        $bytes = @file_put_contents($tmp, $contents, LOCK_EX);
        if ($bytes === false || $bytes !== strlen($contents)) {
            @unlink($tmp);
            return new WP_Error('bastionwp_backup_write', __('A gravação do arquivo temporário não foi concluída.', 'bastionwp'));
        }

        if (!@rename($tmp, $destination)) {
            @unlink($tmp);
            return new WP_Error('bastionwp_backup_replace', __('Não foi possível substituir o arquivo de destino de forma atômica.', 'bastionwp'));
        }

        if ($mode !== null) {
            @chmod($destination, $mode & 0777);
        }

        return true;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private static function apply_retention(array $items): array
    {
        $kept = [];
        $counts = [];
        $storage = self::get_storage_directory();

        foreach ($items as $item) {
            $target = (string) ($item['target_key'] ?? '');
            $counts[$target] = ($counts[$target] ?? 0) + 1;
            if ($counts[$target] <= self::MAX_PER_TARGET) {
                $kept[] = $item;
                continue;
            }

            $path = trailingslashit($storage) . basename((string) ($item['backup_file'] ?? ''));
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return $kept;
    }

    /** @param array<int,array<string,mixed>> $items */
    private static function save_snapshots(array $items): void
    {
        update_option(self::OPTION, array_values($items), false);
    }
}
