<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Admin\Builder\GlobalFields;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;
use WPDeveloper\BetterDocs\Core\Settings as FreeSettings;
use WPDeveloper\BetterDocsPro\Core\InstantAnswer;
use WPDeveloper\BetterDocs\Utils\Database;

class Settings extends FreeSettings {

    public function __construct( Database $database ) {
        parent::__construct( $database );

        add_filter( 'betterdocs_default_settings', array( $this, '_default' ), 11 );
        // add_filter( 'betterdocs_settings_tab_sidebar_layout', [$this, 'tab_sidebar_layout'] );
        add_filter( 'betterdocs_shortcode_fields', array( $this, 'shortcode_fields' ) );
        add_filter( 'betterdocs_instant_answer_fields', array( $this, 'instant_answer_revamp_fields' ) );
        // add_filter( 'betterdocs_instant_answer_fields', [$this, 'instant_answer_fields'] );
        add_filter( 'betterdocs_settings_args', array( $this, '_args' ), 11 );

        add_filter( 'betterdocs_single_doc_attachments', array( $this, 'insert_attachments_settings' ), 10, 1 );
        add_filter( 'betterdocs_single_doc_related_docs', array( $this, 'unlock_related_docs_settings' ), 10, 1 );

        add_filter( 'betterdocs_encyclopedia_settings', array( $this, 'encyclopedia_fields' ) );
        add_filter( 'betterdocs_encyclopedia_settings', array( $this, 'glossary_fields' ) );

        add_filter( 'betterdocs_settings_content_restriction_fields', array( $this, 'content_restriction_fields' ), 10, 1 );

        // Real-time Related Docs Panel settings
        // add_filter( 'betterdocs_settings_tabs', [ $this, 'add_realtime_related_docs_tab' ] );
        // add_filter( 'betterdocs_settings_realtime_related_docs_fields', [ $this, 'realtime_related_docs_fields' ] );

        add_filter( 'betterdocs_settings_tab_github_integration', array( $this, 'git_integration_fields' ) );
        add_filter( 'betterdocs_default_settings', array( $this, 'git_default_settings' ), 12 );
    }

    /**
     * Sanitize Pro settings that are allowed to carry markup before they are stored.
     *
     * The GDPR consent copy accepts a small HTML allowlist so it can link to a
     * privacy policy. Sanitizing it here — rather than only where it is read —
     * means the value in the database is already safe, so a future consumer that
     * forgets to escape cannot turn it into stored XSS.
     *
     * @param array $settings Incoming settings payload.
     * @return mixed
     */
    public function save_settings( $settings ) {
        if ( is_array( $settings ) ) {
            if ( array_key_exists( 'chat_tab_gdpr_text', $settings ) ) {
                $settings[ 'chat_tab_gdpr_text' ] = InstantAnswer::sanitize_gdpr_text( $settings[ 'chat_tab_gdpr_text' ] );
            }

            // Quickbuilder nests the same keys under `values` on save.
            if ( isset( $settings[ 'values' ] ) && is_array( $settings[ 'values' ] ) && array_key_exists( 'chat_tab_gdpr_text', $settings[ 'values' ] ) ) {
                $settings[ 'values' ][ 'chat_tab_gdpr_text' ] = InstantAnswer::sanitize_gdpr_text( $settings[ 'values' ][ 'chat_tab_gdpr_text' ] );
            }
        }

        return parent::save_settings( $settings );
    }

    public function git_default_settings( $defaults ) {
        // NOTE: API Documentation has NO settings tab. Everything about a
        // reference — Try-it label/show-hide, accent colours, Code Snippet mode
        // and the Try-it proxy + its host allowlist — is per reference (meta on
        // `betterdocs_api_ref`), edited in the API Docs Create/Edit drawer.
        // ApiReferences::migrate_proxy_settings() carries the old site-wide
        // api_ref_proxy_* values over to the existing references.

        $defaults['git_provider']       = 'github';
        $defaults['git_repository_url'] = '';
        $defaults['git_branch']         = 'main';
        $defaults['git_access_token']   = '';
        $defaults['git_docs_directory'] = 'docs';
        $defaults['git_file_naming']    = 'slug';
        $defaults['git_auto_sync']      = false;
        // Must be registered here so Settings::get() reads a stored `false` instead
        // of falling back to the default (default-true toggles otherwise never turn off).
        $defaults['enable_write_with_ai_git'] = true;

        return $defaults;
    }

    public function git_integration_fields( $tab ) {
        $tab['fields'] = [
            'title-git-integration' => [
                'name'     => 'title-git-integration-tab',
                'type'     => 'section',
                'label'    => __( 'Git Sync Settings', 'betterdocs-pro' ),
                'priority' => 60,
                'fields'   => [
                    'enable_git_integration' => [
                        'name'                       => 'enable_git_integration',
                        'type'                       => 'toggle',
                        'label'                      => __( 'Enable Git Sync', 'betterdocs-pro' ),
                        'enable_disable_text_active' => true,
                        'default'                    => false,
                        'priority'                   => 1,
                        'label_subtitle'             => __( 'Enable bidirectional synchronization between BetterDocs and Git repositories', 'betterdocs-pro' )
                    ],
                    'github_repo_settings' => [
                        'name'           => 'github_repo_settings',
                        'type'           => 'github-repo-settings',
                        'label'          => __( 'Repo Settings', 'betterdocs-pro' ),
                        'label_subtitle' => __( 'Connect your docs repo', 'betterdocs-pro' ),
                        'priority'       => 2,
                        'rules'          => Rules::is( 'enable_git_integration', true )
                    ],
                    'git_file_naming' => [
                        'name'           => 'git_file_naming',
                        'type'           => 'select',
                        'label'          => __( 'File Naming Convention', 'betterdocs-pro' ),
                        'default'        => 'slug',
                        'priority'       => 7,
                        'options'        => $this->normalize_options( [
                            'slug'  => __( 'Post Slug', 'betterdocs-pro' ),
                            'id'    => __( 'Post ID', 'betterdocs-pro' ),
                            'title' => __( 'Post Title', 'betterdocs-pro' )
                        ] ),
                        'label_subtitle' => __( 'How to name Markdown files in the repository', 'betterdocs-pro' ),
                        'rules'          => Rules::is( 'enable_git_integration', true )
                    ],
                    'git_auto_sync' => [
                        'name'                       => 'git_auto_sync',
                        'type'                       => 'toggle',
                        'label'                      => __( 'Auto Sync on Save', 'betterdocs-pro' ),
                        'enable_disable_text_active' => true,
                        'default'                    => false,
                        'priority'                   => 8,
                        'label_subtitle'             => __( 'Automatically sync changes to Git when docs are saved', 'betterdocs-pro' ),
                        'rules'                      => Rules::is( 'enable_git_integration', true )
                    ],
                    'enable_write_with_ai_git' => [
                        'name'                       => 'enable_write_with_ai_git',
                        'type'                       => 'toggle',
                        'label'                      => __( 'Write with AI from Git', 'betterdocs-pro' ),
                        'enable_disable_text_active' => true,
                        'default'                    => true,
                        'priority'                   => 9,
                        'label_subtitle'             => __( 'Show the "From Git" tab in the Write with AI doc generator so authors can draft docs from a pull request, issue, or repository file', 'betterdocs-pro' ),
                        'rules'                      => Rules::is( 'enable_git_integration', true )
                    ]
                ]
            ]
        ];

        return $tab;
    }

    public function content_restriction_fields( $fields ) {
        $roles_without_admin = wp_roles()->role_names;
        unset( $roles_without_admin[ 'administrator' ] );
        unset( $roles_without_admin[ 'subscriber' ] );
        if ( betterdocs_pro()->multiple_kb->is_enable ) {
            $fields[ 'betterdocs_access_control_repeater_kb' ] = array(
                'label' => __( 'Access Control', 'betterdocs-pro' ),
                'name' => 'betterdocs_access_control_repeater_kb',
                'type' => 'better-repeater',
                'priority' => 11,
                'placeholder_img' => '',
                'button' => array(
                    'label' => __( 'Add New Rule', 'betterdocs-pro' ),
                    'position' => 'bottom',
                    'icon_url' => BETTERDOCS_ABSURL . 'assets/static/images/plus.png'
                ),
                'default' => array(),
                'empty_rules_message' => array( 'icon_url' => BETTERDOCS_ABSURL . 'assets/static/images/no-rules.png', 'heading' => __( 'No Rules Added Yet', 'betterdocs-pro' ), 'subtitle' => __( 'Set up access rules to control who can view, edit, or manage your documentation.', 'betterdocs-pro' ) ),
                'visible_fields' => array( 'access_control_rule_kb' ),
                '_fields' => array(
                    'access_control_rule_kb' => array(
                        'name' => 'access_control_rule_kb',
                        'type' => 'text',
                        'label' => __( 'Rule Name', 'betterdocs-pro' ),
                        'placeholder' => __( 'Add your Rule name e.g., Only access to Editors', 'betterdocs-pro' ),
                        'default' => '',
                        'priority' => 1
                    ),
                    'control_access_restrict_multiple_kb' => array(
                        'name' => 'control_access_restrict_multiple_kb',
                        'type' => 'select',
                        'label' => __( 'Knowledge Base', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 2,
                        'multiple' => false,
                        'show_selected_values' => true,
                        'search' => true,
                        'default' => '',
                        'placeholder' => __( 'Select KB to apply this rule to', 'betterdocs-pro' ),
                        'options' => array(),
                        'ajax' => array(
                            'api' => '/wp/v2/knowledge_base',
                            'data' => array(
                                'suppress_filters' => 'true'
                            ),
                            'method' => "GET",
                            'response_mapper' => array(
                                'value' => 'id',
                                'label' => 'name'
                            )
                        )
                    ),
                    'control_access_restrict_doc_category_kb' => array(
                        'name' => 'control_access_restrict_doc_category_kb',
                        'type' => 'select',
                        'label' => __( 'Doc Categories', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 2,
                        'show_selected_values' => false,
                        'include_all_in_options' => true,
                        'multiple' => true,
                        'search' => true,
                        'default' => array(),
                        'placeholder' => __( 'Select Doc Categories to apply this rule to', 'betterdocs-pro' ),
                        'options' => array(),
                        'ajax' => array(
                            'api' => '/betterdocs/v1/doc-categories-kb',
                            'data' => array(
                                'knowledge_base' => '@betterdocs_access_control_repeater_kb.control_access_restrict_multiple_kb',
                                'suppress_filters' => 'true'
                            ),
                            'method' => "GET",
                            'response_mapper' => array(
                                'value' => 'term_id',
                                'label' => 'name'
                            )
                        ),
                        'rules' => Rules::is( 'betterdocs_access_control_repeater_kb[index].control_access_restrict_multiple_kb', '', true )
                    ),
                    'control_access_restrict_docs_kb' => array(
                        'name' => 'control_access_restrict_docs_kb',
                        'type' => 'select',
                        'label' => __( 'Docs', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 3,
                        'multiple' => true,
                        'show_selected_values' => false,
                        'search' => true,
                        'default' => array(),
                        'placeholder' => __( 'Select Docs to apply this rule to', 'betterdocs-pro' ),
                        'options' => array(),
                        'ajax' => array(
                            'api' => '/wp/v2/docs',
                            'data' => array(
                                'doc_category' => '@betterdocs_access_control_repeater_kb.control_access_restrict_doc_category_kb',
                                'knowledge_base' => '@betterdocs_access_control_repeater_kb.control_access_restrict_multiple_kb',
                                'per_page' => '-1',
                                'suppress_filters' => 'true'
                            ),
                            'method' => "GET",
                            'response_mapper' => array(
                                'value' => 'id',
                                'label' => 'title.rendered'
                            )
                        ),
                        'rules' => Rules::logicalRule(
                            array(
                                Rules::is( 'betterdocs_access_control_repeater_kb[index].control_access_restrict_doc_category_kb', '', true ),
                                Rules::is( 'betterdocs_access_control_repeater_kb[index].control_access_restrict_multiple_kb', '', true ),
                                Rules::is( 'betterdocs_access_control_repeater_kb[index].control_access_restrict_doc_category_kb', 'all', true )
                            ),
                            'and'
                        )
                    ),
                    'control_access_restrict_roles_kb' => array(
                        'name' => 'control_access_restrict_roles_kb',
                        'type' => 'select',
                        'label' => __( 'User Roles', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 4,
                        'multiple' => true,
                        'show_selected_values' => false,
                        'search' => true,
                        'default' => array(),
                        'placeholder' => __( 'Choose which user roles this rule applies to', 'betterdocs-pro' ),
                        'options' => $this->normalize_options( $roles_without_admin )
                    ),
                    'control_access_permission_mode_kb' => array(
                        'name' => 'control_access_permission_mode_kb',
                        'type' => 'radio-card',
                        'label' => __( 'Permission Mode', 'betterdocs-pro' ),
                        'className' => 'modal-radio-card-access-control',
                        'is_pro' => true,
                        'priority' => 5,
                        'multiple' => false,
                        'search' => false,
                        'default' => '',
                        'placeholder' => __( 'Select any', 'betterdocs-pro' ),
                        'options' => $this->normalize_options( array(
                            array(
                                'label' => __( 'View', 'betterdocs-pro' ),
                                'value' => 'view'
                            ),
                            array(
                                'label' => __( 'Edit Only', 'betterdocs-pro' ),
                                'value' => 'edit-only'
                            ),
                            array(
                                'label' => __( 'Full Control', 'betterdocs-pro' ),
                                'value' => 'full-control'
                            ),
                            array(
                                'label' => __( 'Restricted', 'betterdocs-pro' ),
                                'value' => 'restricted'
                            )
                        ) )
                    )
                ),
                'rules' => Rules::logicalRule(
                    array(
                        Rules::is( 'enable_content_restriction', true ),
                        Rules::is( 'internal_knowledge_base_type', 'advanced' )
                    ),
                    'and'
                )
            );
        } else {
            $fields[ 'betterdocs_access_control_repeater' ] = array(
                'label' => __( 'Access Control', 'betterdocs-pro' ),
                'name' => 'betterdocs_access_control_repeater',
                'type' => 'better-repeater',
                'priority' => 11,
                'placeholder_img' => '',
                'button' => array(
                    'label' => __( 'Add New Rule', 'betterdocs-pro' ),
                    'position' => 'bottom',
                    'icon_url' => BETTERDOCS_ABSURL . 'assets/static/images/plus.png'
                ),
                'default' => array(),
                'empty_rules_message' => array( 'icon_url' => BETTERDOCS_ABSURL . 'assets/static/images/no-rules.png', 'heading' => __( 'No Rules Added Yet', 'betterdocs-pro' ), 'subtitle' => __( 'Set up access rules to control who can view, edit, or manage your documentation.', 'betterdocs-pro' ) ),
                'visible_fields' => array( 'access_control_rule' ),
                '_fields' => array(
                    'access_control_rule' => array(
                        'name' => 'access_control_rule',
                        'type' => 'text',
                        'label' => __( 'Rule Name', 'betterdocs-pro' ),
                        'placeholder' => __( 'Add your Rule name e.g., Only access to Editors', 'betterdocs-pro' ),
                        'default' => '',
                        'priority' => 1
                    ),
                    'control_access_restrict_doc_category' => array(
                        'name' => 'control_access_restrict_doc_category',
                        'type' => 'select',
                        'label' => __( 'Doc Categories', 'betterdocs-pro' ),
                        'placeholder' => __( 'Select Doc Categories to apply this rule to', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 2,
                        'multiple' => true,
                        'search' => true,
                        'show_selected_values' => false,
                        'include_all_in_options' => true,
                        'default' => array(),
                        'options' => array(),
                        'ajax' => array(
                            'api' => '/wp/v2/doc_category',
                            'data' => array(
                                'suppress_filters' => 'true',
                                'per_page' => '-1',
                                'suppress_filters' => 'true'
                            ),
                            'method' => "GET",
                            'response_mapper' => array(
                                'value' => 'id',
                                'label' => 'name'
                            )
                        )
                    ),
                    'control_access_restrict_docs' => array(
                        'name' => 'control_access_restrict_docs',
                        'type' => 'select',
                        'label' => __( 'Docs', 'betterdocs-pro' ),
                        'placeholder' => __( 'Select Docs to apply this rule to', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 3,
                        'multiple' => true,
                        'show_selected_values' => false,
                        'search' => true,
                        'default' => array(),
                        'options' => array(),
                        'ajax' => array(
                            'api' => '/wp/v2/docs',
                            'data' => array(
                                'doc_category' => '@betterdocs_access_control_repeater.control_access_restrict_doc_category',
                                'per_page' => '-1',
                                'suppress_filters' => 'true'
                            ),
                            'method' => "GET",
                            'response_mapper' => array(
                                'value' => 'id',
                                'label' => 'title.rendered'
                            )
                        ),
                        'rules' => Rules::logicalRule(
                            array(
                                Rules::is( 'betterdocs_access_control_repeater[index].control_access_restrict_doc_category', '', true ),
                                Rules::is( 'betterdocs_access_control_repeater[index].control_access_restrict_doc_category', 'all', true )
                            ),
                            'and'
                        )
                    ),
                    'control_access_restrict_roles' => array(
                        'name' => 'control_access_restrict_roles',
                        'type' => 'select',
                        'label' => __( 'User Roles', 'betterdocs-pro' ),
                        'placeholder' => __( 'Choose which user roles this rule applies to', 'betterdocs-pro' ),
                        'is_pro' => true,
                        'priority' => 4,
                        'multiple' => true,
                        'search' => true,
                        'default' => array(),
                        'show_selected_values' => false,
                        'options' => $this->normalize_options( $roles_without_admin )
                    ),
                    'control_access_permission_mode' => array(
                        'name' => 'control_access_permission_mode',
                        'type' => 'radio-card',
                        'label' => __( 'Permission Mode', 'betterdocs-pro' ),
                        'className' => 'modal-radio-card-access-control',
                        'is_pro' => true,
                        'priority' => 5,
                        'multiple' => false,
                        'search' => false,
                        'default' => '',
                        'placeholder' => __( 'Select any', 'betterdocs-pro' ),
                        'options' => $this->normalize_options( array(
                            array(
                                'label' => __( 'View', 'betterdocs-pro' ),
                                'value' => 'view'
                            ),
                            array(
                                'label' => __( 'Edit Only', 'betterdocs-pro' ),
                                'value' => 'edit-only'
                            ),
                            array(
                                'label' => __( 'Full Control', 'betterdocs-pro' ),
                                'value' => 'full-control'
                            ),
                            array(
                                'label' => __( 'Restricted', 'betterdocs-pro' ),
                                'value' => 'restricted'
                            )
                        ) )
                    )
                ),
                'rules' => Rules::logicalRule(
                    array(
                        Rules::is( 'enable_content_restriction', true ),
                        Rules::is( 'internal_knowledge_base_type', 'advanced' )
                    ),
                    'and'
                )
            );
        }

        return $fields;
    }

    public function get_raw_field( $key, $default = null ) {
        $_settings = $this->database->get( $this->base_key, array() );

        if ( isset( $_settings[ $key ] ) ) {
            return $this->get_normalized_value( $key, $_settings[ $key ], $default );
        }

        return $default;
    }

    /**
     * A list of deprecated settings key.
     *
     * @return array
     * @since 2.5.0
     */
    public function deprecated_settings() {
        $_deprecated_settings = array(
            'reporting_subject' => 'reporting_subject_updated'
        );

        return array_merge( parent::deprecated_settings(), $_deprecated_settings );
    }

    /**
     * Migration for version 2.5.0
     *
     * @return void
     * @since 2.5.0
     */
    public function v250() {
        if ( get_option( 'betterdocs_settings' ) == false ) {
            return;
        }

        if ( method_exists( parent::class, 'v250' ) ) {
            parent::v250();
        }

        $_reporting_subject = $this->get( 'reporting_subject_updated', '' );
        if ( ! empty( $_reporting_subject ) ) {
            $this->save( 'reporting_subject', $_reporting_subject );
        }

        $_ia_settings_migration = array(
            'ia_heading_font_size',
            'ia_sub_heading_size',
            'iac_article_title_size',
            'iac_article_content_size',
            'ia_feedback_title_size',
            'ia_feedback_icon_size',
            'ia_response_icon_size',
            'ia_response_title_size',
            'iac_docs_title_font_size',
            'iac_article_content_h1',
            'iac_article_content_h2',
            'iac_article_content_h3',
            'iac_article_content_h4',
            'iac_article_content_h5',
            'iac_article_content_h6',
            'iac_article_content_p'
        );

        $_defaults     = $this->get_default();
        $_raw_settings = $this->database->get( $this->base_key, array() );
        foreach ( $_ia_settings_migration as $key ) {
            $data = isset( $_raw_settings[ $key ] ) ? $_raw_settings[ $key ] : null;
            if ( null !== $data ) {
                if ( empty( $data ) ) {
                    $this->save( $key, $_defaults[ $key ] );
                }
            }
        }
    }

    public function v253() {
        if ( get_option( 'betterdocs_settings' ) == false ) {
            return;
        }

        if ( method_exists( parent::class, 'v253' ) ) {
            parent::v253();
        }

        $ia_options = array(
            'display_ia_pages',
            'display_ia_archives',
            'display_ia_texonomy',
            'display_ia_single'
        );

        foreach ( $ia_options as $option ) {
            if ( $this->get_raw_field( $option ) == false ) {
                $this->save( $option, array() );
            }
        }
    }

    public function enqueue( $hook ) {
        if ( 'betterdocs_page_betterdocs-settings' !== $hook ) {
            return;
        }
        wp_enqueue_media();

        parent::enqueue( $hook );

        /**
         * Git Sync is Pro-only, so the nonce its Settings field needs must be
         * provided by Pro. Free dropped its `github_oauth_nonce` localization
         * when Git Integration moved out of Free (Free <= 4.4.x), which left
         * the Git Sync provider tabs disabled (empty `betterdocs_git_providers`
         * response). Attach it as an `after` inline script on Free's settings
         * handle so it augments — never clobbers — the `betterdocs_admin`
         * object the GitHubRepoSettings field reads.
         */
        wp_add_inline_script(
            'betterdocs-admin',
            'window.betterdocs_admin = window.betterdocs_admin || {};'
            . ' window.betterdocs_admin.github_oauth_nonce = '
            . wp_json_encode( wp_create_nonce( 'betterdocs_github_oauth_nonce' ) ) . ';',
            'after'
        );

        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-settings', 'admin/css/settings.css' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-settings', 'admin/js/settings.js' );
        betterdocs_pro()->assets->localize( 'betterdocs-pro-settings', 'betterdocsProAdminSettings', array(
            'multiple_kb_url' => admin_url( 'edit-tags.php?taxonomy=knowledge_base&post_type=docs' ),
            'glossaries_url' => admin_url( 'admin.php?page=betterdocs-glossaries' ),
            'ai_chabot_url' => admin_url( 'admin.php?page=betterdocs-ai-chatbot' )
        ) );
    }

    /**
     * A list of default settings.
     *
     * @param array $defaults
     *
     * @return array
     */
    public function _default( $defaults ) {
        $_pro_defaults = array(
            'multiple_kb' => false,
            'disable_root_slug_mkb' => false,
            'collect_analytics_data' => true,
            'unique_visitor_count' => true,
            'exclude_bot_analytics' => true,
            'advance_search' => false,
            'ia_home_docs_visibility' => true,
            'ia_home_faq_visibility' => true,
            'child_category_exclude' => false,
            'betterdocs_popular_docs_text' => __( 'Popular Docs', 'betterdocs-pro' ),
            'betterdocs_popular_docs_number' => 10,
            'popular_keyword_limit' => 5,
            'search_button_text' => __( 'Search', 'betterdocs-pro' ),
            'kb_based_search' => false,
            'article_roles' => array( 'administrator' ),
            'settings_roles' => array( 'administrator' ),
            'analytics_roles' => array( 'administrator' ),
            'faq_roles' => array( 'administrator' ),
            'enable_content_restriction' => false,
            'content_visibility' => array( 'all' ),
            'restrict_template' => array( 'all' ),
            'restrict_category' => array( 'all' ),
            'restrict_kb' => array( 'all' ),
            'restricted_redirect_url' => '',
            'reporting_frequency' => 'betterdocs_weekly',
            /* translators: %s: site name */
            'reporting_subject' => wp_sprintf( __( 'Your Documentation Performance of %s Website', 'betterdocs-pro' ), get_bloginfo( 'name' ) ),
            'select_reporting_data' => array( 'overview', 'top-docs', 'most-search' ),
            'archive_nested_subcategory' => true,
            'enable_disable' => true,
            'ia_enable_preview' => true,
            'content_type' => 'docs',
            'docs_list' => array(),
            'doc_category_list' => array(),
            'doc_category_list_orderby' => 'doc_category_order',
            'doc_category_list_order' => 'asc',
            'home_doc_category_text' => __( 'Doc Categories', 'betterdocs-pro' ),
            'home_docs_text' => __( 'Docs', 'betterdocs-pro' ),
            'doc_category_limit' => 10,
            'display_ia_pages' => array( 'all' ),
            'display_ia_archives' => array( 'all' ),
            'display_ia_texonomy' => array( 'all' ),
            'display_ia_single' => array( 'all' ),
            'ask_subject' => '[ia_subject]',
            'ask_email' => get_bloginfo( 'admin_email' ),
            'ask_thanks_title' => __( 'Thanks', 'betterdocs-pro' ),
            'ask_thanks_text' => __( 'Your Message Has Been Sent Successfully', 'betterdocs-pro' ),
            'launcher_open_icon' => array(),
            'launcher_close_icon' => array(),
            'search_visibility_switch' => false,
            'search_placeholder_text' => __( 'Search...', 'betterdocs-pro' ),
            'chat_tab_visibility_switch' => true,
            'chat_tab_file_upload_switch' => true,
            'chat_tab_icon' => array(),
            'chat_tab_title' => __( 'Ask', 'betterdocs-pro' ),
            'chat_subtitle_one' => __( 'Need a hand? Shoot us a message.', 'betterdocs-pro' ),
            'chat_subtitle_two' => __( 'We typically respond within 24-48 hours. Your solution is just a message away.', 'betterdocs-pro' ),
            'chat_tab_gdpr_switch' => false,
            'chat_tab_gdpr_text' => __( 'I consent to my data being used to respond to my inquiry.', 'betterdocs-pro' ),
            'enable_recaptcha' => false,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'recaptcha_score_threshold' => 0.5,
            'ia_reaction' => true,
            'reaction_title' => __( 'How did you feel?', 'betterdocs-pro' ),
            'response_title' => __( 'Thanks for the feedback', 'betterdocs-pro' ),
            'ia_branding' => true,
            'chat_position' => 'right',
            'chat_zindex' => 9999,
            'search_not_found_1' => __( 'Oops...', 'betterdocs-pro' ),
            'search_not_found_2' => __( 'We couldn’t find any docs that match your search. Try searching for a new term.', 'betterdocs-pro' ),
            'ia_luncher_bg' => '#00b682',
            'ia_luncher_bg_hover' => '#00b682',
            'ia_color_title' => '',
            'ia_accent_color' => '#19ca9e',
            'ia_sub_accent_color' => '#16b38c',
            'ia_heading_font_size' => 19,
            'ia_heading_color' => '#FFFFFF',
            'ia_sub_heading_size' => 12,
            'ia_sub_heading_color' => '#FFFFFF',
            //No color was set by default in the previous version
            'ia_searchbox_bg' => '#FFFFFF',
            'ia_searc_icon_color' => '#FFFFFF',
            'ia_searchbox_text' => '#2c3338',
            'iac_article_bg' => '',
            //does not contain any default value on default selectors(in previous version as well)
            'iac_article_title_size' => 16,
            'iac_article_title' => '#1d2327',
            'iac_article_content_size' => 16,
            'iac_article_content' => '',
            //does not contain any default value on default selectors(in previous version as well)
            'ia_feedback_title_size' => 14,
            'ia_feedback_title_color' => '',
            //does not contain any default value on default selectors(in previous version as well)
            'ia_feedback_icon_size' => 15,
            'ia_feedback_icon_color' => '#FFFFFF',
            'ia_response_icon_size' => 24,
            //the selector is unknown and does not work in the previous version as well, left it 0
            'ia_response_title_size' => 13,
            //the selector is unknown and does not work in the previous version as well, left it 25
            'ia_response_title_color' => '',
            //the selector is unknown and does not work in the previous version as well, left it empty
            'ia_response_icon_color' => '',
            //the selector is unknown and does not work in the previous version as well(where the key was empty), left it empty
            'ia_ask_bg_color' => '#FFFFFF',
            'ia_ask_input_foreground' => '#939eaa',
            'ia_ask_send_disable_button_bg' => '#19ca9e',
            //does not work in previous version, problem might exist
            'ia_ask_send_disable_button_hover_bg' => '#19ca9e',
            //does not work in previous version, problem might exist
            'ia_ask_send_button_bg' => '#19ca9e',
            //does not work in previous version, problem might exist
            'ia_ask_send_button_hover_bg' => '#19ca9e',
            'content_heading_tag' => '',
            'iac_docs_title_font_size' => 20,
            'iac_article_content_h1' => 26,
            'iac_article_content_h2' => 24,
            'iac_article_content_h3' => 22,
            'iac_article_content_h4' => 20,
            'iac_article_content_h5' => 18,
            'iac_article_content_h6' => 16,
            'iac_article_content_p' => 14,
            'ia_resources_doc_categories' => array(),
            'ia_resources_faq_group' => array(),
            'ia_resources_faq_list' => array(),
            'ia_resources_doc_categories_switch' => true,
            'ia_resources_doc_subcategories_switch' => true,
            'ia_resources_faq_switch' => true,
            'ia_resources_faq_content_type' => 'faq-list',
            'ia_home_faq_content_type' => 'faq-list',
            'ia_home_faq_list' => array( 'all' ),
            'ia_home_faq_group' => array( 'all' ),
            // 'ia_resources_faq_group_number'             => 5,
            // 'ia_resources_faq_list_number'              => 5,
            'ia_resources_doc_category_title_text' => __( 'Doc Categories', 'betterdocs-pro' ),
            'ia_resources_faq_title' => __( 'FAQ', 'betterdocs-pro' ),
            'home_tab_title' => __( 'Home', 'betterdocs-pro' ),
            'home_content_title' => __( 'Get Instant Help', 'betterdocs-pro' ),
            'home_content_subtitle' => __( 'Need any assistance? Get quick solutions to any problems you face.', 'betterdocs-pro' ),
            'ia_resources_general_content_title' => __( 'Resources', 'betterdocs-pro' ),
            'ia_resource_general_tab_title' => __( 'Resources', 'betterdocs-pro' ),
            'ia_card_title_color' => '#111213',
            'ia_card_title_background_color' => '#FFFFFF',
            'ia_card_title_list_color' => '#111213',
            'ia_card_list_description_color' => '#6D7175',
            'ia_card_list_background_color' => '#FFFFFF',
            'ia_search_box_placeholder_text_color' => '#1c1c1c',
            'ia_search_box_input_text_color' => '#000000',
            'ia_message_tab_title_font_color' => '#FFFFFF',
            'ia_message_tab_subtitle_font_color' => '#FFFFFF',
            'ia_message_button_text_color' => '#FFFFFF',
            'ia_message_input_label_text_color' => '#202223',
            'ia_message_upload_button_background_color' => '#FFFFFF',
            'ia_message_upload_text_color' => '#6d7175',
            'ia_message_tab_gdpr_text_color' => '#6d7175',
            'ia_launcher_tabs_background_color' => '#FFFFFF',
            'ia_launcher_tabs_text_color' => '#202223',
            'ia_launcher_active_tab_text_color' => '#16CA9E',
            // 'header_background_color'                   => '#00b682',
            'ia_single_doc_title_font_color' => '#111213',
            'ia_single_title_header_font_color' => '#111213',
            'ia_single_doc_title_header_bg_color' => '#F6F6F7',
            // 'ia_single_doc_back_icon_color'             => '#16CA9E',
            'ia_single_doc_back_icon_hover_color' => '#d6ddd9',
            // 'ia_single_expand_icon_color'               => '#16CA9E',
            'ia_single_expand_icon_hover_color' => '#16CA9E',
            'ia_single_icons_bg_color' => '#f6f6f7',
            'ia_reaction_primary_color' => '#00A375',
            'ia_reaction_secondary_color' => '#FFFFFF',
            'ia_reaction_title_color' => '#FAFAFA',
            'header_background_image' => array(),
            // 'upload_header_logo'                        => [],
            'upload_home_icon' => array(),
            'upload_sendmessage_icon' => array(),
            'upload_resource_icon' => array(),
            'ia_terms_orderby' => 'name',
            'ia_terms_order' => 'asc',
            // 'ia_docs_order_by'                          => 'ID',
            // 'ia_docs_order'                             => 'asc',
            'faq_terms_orderby' => 'name',
            'faq_terms_order' => 'asc',
            'ia_faq_list_order_by' => 'id',
            'ia_faq_list_order' => 'asc',
            // 'ia_resources_doc_category_title_text_color'           => '#19ca9e',
            // 'ia_resources_doc_category_card_title_color'           => '#19ca9e',
            // 'ia_resources_doc_category_card_list_color'            => '#19ca9e',
            // 'ia_resources_doc_category_card_list_background_color' => '#19ca9e',
            // 'ia_resources_doc_category_card_arrow_color'           => '#19ca9e'
            'attachment_label' => __( 'Attachments', 'betterdocs-pro' ),
            'attachment_file_name_format' => '',
            'show_related_docs' => false,
            'enable_realtime_related_docs' => false,
            'track_page_views' => true,
            'track_scroll_depth' => true,
            'track_internal_clicks' => true,
            'track_search_queries' => true,
            'track_ai_traffic' => true,
            'related_docs_model' => 'gpt-4o-mini',
            'recommendation_limit' => 5,
            'journey_batch_size' => 10,
            'journey_batch_interval' => 15,
            'journey_session_timeout' => 30,
            'anonymize_ip_addresses' => true,
            'data_retention_days' => 0,
            'show_attachment' => false,
            'show_attachment_size' => true,
            'show_attachment_icon' => true,
            'attachment_new_tab' => false,
            'attachment_image_icon' => array(),
            'attachment_pdf_icon' => array(),
            'attachment_csv_icon' => array(),
            'attachment_wordfile_icon' => array(),
            'attachment_zip_icon' => array(),
            'attachment_audio_icon' => array(),
            'attachment_video_icon' => array(),
            'attachment_text_icon' => array(),
            'attachment_default_icon' => array(),

            'encyclopedia_page' => 'encyclopedia',
            'encyclopedia_page_title' => 'Encyclopedia',
            'encyclopedia_root_slug' => 'encyclopedia',
            'encyclopedia_source' => 'glossaries',
            'glossary_suggestion' => true,
            'encyclopedia_enable_non_latin' => false,
            'encyclopedia_non_latin_option' => '',
            'ai_chatbot_api_key' => '',
            'ai_chatbot_embed_model' => '',
            'ai_chatbot_chat_model' => '',
            // Multi-platform chatbot config. Chat and embedding are chosen
            // independently (Claude has no embeddings API). Keys are stored per
            // provider so switching never loses a saved key. Legacy
            // ai_chatbot_api_key remains the OpenAI fallback for both roles.
            'ai_chatbot_chat_provider' => 'openai',
            'ai_chatbot_embed_provider' => 'openai',
            'ai_chatbot_chat_api_key_openai' => '',
            'ai_chatbot_chat_api_key_gemini' => '',
            'ai_chatbot_chat_api_key_claude' => '',
            'ai_chatbot_chat_api_key_deepseek' => '',
            'ai_chatbot_chat_api_key_openrouter' => '',
            'ai_chatbot_embed_api_key_openai' => '',
            'ai_chatbot_embed_api_key_gemini' => '',
            'ai_chatbot_embed_api_key_voyage' => ''
        );

        return array_merge( $defaults, $_pro_defaults );
    }

    /**
     * A list of default settings (ONLY PRO)
     * @return array
     */
    public function get_pro_defaults() {
        return $this->_default( array() );
    }

    public function insert_attachments_settings( $settings ) {
        $settings[ 'show_attachment' ][ 'is_pro' ] = false;
        $pro_fields                                = array(
            'attachment_label' => array(
                'name' => 'attachment_label',
                'type' => 'text',
                'label' => __( 'Attachment Label', 'betterdocs-pro' ),
                'label_subtitle' => __( 'This setting changes the attachment title in single docs', 'betterdocs-pro' ),
                'default' => '',
                'priority' => 2,
                'rules' => Rules::is( 'show_attachment', true )
            ),
            'attachment_file_name_format' => array(
                'name' => 'attachment_file_name_format',
                'type' => 'text',
                'label' => __( 'Attachment Default File Name Format', 'betterdocs-pro' ),
                'label_subtitle' => __( 'This setting is not applicable for external type of files', 'betterdocs-pro' ),
                'default' => '',
                'priority' => 3,
                'rules' => Rules::is( 'show_attachment', true )
            ),
            'show_attachment_size' => array(
                'name' => 'show_attachment_size',
                'type' => 'toggle',
                'is_pro' => false,
                'priority' => 4,
                'label' => __( 'Show Attachment Size', 'betterdocs-pro' ),
                'enable_disable_text_active' => true,
                'default' => true,
                'rules' => Rules::is( 'show_attachment', true )
            ),
            'show_attachment_icon' => array(
                'name' => 'show_attachment_icon',
                'type' => 'toggle',
                'is_pro' => false,
                'priority' => 5,
                'label' => __( 'Show Attachment Icon', 'betterdocs-pro' ),
                'enable_disable_text_active' => true,
                'default' => true,
                'rules' => Rules::is( 'show_attachment', true )
            ),
            'attachment_new_tab' => array(
                'name' => 'attachment_new_tab',
                'type' => 'toggle',
                'is_pro' => false,
                'priority' => 6,
                'label' => __( 'Open Attachment In New Tab', 'betterdocs-pro' ),
                'enable_disable_text_active' => true,
                'default' => false,
                'rules' => Rules::is( 'show_attachment', true )
            ),
            'attachment_image_icon' => array(
                'name' => 'attachment_image_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Image Icon', 'betterdocs-pro' ),
                'priority' => 7,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_pdf_icon' => array(
                'name' => 'attachment_pdf_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'PDF File Icon', 'betterdocs-pro' ),
                'priority' => 8,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_csv_icon' => array(
                'name' => 'attachment_csv_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'CSV File Icon', 'betterdocs-pro' ),
                'priority' => 9,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_wordfile_icon' => array(
                'name' => 'attachment_wordfile_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Word File Icon', 'betterdocs-pro' ),
                'priority' => 10,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_zip_icon' => array(
                'name' => 'attachment_zip_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Zip File Icon', 'betterdocs-pro' ),
                'priority' => 11,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_audio_icon' => array(
                'name' => 'attachment_audio_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Audio File Icon', 'betterdocs-pro' ),
                'priority' => 12,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_video_icon' => array(
                'name' => 'attachment_video_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Video File Icon', 'betterdocs-pro' ),
                'priority' => 13,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_text_icon' => array(
                'name' => 'attachment_text_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Text File Icon', 'betterdocs-pro' ),
                'priority' => 14,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            ),
            'attachment_default_icon' => array(
                'name' => 'attachment_default_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Default File Icon', 'betterdocs-pro' ),
                'priority' => 15,
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'show_attachment', true ),
                    Rules::is( 'show_attachment_icon', true )
                ) )
            )
        );
        $settings = array_merge( $settings, $pro_fields );

        return $settings;
    }

    public function unlock_related_docs_settings( $settings ) {
        $settings['show_related_docs'] = [
            'name'                       => 'show_related_docs',
            'type'                       => 'toggle',
            'is_pro'                     => false,
            'priority'                   => 1,
            'label'                      => __( 'Show Related Docs', 'betterdocs-pro' ),
            'enable_disable_text_active' => true,
            'default'                    => false
        ];

        $settings['enable_realtime_related_docs'] = [
            'name' => 'enable_realtime_related_docs',
            'label' => __( 'Real-time Related Docs', 'betterdocs-pro' ),
            'enable_disable_text_active' => true,
            'type' => 'toggle',
            'default' => false,
            'priority' => 2,
            'rules' => Rules::is( 'show_related_docs', true ),
            'label_subtitle' => __( 'Enable AI-powered real-time related document recommendations based on user behavior and session context. AI semantic analysis runs automatically and requires an OpenAI API key (set under Settings → AI Content Suite).', 'betterdocs-pro' )
        ];

        $realtime_related_rules = Rules::logicalRule(
            array(
                Rules::is( 'show_related_docs', true ),
                Rules::is( 'enable_realtime_related_docs', true )
            ),
            'and'
        );

        $settings['realtime_related_docs_heading'] = [
            'name' => 'realtime_related_docs_heading',
            'label' => __( 'Tracking Settings', 'betterdocs-pro' ),
            'type' => 'title',
            'priority' => 10,
            'rules' => $realtime_related_rules
        ];

        $settings['track_page_views'] = [
            'name' => 'track_page_views',
            'label' => __( 'Track Page Views', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 11,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Track when users view documentation pages.', 'betterdocs-pro' )
        ];

        $settings['track_scroll_depth'] = [
            'name' => 'track_scroll_depth',
            'label' => __( 'Track Scroll Depth', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 12,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Track how far users scroll through articles to measure engagement.', 'betterdocs-pro' )
        ];

        $settings['track_internal_clicks'] = [
            'name' => 'track_internal_clicks',
            'label' => __( 'Track Internal Link Clicks', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 13,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Track clicks on internal documentation links to understand user navigation patterns.', 'betterdocs-pro' )
        ];

        $settings['track_search_queries'] = [
            'name' => 'track_search_queries',
            'label' => __( 'Track Search Queries', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 14,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Track search queries to improve recommendations based on user intent.', 'betterdocs-pro' )
        ];

        $settings['track_ai_traffic'] = [
            'name' => 'track_ai_traffic',
            'label' => __( 'Track AI Agent Traffic', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 15,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Detect and record visits from AI agents and crawlers (GPTBot, ClaudeBot, PerplexityBot…) on your documentation.', 'betterdocs-pro' )
        ];

        $settings['related_docs_model'] = [
            'name' => 'related_docs_model',
            'label' => __( 'OpenAI Model', 'betterdocs-pro' ),
            'type' => 'select',
            'default' => 'gpt-4o-mini',
            'priority' => 20,
            'options' => $this->normalize_options( [
                'gpt-4o-mini' => __( 'GPT-4o Mini', 'betterdocs-pro' ),
                'gpt-4o'      => __( 'GPT-4o', 'betterdocs-pro' )
            ] ),
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'OpenAI model used to generate AI semantic recommendations. GPT-4o Mini is recommended for this task.', 'betterdocs-pro' )
        ];

        $settings['recommendation_limit'] = [
            'name' => 'recommendation_limit',
            'label' => __( 'Number of Recommendations', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 5,
            'priority' => 22,
            'min' => 1,
            'max' => 10,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Maximum number of AI-generated recommendations to display.', 'betterdocs-pro' )
        ];

        $settings['journey_batch_size'] = [
            'name' => 'journey_batch_size',
            'label' => __( 'Tracking Batch Size', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 10,
            'priority' => 31,
            'min' => 5,
            'max' => 50,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Number of interactions to batch before sending to server.', 'betterdocs-pro' )
        ];

        $settings['journey_batch_interval'] = [
            'name' => 'journey_batch_interval',
            'label' => __( 'Batch Interval (seconds)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 15,
            'priority' => 32,
            'min' => 5,
            'max' => 60,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'How often to send batched tracking data to the server.', 'betterdocs-pro' )
        ];

        $settings['journey_session_timeout'] = [
            'name' => 'journey_session_timeout',
            'label' => __( 'Session Timeout (minutes)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 30,
            'priority' => 33,
            'min' => 10,
            'max' => 120,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'How long to maintain user session for tracking purposes.', 'betterdocs-pro' )
        ];

        $settings['anonymize_ip_addresses'] = [
            'name' => 'anonymize_ip_addresses',
            'label' => __( 'Anonymize IP Addresses', 'betterdocs-pro' ),
            'type' => 'toggle',
            'enable_disable_text_active' => true,
            'default' => true,
            'priority' => 41,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'Hash IP addresses for privacy compliance (recommended).', 'betterdocs-pro' )
        ];

        $settings['data_retention_days'] = [
            'name' => 'data_retention_days',
            'label' => __( 'Data Retention (days)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 0,
            'priority' => 42,
            'min' => 0,
            'max' => 365,
            'rules' => $realtime_related_rules,
            'label_subtitle' => __( 'How long to keep tracking data before automatic cleanup. Use 0 to keep data forever.', 'betterdocs-pro' )
        ];

        return $settings;
    }

    public function shortcode_fields( $args ) {
        $args[ 'category_box_l3_shortcode' ] = array(
            'name' => 'category_box_l3_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Category Box- Layout 3', 'betterdocs-pro' ),
            'default' => '[betterdocs_category_box_2]',
            'readOnly' => true,
            'priority' => 6,
            'description' => '[betterdocs_category_box_2 column="" nested_subcategory="" terms="" terms_orderby="" kb_slug="" multiple_knowledge_base="false" title_tag="h2"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'category_grid_2_shortcode' ] = array(
            'name' => 'category_grid_2_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Category Grid- Layout 4', 'betterdocs-pro' ),
            'default' => '[betterdocs_category_grid_2]',
            'readOnly' => true,
            'priority' => 7,
            'description' => '[betterdocs_category_grid_2 orderby="" order="" masonry="" column="" posts="" nested_subcategory="" terms="" kb_slug="" terms_orderby="" terms_order="" multiple_knowledge_base="false" title_tag="h2"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'multiple_kb_shortcode' ] = array(
            'name' => 'multiple_kb_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Multiple KB- Layout 1', 'betterdocs-pro' ),
            'default' => '[betterdocs_multiple_kb]',
            'readOnly' => true,
            'priority' => 8,
            'description' => '[betterdocs_multiple_kb column="" terms="" title_tag="h2"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'multiple_kb_2_shortcode' ] = array(
            'name' => 'multiple_kb_2_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Multiple KB- Layout 2', 'betterdocs-pro' ),
            'default' => '[betterdocs_multiple_kb_2]',
            'readOnly' => true,
            'priority' => 9,
            'description' => '[betterdocs_multiple_kb_2 column="" terms="" title_tag="h2" terms_order="" terms_orderby=""]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'multiple_kb_3_shortcode' ] = array(
            'name' => 'multiple_kb_3_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Multiple KB- Layout 3', 'betterdocs-pro' ),
            'default' => '[betterdocs_multiple_kb_list]',
            'readOnly' => true,
            'priority' => 10,
            'description' => '[betterdocs_multiple_kb_list terms="" title_tag="h2"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'multiple_kb_4_shortcode' ] = array(
            'name' => 'multiple_kb_4_shortcode',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Multiple KB- Layout 4', 'betterdocs-pro' ),
            'default' => '[betterdocs_multiple_kb_tab_grid]',
            'readOnly' => true,
            'priority' => 11,
            'description' => '[betterdocs_multiple_kb_tab_grid terms="" terms_orderby="" terms_order="" orderby="" order="" posts_per_page="" title_tag="h2"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );
        $args[ 'mkb_popular_docs' ] = array(
            'name' => 'mkb_popular_docs',
            'type' => 'copy-to-clipboard',
            'label' => __( 'Popular Docs', 'betterdocs-pro' ),
            'default' => '[betterdocs_popular_articles]',
            'readOnly' => true,
            'priority' => 12,
            'description' => '[betterdocs_popular_articles post_per_page="" title="Popular Docs" title_tag="h2" multiple_knowledge_base="false"]',
            'descriptionLabel' => __( 'Example with parameters:', 'betterdocs-pro' ),
            'descriptionCopyable' => true
        );

        return $args;
    }

    public function instant_answer_revamp_fields( $args ) {

        /**
         * I/A Revamped Fields
         *
         * @param array $args
         *
         * @return void
         */
        $args[ 'instant_answer_tab' ] = array(
            'id' => 'instant_answer_tab',
            'name' => 'instant_answer_tab',
            'classes' => 'tab-layout',
            'type' => "tab",
            'label' => __( 'Instant Answer', 'betterdocs-pro' ),
            'active' => "initial_content_type_settings",
            'default' => "initial_content_type_settings",
            'completionTrack' => true,
            'sidebar' => false,
            'save' => false,
            'priority' => 20,
            'submit' => array(
                'show' => false
            ),
            'step' => array(
                'show' => false
            ),
            'rules' => Rules::is( 'enable_disable', true ),
            'fields' => array(
                'initial_content_type_settings' => array(
                    'id' => 'initial_content_type_settings',
                    'name' => 'initial_content_type_settings',
                    'type' => 'section',
                    'label' => __( "Initial Settings", 'betterdocs-pro' ),
                    'priority' => 1,
                    'fields' => array(
                        'intial-content-tab' => array(
                            'id' => 'intial_content_tab',
                            'name' => 'intial_content_tab',
                            'label' => __( 'Content', 'betterdocs-pro' ),
                            'classes' => 'tab-nested-layout',
                            'type' => "tab",
                            'active' => "home_content",
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'home_content',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 1,
                            'fields' => array(
                                'home-content' => array(
                                    'id' => 'home_content',
                                    'name' => 'home_content',
                                    'type' => "section",
                                    'label' => __( 'Content', 'betterdocs-pro' ),
                                    'priority' => 1,
                                    'fields' => array(
                                        'ia_enable_preview' => array(
                                            'name' => 'ia_enable_preview',
                                            'type' => 'toggle',
                                            'priority' => 1,
                                            'label' => __( 'Live Preview', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'is_pro' => true
                                        ),
                                        'display_ia_pages' => array(
                                            'name' => 'display_ia_pages',
                                            'type' => 'checkbox-select',
                                            'label' => __( 'Show on Page', 'betterdocs-pro' ),
                                            'priority' => 2,
                                            'multiple' => true,
                                            'default' => array( 'all' ),
                                            'options' => $this->get_pages_for_ia()
                                        ),
                                        'display_ia_archives' => array(
                                            'name' => 'display_ia_archives',
                                            'type' => 'checkbox-select',
                                            'label' => __( 'Show on Archive Templates', 'betterdocs-pro' ),
                                            'priority' => 3,
                                            'multiple' => true,
                                            'default' => array( 'all' ),
                                            'options' => $this->get_all_post_types()
                                        ),
                                        'display_ia_texonomy' => array(
                                            'name' => 'display_ia_texonomy',
                                            'type' => 'checkbox-select',
                                            'label' => __( 'Show on Taxonomy Templates', 'betterdocs-pro' ),
                                            'priority' => 4,
                                            'multiple' => true,
                                            'default' => array( 'all' ),
                                            'options' => $this->get_all_registered_texonomy()
                                        ),
                                        'display_ia_single' => array(
                                            'name' => 'display_ia_single',
                                            'type' => 'checkbox-select',
                                            'label' => __( 'Show on Single Pages', 'betterdocs-pro' ),
                                            'priority' => 5,
                                            'multiple' => true,
                                            'default' => array( 'all' ),
                                            'options' => $this->get_all_post_types()
                                        )
                                    )
                                ),
                                'style-content' => array(
                                    'id' => 'style_content',
                                    'name' => 'style_content',
                                    'type' => "section",
                                    'label' => __( 'Style', 'betterdocs-pro' ),
                                    'priority' => 2,
                                    'fields' => array(
                                        'intial-content-style' => array(
                                            'id' => 'intial_content_style',
                                            'name' => 'intial_content_style',
                                            'label' => __( 'Style', 'betterdocs-pro' ),
                                            'classes' => 'tab-nested-layout',
                                            'type' => "tab",
                                            'active' => "launcher_style",
                                            'completionTrack' => true,
                                            'sidebar' => false,
                                            'save' => false,
                                            'title' => false,
                                            'config' => array(
                                                'active' => 'launcher_style',
                                                'sidebar' => false,
                                                'title' => false
                                            ),
                                            'submit' => array(
                                                'show' => false
                                            ),
                                            'step' => array(
                                                'show' => false
                                            ),
                                            'priority' => 1,
                                            'fields' => array(
                                                'launcher-style' => array(
                                                    'id' => 'launcher_style',
                                                    'name' => 'launcher_style',
                                                    'type' => "section",
                                                    'label' => __( 'Common', 'betterdocs-pro' ),
                                                    'priority' => 1,
                                                    'fields' => array(
                                                        'chat_position' => array(
                                                            'name' => 'chat_position',
                                                            'label' => __( "I/A Appearance Position", "betterdocs-pro" ),
                                                            "type" => 'select',
                                                            'priority' => 1,
                                                            'default' => '',
                                                            'options' => GlobalFields::normalize_fields( array(
                                                                'left' => __( 'Left', 'betterdocs-pro' ),
                                                                'right' => __( 'Right', 'betterdocs-pro' )
                                                            ) )
                                                        ),
                                                        'ia_luncher_bg' => array(
                                                            'name' => 'ia_luncher_bg',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Primary Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#00b682',
                                                            'priority' => 3
                                                        ),
                                                        'ia_luncher_bg_hover' => array(
                                                            'name' => 'ia_luncher_bg_hover',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Launcher Hover Background Color', 'betterdocs-pro' ),
                                                            'default' => '#00b682',
                                                            'priority' => 4
                                                        ),
                                                        'launcher_open_icon' => array(
                                                            'name' => 'launcher_open_icon',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Instant Answer Open Icon', 'betterdocs-pro' ),
                                                            'priority' => 5
                                                        ),
                                                        'launcher_close_icon' => array(
                                                            'name' => 'launcher_close_icon',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Instant Answer Close Icon', 'betterdocs-pro' ),
                                                            'priority' => 6
                                                        )
                                                    )
                                                ),
                                                'header-style' => array(
                                                    'id' => 'header_style',
                                                    'name' => 'header_style',
                                                    'type' => "section",
                                                    'label' => __( 'Header', 'betterdocs-pro' ),
                                                    'priority' => 2,
                                                    'fields' => array(
                                                        // 'header_background_color' => [
                                                        //     'name'       => 'header_background_color',
                                                        //     'type'       => 'colorpicker',
                                                        //     'label'      => __( 'Background Color', 'betterdocs-pro' ),
                                                        //     'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                        //     'default'    => '#00b682',
                                                        //     'priority'   => 1
                                                        // ],
                                                        'header_background_image' => array(
                                                            'name' => 'header_background_image',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Upload Background Image', 'betterdocs-pro' ),
                                                            'priority' => 1
                                                        ),
                                                        // 'upload_header_logo'      => [
                                                        //     'name'     => 'upload_header_logo',
                                                        //     'type'     => 'media',
                                                        //     'value'    => '',
                                                        //     'label'    => __( 'Upload Logo', 'betterdocs-pro' ),
                                                        //     'priority' => 2
                                                        // ],
                                                        'ia_heading_color' => array(
                                                            'name' => 'ia_heading_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Title Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#fff',
                                                            'priority' => 3
                                                        ),
                                                        'ia_sub_heading_color' => array(
                                                            'name' => 'ia_sub_heading_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Subtitle Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#fff',
                                                            'priority' => 4
                                                        )
                                                    )
                                                ),
                                                'card-style' => array(
                                                    'id' => 'card_style',
                                                    'name' => 'card_style',
                                                    'type' => "section",
                                                    'label' => __( 'Card', 'betterdocs-pro' ),
                                                    'priority' => 3,
                                                    'fields' => array(
                                                        'ia_card_title_color' => array(
                                                            'name' => 'ia_card_title_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Title Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#111213',
                                                            'priority' => 1
                                                        ),
                                                        'ia_card_title_background_color' => array(
                                                            'name' => 'ia_card_title_background_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Title Background Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#fff',
                                                            'priority' => 2
                                                        ),
                                                        'ia_card_title_list_color' => array(
                                                            'name' => 'ia_card_title_list_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'List Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#111213',
                                                            'priority' => 3
                                                        ),
                                                        'ia_card_list_description_color' => array(
                                                            'name' => 'ia_card_list_description_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'List Description Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#6D7175',
                                                            'priority' => 4
                                                        ),
                                                        'ia_card_list_background_color' => array(
                                                            'name' => 'ia_card_list_background_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'List Background Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#fff',
                                                            'priority' => 5
                                                        )
                                                        // 'ia_card_list_arrow_color'       => [
                                                        //     'name'       => 'ia_card_list_arrow_color',
                                                        //     'type'       => 'colorpicker',
                                                        //     'label'      => __( 'Arrow Color', 'betterdocs-pro' ),
                                                        //     'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                        //     'default'    => '#19ca9e',
                                                        //     'priority'   => 6
                                                        // ]
                                                    )
                                                ),
                                                'search-style' => array(
                                                    'id' => 'search_style',
                                                    'name' => 'search_style',
                                                    'type' => "section",
                                                    'label' => __( 'Search', 'betterdocs-pro' ),
                                                    'priority' => 4,
                                                    'fields' => array(
                                                        'ia_searchbox_bg' => array(
                                                            'name' => 'ia_searchbox_bg',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Background Color', "betterdocs-pro" ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#fff',
                                                            'priority' => 1
                                                        ),
                                                        'ia_search_box_placeholder_text_color' => array(
                                                            'name' => 'ia_search_box_placeholder_text_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Placeholder Text Color', "betterdocs-pro" ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#1c1c1c',
                                                            'priority' => 2
                                                        ),
                                                        'ia_search_box_input_text_color' => array(
                                                            'name' => 'ia_search_box_input_text_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Input Text Color', "betterdocs-pro" ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#000000',
                                                            'priority' => 3
                                                        ),
                                                        'ia_searc_icon_color' => array(
                                                            'name' => 'ia_searc_icon_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Icon Color', "betterdocs-pro" ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#FFFFFF',
                                                            'priority' => 4
                                                        )
                                                    )
                                                ),
                                                'tabs-style' => array(
                                                    'id' => 'tabs_style',
                                                    'name' => 'tabs_style',
                                                    'type' => "section",
                                                    'label' => __( 'Tabs', 'betterdocs-pro' ),
                                                    'priority' => 5,
                                                    'fields' => array(
                                                        'ia_launcher_tabs_background_color' => array(
                                                            'name' => 'ia_launcher_tabs_background_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Background Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#FFF',
                                                            'priority' => 1
                                                        ),
                                                        'ia_launcher_tabs_text_color' => array(
                                                            'name' => 'ia_launcher_tabs_text_color',
                                                            'type' => 'colorpicker',
                                                            'label' => __( 'Text Color', 'betterdocs-pro' ),
                                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                                            'default' => '#202223',
                                                            'priority' => 2
                                                        ),
                                                        'upload_home_icon' => array(
                                                            'name' => 'upload_home_icon',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Upload Home Icon', 'betterdocs-pro' ),
                                                            'priority' => 3
                                                        ),
                                                        'upload_sendmessage_icon' => array(
                                                            'name' => 'upload_sendmessage_icon',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Upload Send Message Icon', 'betterdocs-pro' ),
                                                            'priority' => 4
                                                        ),
                                                        'upload_resource_icon' => array(
                                                            'name' => 'upload_resource_icon',
                                                            'type' => 'media',
                                                            'value' => '',
                                                            'label' => __( 'Upload Resource Icon', 'betterdocs-pro' ),
                                                            'priority' => 5
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'content_home_section' => array(
                    'id' => 'content_home_section',
                    'name' => 'content_home_section',
                    'type' => 'section',
                    'label' => __( "Home", 'betterdocs-pro' ),
                    'priority' => 2,
                    'fields' => array(
                        'intial-content-tab' => array(
                            'id' => 'intial_content_tab',
                            'name' => 'intial_content_tab',
                            'label' => __( 'Content', 'betterdocs-pro' ),
                            'classes' => 'tab-nested-layout',
                            'type' => "tab",
                            'active' => "ia_home_docs",
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'ia_home_docs',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 1,
                            'fields' => array(
                                'ia_home_docs' => array(
                                    'id' => 'ia_home_docs',
                                    'name' => 'ia_home_docs',
                                    'type' => 'section',
                                    'label' => __( "Docs", "betterdocs-pro" ),
                                    'priority' => 2,
                                    'fields' => array(
                                        'ia_home_docs_visibility' => array(
                                            'name' => 'ia_home_docs_visibility',
                                            'type' => 'toggle',
                                            'label' => __( 'Show Docs Section', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 1
                                        ),
                                        'content_type' => array(
                                            'name' => 'content_type',
                                            'label' => __( 'Content Type', 'betterdocs-pro' ),
                                            'type' => 'select',
                                            'priority' => 2,
                                            'default' => 'docs',
                                            'options' => GlobalFields::normalize_fields( array(
                                                'docs' => __( 'Docs', 'betterdocs-pro' ),
                                                'docs_categories' => __( 'Doc Categories', 'betterdocs-pro' )
                                            ) ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'docs_list' => array(
                                            'name' => 'docs_list',
                                            'label' => __( 'Select Docs', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 3,
                                            'multiple' => true,
                                            'search' => true,
                                            'options' => array_merge( array(
                                                array(
                                                    'value' => 'all',
                                                    'label' => 'All'
                                                )
                                            ), $this->docs() ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::includes( 'content_type', 'docs' )
                                            ) )
                                        ),
                                        'doc_category_list' => array(
                                            'name' => 'doc_category_list',
                                            'label' => __( 'Select Doc Categories', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 4,
                                            'multiple' => true,
                                            'options' => array_merge( array(
                                                array(
                                                    'value' => 'all',
                                                    'label' => 'All'
                                                )
                                            ), $this->docs_categories() ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::includes( 'content_type', 'docs_categories' )
                                            ) )
                                        ),
                                        'doc_category_list_orderby' => array(
                                            'name' => 'doc_category_list_orderby',
                                            'type' => 'select',
                                            'label' => __( 'Category Order By', 'betterdocs-pro' ),
                                            'default' => 'doc_category_order',
                                            'options' => $this->normalize_options( array(
                                                'none' => __( 'No order', 'betterdocs-pro' ),
                                                'name' => __( 'Name', 'betterdocs-pro' ),
                                                'slug' => __( 'Slug', 'betterdocs-pro' ),
                                                'term_group' => __( 'Term Group', 'betterdocs-pro' ),
                                                'term_id' => __( 'Term ID', 'betterdocs-pro' ),
                                                'id' => __( 'ID', 'betterdocs-pro' ),
                                                'description' => __( 'Description', 'betterdocs-pro' ),
                                                'parent' => __( 'Parent', 'betterdocs-pro' ),
                                                'doc_category_order' => __( 'BetterDocs Order', 'betterdocs-pro' )
                                            ) ),
                                            'priority' => 5,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::includes( 'content_type', 'docs_categories' )
                                            ) )
                                        ),
                                        'doc_category_list_order' => array(
                                            'name' => 'doc_category_list_order',
                                            'type' => 'select',
                                            'label' => __( 'Category Order', 'betterdocs-pro' ),
                                            'default' => 'asc',
                                            'options' => $this->normalize_options( array(
                                                'asc' => 'Ascending',
                                                'desc' => 'Descending'
                                            ) ),
                                            'priority' => 6,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::is( 'content_type', 'docs_categories' ),
                                                Rules::is( 'doc_category_list_orderby', 'doc_category_order', true )
                                            ) )
                                        ),
                                        'home_doc_category_text' => array(
                                            'name' => 'home_doc_category_text',
                                            'label' => __( 'Category Title Text', 'betterdocs-pro' ),
                                            'type' => 'text',
                                            'priority' => 7,
                                            'default' => __( 'Doc Categories', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::includes( 'content_type', 'docs_categories' )
                                            ) )
                                        ),
                                        'home_docs_text' => array(
                                            'name' => 'home_docs_text',
                                            'label' => __( 'Docs Title Text', 'betterdocs-pro' ),
                                            'type' => 'text',
                                            'priority' => 8,
                                            'default' => __( 'Docs', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::includes( 'content_type', 'docs' )
                                            ) )
                                        ),
                                        'doc_category_limit' => array(
                                            'name' => 'doc_category_limit',
                                            'type' => 'number',
                                            'label' => __( 'Number Of Categories', 'betterdocs-pro' ),
                                            'default' => 10,
                                            'priority' => 9,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true ),
                                                Rules::is( 'content_type', 'docs_categories' )
                                            ) )
                                        ),
                                        'home_tab_title' => array(
                                            'name' => 'home_tab_title',
                                            'type' => 'text',
                                            'label' => __( 'Tab Title', 'betterdocs-pro' ),
                                            'priority' => 10,
                                            'default' => __( 'Home', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'home_content_title' => array(
                                            'name' => 'home_content_title',
                                            'type' => 'text',
                                            'label' => __( 'Title', 'betterdocs-pro' ),
                                            'priority' => 11,
                                            'default' => __( 'Get Instant Help', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'home_content_subtitle' => array(
                                            'name' => 'home_content_subtitle',
                                            'type' => 'text',
                                            'label' => __( 'Subtitle', 'betterdocs-pro' ),
                                            'priority' => 12,
                                            'default' => __( 'Need any assistance? Get quick solutions to any problems you face.', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'search_placeholder_text' => array(
                                            'name' => 'search_placeholder_text',
                                            'type' => 'text',
                                            'label' => __( 'Search Placeholder', 'betterdocs-pro' ),
                                            'priority' => 13,
                                            'default' => __( 'Search...', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'search_not_found_1' => array(
                                            'name' => 'search_not_found_1',
                                            'type' => 'text',
                                            'label' => __( 'Docs not Found Title', 'betterdocs-pro' ),
                                            'priority' => 14,
                                            'default' => __( 'Oops...', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        ),
                                        'search_not_found_2' => array(
                                            'name' => 'search_not_found_2',
                                            'type' => 'textarea',
                                            'label' => __( 'Docs not Found Subtitle', 'betterdocs-pro' ),
                                            'priority' => 15,
                                            'default' => __( 'We couldn’t find any docs that match your search. Try searching for a new term.', 'betterdocs-pro' ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_docs_visibility', true )
                                            ) )
                                        )
                                    )
                                ),
                                'ia_home_faq' => array(
                                    'id' => 'ia_home_faq',
                                    'name' => 'ia_home_faq',
                                    'type' => 'section',
                                    'label' => __( "FAQ", "betterdocs-pro" ),
                                    'priority' => 2,
                                    'fields' => array(
                                        'ia_home_faq_visibility' => array(
                                            'name' => 'ia_home_faq_visibility',
                                            'type' => 'toggle',
                                            'label' => __( 'Show FAQ Section', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 1
                                        ),
                                        'ia_home_faq_content_type' => array(
                                            'name' => 'ia_home_faq_content_type',
                                            'label' => __( 'Content Type', 'betterdocs-pro' ),
                                            'type' => 'select',
                                            'priority' => 2,
                                            'default' => 'faq-list',
                                            'options' => GlobalFields::normalize_fields( array(
                                                'faq-list' => __( 'FAQ List', 'betterdocs-pro' ),
                                                'faq-group' => __( 'FAQ Group', 'betterdocs-pro' )
                                            ) ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_faq_visibility', true )
                                            ) )
                                        ),
                                        'ia_home_faq_list' => array(
                                            'name' => 'ia_home_faq_list',
                                            'label' => __( 'Select FAQ List', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 3,
                                            'multiple' => true,
                                            'search' => true,
                                            'default' => array(),
                                            'options' => $this->faqs(),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_faq_visibility', true ),
                                                Rules::is( 'ia_home_faq_content_type', 'faq-list' )
                                            ) )
                                        ),
                                        'ia_home_faq_group' => array(
                                            'name' => 'ia_home_faq_group',
                                            'label' => __( 'Select FAQ Group', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 4,
                                            'multiple' => true,
                                            'search' => true,
                                            'default' => array(),
                                            'options' => $this->faqs_categories(),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_home_faq_visibility', true ),
                                                Rules::is( 'ia_home_faq_content_type', 'faq-group' )
                                            ) )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'ia_message_settings' => array(
                    'id' => 'ia_message_settings',
                    'name' => 'ia_message_settings',
                    'type' => 'section',
                    'label' => __( 'Message', 'betterdocs-pro' ),
                    'priority' => 3,
                    'fields' => array(
                        'ia-message-content' => array(
                            'id' => 'ia-message-content',
                            'name' => 'ia-message-content',
                            'label' => __( 'Content', 'betterdocs-pro' ),
                            'classes' => 'tab-nested-layout',
                            'type' => "tab",
                            'active' => "ia-message-tab-content",
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'ia-message-tab-content',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 1,
                            'fields' => array(
                                'ia-message-tab-content' => array(
                                    'id' => 'ia-message-tab-content',
                                    'name' => 'ia-message-tab-content',
                                    'type' => "section",
                                    'label' => __( 'Content', 'betterdocs-pro' ),
                                    'priority' => 1,
                                    'fields' => array(
                                        'chat_tab_visibility_switch' => array(
                                            'name' => 'chat_tab_visibility_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'Show Ask Tab', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 1
                                        ),
                                        'chat_tab_file_upload_switch' => array(
                                            'name' => 'chat_tab_file_upload_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'File Upload', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 2,
                                            'rules' => Rules::is( 'chat_tab_visibility_switch', true )
                                        ),
                                        'chat_tab_title' => array(
                                            'name' => 'chat_tab_title',
                                            'type' => 'text',
                                            'label' => __( 'Instant Ask Tab Title', 'betterdocs-pro' ),
                                            'priority' => 3,
                                            'default' => __( 'Ask', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'chat_tab_visibility_switch', true )
                                        ),
                                        'chat_subtitle_one' => array(
                                            'name' => 'chat_subtitle_one',
                                            'type' => 'text',
                                            'label' => __( 'Ask Tab Subtitle One', 'betterdocs-pro' ),
                                            'priority' => 4,
                                            'default' => __( 'Need a hand? Shoot us a message.', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'chat_tab_visibility_switch', true )
                                        ),
                                        'chat_subtitle_two' => array(
                                            'name' => 'chat_subtitle_two',
                                            'type' => 'text',
                                            'label' => __( 'Ask Tab Subtitle Two', 'betterdocs-pro' ),
                                            'priority' => 5,
                                            'default' => __( 'We typically respond within 24-48 hours. Your solution is just a message away.', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'chat_tab_visibility_switch', true )
                                        ),
                                        'chat_tab_gdpr_switch' => array(
                                            'name' => 'chat_tab_gdpr_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'GDPR Consent', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => false,
                                            'priority' => 6,
                                            'rules' => Rules::is( 'chat_tab_visibility_switch', true )
                                        ),
                                        'chat_tab_gdpr_text' => array(
                                            'name' => 'chat_tab_gdpr_text',
                                            'type' => 'text',
                                            'label' => __( 'GDPR Consent Text', 'betterdocs-pro' ),
                                            'priority' => 7,
                                            'default' => __( 'I consent to my data being used to respond to my inquiry.', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'chat_tab_gdpr_switch', true )
                                        )
                                    )
                                ),
                                'ia-message-tab-style' => array(
                                    'id' => 'ia-message-tab-style',
                                    'name' => 'ia-message-tab-style',
                                    'type' => 'section',
                                    'label' => __( 'Style', "betterdocs-pro" ),
                                    'priotity' => 2,
                                    'fields' => array(
                                        'ia_message_tab_title_font_color' => array(
                                            'name' => 'ia_message_tab_title_font_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Title Font Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#fff',
                                            'priority' => 1
                                        ),
                                        'ia_message_tab_subtitle_font_color' => array(
                                            'name' => 'ia_message_tab_subtitle_font_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Subtitle Font Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#fff',
                                            'priority' => 2
                                        ),
                                        'ia_message_button_text_color' => array(
                                            'name' => 'ia_message_button_text_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Button Text Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#fff',
                                            'priority' => 3
                                        ),
                                        'ia_ask_bg_color' => array(
                                            'name' => 'ia_ask_bg_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Input Background Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#fff',
                                            'priority' => 4
                                        ),
                                        'ia_message_input_label_text_color' => array(
                                            'name' => 'ia_message_input_label_text_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Input Label Text Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#202223',
                                            'priority' => 5
                                        ),
                                        'ia_message_upload_button_background_color' => array(
                                            'name' => 'ia_message_upload_button_background_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Upload Button Background Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#fff',
                                            'priority' => 6
                                        ),
                                        'ia_message_upload_text_color' => array(
                                            'name' => 'ia_message_upload_text_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Upload Text Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#6d7175',
                                            'priority' => 7
                                        ),
                                        'ia_message_tab_gdpr_text_color' => array(
                                            'name' => 'ia_message_tab_gdpr_text_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'GDPR Text Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#6d7175',
                                            'priority' => 8
                                        )
                                    )
                                ),
                                'ia-message-mail-settings' => array(
                                    'id' => 'ia-message-mail-settings',
                                    'name' => 'ia-message-mail-settings',
                                    'type' => 'section',
                                    'label' => __( 'Mail Settings', "betterdocs-pro" ),
                                    'priority' => 3,
                                    'fields' => array(
                                        'ask_subject' => array(
                                            'name' => 'ask_subject',
                                            'type' => 'text',
                                            'label' => __( 'Subject', 'betterdocs-pro' ),
                                            'priority' => 1,
                                            'default' => __( '[ia_subject]', 'betterdocs-pro' ),
                                            'label_subtitle' => __( 'You can use [ia_subject], [ia_email], [ia_name] as placeholder. i.e: An enquiry is placed By [ia_name] for [ia_subject].', 'betterdocs-pro' )
                                        ),
                                        'ask_email' => array(
                                            'name' => 'ask_email',
                                            'type' => 'text',
                                            'label' => __( 'Email Address', 'betterdocs-pro' ),
                                            'priority' => 2,
                                            'default' => get_bloginfo( 'admin_email' )
                                        )
                                    )
                                ),
                                'ia-message-recaptcha-settings' => array(
                                    'id' => 'ia-message-recaptcha-settings',
                                    'name' => 'ia-message-recaptcha-settings',
                                    'type' => 'section',
                                    'label' => __( 'Recaptcha', "betterdocs-pro" ),
                                    'priority' => 4,
                                    'fields' => array(
                                        'enable_recaptcha' => array(
                                            'name' => 'enable_recaptcha',
                                            'type' => 'toggle',
                                            'label' => __( 'Enable reCAPTCHA v3', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => false,
                                            'priority' => 1,
                                            'label_subtitle' => __( 'Protect the Ask form from spam using Google reCAPTCHA v3 (invisible, score-based).', 'betterdocs-pro' )
                                        ),
                                        'recaptcha_site_key' => array(
                                            'name' => 'recaptcha_site_key',
                                            'type' => 'text',
                                            'label' => __( 'Site Key', 'betterdocs-pro' ),
                                            'priority' => 2,
                                            'default' => '',
                                            'label_subtitle' => __( 'Public key from Google reCAPTCHA admin console (v3).', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'enable_recaptcha', true )
                                        ),
                                        'recaptcha_secret_key' => array(
                                            'name' => 'recaptcha_secret_key',
                                            'type' => 'text',
                                            'label' => __( 'Secret Key', 'betterdocs-pro' ),
                                            'priority' => 3,
                                            'default' => '',
                                            'label_subtitle' => __( 'Server-side secret key — used only on the server, never exposed to the browser.', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'enable_recaptcha', true )
                                        ),
                                        'recaptcha_score_threshold' => array(
                                            'name' => 'recaptcha_score_threshold',
                                            'type' => 'number',
                                            'label' => __( 'Score Threshold', 'betterdocs-pro' ),
                                            'priority' => 4,
                                            'default' => 0.5,
                                            'min' => 0,
                                            'max' => 1,
                                            'step' => 0.1,
                                            'label_subtitle' => __( 'Reject submissions with a reCAPTCHA score below this value (0.0 = bot, 1.0 = human). Default 0.5.', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'enable_recaptcha', true )
                                        )
                                    )
                                ),
                                'ia-message-success-settings' => array(
                                    'id' => 'ia-message-success-settings',
                                    'name' => 'ia-message-success-settings',
                                    'type' => 'section',
                                    'label' => __( 'Success Screen', "betterdocs-pro" ),
                                    'priority' => 5,
                                    'fields' => array(
                                        'ask_thanks_title' => array(
                                            'name' => 'ask_thanks_title',
                                            'type' => 'text',
                                            'label' => __( 'Success Message Title', 'betterdocs-pro' ),
                                            'priority' => 1,
                                            'default' => __( 'Thanks', 'betterdocs-pro' )
                                        ),
                                        'ask_thanks_text' => array(
                                            'name' => 'ask_thanks_text',
                                            'type' => 'textarea',
                                            'label' => __( 'Success Message Text', 'betterdocs-pro' ),
                                            'priority' => 2,
                                            'default' => __( 'Your Message Has Been Sent Successfully', 'betterdocs-pro' )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'ia_resources_settings' => array(
                    'id' => 'ia_resources_settings',
                    'name' => 'ia_resources_settings',
                    'type' => 'section',
                    'priority' => 4,
                    'label' => __( 'Resources', "betterdocs-pro" ),
                    'fields' => array(
                        'ia-resources-general' => array(
                            'id' => 'ia-resources-general',
                            'name' => 'ia-resources-general',
                            'label' => __( 'General', 'betterdocs-pro' ),
                            'classes' => 'tab-nested-layout',
                            'type' => "tab",
                            'active' => "ia-resources-general-tab",
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'ia-resources-general-tab',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 1,
                            'fields' => array(
                                'ia-resources-general-tab' => array(
                                    'id' => 'ia-resources-general-tab',
                                    'name' => 'ia-resources-general-tab',
                                    'type' => 'section',
                                    'priority' => 1,
                                    'label' => __( 'General', "betterdocs-pro" ),
                                    'fields' => array(
                                        'ia_resources_general_content_title' => array(
                                            'name' => 'ia_resources_general_content_title',
                                            'type' => 'text',
                                            'label' => __( 'Header Title', 'betterdocs-pro' ),
                                            'priority' => 2,
                                            'default' => __( 'Resources', 'betterdocs-pro' )
                                        ),
                                        'ia_resource_general_tab_title' => array(
                                            'name' => 'ia_resource_general_tab_title',
                                            'type' => 'text',
                                            'label' => __( 'Tab Title', 'betterdocs-pro' ),
                                            'priority' => 3,
                                            'default' => __( 'Resources', 'betterdocs-pro' )
                                        )
                                    )
                                ),
                                'ia-resources-doc-category-tab' => array(
                                    'id' => 'ia-resources-doc-category-tab',
                                    'name' => 'ia-resources-doc-category-tab',
                                    'priority' => 2,
                                    'type' => 'section',
                                    'label' => __( "Docs Category", "betterdocs-pro" ),
                                    'fields' => array(
                                        'ia_resources_doc_categories_switch' => array(
                                            'name' => 'ia_resources_doc_categories_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'Show Doc Category Section', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'priority' => 1,
                                            'default' => true
                                        ),
                                        'ia_resources_doc_subcategories_switch' => array(
                                            'name' => 'ia_resources_doc_subcategories_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'Enable Subcategories', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'priority' => 2,
                                            'default' => true
                                        ),
                                        'ia_resources_doc_categories' => array(
                                            'name' => 'ia_resources_doc_categories',
                                            'label' => __( 'Select Categories', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 3,
                                            'default' => array(),
                                            'multiple' => true,
                                            'options' => array_merge( array(
                                                array(
                                                    'value' => 'all',
                                                    'label' => 'All'
                                                )
                                            ), $this->docs_categories() ),
                                            'rules' => Rules::is( 'ia_resources_doc_categories_switch', true )
                                        ),
                                        'ia_resources_doc_category_title_text' => array(
                                            'name' => 'ia_resources_doc_category_title_text',
                                            'type' => 'text',
                                            'label' => __( 'Category Title Text', 'betterdocs-pro' ),
                                            'priority' => 4,
                                            'default' => __( 'Doc Categories', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'ia_resources_doc_categories_switch', true )
                                        ),
                                        'ia_terms_orderby' => array(
                                            'name' => 'ia_terms_orderby',
                                            'type' => 'select',
                                            'label' => __( 'Terms Order By', 'betterdocs-pro' ),
                                            'default' => 'name',
                                            'options' => $this->normalize_options( array(
                                                'none' => __( 'No order', 'betterdocs-pro' ),
                                                'name' => __( 'Name', 'betterdocs-pro' ),
                                                'slug' => __( 'Slug', 'betterdocs-pro' ),
                                                'term_group' => __( 'Term Group', 'betterdocs-pro' ),
                                                'term_id' => __( 'Term ID', 'betterdocs-pro' ),
                                                'id' => __( 'ID', 'betterdocs-pro' ),
                                                'description' => __( 'Description', 'betterdocs-pro' ),
                                                'parent' => __( 'Parent', 'betterdocs-pro' ),
                                                'doc_category_order' => __( 'BetterDocs Order', 'betterdocs-pro' )
                                            ) ),
                                            'priority' => 5,
                                            'rules' => Rules::is( 'ia_resources_doc_categories_switch', true )
                                        ),
                                        'ia_terms_order' => array(
                                            'name' => 'ia_terms_order',
                                            'type' => 'select',
                                            'label' => __( 'Terms Order', 'betterdocs-pro' ),
                                            'default' => 'asc',
                                            'options' => $this->normalize_options( array(
                                                'asc' => 'Ascending',
                                                'desc' => 'Descending'
                                            ) ),
                                            'priority' => 6,
                                            'rules' => Rules::is( 'ia_resources_doc_categories_switch', true )
                                        )
                                    )
                                ),
                                'ia-resources-faq-tab' => array(
                                    'id' => 'ia-resources-faq-tab',
                                    'name' => 'ia-resources-faq-tab',
                                    'priority' => 3,
                                    'type' => 'section',
                                    'label' => __( "FAQ", "betterdocs-pro" ),
                                    'fields' => array(
                                        'ia_resources_faq_switch' => array(
                                            'name' => 'ia_resources_faq_switch',
                                            'type' => 'toggle',
                                            'label' => __( 'Show FAQ Section', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'priority' => 1,
                                            'default' => true
                                        ),
                                        'ia_resources_faq_content_type' => array(
                                            'name' => 'ia_resources_faq_content_type',
                                            'label' => __( 'Content Type', 'betterdocs-pro' ),
                                            'type' => 'select',
                                            'priority' => 2,
                                            'default' => 'faq-list',
                                            'options' => GlobalFields::normalize_fields( array(
                                                'faq-list' => __( 'FAQ List', 'betterdocs-pro' ),
                                                'faq-group' => __( 'FAQ Group', 'betterdocs-pro' )
                                            ) ),
                                            'rules' => Rules::is( 'ia_resources_faq_switch', true )
                                        ),
                                        'ia_resources_faq_list' => array(
                                            'name' => 'ia_resources_faq_list',
                                            'label' => __( 'Select FAQ List', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 3,
                                            'multiple' => true,
                                            'search' => true,
                                            'default' => array(),
                                            'options' => $this->faqs(),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-list' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        'ia_resources_faq_group' => array(
                                            'name' => 'ia_resources_faq_group',
                                            'label' => __( 'Select FAQ Group', 'betterdocs-pro' ),
                                            'type' => 'checkbox-select',
                                            'priority' => 4,
                                            'multiple' => true,
                                            'search' => true,
                                            'default' => array(),
                                            'options' => $this->faqs_categories(),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-group' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        'faq_terms_orderby' => array(
                                            'name' => 'faq_terms_orderby',
                                            'type' => 'select',
                                            'label' => __( 'FAQ Group Order By', 'betterdocs-pro' ),
                                            'default' => 'name',
                                            'options' => $this->normalize_options( array(
                                                'none' => __( 'No order', 'betterdocs-pro' ),
                                                'name' => __( 'Name', 'betterdocs-pro' ),
                                                'slug' => __( 'Slug', 'betterdocs-pro' ),
                                                'term_group' => __( 'Term Group', 'betterdocs-pro' ),
                                                'id' => __( 'ID', 'betterdocs-pro' ),
                                                'description' => __( 'Description', 'betterdocs-pro' ),
                                                'parent' => __( 'Parent', 'betterdocs-pro' )
                                            ) ),
                                            'priority' => 6,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-group' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        'faq_terms_order' => array(
                                            'name' => 'faq_terms_order',
                                            'type' => 'select',
                                            'label' => __( 'FAQ Group Order', 'betterdocs-pro' ),
                                            'default' => 'asc',
                                            'options' => $this->normalize_options( array(
                                                'asc' => 'Ascending',
                                                'desc' => 'Descending'
                                            ) ),
                                            'priority' => 7,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-group' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        'ia_faq_list_order_by' => array(
                                            'name' => 'ia_faq_list_order_by',
                                            'type' => 'select',
                                            'label' => __( 'FAQ List Order By', 'betterdocs-pro' ),
                                            'default' => 'id',
                                            'options' => $this->normalize_options( array(
                                                'none' => __( 'No order', 'betterdocs-pro' ),
                                                'id' => __( 'ID', 'betterdocs-pro' ),
                                                'author' => __( 'Author', 'betterdocs-pro' ),
                                                'title' => __( 'Title', 'betterdocs-pro' ),
                                                'date' => __( 'Date', 'betterdocs-pro' ),
                                                'modified' => __( 'Last Modified Date', 'betterdocs-pro' ),
                                                'parent' => __( 'Parent Id', 'betterdocs-pro' )
                                            ) ),
                                            'priority' => 8,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-list' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        'ia_faq_list_order' => array(
                                            'name' => 'ia_faq_list_order',
                                            'type' => 'select',
                                            'label' => __( 'FAQ List Order', 'betterdocs-pro' ),
                                            'default' => 'asc',
                                            'options' => $this->normalize_options( array(
                                                'asc' => 'Ascending',
                                                'desc' => 'Descending'
                                            ) ),
                                            'priority' => 9,
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'ia_resources_faq_content_type', 'faq-list' ),
                                                Rules::is( 'ia_resources_faq_switch', true )
                                            ) )
                                        ),
                                        // 'ia_resources_faq_list_number'  => [
                                        //     'name'     => 'ia_resources_faq_list_number',
                                        //     'type'     => 'number',
                                        //     'label'    => __( 'Number Of FAQ List', 'betterdocs-pro' ),
                                        //     'default'  => 5,
                                        //     'priority' => 5,
                                        //     'rules'    => Rules::logicalRule( [
                                        //         Rules::is( 'ia_resources_faq_content_type', 'faq-list' ),
                                        //         Rules::is( 'ia_resources_faq_switch', true )
                                        //     ] )
                                        // ],
                                        // 'ia_resources_faq_group_number' => [
                                        //     'name'     => 'ia_resources_faq_group_number',
                                        //     'type'     => 'number',
                                        //     'label'    => __( 'Number Of FAQ Group', 'betterdocs-pro' ),
                                        //     'default'  => 5,
                                        //     'priority' => 6,
                                        //     'rules'    => Rules::logicalRule( [
                                        //         Rules::is( 'ia_resources_faq_content_type', 'faq-group' ),
                                        //         Rules::is( 'ia_resources_faq_switch', true )
                                        //     ] )
                                        // ],
                                        'ia_resources_faq_title' => array(
                                            'name' => 'ia_resources_faq_title',
                                            'type' => 'text',
                                            'label' => __( 'FAQ Title Text', 'betterdocs-pro' ),
                                            'priority' => 5,
                                            'default' => __( 'FAQ', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'ia_resources_faq_switch', true )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'ia_single_doc' => array(
                    'id' => 'ia_single_doc',
                    'name' => 'ia_single_doc',
                    'type' => 'section',
                    'priority' => 5,
                    'label' => __( "Single Doc", "betterdocs-pro" ),
                    'fields' => array(
                        'ia_single_doc' => array(
                            'id' => 'ia_single_doc',
                            'name' => 'ia_single_doc',
                            'label' => __( 'Single Doc', 'betterdocs-pro' ),
                            'classes' => 'tab-nested-layout',
                            'type' => "tab",
                            'active' => "ia-single-doc-content",
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'ia-single-doc-content',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 1,
                            'fields' => array(
                                'ia-single-doc-content' => array(
                                    'id' => 'ia-single-doc-content',
                                    'name' => 'ia-single-dia_brandingoc-content',
                                    'type' => 'section',
                                    'priority' => 1,
                                    'label' => __( 'Content', "betterdocs-pro" ),
                                    'fields' => array(
                                        'ia_branding' => array(
                                            'name' => 'ia_branding',
                                            'type' => 'toggle',
                                            'label' => __( 'Branding', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 1
                                        ),
                                        'ia_reaction' => array(
                                            'name' => 'ia_reaction',
                                            'type' => 'toggle',
                                            'label' => __( 'Reaction', 'betterdocs-pro' ),
                                            'enable_disable_text_active' => true,
                                            'default' => true,
                                            'priority' => 2
                                        ),
                                        'reaction_title' => array(
                                            'name' => 'reaction_title',
                                            'type' => 'text',
                                            'label' => __( 'Reaction Text', 'betterdocs-pro' ),
                                            'priority' => 5,
                                            'default' => __( 'How did you feel?', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'ia_reaction', true )
                                        ),
                                        'response_title' => array(
                                            'name' => 'response_title',
                                            'type' => 'text',
                                            'label' => __( 'Response Text', 'betterdocs-pro' ),
                                            'priority' => 6,
                                            'default' => __( 'Thanks for the feedback', 'betterdocs-pro' ),
                                            'rules' => Rules::is( 'ia_reaction', true )
                                        )
                                    )
                                ),
                                'ia-single-doc-style' => array(
                                    'id' => 'ia-single-doc-style',
                                    'name' => 'ia-single-doc-style',
                                    'priority' => 2,
                                    'type' => 'section',
                                    'label' => __( "Style", "betterdocs-pro" ),
                                    'fields' => array(
                                        'ia_single_doc_title_header_bg_color' => array(
                                            'name' => 'ia_single_doc_title_header_bg_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Header Background Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#F6F6F7',
                                            'priority' => 1
                                        ),
                                        'ia_single_doc_title_font_color' => array(
                                            'name' => 'ia_single_doc_title_font_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Title Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#111213',
                                            'priority' => 2
                                        ),
                                        'ia_single_title_header_font_color' => array(
                                            'name' => 'ia_single_title_header_font_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'On Scroll Title Color', 'betterdocs-pro' ),
                                            'reset_text' => __( 'Reset', 'betterdocs-pro' ),
                                            'default' => '#111213',
                                            'priority' => 3
                                        ),
                                        'ia_single_icons_bg_color' => array(
                                            'name' => 'ia_single_icons_bg_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Icons Background Color', 'betterdocs-pro' ),
                                            'default' => '#f6f6f7',
                                            'priority' => 4
                                        ),
                                        'ia_reaction_primary_color' => array(
                                            'name' => 'ia_reaction_primary_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Reactions Primary Color', 'betterdocs-pro' ),
                                            'default' => '#00A375',
                                            'priority' => 5
                                        ),
                                        'ia_reaction_secondary_color' => array(
                                            'name' => 'ia_reaction_secondary_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Reactions Secondary Color', 'betterdocs-pro' ),
                                            'default' => '#ffffff',
                                            'priority' => 6
                                        ),
                                        'ia_reaction_title_color' => array(
                                            'name' => 'ia_reaction_title_color',
                                            'type' => 'colorpicker',
                                            'label' => __( 'Reaction Title Color', 'betterdocs-pro' ),
                                            'default' => '#FAFAFA',
                                            'priority' => 7
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'betterdocs_cross_domain_settings' => array(
                    'id' => 'betterdocs_cross_domain_settings',
                    'name' => 'betterdocs_cross_domain_settings',
                    'type' => 'section',
                    'label' => __( 'Cross Domain', 'betterdocs-pro' ),
                    'priority' => 6,
                    'fields' => $this->get_code_snippet_settings()
                )

            )
        );

        return $args;
    }

    /**
     * Get code snippet settings for cross domain
     *
     * @return array
     */
    public function get_code_snippet_settings() {
        if ( $this->is_multilingual_active() ) {
            return array(
                'ia_cross_domain_code' => array(
                    'name' => 'ia_cross_domain_code',
                    'type' => 'cross_domain_code',
                    'label' => __( 'Cross Domain Code', 'betterdocs-pro' ),
                    'priority' => 2,
                    'base_code' => InstantAnswer::snippet_base(),
                    'language_options' => $this->get_cross_domain_language_options( true ),
                    'betterdocs_config' => InstantAnswer::get_instance( betterdocs()->settings )->localize_settings(),
                    'chatbot_config' => InstantAnswer::get_instance( betterdocs()->settings )->localize_chatbot(),
                    'is_multilingual_active' => $this->is_multilingual_active()
                )
            );
        }
        return array( 'ia_cd_title_content' => array(
            'name' => 'ia_cd_title_content',
            'type' => 'codeviewer',
            'readOnly' => true,
            'copyOnClick' => true,
            'code' => InstantAnswer::snippet(),
            'label' => __( 'Code snippet', 'betterdocs-pro' ),
            'priority' => 1
        ) );
    }

    public function _args( $args ) {
        $args[ 'is_license_active' ] = get_option( BETTERDOCS_PRO_SL_DB_PREFIX . '_license_status', '' ) === 'valid';

        /**
         * License Tab
         */
        $args[ 'tabs' ][ 'tab-license' ] = apply_filters( 'betterdocs_settings_tab_license', array(
            'id' => 'tab-license',
            'label' => __( 'License', 'betterdocs-pro' ),
            'priority' => 90,
            'fields' => array(
                'title-design' => array(
                    'name' => 'title-design-tab',
                    'type' => 'section',
                    'label' => __( 'License', 'betterdocs-pro' ),
                    'priority' => 30,
                    'fields' => apply_filters( 'betterdocs_license_fields', array(
                        'betterdocs_licnese' => array(
                            'name' => 'betterdocs_licnese',
                            'type' => 'action',
                            'action' => 'betterdocs_settings_licnese',
                            'label' => __( 'License', 'betterdocs-pro' ),
                            'logourl' => BETTERDOCS_ABSURL . 'assets/static/admin/images/betterdocs-icon.svg'
                        )
                    ) )

                )
            )
        ) );

        $args[ 'submit' ][ 'rules' ] = Rules::logicalRule( array(
            Rules::is( 'config.active', 'tab-design', true ),
            Rules::is( 'config.active', 'tab-shortcodes', true ),
            Rules::is( 'config.active', 'tab-import-export', true ),
            Rules::is( 'config.active', 'tab-ai-chatbot', true ),
            Rules::is( 'config.active', 'tab-migration', true ),
            Rules::is( 'config.active', 'tab-license', true )
        ), 'and' );

        return $args;
    }

    /**
     * Get all docs
     */
    public function docs() {
        $docs = $this->database->get_cache( 'betterdocs::instant_answer::all_docs' );

        if ( $docs ) {
            return $docs;
        }

        $docs = array();

        $_docs = get_posts( array(
            'post_type' => 'docs',
            'numberposts' => -1,
            'posts_per_page' => -1
        ) );

        if ( ! empty( $_docs ) ) {
            foreach ( $_docs as $doc ) {
                $docs[ $doc->ID ] = betterdocs()->template_helper->kses( $doc->post_title );
            }
            $docs = GlobalFields::normalize_fields( $docs );
            $this->database->set_cache( 'betterdocs::instant_answer::all_docs', $docs );
        }

        return $docs;
    }

    /**
     * Get All FAQ
     */
    public function faqs() {
        $faqs = $this->database->get_cache( 'betterdocs::instant_answer::all_faq' );

        if ( $faqs ) {
            return $faqs;
        }

        $faqs = array(
            'all' => array(
                'value' => 'all',
                'label' => __( "All", "betterdocs-pro" )
            )
        );

        $_faqs = get_posts( array(
            'post_type' => 'betterdocs_faq',
            'numberposts' => -1,
            'posts_per_page' => -1
        ) );

        if ( ! empty( $_faqs ) ) {
            foreach ( $_faqs as $faq ) {
                $faqs[ $faq->ID ] = betterdocs()->template_helper->kses( $faq->post_title );
            }
            $faqs = GlobalFields::normalize_fields( $faqs );
            $this->database->set_cache( 'betterdocs::instant_answer::all_faq', $faqs );
        }

        return $faqs;
    }

    /**
     * Get All Categories for Docs Type
     * @return array
     */
    public function docs_categories() {
        $docs_terms = array();
        $terms      = get_terms( array(
            'taxonomy' => 'doc_category'
        ) );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $docs_terms[ $term->term_id ] = $term->name;
            }
        }

        $docs_terms = GlobalFields::normalize_fields( $docs_terms );

        return $docs_terms;
    }

    /**
     * Get all FAQ Categories
     *
     * @return void
     */
    public function faqs_categories() {
        $faq_terms = array(
            'all' => array(
                'value' => 'all',
                'label' => __( "All", "betterdocs-pro" )
            )
        );

        $terms = get_terms( array(
            'taxonomy' => 'betterdocs_faq_category'
        ) );

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $faq_terms[ $term->term_id ] = $term->name;
            }
        }

        $faq_terms = GlobalFields::normalize_fields( $faq_terms );

        return $faq_terms;
    }

    /**
     * Get All Pages
     * @return array
     */
    public function get_pages_for_ia() {
        $pages = $this->database->get_cache( 'betterdocs::instant_answer::all_pages' );

        if ( $pages ) {
            return $pages;
        }

        $allpages  = get_pages( array( 'post_status' => 'publish' ) );
        $page_list = array();
        if ( $allpages ) {
            $page_list[ 'all' ] = 'All';
            foreach ( $allpages as $page ) {
                $page_list[ $page->ID ] = betterdocs()->template_helper->kses( $page->post_title );
            }

            $page_list = GlobalFields::normalize_fields( $page_list );
            if ( count( $page_list ) > 1 ) {
                $this->database->set_cache( 'betterdocs::instant_answer::all_pages', $page_list );
            }
        }

        return $page_list;
    }

    /**
     * Get All Post Type
     * @return array
     */
    public function get_all_post_types() {
        $types = $this->database->get_cache( 'betterdocs::instant_answer::types' );

        if ( $types ) {
            return $types;
        }

        $args = array(
            'public' => true,
            '_builtin' => false
        );
        $types      = array();
        $post_types = get_post_types( $args, 'objects' );
        if ( $post_types ) {
            $types[ 'post' ] = 'Post';
            foreach ( $post_types as $post_type ) {
                $types[ $post_type->name ] = $post_type->labels->name;
            }

            $types = GlobalFields::normalize_fields( $types );
            if ( count( $types ) > 2 ) {
                $this->database->set_cache( 'betterdocs::instant_answer::types', $types );
            }
        }

        return $types;
    }

    /**
     * Get All Registered Texonomy
     * @return array|void
     */
    public function get_all_registered_texonomy() {
        $taxonomies = $this->database->get_cache( 'betterdocs::instant_answer::taxonomies' );

        if ( $taxonomies ) {
            return $taxonomies;
        }

        $args = array(
            'public' => true,
            '_builtin' => false
        );
        $_taxonomies = get_taxonomies( $args, 'objects' );
        $taxonomies  = array();
        if ( $_taxonomies ) {
            $taxonomies[ 'all' ]      = 'All';
            $taxonomies[ 'category' ] = 'Post Categories';
            $taxonomies[ 'post_tag' ] = 'Post Tag';
            foreach ( $_taxonomies as $taxonomy ) {
                $taxonomies[ $taxonomy->name ] = $taxonomy->labels->name;
            }

            $taxonomies = GlobalFields::normalize_fields( $taxonomies );
            if ( count( $taxonomies ) > 3 ) {
                $this->database->set_cache( 'betterdocs::instant_answer::taxonomies', $taxonomies );
            }
        }

        return $taxonomies;
    }

    public function custom_get_pages() {
        // Retrieve existing pages
        $existing_pages = get_pages();
        $result         = array();

        // Iterate through existing pages
        foreach ( $existing_pages as $page ) {
            $page_id              = $page->ID;
            $page_title           = $page->post_title;
            $page_slug            = $page->post_name; // Use post_name for slug
            $result[ "$page_id" ] = ! empty( $page_title ) ? $page_title : '(no title)';
        }

        return $result;
    }

    public function encyclopedia_fields( $args ) {

        $args[ 'fields' ][ 'encyclopedia_page_title' ] = array(
            'name' => 'encyclopedia_page_title',
            'type' => 'text',
            'label' => __( 'Encyclopedia Page Title', 'betterdocs-pro' ),
            'default' => 'Encyclopedia',
            'priority' => 11,
            'rules' => Rules::is( 'enable_encyclopedia', true )
        );

        $args[ 'fields' ][ 'encyclopedia_root_slug' ] = array(
            'name' => 'encyclopedia_root_slug',
            'type' => 'text',
            'label' => __( 'Encyclopedia Root Slug', 'betterdocs-pro' ),
            'label_subtitle' => __( 'Modify this option to change the root slug for your Encyclopedia. For example, this will be the default URL: https://example.com/encyclopedia', 'betterdocs-pro' ),
            'default' => 'encyclopedia',
            'priority' => 12,
            'rules' => Rules::is( 'enable_encyclopedia', true )
        );
        $args[ 'fields' ][ 'encyclopedia_enable_non_latin' ] = array(
            'name' => 'encyclopedia_enable_non_latin',
            'type' => 'toggle',
            'label' => __( 'Enable Non-Latin Alphabetical Order', 'betterdocs-pro' ),
            'label_subtitle' => __( 'Allow alphabetical sorting of content using non-Latin character sets, ensuring accurate ordering for languages such as Chinese, Arabic, Cyrillic, and others.', 'betterdocs-pro' ),
            'enable_disable_text_active' => true,
            'default' => false,
            'priority' => 13,
            'rules' => Rules::is( 'enable_encyclopedia', true )
        );
        $args[ 'fields' ][ 'encyclopedia_non_latin_option' ] = array(
            'name' => 'encyclopedia_non_latin_option',
            'type' => 'select',
            'label' => __( 'Choose Script', 'betterdocs-pro' ),
            'default' => '',
            'rules' => Rules::logicalRule( array(
                Rules::is( 'enable_encyclopedia', true ),
                Rules::is( 'encyclopedia_enable_non_latin', true )
            ), 'and' ),
            'priority' => 14,
            'options' => $this->normalize_options( array(
                'arabic' => __( 'Arabic', 'betterdocs-pro' ),
                'cyrillic' => __( 'Cyrillic', 'betterdocs-pro' ),
                'greek' => __( 'Greek', 'betterdocs-pro' ),
                'hebrew' => __( 'Hebrew', 'betterdocs-pro' )
            ) )
        );

        $args[ 'fields' ][ 'encyclopedia_source' ] = array(
            'name' => 'encyclopedia_source',
            'type' => 'select',
            'label' => __( 'Encyclopedia Source', 'betterdocs-pro' ),
            'options' => $this->normalize_options( array(
                'docs' => __( 'Docs', 'betterdocs-pro' ),
                'glossaries' => __( 'Glossaries', 'betterdocs-pro' )
            ) ),
            'default' => 'glossaries',
            'priority' => 11,
            'rules' => Rules::logicalRule( array(
                Rules::is( 'enable_encyclopedia', true ),
                Rules::is( 'enable_glossaries', true )
            ) )
        );

        return $args;
    }

    public function glossary_fields( $args ) {

        $args[ 'fields' ][ 'show_glossary_suggestions' ] = array(
            'name' => 'show_glossary_suggestions',
            'type' => 'toggle',
            'is_pro' => false,
            'priority' => 10,
            'label' => __( 'Show Glossary Suggestions', 'betterdocs-pro' ),
            'label_subtitle' => __( 'Enable this option to show Glossary suggestions inside your Gutenberg Editor', 'betterdocs-pro' ),
            'enable_disable_text_active' => true,
            'default' => true,
            'rules' => Rules::is( 'enable_glossaries', true )
        );

        return $args;
    }

    /**
     * Add Real-time Related Docs tab to settings
     */
    public function add_realtime_related_docs_tab( $tabs ) {
        $tabs['realtime_related_docs'] = [
            'title' => __( 'Real-time Related Docs', 'betterdocs-pro' ),
            'priority' => 25,
            'button_text' => __( 'Save Settings', 'betterdocs-pro' ),
            'classes' => 'betterdocs-tab-realtime-related-docs'
        ];
        return $tabs;
    }

    /**
     * Real-time Related Docs settings fields
     */
    public function realtime_related_docs_fields( $fields ) {
        $fields['enable_realtime_related_docs'] = [
            'label' => __( 'Enable Real-time Related Docs', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => false,
            'priority' => 1,
            'help' => __( 'Enable AI-powered real-time related document recommendations based on user behavior and session context. AI semantic analysis runs automatically and requires an OpenAI API key (set under Settings → AI Content Suite).', 'betterdocs-pro' )
        ];

        $fields['realtime_related_docs_heading'] = [
            'label' => __( 'Tracking Settings', 'betterdocs-pro' ),
            'type' => 'title',
            'priority' => 10
        ];

        $fields['track_page_views'] = [
            'label' => __( 'Track Page Views', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => true,
            'priority' => 11,
            'help' => __( 'Track when users view documentation pages.', 'betterdocs-pro' )
        ];

        $fields['track_scroll_depth'] = [
            'label' => __( 'Track Scroll Depth', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => true,
            'priority' => 12,
            'help' => __( 'Track how far users scroll through articles to measure engagement.', 'betterdocs-pro' )
        ];

        $fields['track_internal_clicks'] = [
            'label' => __( 'Track Internal Link Clicks', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => true,
            'priority' => 13,
            'help' => __( 'Track clicks on internal documentation links to understand user navigation patterns.', 'betterdocs-pro' )
        ];

        $fields['track_search_queries'] = [
            'label' => __( 'Track Search Queries', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => true,
            'priority' => 14,
            'help' => __( 'Track search queries to improve recommendations based on user intent.', 'betterdocs-pro' )
        ];

        $fields['recommendation_limit'] = [
            'label' => __( 'Number of Recommendations', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 5,
            'priority' => 22,
            'min' => 1,
            'max' => 10,
            'help' => __( 'Maximum number of AI-generated recommendations to display.', 'betterdocs-pro' )
        ];

        $fields['journey_batch_size'] = [
            'label' => __( 'Tracking Batch Size', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 10,
            'priority' => 31,
            'min' => 5,
            'max' => 50,
            'help' => __( 'Number of interactions to batch before sending to server.', 'betterdocs-pro' )
        ];

        $fields['journey_batch_interval'] = [
            'label' => __( 'Batch Interval (seconds)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 15,
            'priority' => 32,
            'min' => 5,
            'max' => 60,
            'help' => __( 'How often to send batched tracking data to the server.', 'betterdocs-pro' )
        ];

        $fields['journey_session_timeout'] = [
            'label' => __( 'Session Timeout (minutes)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 30,
            'priority' => 33,
            'min' => 10,
            'max' => 120,
            'help' => __( 'How long to maintain user session for tracking purposes.', 'betterdocs-pro' )
        ];

        $fields['anonymize_ip_addresses'] = [
            'label' => __( 'Anonymize IP Addresses', 'betterdocs-pro' ),
            'type' => 'toggle',
            'default' => true,
            'priority' => 41,
            'help' => __( 'Hash IP addresses for privacy compliance (recommended).', 'betterdocs-pro' )
        ];

        $fields['data_retention_days'] = [
            'label' => __( 'Data Retention (days)', 'betterdocs-pro' ),
            'type' => 'number',
            'default' => 0,
            'priority' => 42,
            'min' => 0,
            'max' => 365,
            'help' => __( 'How long to keep tracking data before automatic cleanup. Use 0 to keep data forever.', 'betterdocs-pro' )
        ];

        return $fields;
    }

    /**
     * Get language options for cross domain settings
     *
     * @param bool $raw Whether to return raw options without normalization (for React component)
     * @return array
     */
    public function get_cross_domain_language_options( $raw = false ) {
        $options = array();

        // WPML Support
        if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
            global $sitepress;
            if ( $sitepress && method_exists( $sitepress, 'get_active_languages' ) ) {
                $active_languages = $sitepress->get_active_languages();
                if ( is_array( $active_languages ) ) {
                    foreach ( $active_languages as $code => $lang ) {
                        $options[] = array(
                            'value' => $code,
                            'label' => isset( $lang['native_name'] ) ? $lang['native_name'] : $code
                        );
                    }
                }
            }
        }
        // Polylang Support
        elseif ( function_exists( 'pll_languages_list' ) ) {
            $languages = pll_languages_list( array( 'fields' => array() ) );
            if ( is_array( $languages ) ) {
                foreach ( $languages as $lang ) {
                    $options[] = array(
                        'value' => $lang->slug,
                        'label' => $lang->name
                    );
                }
            }
        }
        // Loco Translate - gets locales from WordPress
        elseif ( class_exists( 'Loco_Locale' ) || is_plugin_active( 'loco-translate/loco.php' ) ) {
            $available_locales = get_available_languages();
            $options[] = array(
                'value' => 'en',
                'label' => __( 'English', 'betterdocs-pro' )
            );
            foreach ( $available_locales as $locale ) {
                $lang_code = substr( $locale, 0, 2 );
                require_once ABSPATH . 'wp-admin/includes/translation-install.php';
                $translations = wp_get_available_translations();
                $label        = isset( $translations[ $locale ]['native_name'] ) ? $translations[ $locale ]['native_name'] : $locale;
                $options[]    = array(
                    'value' => $lang_code,
                    'label' => $label
                );
            }
        }

        if ( $raw ) {
            return $options;
        }

        array_unshift( $options, array(
            'value' => '',
            'label' => __( 'Default (Site Language)', 'betterdocs-pro' )
        ) );

        return $this->normalize_options( $options );
    }

    /**
     * Check if any multilingual plugin is active
     *
     * @return bool
     */
    public function is_multilingual_active() {
        return is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ||
            function_exists( 'pll_current_language' ) ||
            ( class_exists( 'Loco_Locale' ) || is_plugin_active( 'loco-translate/loco.php' ) );
    }
}
