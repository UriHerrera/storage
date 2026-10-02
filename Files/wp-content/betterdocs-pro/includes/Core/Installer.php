<?php

namespace WPDeveloper\BetterDocsPro\Core;

use Plugin_Upgrader;
use Automatic_Upgrader_Skin;
use WPDeveloper\BetterDocsPro\Utils\Helper;

class Installer {
    private $slug           = 'betterdocs';
    private $basename       = 'betterdocs/betterdocs.php';

    /**
     * Transient that throttles install attempts. Without it a persistently
     * failing install (no filesystem write access, wordpress.org unreachable)
     * would fire a plugins_api() round-trip and a full install attempt on every
     * admin page load, slowing wp-admin indefinitely.
     */
    const INSTALL_BACKOFF_KEY = 'betterdocs_pro_free_install_backoff';

    /**
     * Hosts whose packages this installer is willing to execute.
     *
     * The companion is a WordPress.org-hosted plugin, so the download URL must
     * come from WordPress.org over TLS. Anything else is refused rather than
     * unpacked and activated.
     */
    private $allowed_package_hosts = [
        'downloads.wordpress.org',
        'wordpress.org',
        'www.wordpress.org'
    ];

    public function __construct() {
        // Defer the actual install decision to admin_init. This class is
        // constructed from the Pro bootstrap while active plugins are being
        // included — which is BEFORE wp-settings.php loads pluggable.php. Calling
        // current_user_can() here would reach the not-yet-defined
        // wp_get_current_user() and fatal on every admin request whenever the
        // Free companion is inactive (the exact state that constructs this
        // class). admin_init runs after pluggable.php with a resolved current
        // user, fires only in wp-admin, and never on front-end/cron requests.
        add_action( 'admin_init', array( $this, 'maybe_install' ) );
    }

    /**
     * Install the Free companion when the current admin is allowed to, guarded so
     * it runs at most once per request and never for AJAX.
     *
     * @return void
     */
    public function maybe_install() {
        if ( Helper::is_plugin_active( $this->basename ) ) {
            return;
        }

        // Installing and activating a plugin is an administrator action. Without
        // this check the path would still fire on admin-ajax and for admins who
        // lack the install capability (per-site admins on multisite, or any site
        // where plugin installation is disabled).
        if ( ! $this->current_user_may_install() ) {
            return;
        }

        // Throttle attempts. Set the backoff BEFORE attempting, so a persistent
        // failure (or even a fatal inside the upgrader) cannot turn every admin
        // page load into a fresh plugins_api() round-trip plus install attempt.
        // A successful install activates Free, so the is_plugin_active() guard
        // above short-circuits future loads regardless.
        if ( get_transient( self::INSTALL_BACKOFF_KEY ) ) {
            return;
        }
        set_transient( self::INSTALL_BACKOFF_KEY, 1, 15 * MINUTE_IN_SECONDS );

        // Install & Activate Free Plugin. Only clear the backoff on a genuine
        // success — verified by the companion actually being active. install()
        // returns the upgrader's $skin->result, which is TRUTHY on failure
        // (a WP_Error when install_package() fails, or an 'up_to_date' string),
        // so keying "success" off its truthiness would delete the backoff on the
        // exact persistent-failure case (read-only dir, disk full) the throttle
        // exists for, restoring the per-page-load retry storm.
        $this->install();
        if ( Helper::is_plugin_active( $this->basename ) ) {
            delete_transient( self::INSTALL_BACKOFF_KEY );
            set_transient( 'betterdocs_maybe_redirect', true );
        }
    }

    /**
     * Whether the current request is an authenticated administrator request that
     * may install plugins.
     *
     * Intended to run at admin_init or later, once pluggable.php has defined the
     * current-user helpers.
     *
     * @return bool
     */
    protected function current_user_may_install() {
        if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_doing_ajax() ) {
            return false;
        }

        if ( ! is_admin() ) {
            return false;
        }

        return function_exists( 'current_user_can' ) && current_user_can( 'install_plugins' );
    }

    public function install() {
        $is_installed = $this->get_installed_plugin_data();
        $plugin_data  = $this->get_plugin_data();

        set_transient( 'maybe_betterdocs_installed_by_pro', true );

        if ( $is_installed ) {
            if ( isset( $plugin_data->version ) && $is_installed['Version'] != $plugin_data->version ) {
                $this->upgrade_or_install_plugin();
            }

            if ( Helper::is_plugin_active( $this->basename ) ) {
                return false;
            } else {
                activate_plugin( $this->safe_path( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename ), '', false, true );
                return true;
            }
        } else {
            $download_link = isset( $plugin_data->download_link ) ? $plugin_data->download_link : '';
            if ( ! empty( $download_link ) && $this->upgrade_or_install_plugin( $download_link, false ) ) {
                return true;
            }
        }

        return false;
    }

    public function get_installed_plugin_data() {
        $plugins = Helper::get_plugins();
        return isset( $plugins[$this->basename] ) ? $plugins[$this->basename] : false;
    }

    protected function get_plugin_data() {
        $installed_plugin = false;
        if ( $this->basename ) {
            $installed_plugin = $this->get_installed_plugin_data();
        }

        if ( $installed_plugin ) {
            return $installed_plugin;
        }

        // Use core's plugins_api() instead of hand-rolling the request. It talks
        // to api.wordpress.org over HTTPS and returns a decoded object, replacing
        // both the plaintext transport and the unserialize() of a remote body —
        // either of which let anyone able to intercept the connection choose the
        // package that gets installed and activated, or instantiate arbitrary
        // objects during deserialization.
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $response = plugins_api(
            'plugin_information',
            [
                'slug'   => $this->slug,
                'fields' => [
                    'version' => true
                ]
            ]
        );

        if ( is_wp_error( $response ) || ! is_object( $response ) ) {
            return false;
        }

        // plugins_api() is asked about a fixed slug, but the response still
        // carries the package URL — bind it to that slug and to an approved
        // HTTPS host before anything executes it.
        if ( isset( $response->slug ) && $response->slug !== $this->slug ) {
            return false;
        }

        if ( isset( $response->download_link ) && ! $this->is_allowed_package_url( $response->download_link ) ) {
            unset( $response->download_link );
        }

        return $response;
    }

    /**
     * Whether a package URL is safe to hand to the plugin upgrader.
     *
     * Requires HTTPS and a WordPress.org host, so a tampered or redirected
     * response cannot point the installer at an attacker-controlled archive.
     *
     * @param string $url Package URL from the plugin API response.
     * @return bool
     */
    protected function is_allowed_package_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return false;
        }

        $parts = wp_parse_url( $url );

        if ( empty( $parts['scheme'] ) || strtolower( $parts['scheme'] ) !== 'https' ) {
            return false;
        }

        if ( empty( $parts['host'] ) ) {
            return false;
        }

        return in_array( strtolower( $parts['host'] ), $this->allowed_package_hosts, true );
    }

    public function upgrade_or_install_plugin( $basename = '', $upgrade = true ) {
        if ( empty( $basename ) ) {
            $basename = $this->basename;
        }

        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
        include_once ABSPATH . 'wp-includes/pluggable.php';

        $skin     = new Automatic_Upgrader_Skin;
        $upgrader = new Plugin_Upgrader( $skin );

        if ( $upgrade == true ) {
            $upgrader->upgrade( $basename );
        } else {
            $upgrader->install( $basename );
            activate_plugin( $upgrader->plugin_info(), '', false, true );
        }

        return $skin->result;
    }

    public function safe_path( $path ) {
        $path = str_replace( ['//', '\\\\'], ['/', '\\'], $path );
        return str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $path );
    }
}
