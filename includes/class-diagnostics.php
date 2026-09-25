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
        $update_provider = new BastionWP_GitHub_Provider(
            (string) $update_settings['owner'],
            (string) $update_settings['repo'],
            (string) $update_settings['channel']
        );
        $update_release = $update_provider->is_configured()
            ? $update_provider->get_latest_release()
            : new WP_Error('not_configured', __('GitHub não configurado.', 'bastionwp'));

        $hardening_profile = BastionWP_Hardening::get_profile();
        $hardening_settings = BastionWP_Hardening::get_effective_settings($hardening_profile);
        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $display_errors = $this->effective_display_errors();
        $disable_wp_cron = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $administrator_ids = array_map(
            'absint',
            get_users([
                'role'   => 'administrator',
                'fields' => 'ID',
            ])
        );
        $other_administrator_ids = array_values(
            array_diff($administrator_ids, BastionWP_Users::get_developer_ids())
        );
        $protected_administrator_ids = [];
        $native_administrator_ids = [];
        foreach ($other_administrator_ids as $administrator_id) {
            if (class_exists('BastionWP_Protected_Admin') && BastionWP_Protected_Admin::is_user((int) $administrator_id)) {
                $protected_administrator_ids[] = (int) $administrator_id;
            } else {
                $native_administrator_ids[] = (int) $administrator_id;
            }
        }
        $logger_last_error = (string) get_option('bastionwp_log_last_error', '');

        $php_status = 'ok';
        $php_description = sprintf(__('Mínimo técnico suportado: %s.', 'bastionwp'), BASTIONWP_MIN_PHP);
        if (version_compare(PHP_VERSION, BASTIONWP_MIN_PHP, '<')) {
            $php_status = 'error';
        } elseif (version_compare(PHP_VERSION, '8.2', '<')) {
            $php_status = 'warning';
            $php_description = __('A versão atende ao mínimo técnico do plugin, mas está fora do ciclo oficial de suporte do PHP. Planeje atualização.', 'bastionwp');
        }

        $update_configured = $update_provider->is_configured();
        $update_ok = $update_configured && !is_wp_error($update_release);

        $checks = [
            $this->check('bastionwp', __('BastionWP', 'bastionwp'), 'ok', BASTIONWP_VERSION, __('Versão instalada do plugin principal.', 'bastionwp')),
            $this->check(
                'core',
                __('Bastion Core', 'bastionwp'),
                ($core['status'] ?? '') === 'ok' ? 'ok' : 'error',
                (string) ($core['target_version'] ?? __('Não instalado', 'bastionwp')),
                (string) ($core['message'] ?? '')
            ),
            $this->check(
                'logs_schema',
                __('Tabela de logs', 'bastionwp'),
                BastionWP_Logger::table_exists() && BastionWP_Logger::schema_is_valid() ? 'ok' : 'error',
                BastionWP_Logger::table_exists() && BastionWP_Logger::schema_is_valid() ? __('Disponível', 'bastionwp') : __('Ausente ou incompatível', 'bastionwp'),
                __('Banco usado para auditoria interna do BastionWP.', 'bastionwp')
            ),
            $this->check(
                'wordpress',
                __('WordPress', 'bastionwp'),
                version_compare((string) $wp_version, BASTIONWP_MIN_WP, '>=') ? 'ok' : 'error',
                (string) $wp_version,
                sprintf(__('Mínimo suportado: %s.', 'bastionwp'), BASTIONWP_MIN_WP)
            ),
            $this->check('php', __('PHP', 'bastionwp'), $php_status, PHP_VERSION, $php_description),
            $this->check(
                'https',
                __('HTTPS', 'bastionwp'),
                is_ssl() ? 'ok' : 'warning',
                is_ssl() ? __('Ativo', 'bastionwp') : __('Não detectado', 'bastionwp'),
                __('A administração e autenticação devem usar HTTPS.', 'bastionwp')
            ),
            $this->check(
                'wp_debug',
                __('WP_DEBUG', 'bastionwp'),
                $wp_debug && in_array($hardening_profile, [BastionWP_Hardening::PROFILE_PRODUCTION, BastionWP_Hardening::PROFILE_LOCKED], true) ? 'warning' : 'ok',
                $wp_debug ? __('Ativo', 'bastionwp') : __('Desativado', 'bastionwp'),
                __('Em produção, normalmente deve permanecer desativado.', 'bastionwp')
            ),
            $this->check(
                'display_errors',
                __('WP_DEBUG_DISPLAY / display_errors', 'bastionwp'),
                $display_errors ? 'warning' : 'ok',
                $display_errors ? __('Exibição efetiva está ativa', 'bastionwp') : __('Suprimido', 'bastionwp'),
                $display_errors
                    ? __('Use “Corrigir agora” para suprimir a exibição em runtime. Para correção definitiva, altere wp-config.php.', 'bastionwp')
                    : __('Exibição de erros não está ativa nesta requisição.', 'bastionwp'),
                $display_errors ? 'fix_display_errors' : ''
            ),
            $this->check(
                'cron',
                __('WP-Cron', 'bastionwp'),
                $disable_wp_cron ? 'warning' : 'ok',
                $disable_wp_cron ? __('Desativado no WordPress', 'bastionwp') : __('Disponível', 'bastionwp'),
                $disable_wp_cron
                    ? __('Confirme que existe um cron real no servidor; auto-updates dependem de tarefas agendadas.', 'bastionwp')
                    : __('Usado por tarefas agendadas e atualizações automáticas.', 'bastionwp')
            ),
            $this->check(
                'hardening_profile',
                __('Hardening', 'bastionwp'),
                $hardening_profile === BastionWP_Hardening::PROFILE_UNCONFIGURED ? 'warning' : 'ok',
                $this->profile_label(),
                __('Perfil de segurança atualmente aplicado.', 'bastionwp')
            ),
            $this->check(
                'xmlrpc',
                __('XML-RPC', 'bastionwp'),
                'ok',
                !empty($hardening_settings['disable_xmlrpc']) ? __('Métodos desabilitados pelo perfil', 'bastionwp') : __('Disponível pelo perfil', 'bastionwp'),
                !empty($hardening_settings['disable_xmlrpc'])
                    ? __('O BastionWP remove os métodos XML-RPC do WordPress; o endpoint pode continuar respondendo apenas com falha.', 'bastionwp')
                    : __('XML-RPC segue o comportamento padrão do WordPress.', 'bastionwp')
            ),
            $this->check(
                'application_passwords',
                __('Application Passwords', 'bastionwp'),
                'ok',
                !empty($hardening_settings['disable_application_passwords']) ? __('Bloqueadas pelo perfil', 'bastionwp') : __('Permitidas pelo perfil', 'bastionwp'),
                __('Estado efetivo definido pelo perfil de Hardening.', 'bastionwp')
            ),
            $this->check(
                'file_editors',
                __('Editor de arquivos de tema/plugin', 'bastionwp'),
                'ok',
                !empty($hardening_settings['block_file_editors']) ? __('Bloqueado pelo BastionWP', 'bastionwp') : __('Permitido pelo perfil', 'bastionwp'),
                __('Controle efetivo de capabilities no WordPress.', 'bastionwp')
            ),
            $this->check(
                'manual_infrastructure',
                __('Alterações manuais de infraestrutura', 'bastionwp'),
                'ok',
                !empty($hardening_settings['block_manual_infrastructure_changes']) ? __('Bloqueadas', 'bastionwp') : __('Permitidas ao Developer', 'bastionwp'),
                __('Instalação, atualização e alteração de plugins/temas/core conforme o perfil.', 'bastionwp')
            ),
            $this->check(
                'auto_update',
                __('Atualização automática do BastionWP', 'bastionwp'),
                BastionWP_Update_Manager::is_auto_update_enabled() ? 'ok' : 'warning',
                BastionWP_Update_Manager::is_auto_update_enabled() ? __('Ativada', 'bastionwp') : __('Desativada', 'bastionwp'),
                __('Preferência do mecanismo nativo de auto-update do WordPress.', 'bastionwp')
            ),
            $this->check(
                'update_source',
                __('Fonte de atualização', 'bastionwp'),
                $update_ok ? 'ok' : 'warning',
                $update_ok
                    ? sprintf('%s/%s — %s', (string) $update_settings['owner'], (string) $update_settings['repo'], (string) ($update_release['version'] ?? ''))
                    : ($update_configured ? __('Configurada, mas não validada nesta consulta', 'bastionwp') : __('GitHub não configurado', 'bastionwp')),
                is_wp_error($update_release) ? $update_release->get_error_message() : __('Release instalável validada pelo provider.', 'bastionwp')
            ),
            $this->check(
                'wordfence',
                __('Wordfence', 'bastionwp'),
                !empty($wordfence['active']) ? 'ok' : 'warning',
                !empty($wordfence['active'])
                    ? sprintf(__('Ativo — %s', 'bastionwp'), (string) $wordfence['version'])
                    : (!empty($wordfence['installed']) ? __('Instalado, mas inativo', 'bastionwp') : __('Não instalado', 'bastionwp')),
                __('Indica instalação/ativação; não comprova configuração, licença, scan ou regras atualizadas.', 'bastionwp')
            ),
            $this->check(
                'wordfence_waf',
                __('Wordfence WAF', 'bastionwp'),
                !empty($wordfence['waf_loaded']) ? 'ok' : 'warning',
                !empty($wordfence['waf_loaded']) ? __('Constante WAF detectada nesta requisição', 'bastionwp') : __('Não detectada nesta requisição', 'bastionwp'),
                __('A detecção não certifica otimização, regras, licença ou resultado de scan.', 'bastionwp')
            ),
            $this->check(
                'other_administrators',
                __('Administradores do site', 'bastionwp'),
                empty($native_administrator_ids) ? 'ok' : 'warning',
                empty($native_administrator_ids)
                    ? sprintf(
                        _n('%d Administrador Protegido · nenhum Administrator nativo', '%d Administradores Protegidos · nenhum Administrator nativo', count($protected_administrator_ids), 'bastionwp'),
                        count($protected_administrator_ids)
                    )
                    : sprintf(
                        _n('%d Administrator nativo sem proteção individual', '%d Administrators nativos sem proteção individual', count($native_administrator_ids), 'bastionwp'),
                        count($native_administrator_ids)
                    ),
                empty($native_administrator_ids)
                    ? __('Contas administrativas adicionais conhecidas estão sob a política Administrador Protegido.', 'bastionwp')
                    : __('Administrators nativos continuam com poderes administrativos sem a política individual do BastionWP. Converta-os para Administrador Protegido ou para um nível inferior quando apropriado.', 'bastionwp')
            ),
            $this->check(
                'logger_write_health',
                __('Gravação de logs', 'bastionwp'),
                $logger_last_error === '' ? 'ok' : 'warning',
                $logger_last_error === '' ? __('Sem erro de gravação registrado', 'bastionwp') : __('Última gravação reportou erro', 'bastionwp'),
                $logger_last_error === ''
                    ? __('O schema está disponível e não há erro de gravação conhecido.', 'bastionwp')
                    : sprintf(__('Último erro registrado: %s', 'bastionwp'), $logger_last_error)
            ),
            $this->check(
                'multisite',
                __('Escopo WordPress', 'bastionwp'),
                is_multisite() ? 'warning' : 'ok',
                is_multisite() ? __('Multisite detectado', 'bastionwp') : __('Single-site', 'bastionwp'),
                is_multisite()
                    ? __('BastionWP 0.9.9.3 ainda não é homologado para multisite; use somente após validação específica de rede.', 'bastionwp')
                    : __('Escopo homologado nesta versão.', 'bastionwp')
            ),
        ];

        $counts = ['ok' => 0, 'warning' => 0, 'error' => 0];
        foreach ($checks as $check) {
            if (isset($counts[$check['status']])) {
                $counts[$check['status']]++;
            }
        }

        return [
            'generated_at' => current_time('mysql'),
            'site' => [
                'home_url'             => home_url('/'),
                'site_url'             => site_url('/'),
                'wordpress_version'    => (string) $wp_version,
                'php_version'          => PHP_VERSION,
                'bastionwp_version'    => BASTIONWP_VERSION,
                'bastion_core_version' => (string) ($core['target_version'] ?? ''),
                'bastion_core_status'  => (string) ($core['status'] ?? ''),
                'hardening_profile'    => $hardening_profile,
                'developer_count'      => count(BastionWP_Users::get_developer_ids()),
                'client_manager_count' => count(BastionWP_Users::get_client_managers()),
                'protected_admin_count' => count($protected_administrator_ids),
                'native_admin_count'    => count($native_administrator_ids),
                'multisite'            => is_multisite(),
            ],
            'summary' => $counts,
            'checks'  => $checks,
        ];
    }

    private function check(
        string $key,
        string $label,
        string $status,
        string $value,
        string $description,
        string $action = ''
    ): array {
        return [
            'key'         => sanitize_key($key),
            'label'       => $label,
            'status'      => in_array($status, ['ok', 'warning', 'error'], true) ? $status : 'warning',
            'value'       => $value,
            'description' => $description,
            'action'      => sanitize_key($action),
        ];
    }

    private function profile_label(): string
    {
        $profile = BastionWP_Hardening::get_profile();
        if ($profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) {
            return __('Não configurado', 'bastionwp');
        }
        $profiles = BastionWP_Hardening::get_profiles();
        return isset($profiles[$profile]['label']) ? (string) $profiles[$profile]['label'] : $profile;
    }

    private function effective_display_errors(): bool
    {
        $value = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value !== null) {
            return $value;
        }
        $raw = strtolower((string) ini_get('display_errors'));
        return $raw !== '' && $raw !== '0' && $raw !== 'off';
    }
}
