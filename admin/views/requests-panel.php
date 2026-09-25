<?php
if (!defined('ABSPATH')) { exit; }
?>
<?php
    $request_counts = [
        'all'      => count($temp_admin_requests),
        'pending'  => 0,
        'approved' => 0,
        'expired'  => 0,
    ];
    $latest_request_at = 0;

    foreach ($temp_admin_requests as $request_item) {
        $request_status_tmp = (string) ($request_item['status'] ?? '');
        if (isset($request_counts[$request_status_tmp])) {
            $request_counts[$request_status_tmp]++;
        }
        $latest_request_at = max($latest_request_at, (int) ($request_item['requested_at'] ?? 0));
    }

    $request_status_filter = isset($_GET['request_status']) ? sanitize_key(wp_unslash($_GET['request_status'])) : 'all';
    if (!in_array($request_status_filter, ['all', 'pending', 'approved', 'expired', 'denied', 'revoked'], true)) {
        $request_status_filter = 'all';
    }
    $request_search = isset($_GET['request_search']) ? sanitize_text_field(wp_unslash($_GET['request_search'])) : '';
    $request_detail_id = isset($_GET['request_detail']) ? sanitize_text_field(wp_unslash($_GET['request_detail'])) : '';
    $request_sort = isset($_GET['request_sort']) ? sanitize_key(wp_unslash($_GET['request_sort'])) : 'recent';

    $filtered_requests = [];
    foreach ($temp_admin_requests as $request_item) {
        $req_user = get_userdata((int) ($request_item['user_id'] ?? 0));
        $req_text_raw = trim(($req_user ? $req_user->display_name . ' ' . $req_user->user_login : '') . ' ' . (string) ($request_item['reason'] ?? ''));
        $req_text = function_exists('mb_strtolower') ? mb_strtolower($req_text_raw) : strtolower($req_text_raw);
        $matches_status = $request_status_filter === 'all' || (string) ($request_item['status'] ?? '') === $request_status_filter;
        $search_normalized = function_exists('mb_strtolower') ? mb_strtolower($request_search) : strtolower($request_search);
        $matches_search = $request_search === '' || str_contains($req_text, $search_normalized);
        if ($matches_status && $matches_search) {
            $filtered_requests[] = $request_item;
        }
    }
    if ($request_sort === 'older') {
        usort($filtered_requests, static fn($a, $b) => ((int) ($a['requested_at'] ?? 0)) <=> ((int) ($b['requested_at'] ?? 0)));
    }
    $selected_request = $request_detail_id !== '' ? BastionWP_Temporary_Admin::get_request($request_detail_id) : null;
    $selected_request_logs = [];
    $selected_request_logs_truncated = false;
    if ($selected_request && !empty($selected_request['approved_at'])) {
        $time_start = (int) ($selected_request['approved_at'] ?? 0);
        $time_end = (int) ($selected_request['revoked_at'] ?? 0);
        if ($time_end <= 0) {
            $time_end = (int) ($selected_request['expires_at'] ?? 0);
        }
        if ($time_end <= 0) {
            $time_end = time();
        }

        $request_filters = [
            'request_id' => (string) ($selected_request['id'] ?? ''),
            'start_time' => $time_start,
            'end_time'   => $time_end,
        ];
        $request_total = BastionWP_Logger::count_logs($request_filters);
        $selected_request_logs = BastionWP_Logger::get_logs($request_filters, 1000, 0);

        if (empty($selected_request_logs)) {
            $legacy_filters = [
                'user_id'    => (int) ($selected_request['user_id'] ?? 0),
                'start_time' => $time_start,
                'end_time'   => $time_end,
            ];
            $request_total = BastionWP_Logger::count_logs($legacy_filters);
            $selected_request_logs = BastionWP_Logger::get_logs($legacy_filters, 1000, 0);
        }

        $selected_request_logs_truncated = $request_total > count($selected_request_logs);
    }
    ?>
    <div class="bastionwp-grid bastionwp-page-requests bastionwp-page-requests-modern">
        <section class="bastionwp-card bastionwp-card-wide bastionwp-request-stats-card">
            <div class="bastionwp-request-stats-grid">
                <article class="bastionwp-request-stat-item is-pending">
                    <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Pendentes', 'bastionwp'); ?></strong><small><?php echo esc_html__('Aguardando sua análise', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['pending']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-active">
                    <span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Ativas', 'bastionwp'); ?></strong><small><?php echo esc_html__('Acessos em andamento', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['approved']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-expired">
                    <span class="dashicons dashicons-backup" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Expiradas', 'bastionwp'); ?></strong><small><?php echo esc_html__('Acessos finalizados', 'bastionwp'); ?></small></div>
                    <b><?php echo esc_html((string) $request_counts['expired']); ?></b>
                </article>
                <article class="bastionwp-request-stat-item is-latest">
                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                    <div><strong><?php echo esc_html__('Última solicitação', 'bastionwp'); ?></strong><small><?php echo $latest_request_at ? esc_html(sprintf(__('Em %s', 'bastionwp'), wp_date('d/m/Y H:i', $latest_request_at))) : esc_html__('Nenhum registro', 'bastionwp'); ?></small></div>
                    <b><?php echo $latest_request_at ? esc_html(human_time_diff($latest_request_at, time()) . ' ' . __('atrás', 'bastionwp')) : '—'; ?></b>
                </article>
            </div>
        </section>

        <section class="bastionwp-card bastionwp-card-wide bastionwp-request-board">
            <div class="bastionwp-overview-section-head bastionwp-request-board-head">
                <div class="bastionwp-overview-section-title">
                    <span class="bastionwp-overview-card-icon dashicons dashicons-list-view" aria-hidden="true"></span>
                    <div>
                        <h2><?php echo esc_html__('Solicitações de privilégios temporários', 'bastionwp'); ?></h2>
                        <p><?php echo esc_html__('Clientes Protegidos podem solicitar privilégios temporários de configuração. Você aprova, nega ou encerra e pode revisar os eventos que o próprio BastionWP registrou durante o período.', 'bastionwp'); ?></p>
                    </div>
                </div>
                <a class="button bastionwp-button bastionwp-button-secondary" href="#bastionwp-request-howto"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><?php echo esc_html__('Como funciona?', 'bastionwp'); ?></a>
            </div>

            <div class="bastionwp-callout bastionwp-callout-warning">
                <strong><?php echo esc_html__('Proteções que permanecem ativas:', 'bastionwp'); ?></strong>
                <?php echo esc_html__('Capabilities técnicas, rotas críticas, Code Snippets, Wordfence e infraestrutura continuam protegidos. O acesso não transforma o cliente em Administrador e expira automaticamente.', 'bastionwp'); ?>
            </div>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="bastionwp-request-toolbar">
                <input type="hidden" name="page" value="bastionwp">
                <input type="hidden" name="tab" value="access"><input type="hidden" name="access_section" value="requests">
                <input type="hidden" name="request_status" value="<?php echo esc_attr($request_status_filter); ?>">
                <div class="bastionwp-request-filter-pills">
                    <?php
                    $request_filters = [
                        'all' => ['label' => __('Todas', 'bastionwp'), 'count' => $request_counts['all']],
                        'pending' => ['label' => __('Pendentes', 'bastionwp'), 'count' => $request_counts['pending']],
                        'approved' => ['label' => __('Ativas', 'bastionwp'), 'count' => $request_counts['approved']],
                        'expired' => ['label' => __('Expiradas', 'bastionwp'), 'count' => $request_counts['expired']],
                        'denied' => ['label' => __('Negadas', 'bastionwp'), 'count' => count(array_filter($temp_admin_requests, static fn($item) => ($item['status'] ?? '') === 'denied'))],
                        'revoked' => ['label' => __('Encerradas', 'bastionwp'), 'count' => count(array_filter($temp_admin_requests, static fn($item) => ($item['status'] ?? '') === 'revoked'))],
                    ];
                    foreach ($request_filters as $filter_key => $filter_item) :
                    ?>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'bastionwp', 'tab' => 'access', 'access_section' => 'requests', 'request_status' => $filter_key, 'request_search' => $request_search, 'request_sort' => $request_sort], admin_url('admin.php'))); ?>" class="bastionwp-request-pill <?php echo $request_status_filter === $filter_key ? 'is-active' : ''; ?>">
                            <span><?php echo esc_html($filter_item['label']); ?></span>
                            <b><?php echo esc_html((string) $filter_item['count']); ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="bastionwp-request-toolbar-search">
                    <label class="screen-reader-text" for="request_search"><?php echo esc_html__('Buscar solicitação', 'bastionwp'); ?></label>
                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                    <input type="text" id="request_search" name="request_search" value="<?php echo esc_attr($request_search); ?>" placeholder="<?php echo esc_attr__('Buscar solicitações por usuário ou motivo...', 'bastionwp'); ?>">
                </div>
                <select name="request_sort">
                    <option value="recent" <?php selected($request_sort, 'recent'); ?>><?php echo esc_html__('Mais recentes', 'bastionwp'); ?></option>
                    <option value="older" <?php selected($request_sort, 'older'); ?>><?php echo esc_html__('Mais antigas', 'bastionwp'); ?></option>
                </select>
                <button type="submit" class="button"><?php echo esc_html__('Aplicar filtros', 'bastionwp'); ?></button>
            </form>

            <?php if (empty($filtered_requests)) : ?>
                <div class="bastionwp-empty-state">
                    <span class="dashicons dashicons-archive" aria-hidden="true"></span>
                    <p><?php echo esc_html__('Nenhuma solicitação encontrada para os filtros selecionados.', 'bastionwp'); ?></p>
                </div>
            <?php else : ?>
                <div class="bastionwp-request-list bastionwp-request-list-modern">
                    <?php foreach ($filtered_requests as $request) : ?>
                        <?php
                        $request_user = get_userdata((int) ($request['user_id'] ?? 0));
                        $request_status = (string) ($request['status'] ?? '');
                        $status_labels = [
                            'pending'  => __('Pendente', 'bastionwp'),
                            'approved' => __('Aprovada', 'bastionwp'),
                            'denied'   => __('Negada', 'bastionwp'),
                            'expired'  => __('Expirada', 'bastionwp'),
                            'revoked'  => __('Encerrada', 'bastionwp'),
                        ];
                        ?>
                        <article class="bastionwp-request-row-card">
                            <div class="bastionwp-request-user">
                                <?php echo get_avatar((int) ($request['user_id'] ?? 0), 56, '', '', ['class' => 'bastionwp-request-user-avatar']); ?>
                                <div>
                                    <strong><?php echo esc_html($request_user ? $request_user->display_name : '#' . (int) ($request['user_id'] ?? 0)); ?></strong>
                                    <small><?php echo esc_html($request_user ? $request_user->user_login : ''); ?></small>
                                    <span class="bastionwp-request-status bastionwp-request-<?php echo esc_attr($request_status); ?>"><?php echo esc_html($status_labels[$request_status] ?? $request_status); ?></span>
                                </div>
                            </div>
                            <div class="bastionwp-request-info-col">
                                <strong><?php echo esc_html__('Informações da solicitação', 'bastionwp'); ?></strong>
                                <dl>
                                    <div><dt><?php echo esc_html__('Solicitado em', 'bastionwp'); ?></dt><dd><?php echo esc_html(wp_date('d/m/Y H:i', (int) ($request['requested_at'] ?? 0))); ?></dd></div>
                                    <div><dt><?php echo esc_html__('Motivo', 'bastionwp'); ?></dt><dd><?php echo esc_html((string) (($request['reason'] ?? '') !== '' ? $request['reason'] : __('Não informado', 'bastionwp'))); ?></dd></div>
                                </dl>
                            </div>
                            <div class="bastionwp-request-info-col">
                                <strong><?php echo esc_html__('Status do acesso', 'bastionwp'); ?></strong>
                                <p><?php echo esc_html($status_labels[$request_status] ?? $request_status); ?></p>
                                <small>
                                    <?php
                                    if ($request_status === 'approved') {
                                        echo esc_html(sprintf(__('Expira em %s', 'bastionwp'), wp_date('d/m/Y H:i', (int) ($request['expires_at'] ?? 0))));
                                    } elseif ($request_status === 'expired') {
                                        echo esc_html__('O acesso foi encerrado automaticamente após o período definido.', 'bastionwp');
                                    } elseif ($request_status === 'denied') {
                                        echo esc_html__('A solicitação foi negada pelo Developer.', 'bastionwp');
                                    } elseif ($request_status === 'revoked') {
                                        echo esc_html__('O acesso foi encerrado manualmente antes do prazo.', 'bastionwp');
                                    } else {
                                        echo esc_html__('Aguardando análise do Developer.', 'bastionwp');
                                    }
                                    ?>
                                </small>
                            </div>
                            <div class="bastionwp-request-actions-modern">
                                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'bastionwp', 'tab' => 'access', 'access_section' => 'requests', 'request_status' => $request_status_filter, 'request_search' => $request_search, 'request_sort' => $request_sort, 'request_detail' => (string) $request['id']], admin_url('admin.php'))); ?>#bastionwp-request-details">
                                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php echo esc_html__('Ver detalhes', 'bastionwp'); ?>
                                </a>
                                <?php if ($request_status === 'pending') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-request-actions">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>
                                        <select name="duration"><?php foreach ($temp_admin_durations as $duration_value => $duration_label) : ?><option value="<?php echo esc_attr((string) $duration_value); ?>"><?php echo esc_html($duration_label); ?></option><?php endforeach; ?></select>
                                        <button type="submit" class="button button-primary" name="decision" value="approve"><?php echo esc_html__('Aprovar', 'bastionwp'); ?></button>
                                        <button type="submit" class="button" name="decision" value="deny"><?php echo esc_html__('Negar', 'bastionwp'); ?></button>
                                    </form>
                                <?php elseif ($request_status === 'approved') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bastionwp-request-actions">
                                        <input type="hidden" name="action" value="bastionwp_temp_admin_decision">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request['id']); ?>">
                                        <?php wp_nonce_field('bastionwp_temp_admin_decision'); ?>
                                        <button type="submit" class="button button-primary" name="decision" value="revoke"><?php echo esc_html__('Encerrar agora', 'bastionwp'); ?></button>
                                    </form>
                                <?php elseif (in_array($request_status, ['expired', 'revoked', 'denied'], true)) : ?>
                                    <span class="button disabled"><?php echo esc_html__('Sem ações', 'bastionwp'); ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="bastionwp-card bastionwp-card-wide" id="bastionwp-request-details">
            <div class="bastionwp-overview-section-title">
                <span class="bastionwp-overview-card-icon dashicons dashicons-media-text" aria-hidden="true"></span>
                <div>
                    <span class="bastionwp-eyebrow"><?php echo esc_html__('Detalhes', 'bastionwp'); ?></span>
                    <h2><?php echo esc_html__('Eventos registrados pelo BastionWP durante o período', 'bastionwp'); ?></h2>
                    <p><?php echo esc_html__('O BastionWP apresenta somente eventos que ele próprio registrou durante a janela aprovada. Este histórico não monitora arquivos e não prova que nenhuma outra alteração ocorreu fora dos eventos auditados.', 'bastionwp'); ?></p>
                </div>
            </div>

            <?php if (!$selected_request) : ?>
                <p><?php echo esc_html__('Selecione uma solicitação em “Ver detalhes” para abrir o histórico relacionado.', 'bastionwp'); ?></p>
            <?php else : ?>
                <?php $selected_request_user = get_userdata((int) ($selected_request['user_id'] ?? 0)); ?>
                <div class="bastionwp-request-detail-meta">
                    <div><strong><?php echo esc_html__('Usuário', 'bastionwp'); ?></strong><span><?php echo esc_html($selected_request_user ? $selected_request_user->display_name : '#' . (int) ($selected_request['user_id'] ?? 0)); ?></span></div>
                    <div><strong><?php echo esc_html__('Período aprovado', 'bastionwp'); ?></strong><span><?php echo !empty($selected_request['approved_at']) ? esc_html(wp_date('d/m/Y H:i', (int) $selected_request['approved_at'])) : '—'; ?></span></div>
                    <div><strong><?php echo esc_html__('Encerramento', 'bastionwp'); ?></strong><span><?php echo !empty($selected_request['expires_at']) ? esc_html(wp_date('d/m/Y H:i', (int) $selected_request['expires_at'])) : '—'; ?></span></div>
                </div>
                <?php if (empty($selected_request_logs)) : ?>
                    <div class="bastionwp-callout">
                        <strong><?php echo esc_html__('Nenhum evento do BastionWP encontrado para este período.', 'bastionwp'); ?></strong>
                        <?php echo esc_html__('Nenhum evento compatível foi registrado pelo BastionWP dentro do período. Isso não significa que nenhuma alteração tenha ocorrido em plugins, arquivos ou serviços externos.', 'bastionwp'); ?>
                    </div>
                <?php else : ?>
                    <?php if ($selected_request_logs_truncated) : ?>
                        <div class="bastionwp-callout bastionwp-callout-warning">
                            <?php echo esc_html__('Há mais eventos do que o limite exibido nesta tela. Consulte Sistema > Logs para o histórico completo e exportação.', 'bastionwp'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="bastionwp-request-detail-loglist">
                        <?php foreach ($selected_request_logs as $request_log_row) : ?>
                            <article class="bastionwp-request-log-item">
                                <div>
                                    <strong><?php echo esc_html(get_date_from_gmt((string) $request_log_row['event_time'], 'd/m/Y H:i:s')); ?></strong>
                                    <small><code><?php echo esc_html($request_log_row['event_type']); ?></code> · <?php echo esc_html($request_log_row['level']); ?></small>
                                </div>
                                <p><?php echo esc_html($request_log_row['message']); ?></p>
                                <?php if (!empty($request_log_row['context'])) : ?><details><summary><?php echo esc_html__('Contexto do log', 'bastionwp'); ?></summary><pre><?php echo esc_html($request_log_row['context']); ?></pre></details><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="bastionwp-card bastionwp-card-wide" id="bastionwp-request-howto">
            <div class="bastionwp-callout bastionwp-callout-info">
                <strong><?php echo esc_html__('Proteções técnicas mantidas durante o acesso temporário', 'bastionwp'); ?></strong>
                <?php echo esc_html__('Mesmo durante o acesso temporário de configuração, capabilities técnicas e rotas críticas permanecem bloqueadas. O acesso expira automaticamente e não desfaz alterações legítimas já realizadas durante a janela.', 'bastionwp'); ?>
            </div>
        </section>
    </div>
