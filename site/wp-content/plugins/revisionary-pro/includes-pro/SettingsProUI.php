<?php
class RevisionaryProSettingsUI {
    function __construct() {
        add_action('revisionary_settings_ui', [$this, 'actSettingsUI']);
        add_action('revisionary_option_ui_pending_revisions', [$this, 'actPendingRevisionsUI']);
        add_action('revisionary_option_ui_revision_options', [$this, 'actRevisionOptionsUI']);
        add_filter('revisionary_option_captions', [$this, 'fltOptionCaptions']);
        add_filter('revisionary_option_sections', [$this, 'fltOptionSections']);
    }

    function actSettingsUI($ui) {
        $ui->option_captions['display_pp_branding'] = __('Display PublishPress Branding in Admin', 'revisionary');

        $ui->section_captions = ['license' => __('License Key', 'revisionary'), 'branding' => __('Branding', 'revisionary')] + $ui->section_captions;

        $ui->form_options['features']['branding'] = ['display_pp_branding'];
    }

    function fltOptionCaptions($captions) {
        $captions['pending_revision_unpublished'] = __('Revision Submission for Unpublished Posts', 'revisionary');

        if (class_exists('ACF')) {
            $captions['prevent_rest_revisions'] = __('Prevent Redundant Revisions', 'revisionary');
        }

        return $captions;
    }

    function fltOptionSections($sections) {
        $sections['features']['pending_revisions'][] = 'pending_revision_unpublished';

        if (class_exists('ACF')) {
            $sections['features']['revisions'][] = 'prevent_rest_revisions';
        }

        return $sections;
    }

    function actPendingRevisionsUI($settings_ui) {
        $hint = __( 'All users have pending revision checkbox available when editing an unpublished post.', 'revisionary' );
        $settings_ui->option_checkbox('pending_revision_unpublished', 'features', 'pending_revisions', $hint, '');
    }

    function actRevisionOptionsUI($settings_ui) {
        if (class_exists('ACF')) {
            $hint = __( 'Prevent REST requests from generating revisions (which may be stored without ACF fields)', 'revisionary' );
            $settings_ui->option_checkbox('prevent_rest_revisions', 'features', 'revisions', $hint, '');
        }
    }
}