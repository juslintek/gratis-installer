<?php declare(strict_types=1);

namespace Gratis\Installer;

defined('ABSPATH') || exit;

/**
 * Suite overview screen + one-click activation, plus WP-CLI commands.
 */
final class Installer {

    private const PLUGINS = [
        'core' => [
            'juslintek/gratis-cache'    => 'Gratis Cache — Performance, Redis, tracing',
            'juslintek/gratis-backup'   => 'Backup — Stream-first, scheduled',
            'juslintek/gratis-fleet'    => 'Fleet — Multi-site management',
            'juslintek/gratis-seo'      => 'SEO — Meta tags, sitemap, schema',
            'juslintek/gratis-security' => 'Security — WAF, malware scan, 2FA',
        ],
        'content' => [
            'juslintek/gratis-builder' => 'Builder — Patterns, directory, AI REST API',
            'juslintek/gratis-forms'   => 'Forms — Conditional logic, Stripe',
            'juslintek/gratis-media'   => 'Media — Folders, gallery',
            'juslintek/gratis-tables'  => 'Tables — Sortable, searchable, CSV import',
            'juslintek/gratis-reviews' => 'Reviews — Ratings, testimonials, schema',
        ],
        'marketing' => [
            'juslintek/gratis-analytics' => 'Analytics — Privacy-first',
            'juslintek/gratis-crm'       => 'CRM — Contacts, tags, campaigns',
            'juslintek/gratis-popup'     => 'Popup — Exit intent, scroll, timed',
            'juslintek/gratis-social'    => 'Social — Share buttons',
            'juslintek/gratis-mail'      => 'Mail — SMTP, logs, templates',
            'juslintek/gratis-notify'    => 'Notify — Push notifications',
        ],
        'business' => [
            'juslintek/gratis-commerce'  => 'Commerce — Products, Stripe checkout',
            'juslintek/gratis-invoice'   => 'Invoice — PDF, multi-currency',
            'juslintek/gratis-booking'   => 'Booking — Appointments, calendar',
            'juslintek/gratis-affiliate' => 'Affiliate — Referrals, commissions',
        ],
        'advanced' => [
            'juslintek/gratis-ai'         => 'AI — BYOM, content generation, Abilities',
            'juslintek/gratis-automation' => 'Automation — Triggers, actions, workflows',
            'juslintek/gratis-learn'      => 'Learn — Courses, lessons, quizzes',
            'juslintek/gratis-community'  => 'Community — Profiles, activity, messaging',
            'juslintek/gratis-translate'  => 'Translate — Multilingual, hreflang',
            'juslintek/gratis-access'     => 'Access — Membership, content restriction',
            'juslintek/gratis-project'    => 'Project — Tasks, Kanban boards',
            'juslintek/gratis-events'     => 'Events — Calendar, RSVP, iCal',
            'juslintek/gratis-comply'     => 'Comply — GDPR, cookie consent',
            'juslintek/gratis-cdn'        => 'CDN — Asset fingerprinting, rewriting',
            'juslintek/gratis-devtools'   => 'DevTools — Staging, migrations',
            'juslintek/gratis-db'         => 'DB — SQLite mode, scanner',
        ],
    ];

    /** Register the suite overview page under Plugins. */
    public static function addPage(): void {
        add_submenu_page(
            'plugins.php',
            __('Gratis Suite', 'gratis-installer'),
            __('⚡ Gratis Suite', 'gratis-installer'),
            'activate_plugins',
            'gratis-installer',
            [__CLASS__, 'renderPage']
        );
    }

    /** Resolve a plugin's main file (slug/slug.php) if it exists. */
    private static function pluginFile(string $slug): ?string {
        $candidate = $slug . '/' . $slug . '.php';
        return file_exists(WP_PLUGIN_DIR . '/' . $candidate) ? $candidate : null;
    }

    /** Activate an installed-but-inactive Gratis plugin (nonce + cap gated). */
    public static function handle_activate(): void {
        if (! current_user_can('activate_plugins')) {
            wp_die(esc_html__('You do not have permission to do that.', 'gratis-installer'));
        }
        check_admin_referer('gratis_installer_activate');

        // Nonce + capability verified above.
        $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
        $file = $slug !== '' ? self::pluginFile($slug) : null;

        $result = '0';
        if ($file !== null) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $activated = activate_plugin($file);
            $result    = is_wp_error($activated) ? '0' : '1';
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'gratis-installer', 'gratis_activated' => $result],
            admin_url('plugins.php')
        ));
        exit;
    }

    /** Render the suite overview table. */
    public static function renderPage(): void {
        if (! current_user_can('activate_plugins')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-installer'));
        }
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        echo '<div class="wrap"><h1>' . esc_html__('⚡ Gratis Suite Installer', 'gratis-installer') . '</h1>';
        echo '<p>' . esc_html__('Install and activate the complete free WordPress ecosystem. Replaces $4,500+/yr in paid plugins.', 'gratis-installer') . '</p>';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
        if (isset($_GET['gratis_activated'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
            $ok    = '1' === sanitize_key(wp_unslash($_GET['gratis_activated']));
            $class = $ok ? 'notice-success' : 'notice-error';
            $text  = $ok
                ? __('Plugin activated.', 'gratis-installer')
                : __('Could not activate the plugin.', 'gratis-installer');
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
        }

        foreach (self::PLUGINS as $category => $plugins) {
            echo '<h2 style="margin-top:24px;text-transform:capitalize">' . esc_html($category) . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped"><tbody>';
            foreach ($plugins as $repo => $desc) {
                self::renderRow(basename($repo), (string) $desc);
            }
            echo '</tbody></table>';
        }

        echo '<p style="margin-top:24px"><strong>' . esc_html__('Install via WP-CLI:', 'gratis-installer') . '</strong></p>';
        echo '<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px">wp gratis-install status</pre>';
        echo '</div>';
    }

    /** Render a single plugin row with its status + activate control. */
    private static function renderRow(string $slug, string $desc): void {
        $file      = self::pluginFile($slug);
        $installed = $file !== null;
        $active    = $installed && is_plugin_active($file);

        if ($active) {
            $status = '<span style="color:#22c55e">' . esc_html__('✓ Active', 'gratis-installer') . '</span>';
        } elseif ($installed) {
            $status = '<span style="color:#d97706">' . esc_html__('Installed (inactive)', 'gratis-installer') . '</span>';
        } else {
            $status = '<span style="color:#94a3b8">' . esc_html__('Not installed', 'gratis-installer') . '</span>';
        }

        echo '<tr><td><strong>' . esc_html($slug) . '</strong><br><small>' . esc_html($desc) . '</small></td>';
        echo '<td>' . wp_kses_post($status) . '</td><td>';

        if ($installed && ! $active) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('gratis_installer_activate');
            echo '<input type="hidden" name="action" value="gratis_installer_activate">';
            echo '<input type="hidden" name="slug" value="' . esc_attr($slug) . '">';
            submit_button(__('Activate', 'gratis-installer'), 'small', 'submit', false);
            echo '</form>';
        } else {
            echo '&mdash;';
        }

        echo '</td></tr>';
    }

    /**
     * Report the install/activation status of the suite.
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args): void {
        unset($args, $assoc_args);
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $all       = array_merge(...array_values(self::PLUGINS));
        $installed = 0;
        $active    = 0;
        foreach ($all as $repo => $desc) {
            $slug = basename($repo);
            $file = self::pluginFile($slug);
            if ($file !== null) {
                ++$installed;
                if (is_plugin_active($file)) {
                    ++$active;
                }
            }
        }

        \WP_CLI::log('Gratis Suite: ' . $installed . '/' . count($all) . ' installed, ' . $active . ' active');
        \WP_CLI::log('Categories: ' . implode(', ', array_keys(self::PLUGINS)));
    }

    /**
     * Activate all installed Gratis plugins.
     *
     * [--category=<cat>]
     * : Limit to a category (core, content, marketing, business, advanced).
     *
     * @when after_wp_load
     */
    public function activate($args, $assoc_args): void {
        unset($args);
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $category = isset($assoc_args['category']) ? sanitize_key((string) $assoc_args['category']) : '';
        $plugins  = ($category !== '' && isset(self::PLUGINS[$category]))
            ? self::PLUGINS[$category]
            : array_merge(...array_values(self::PLUGINS));

        $activated = 0;
        foreach ($plugins as $repo => $desc) {
            $slug = basename($repo);
            $file = self::pluginFile($slug);
            if ($file === null) {
                \WP_CLI::log("  - {$slug} (not installed)");
                continue;
            }
            if (is_plugin_active($file)) {
                \WP_CLI::log("  ✓ {$slug} (already active)");
                ++$activated;
                continue;
            }
            $result = activate_plugin($file);
            if (is_wp_error($result)) {
                \WP_CLI::warning("  ! {$slug}: " . $result->get_error_message());
            } else {
                \WP_CLI::log("  → activated {$slug}");
                ++$activated;
            }
        }
        \WP_CLI::success("{$activated}/" . count($plugins) . ' plugins active.');
    }
}
