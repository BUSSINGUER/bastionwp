<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * O uninstall.php permanece deliberadamente não destrutivo.
 *
 * A remoção completa, incluindo Bastion Core, roles, options, logs e snapshots,
 * é executada pelo fluxo autenticado Sistema → Zona de risco → Remover BastionWP.
 * Uma exclusão direta pelo WordPress não apaga dados de segurança silenciosamente.
 */
