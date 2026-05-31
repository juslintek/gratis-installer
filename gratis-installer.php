<?php
/**
 * Plugin Name: Gratis Installer
 * Description: One-click install of the entire Gratis suite. Install 30+ plugins from a single button.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';
add_action('admin_menu', [\Gratis\Installer\Installer::class, 'addPage']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) \WP_CLI::add_command('gratis-install', \Gratis\Installer\Installer::class);
});
