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
        $this->directory = trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
        $this->target = trailingslashit($this->directory) . 'bastion-core.php';
    }

    public function get_status(): array
    {
        $source_version = $this->read_version($this->source);
        $target_version = $this->read_version($this->target);

        if (!file_exists($this->target)) {
            return [
                'status'         => 'missing',
                'source_version' => $source_version,
                'target_version' => null,
                'message'        => __('Bastion Core não está instalado.', 'bastionwp'),
            ];
        }

        if ($target_version === null) {
            return [
                'status'         => 'invalid',
                'source_version' => $source_version,
                'target_version' => null,
                'message'        => __('Bastion Core existe, mas sua versão não pôde ser validada.', 'bastionwp'),
            ];
        }

        if ($source_version !== null && version_compare($target_version, $source_version, '<')) {
            return [
                'status'         => 'outdated',
                'source_version' => $source_version,
                'target_version' => $target_version,
                'message'        => __('Bastion Core está instalado, mas está desatualizado.', 'bastionwp'),
            ];
        }

        return [
            'status'         => 'ok',
            'source_version' => $source_version,
            'target_version' => $target_version,
            'message'        => __('Bastion Core instalado e validado.', 'bastionwp'),
        ];
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
                __('Não foi possível criar a pasta mu-plugins do WordPress.', 'bastionwp')
            );
        }

        if (!is_writable($this->directory)) {
            return new WP_Error(
                'bastionwp_mu_directory_not_writable',
                __('A pasta mu-plugins do WordPress não possui permissão de escrita.', 'bastionwp')
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

        $temporary = $this->target . '.bastionwp-new';

        $written = file_put_contents($temporary, $contents, LOCK_EX);

        if ($written === false) {
            return new WP_Error(
                'bastionwp_core_write_failed',
                __('Não foi possível preparar o novo Bastion Core.', 'bastionwp')
            );
        }

        if (!@rename($temporary, $this->target)) {
            @unlink($temporary);

            return new WP_Error(
                'bastionwp_core_replace_failed',
                __('Não foi possível substituir o Bastion Core com segurança.', 'bastionwp')
            );
        }

        clearstatcache(true, $this->target);

        $status = $this->get_status();

        if ($status['status'] !== 'ok') {
            return new WP_Error(
                'bastionwp_core_validation_failed',
                __('O Bastion Core foi instalado, mas não pôde ser validado.', 'bastionwp')
            );
        }

        return true;
    }

    public function get_target_path(): string
    {
        return $this->target;
    }

    private function validate_php_syntax(string $contents)
    {
        if (!function_exists('exec')) {
            // O ambiente pode bloquear exec(). Nesse caso o pacote já foi
            // validado no build; seguimos sem prometer validação runtime.
            return true;
        }

        $temp = wp_tempnam('bastion-core.php');

        if (!$temp) {
            return true;
        }

        $result = file_put_contents($temp, $contents, LOCK_EX);

        if ($result === false) {
            @unlink($temp);
            return true;
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
}
