<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_MU_Installer
{
    private string $source;
    private string $directory;
    private string $target;

    public function __construct()
    {
        $this->source = BASTIONWP_DIR . 'mu/bastion-core.stub';
        $this->directory = defined('WPMU_PLUGIN_DIR')
            ? untrailingslashit((string) WPMU_PLUGIN_DIR)
            : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
        $this->target = trailingslashit($this->directory) . 'bastion-core.php';
    }

    public function get_status(): array
    {
        $source_version = $this->read_version($this->source);
        $target_version = $this->read_version($this->target);
        $source_hash = $this->file_hash($this->source);
        $target_hash = $this->file_hash($this->target);
        $integrity = $source_hash !== null && $target_hash !== null && hash_equals($source_hash, $target_hash);
        $disabled = defined('BASTIONWP_DISABLE_CORE') && BASTIONWP_DISABLE_CORE;
        $active = !$disabled && (
            (defined('BASTION_CORE_BOOTED') && BASTION_CORE_BOOTED)
            || did_action('bastionwp_core_loaded') > 0
        );

        $base = [
            'source_version' => $source_version,
            'target_version' => $target_version,
            'source_hash'    => $source_hash,
            'target_hash'    => $target_hash,
            'integrity'      => $integrity,
            'active'         => $active,
            'disabled'       => $disabled,
            'target_path'    => $this->target,
        ];

        if (!file_exists($this->target)) {
            return array_merge($base, [
                'status'  => 'missing',
                'message' => __('Bastion Core não está instalado.', 'bastionwp'),
            ]);
        }

        if ($target_version === null || $target_hash === null) {
            return array_merge($base, [
                'status'  => 'invalid',
                'message' => __('Bastion Core existe, mas o arquivo não pôde ser validado.', 'bastionwp'),
            ]);
        }

        if ($source_version !== null && version_compare($target_version, $source_version, '<')) {
            return array_merge($base, [
                'status'  => 'outdated',
                'message' => __('Bastion Core está instalado, mas está desatualizado.', 'bastionwp'),
            ]);
        }

        if (!$integrity) {
            return array_merge($base, [
                'status'  => 'integrity_error',
                'message' => __('Bastion Core não coincide com a cópia auditada incluída nesta versão.', 'bastionwp'),
            ]);
        }

        if ($disabled) {
            return array_merge($base, [
                'status'  => 'disabled',
                'message' => __('Bastion Core está instalado e íntegro, porém desabilitado pelo modo de emergência.', 'bastionwp'),
            ]);
        }

        if (!$active) {
            return array_merge($base, [
                'status'  => 'inactive',
                'message' => __('Bastion Core está instalado e íntegro, mas não há sinal de que suas proteções carregaram nesta requisição.', 'bastionwp'),
            ]);
        }

        return array_merge($base, [
            'status'  => 'ok',
            'message' => __('Bastion Core instalado, íntegro e ativo.', 'bastionwp'),
        ]);
    }

    public function install_or_repair()
    {
        if (!file_exists($this->source) || !is_readable($this->source)) {
            return new WP_Error(
                'bastionwp_core_source_missing',
                __('O arquivo interno do Bastion Core está ausente ou não pode ser lido.', 'bastionwp')
            );
        }

        if (!is_dir($this->directory) && !wp_mkdir_p($this->directory)) {
            return new WP_Error(
                'bastionwp_mu_directory_failed',
                __('Não foi possível criar a pasta mu-plugins configurada pelo WordPress.', 'bastionwp')
            );
        }

        if (!is_writable($this->directory)) {
            return new WP_Error(
                'bastionwp_mu_directory_not_writable',
                __('A pasta mu-plugins configurada pelo WordPress não possui permissão de escrita.', 'bastionwp')
            );
        }

        $contents = file_get_contents($this->source);

        if ($contents === false || $contents === '') {
            return new WP_Error(
                'bastionwp_core_source_read_failed',
                __('Não foi possível ler o arquivo interno do Bastion Core.', 'bastionwp')
            );
        }

        $syntax = $this->validate_php_syntax($contents);

        if (is_wp_error($syntax)) {
            return $syntax;
        }

        $temporary = $this->create_temporary_file('bastion-core.php', $this->directory);

        if (!$temporary) {
            return new WP_Error(
                'bastionwp_core_temp_failed',
                __('Não foi possível criar um arquivo temporário seguro para o Bastion Core.', 'bastionwp')
            );
        }

        $expected_bytes = strlen($contents);
        $written = file_put_contents($temporary, $contents, LOCK_EX);

        if ($written === false || $written !== $expected_bytes) {
            @unlink($temporary);
            return new WP_Error(
                'bastionwp_core_write_failed',
                __('Não foi possível preparar integralmente o novo Bastion Core.', 'bastionwp')
            );
        }

        $source_hash = hash('sha256', $contents);
        $temporary_hash = hash_file('sha256', $temporary);

        if (!is_string($temporary_hash) || !hash_equals($source_hash, $temporary_hash)) {
            @unlink($temporary);
            return new WP_Error(
                'bastionwp_core_temp_integrity_failed',
                __('O arquivo temporário do Bastion Core falhou na verificação de integridade.', 'bastionwp')
            );
        }

        $backup = $this->target . '.bastionwp-backup';
        @unlink($backup);
        $had_target = file_exists($this->target);

        if ($had_target && !@rename($this->target, $backup)) {
            @unlink($temporary);
            return new WP_Error(
                'bastionwp_core_backup_failed',
                __('Não foi possível preservar a versão anterior do Bastion Core antes da substituição.', 'bastionwp')
            );
        }

        if (!@rename($temporary, $this->target)) {
            @unlink($temporary);
            if ($had_target && file_exists($backup)) {
                @rename($backup, $this->target);
            }
            return new WP_Error(
                'bastionwp_core_replace_failed',
                __('Não foi possível substituir o Bastion Core com segurança; a versão anterior foi preservada quando possível.', 'bastionwp')
            );
        }

        clearstatcache(true, $this->target);
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->target, true);
        }

        $target_hash = $this->file_hash($this->target);
        $target_version = $this->read_version($this->target);
        $source_version = $this->read_version($this->source);

        if (
            $target_hash === null
            || !hash_equals($source_hash, $target_hash)
            || $target_version === null
            || ($source_version !== null && $target_version !== $source_version)
        ) {
            @unlink($this->target);
            if ($had_target && file_exists($backup)) {
                @rename($backup, $this->target);
            }
            return new WP_Error(
                'bastionwp_core_validation_failed',
                __('O Bastion Core foi gravado, mas falhou na validação final; a versão anterior foi restaurada quando possível.', 'bastionwp')
            );
        }

        @unlink($backup);
        return true;
    }

    public function get_target_path(): string
    {
        return $this->target;
    }

    public function get_source_path(): string
    {
        return $this->source;
    }

    public function remove_core()
    {
        if (!file_exists($this->target)) {
            return true;
        }

        if (!is_writable($this->target) && !is_writable(dirname($this->target))) {
            return new WP_Error(
                'bastionwp_core_remove_not_writable',
                __('O Bastion Core não pôde ser removido porque o arquivo/pasta não possui permissão de escrita.', 'bastionwp')
            );
        }

        if (!@unlink($this->target)) {
            return new WP_Error(
                'bastionwp_core_remove_failed',
                __('Não foi possível remover o Bastion Core da pasta mu-plugins.', 'bastionwp')
            );
        }

        clearstatcache(true, $this->target);
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->target, true);
        }

        return true;
    }

    private function validate_php_syntax(string $contents)
    {
        if (!function_exists('exec') || !defined('PHP_BINARY') || PHP_BINARY === '' || !is_executable(PHP_BINARY)) {
            return null;
        }

        $temp = $this->create_temporary_file('bastion-core-lint.php');

        if (!$temp) {
            return null;
        }

        $expected_bytes = strlen($contents);
        $result = file_put_contents($temp, $contents, LOCK_EX);

        if ($result === false || $result !== $expected_bytes) {
            @unlink($temp);
            return new WP_Error(
                'bastionwp_core_lint_temp_failed',
                __('Não foi possível preparar o arquivo temporário para validação de sintaxe.', 'bastionwp')
            );
        }

        $output = [];
        $exit_code = 0;
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($temp) . ' 2>&1';
        @exec($command, $output, $exit_code);
        @unlink($temp);

        if ($exit_code !== 0) {
            return new WP_Error(
                'bastionwp_core_syntax_error',
                __('O novo Bastion Core falhou na validação de sintaxe e não foi instalado.', 'bastionwp')
            );
        }

        return true;
    }

    /**
     * Creates a temporary file safely even when the WordPress file API has
     * not yet been loaded during early init/version migrations.
     *
     * @return string|false
     */
    private function create_temporary_file(string $filename, ?string $directory = null)
    {
        if (!function_exists('wp_tempnam')) {
            $wordpress_file_api = ABSPATH . 'wp-admin/includes/file.php';

            if (is_readable($wordpress_file_api)) {
                require_once $wordpress_file_api;
            }
        }

        if (function_exists('wp_tempnam')) {
            return wp_tempnam($filename, $directory ?? '');
        }

        $temp_directory = $directory;

        if ($temp_directory === null || $temp_directory === '') {
            $temp_directory = function_exists('get_temp_dir')
                ? get_temp_dir()
                : sys_get_temp_dir();
        }

        $temp_directory = untrailingslashit((string) $temp_directory);

        if (
            $temp_directory === ''
            || !is_dir($temp_directory)
            || !is_writable($temp_directory)
        ) {
            return false;
        }

        $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '-', basename($filename));
        $safe_name = is_string($safe_name) && $safe_name !== ''
            ? substr($safe_name, 0, 24)
            : 'bastion-core';

        return tempnam($temp_directory, 'bwp-' . $safe_name . '-');
    }

    private function read_version(string $path): ?string
    {
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        if (preg_match("/define\\(\\s*'BASTION_CORE_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $contents, $matches)) {
            return sanitize_text_field($matches[1]);
        }

        return null;
    }

    private function file_hash(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);
        return is_string($hash) && $hash !== '' ? $hash : null;
    }
}
