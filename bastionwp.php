<?php
/**
 * Plugin Name: BastionWP
 * Description: Controle administrativo e camada de proteção para sites WordPress gerenciados.
 * Version:     0.9.9.2
 * Author:      Kaio Bussinguer
 * Update URI:  https://github.com/BUSSINGUER/bastionwp
 * Text Domain: bastionwp
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Network:      false
 * License:     GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BASTIONWP_VERSION', '0.9.9.2');
define('BASTIONWP_FILE', __FILE__);
define('BASTIONWP_DIR', plugin_dir_path(__FILE__));
define('BASTIONWP_URL', plugin_dir_url(__FILE__));
define('BASTIONWP_BASENAME', plugin_basename(__FILE__));
define('BASTIONWP_SLUG', 'bastionwp');
define('BASTIONWP_MIN_PHP', '8.1');
define('BASTIONWP_MIN_WP', '6.5');
define('BASTIONWP_UPDATE_URI', 'https://github.com/BUSSINGUER/bastionwp');

require_once BASTIONWP_DIR . 'includes/class-bastionwp.php';

register_activation_hook(__FILE__, ['BastionWP', 'activate']);
register_deactivation_hook(__FILE__, ['BastionWP', 'deactivate']);

BastionWP::instance();
