<?php
class RevisionaryProAdmin {
    function __construct() {
        add_action('revisionary_load_options_ui', [$this, 'load_revisionary_pro_options_ui']);

        add_action('revisionary_refresh_updates', [$this, 'clear_edd_cache'], 2);

        add_filter('revisionary_published_post_caption', [$this, 'fltPublishedPostCaption']);

        add_filter('revisionary_queue_allow_post_types', [$this, 'fltQueueAllowPostTypes']);
        add_filter('revisionary_display_save_as_button', [$this, 'fltDisplaySaveAsButton'], 10, 3);
        add_filter('presspermit_display_save_as_button', [$this, 'fltDisplaySaveAsButton'], 10, 3);
    }

    function load_revisionary_pro_options_ui() {
        require_once(RVY_ABSPATH . '/includes-pro/SettingsProUI.php');
        $license_ui = new RevisionaryProSettingsUI();
    }

    function clear_edd_cache() {
        revisionary()->keyStatus(true);
        set_transient('revisionary-pro-refresh-update-info', true, 86400);

        delete_site_transient('update_plugins');
        delete_option('_site_transient_update_plugins');

        $opt_val = get_option('rvy_edd_key');
        if (is_array($opt_val) && !empty($opt_val['license_key'])) {
            $plugin_slug = basename(REVISIONARY_FILE, '.php'); // 'revisionary-pro';
            $plugin_relpath = basename(dirname(REVISIONARY_FILE)) . '/' . basename(REVISIONARY_FILE);  // $_REQUEST['plugin']
            $license_key = $opt_val['license_key'];
            $beta = false;

            delete_option(md5(serialize($plugin_slug . $license_key . $beta)));
            delete_option('edd_api_request_' . md5(serialize($plugin_slug . $license_key . $beta)));
            delete_option(md5('edd_plugin_' . sanitize_key($plugin_relpath) . '_' . $beta . '_version_info'));
        }

        wp_update_plugins();
        //wp_version_check(array(), true);

        if (current_user_can('update_plugins')) {
            $url = remove_query_arg('rvy_refresh_updates', esc_url($_SERVER['REQUEST_URI']));
            $url = add_query_arg('rvy_refresh_done', 1, $url);

            $https = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
            $url = esc_url($_SERVER['HTTP_HOST']) . $url;
            wp_redirect($url);
            exit;
        }
    }

    function fltPublishedPostCaption($caption) {
        if (rvy_get_option('pending_revision_unpublished')) {
            $caption = __('Current Post', 'revisionary');
        }

        return $caption;
    }

    function fltQueueAllowPostTypes($post_types) {
        global $revisionary, $current_user;

        if (rvy_get_option('pending_revision_unpublished')) {
            foreach(array_keys($revisionary->enabled_post_types) as $post_type) {
                if ($type_obj = get_post_type_object($post_type)) {
                    if (isset($type_obj->cap->edit_posts) && !empty($current_user->allcaps[$type_obj->cap->edit_posts])) {
                        $post_types []= $post_type;
                    }
                }
            }
        }

        return $post_types;
    }

    function fltDisplaySaveAsButton($display, $post, $args) {
        if (rvy_get_option('pending_revision_unpublished')) {
            if ($status_obj = get_post_status_object($post->post_status)) {
                if (!empty($status_obj->moderation)) {
                    if (!agp_user_can('edit_post', $post->ID, '', ['skip_revision_allowance' => true])) {
                        $display = false;
                    }
                }
            }
        }

        return $display;
    }
}
