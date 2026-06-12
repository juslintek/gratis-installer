<?php
/**
 * Plugin Name: Gratis Installer
 * Plugin URI: https://github.com/juslintek/gratis-installer
 * Description: One-click overview and activation for the entire Gratis suite. Manage 30+ plugins from a single screen.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-installer
 */

defined('ABSPATH') || exit;

define('GRATIS_INSTALLER_VERSION', '1.0.0');
define('GRATIS_INSTALLER_FILE', __FILE__);
define('GRATIS_INSTALLER_DIR', plugin_dir_path(__FILE__));
define('GRATIS_INSTALLER_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('admin_menu', [\Gratis\Installer\Installer::class, 'addPage']);
add_action('admin_post_gratis_installer_activate', [\Gratis\Installer\Installer::class, 'handle_activate']);

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-install', \Gratis\Installer\Installer::class);
    }
});
