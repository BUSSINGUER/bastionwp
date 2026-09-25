<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_Wizard
{
    private const STATE_OPTION = 'bastionwp_wizard_state';

    private BastionWP_MU_Installer $mu_installer;
    private BastionWP_Hardening $hardening;
    private BastionWP_Wordfence_Integration $wordfence;
    private BastionWP_Diagnostics $diagnostics;

    public function __construct(
        BastionWP_MU_Installer $mu_installer,
        BastionWP_Hardening $hardening,
        BastionWP_Wordfence_Integration $wordfence,
        BastionWP_Diagnostics $diagnostics
    ) {
        $this->mu_installer = $mu_installer;
        $this->hardening = $hardening;
        $this->wordfence = $wordfence;
        $this->diagnostics = $diagnostics;
    }

    public function get_state(): array
    {
        $state = get_option(self::STATE_OPTION, []);
        $state = is_array($state) ? $state : [];

        return wp_parse_args($state, [
            'first_run'    => false,
            'started'      => false,
            'current_step' => 0,
            'completed'    => false,
            'paused'       => false,
            'version'      => BASTIONWP_VERSION,
        ]);
    }

    public function start(int $user_id): void
    {
        $state = $this->get_state();
        $state['started'] = true;
        $state['paused'] = false;
        $state['current_step'] = 1;
        $state['started_at'] = current_time('mysql');
        $state['started_by'] = absint($user_id);
        $state['version'] = BASTIONWP_VERSION;
        update_option(self::STATE_OPTION, $state, false);
    }

    public function set_step(int $step): void
    {
        $state = $this->get_state();
        $state['started'] = true;
        $state['current_step'] = max(0, min(6, $step));
        update_option(self::STATE_OPTION, $state, false);
    }

    public function get_current_step(): int
    {
        return (int) ($this->get_state()['current_step'] ?? 0);
    }

    public function is_focus_mode(): bool
    {
        $state = $this->get_state();
        return !empty($state['first_run']) && empty($state['completed']) && empty($state['paused']);
    }

    public function should_auto_redirect(): bool
    {
        $state = $this->get_state();
        return !empty($state['first_run']) && empty($state['completed']) && empty($state['paused']);
    }

    public function pause(): void
    {
        $state = $this->get_state();
        $state['paused'] = true;
        update_option(self::STATE_OPTION, $state, false);
    }

    public function is_first_run_pending(): bool
    {
        $state = $this->get_state();
        return !empty($state['first_run']) && empty($state['completed']);
    }

    public function mark_completed(int $user_id): void
    {
        $state = $this->get_state();
        $state['completed'] = true;
        $state['first_run'] = false;
        $state['paused'] = false;
        $state['started'] = true;
        $state['current_step'] = 6;
        $state['completed_at'] = current_time('mysql');
        $state['completed_by'] = absint($user_id);
        $state['version'] = BASTIONWP_VERSION;
        update_option(self::STATE_OPTION, $state, false);
    }

    public function reopen(): void
    {
        $state = $this->get_state();
        $state['first_run'] = true;
        $state['started'] = false;
        $state['paused'] = false;
        $state['current_step'] = 0;
        $state['completed'] = false;
        unset($state['completed_at'], $state['completed_by']);
        update_option(self::STATE_OPTION, $state, false);
    }

    public function get_steps(): array
    {
        $core = $this->mu_installer->get_status();
        $developer_ids = BastionWP_Users::get_developer_ids();
        $hardening_profile = BastionWP_Hardening::get_profile();
        $updates = BastionWP_Update_Manager::get_settings();
        $auto_update = BastionWP_Update_Manager::is_auto_update_enabled();
        $wordfence = $this->wordfence->get_status();
        $diagnostics = $this->diagnostics->get_report();

        $provider = new BastionWP_GitHub_Provider(
            (string) $updates['owner'],
            (string) $updates['repo'],
            (string) $updates['channel']
        );
        $release = $provider->is_configured() ? $provider->get_latest_release() : null;
        $source_ready = is_array($release) && !empty($release['package']);
        $diagnostic_errors = (int) ($diagnostics['summary']['error'] ?? 0);
        $blocking_warning_keys = ['core', 'logs_schema', 'wordpress', 'php', 'https', 'display_errors', 'multisite'];
        $blocking_warnings = 0;
        foreach (($diagnostics['checks'] ?? []) as $diagnostic_check) {
            if (
                ($diagnostic_check['status'] ?? '') === 'warning'
                && in_array((string) ($diagnostic_check['key'] ?? ''), $blocking_warning_keys, true)
            ) {
                $blocking_warnings++;
            }
        }

        $steps = [
            [
                'id'          => 'foundation',
                'title'       => __('Fundação', 'bastionwp'),
                'description' => __('Bastion Core e Developer Principal.', 'bastionwp'),
                'status'      => (($core['status'] ?? '') === 'ok' && !empty($developer_ids)) ? 'ok' : 'error',
                'value'       => (($core['status'] ?? '') === 'ok' && !empty($developer_ids))
                    ? __('Pronto', 'bastionwp')
                    : __('Requer atenção', 'bastionwp'),
                'tab'         => 'overview',
                'required'    => true,
            ],
            [
                'id'          => 'access',
                'title'       => __('Proteção de acesso', 'bastionwp'),
                'description' => __('Gerenciadores do Cliente e menus individuais.', 'bastionwp'),
                'status'      => 'ok',
                'value'       => sprintf(
                    _n(
                        '%d Gerenciador do Cliente',
                        '%d Gerenciadores do Cliente',
                        count(BastionWP_Users::get_client_managers()),
                        'bastionwp'
                    ),
                    count(BastionWP_Users::get_client_managers())
                ),
                'tab'         => 'access',
                'required'    => false,
            ],
            [
                'id'          => 'hardening',
                'title'       => __('Segurança', 'bastionwp'),
                'description' => __('Perfil de proteção adequado ao ambiente.', 'bastionwp'),
                'status'      => $hardening_profile === BastionWP_Hardening::PROFILE_UNCONFIGURED ? 'warning' : 'ok',
                'value'       => $this->profile_label($hardening_profile),
                'tab'         => 'hardening',
                'required'    => true,
            ],
            [
                'id'          => 'wordfence',
                'title'       => __('Wordfence', 'bastionwp'),
                'description' => __('Firewall, scanner e proteção especializada.', 'bastionwp'),
                'status'      => $wordfence['active'] ? 'ok' : 'warning',
                'value'       => $wordfence['active']
                    ? sprintf(__('Ativo — %s', 'bastionwp'), $wordfence['version'])
                    : ($wordfence['installed'] ? __('Instalado, mas inativo', 'bastionwp') : __('Não instalado', 'bastionwp')),
                'tab'         => 'integrations',
                'required'    => false,
            ],
            [
                'id'          => 'updates',
                'title'       => __('Atualizações', 'bastionwp'),
                'description' => __('Fonte GitHub e atualização automática.', 'bastionwp'),
                'status'      => ($source_ready && $auto_update) ? 'ok' : 'warning',
                'value'       => ($source_ready && $auto_update)
                    ? __('Configuradas e automáticas', 'bastionwp')
                    : __('Revisar configuração', 'bastionwp'),
                'tab'         => 'system',
                'required'    => false,
            ],
            [
                'id'          => 'diagnostics',
                'title'       => __('Diagnóstico', 'bastionwp'),
                'description' => __('Validação final do ambiente.', 'bastionwp'),
                'status'      => $diagnostic_errors > 0 ? 'error' : ($blocking_warnings > 0 ? 'warning' : 'ok'),
                'value'       => sprintf(
                    __('%1$d erros · %2$d atenções (%3$d impeditivas)', 'bastionwp'),
                    $diagnostic_errors,
                    (int) ($diagnostics['summary']['warning'] ?? 0),
                    $blocking_warnings
                ),
                'tab'         => 'diagnostics',
                'required'    => true,
            ],
        ];

        return $steps;
    }

    public function get_progress(): array
    {
        $steps = $this->get_steps();
        $required = array_values(
            array_filter(
                $steps,
                static fn(array $step): bool => !empty($step['required'])
            )
        );

        $required_ok = count(
            array_filter(
                $required,
                static fn(array $step): bool => $step['status'] === 'ok'
            )
        );

        $total = count($required);
        $percent = $total > 0 ? (int) round(($required_ok / $total) * 100) : 0;

        return [
            'required_ok' => $required_ok,
            'required_total' => $total,
            'percent' => $percent,
            'can_complete' => $total > 0 && $required_ok === $total,
        ];
    }

    private function profile_label(string $profile): string
    {
        if ($profile === BastionWP_Hardening::PROFILE_UNCONFIGURED) {
            return __('Não configurado', 'bastionwp');
        }

        $profiles = BastionWP_Hardening::get_profiles();

        return isset($profiles[$profile]['label'])
            ? (string) $profiles[$profile]['label']
            : $profile;
    }
}
