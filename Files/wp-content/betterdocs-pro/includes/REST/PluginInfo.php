<?php

namespace WPDeveloper\BetterDocsPro\REST;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class PluginInfo extends BaseAPI {

    /**
     * @return mixed
     */
    public function register() {
        $this->get( '/plugin_info/', [$this, 'get_plugin_info'] );
    }

    /**
     * Inheriting BaseAPI::permission_check() (which returns true) published the
     * exact Free and Pro version numbers plus the plugin's filesystem URL to
     * anonymous callers — a ready-made fingerprint for matching an install
     * against known per-version vulnerabilities. Nothing in either plugin's
     * sources or built bundles calls this route, so gating it costs nothing.
     *
     * @return bool
     */
    public function permission_check() {
        return current_user_can( 'edit_posts' );
    }

    public function get_plugin_info() {
        return [
            'betterdocs_dir_url'     => BETTERDOCS_PRO_ABSURL,
            'betterdocs_rest_url'    => get_rest_url(),
            'betterdocs_version'     => BETTERDOCS_VERSION,
            'betterdocs_pro_version' => BETTERDOCS_PRO_VERSION
        ];
    }
}
