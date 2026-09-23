<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Diagnostics
{
    private BastionWP_MU_Installer $mu_installer;
    private BastionWP_Hardening $hardening;
    private BastionWP_Wordfence_Integration $wordfence;

    public function __construct(
        BastionWP_MU_Installer $mu_installer,
        BastionWP_Hardening $hardening,
        BastionWP_Wordfence_Integration $wordfence
    ) {
        $this->mu_installer = $mu_installer;
        $this->hardening = $hardening;
        $this->wordfence = $wordfence;
    }

    public function get_report(): array
    {
        global $wp_version;

        $core = $this->mu_installer->get_status();
        $wordfence = $this->wordfence->get_status();
        $update_settings = BastionWP_Update_Manager::get_settings();

        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $wp_debug_display = filter_var(
            ini_get('display_errors'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($wp_debug_display === null) {
            $wp_debug_display = (string) ini_get('display_errors') !== '0'
                && strtolower((string) ini_get('display_errors')) !== 'off';
        }

        $disable_wp_cron = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;

        $checks = [
            $this->check(
                __('BastionWP', 'bastionwp'),
                'ok',
                BASTIONWP_VERSION,
                __('Versão instalada do plugin principal.', 'bastionwp')
            ),
            $this->check(
                __('Bastion Core', 'bastionwp'),
                ($core['status'] ?? '') === 'ok' ? 'ok' : 'error',
                (string) ($core['target_version'] ?? __('Não instalado', 'bastionwp')),
                (string) ($core['message'] ?? '')
            ),
            $this->check(
                __('Tabela de logs', 'bastionwp'),
                BastionWP_Logger::table_exists() ? 'ok' : 'error',
                BastionWP_Logger::table_exists()
                    ? __('Disponível', 'bastionwp')
                    : __('Ausente', 'bastionwp'),
                __('Banco usado para auditoria interna do BastionWP.', 'bastionwp')
            ),
            $this->check(
                __('WordPress', 'bastionwp'),
                version_compare((string) $wp_version, BASTIONWP_MIN_WP, '>=') ? 'ok' : 'error',
                (string) $wp_version,
                sprintf(__('Mínimo suportado: %s.', 'bastionwp'), BASTIONWP_MIN_WP)
            ),
            $this->check(
                __('PHP', 'bastionwp'),
                version_compare(PHP_VERSION, BASTIONWP_MIN_PHP, '>=') ? 'ok' : 'error',
                PHP_VERSION,
                sprintf(__('Mínimo suportado: %s.', 'bastionwp'), BASTIONWP_MIN_PHP)
            ),
            $this->check(
                __('HTTPS', 'bastionwp'),
                is_ssl() ? 'ok' : 'warning',
                is_ssl() ? __('Ativo', 'bastionwp') : __('Não detectado', 'bastionwp'),
                __('A administração e autenticação devem usar HTTPS.', 'bastionwp')
            ),
            $this->check(
                __('WP_DEBUG', 'bastionwp'),
                $wp_debug && in_array(
                    BastionWP_Hardening::get_profile(),
                    [BastionWP_Hardening::PROFILE_PRODUCTION, BastionWP_Hardening::PROFILE_LOCKED],
                    true
                ) ? 'warning' : 'ok',
                $wp_debug ? __('Ativo', 'bastionwp') : __('Desativado', 'bastionwp'),
                __('Em produção, normalmente deve permanecer desativado.', 'bastionwp')
            ),
            $this->check(
                __('WP_DEBUG_DISPLAY / display_errors', 'bastionwp'),
                $wp_debug_display ? 'warning' : 'ok',
                $wp_debug_display ? __('Pode exibir erros', 'bastionwp') : __('Suprimido', 'bastionwp'),
                __('Evite exibir detalhes técnicos em sites publicados.', 'bastionwp')
            ),
            $this->check(
                __('WP-Cron', 'bastionwp'),
                $disable_wp_cron ? 'warning' : 'ok',
                $disable_wp_cron ? __('Desativado no WordPress', 'bastionwp') : __('Disponível', 'bastionwp'),
                $disable_wp_cron
                    ? __('Confirme que existe um cron real no servidor; auto-updates dependem de tarefas agendadas.', 'bastionwp')
                    : __('Usado por tarefas agendadas e atualizações automáticas.', 'bastionwp')
            ),
            $this->check(
                __('Hardening', 'bastionwp'),
                BastionWP_Hardening::get_profile() === BastionWP_Hardening::PROFILE_UNCONFIGURED
                    ? 'warning'
                    : 'ok',
                $this->profile_label(),
                __('Perfil de segurança atualmente aplicado.', 'bastionwp')
            ),
            $this->check(
                __('Atualização automática do BastionWP', 'bastionwp'),
                BastionWP_Update_Manager::is_auto_update_enabled() ? 'ok' : 'warning',
                BastionWP_Update_Manager::is_auto_update_enabled()
                    ? __('Ativada', 'bastionwp')
                    : __('Desativada', 'bastionwp'),
                __('Mantém o plugin atualizado via mecanismo nativo do WordPress.', 'bastionwp')
            ),
            $this->check(
                __('Fonte de atualização', 'bastionwp'),
                !empty($update_settings['owner']) && !empty($update_settings['repo']) ? 'ok' : 'warning',
                !empty($update_settings['owner']) && !empty($update_settings['repo'])
                    ? sprintf(
                        '%s/%s (%s)',
                        sanitize_text_field((string) $update_settings['owner']),
                        sanitize_text_field((string) $update_settings['repo']),
                        sanitize_text_field((string) $update_settings['channel'])
                    )
                    : __('GitHub não configurado', 'bastionwp'),
                __('Origem das Releases do BastionWP.', 'bastionwp')
            ),
            $this->check(
                __('Wordfence', 'bastionwp'),
                $wordfence['active'] ? 'ok' : 'warning',
                $wordfence['active']
                    ? sprintf(__('Ativo — %s', 'bastionwp'), $wordfence['version'])
                    : ($wordfence['installed'] ? __('Instalado, mas inativo', 'bastionwp') : __('Não instalado', 'bastionwp')),
                __('Camada especializada de firewall, scanner e segurança de login.', 'bastionwp')
            ),
            $this->check(
                __('Wordfence WAF', 'bastionwp'),
                $wordfence['waf_loaded'] ? 'ok' : 'warning',
                $wordfence['waf_loaded']
                    ? __('Detectado nesta requisição', 'bastionwp')
                    : __('Não detectado nesta requisição', 'bastionwp'),
                __('Revise o Firewall do Wordfence se o WAF não estiver carregado.', 'bastionwp')
            ),
        ];

        $counts = [
            'ok'      => 0,
            'warning' => 0,
            'error'   => 0,
        ];

        foreach ($checks as $check) {
            if (isset($counts[$check['status']])) {
                $counts[$check['status']]++;
            }
        }

        return [
            'generated_at' => current_time('mysql'),
            'site' => [
                'home_url'            => home_url('/'),
                'site_url'            => site_url('/'),
                'wordpress_version'   => (string) $wp_version,
                'php_version'         => PHP_VERSION,
                'bastionwp_version'   => BASTIONWP_VERSION,
                'bastion_core_version'=> (string) ($core['target_version'] ?? ''),
                'hardening_profile'   => BastionWP_Hardening::get_profile(),
                'developer_count'     => count(BastionWP_Users::get_developer_ids()),
                'client_manager_count'=> count(BastionWP_Users::get_client_managers()),
            ],
            'summary' => $counts,
            'checks'  => $checks,
        ];
    }

    private function check(string $label, string $status, string $value, string $description): array
    {
        return [
            'label'       => $label,
            'status'      => in_array($status, ['ok', 'warning', 'error'], true) ? $status : 'warning',
            'value'       => $value,
            'description' => $description,
        ];
    }

    private function profile_label(): string
    {
        $profile = BastionWP_Hardening::get_profile();

        if ($profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) {
            return __('Não configurado', 'bastionwp');
        }

        $profiles = BastionWP_Hardening::get_profiles();

        return isset($profiles[$profile]['label'])
            ? (string) $profiles[$profile]['label']
            : $profile;
    }
}
