<?php

class RevisionaryPro {
    function __construct() {
        add_filter('default_options_rvy', [$this, 'fltDefaultOptions']);
        add_filter('options_sitewide_rvy', [$this, 'fltDefaultOptionScope']);
        add_filter('wp_revisions_to_keep', [$this, 'fltMaybeSkipRevisionCreation'], 10, 2);

        add_filter('revisionary_main_post_statuses', [$this, 'fltMainPostStatuses'], 5, 2);
        add_filter('revisionary_implicit_edit_caps', [$this, 'fltImplicitEditCaps'], 10, 2);
        add_filter('rvy_replace_post_edit_caps', [$this, 'fltReplacePostEditCaps'], 10, 3);
        add_filter('user_has_cap', [$this, 'fltEnsureRevisionSubmitReadCap'], 200, 3 );
        add_filter('revisionary_preview_compare_view_caption', [$this, 'fltPreviewCompareViewCaption'], 10, 2);
        add_filter('revisionary_preview_view_caption', [$this, 'fltPreviewCompareViewCaption'], 10, 2);    
    }

    function fltDefaultOptions($options) {
        $options['pending_revision_unpublished'] = 0;
        $options['prevent_rest_revisions'] = 0;
        return $options;
    }

    function fltDefaultOptionScope($options) {
        $options['pending_revision_unpublished'] = true;
        $options['prevent_rest_revisions'] = true;
        return $options;
    }

    function fltMaybeSkipRevisionCreation($num, $post) {	
		if (class_exists('ACF') && rvy_get_option('prevent_rest_revisions')) {	
			$arr_url = parse_url(get_option('siteurl'));
			
			if ($arr_url && isset($arr_url['path'])) {
				if (0 === strpos($_SERVER['REQUEST_URI'], $arr_url['path'] . '/wp-json/wp/')) {
					$num = 0;
				}
			}
		}

		return $num;
	}

    function fltPreviewCompareViewCaption($caption, $revision) {
        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = __("%sCompare%s%sView&nbsp;Current&nbsp;Draft%s", 'revisionary');
        }

        return $caption;
    }

    function fltPreviewViewCaption($caption, $revision) {

        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = __("%sView&nbsp;Current&nbsp;Draft%s", 'revisionary');
        }

        return $caption;
    }

    function fltEnsureRevisionSubmitReadCap($wp_blogcaps, $reqd_caps, $args) {
        global $current_user;

        if (rvy_get_option('pending_revision_unpublished')) {
            // Work around edit_others capability getting stripped out upstream on REST permission checks
            if (!empty($args) && !empty($args[0]) && in_array($args[0], ['read_post', 'edit_post']) && !empty($args[2]) && defined('REST_REQUEST') && REST_REQUEST) {
                if ($type_obj = get_post_type_object(get_post_field('post_type', $args[2]))) {
                    if (!empty($type_obj->cap->edit_others_posts) && in_array($type_obj->cap->edit_others_posts, $reqd_caps)) {
                        // Note: we are only adding the edit_others cap if user's role already has it
                        if (!empty($current_user->allcaps[$type_obj->cap->edit_others_posts])) {
                            $wp_blogcaps[$type_obj->cap->edit_others_posts] = true;
                        }
                    }
                }
            }
        }

        return $wp_blogcaps;
    }

    function fltMainPostStatuses($statuses, $return = 'object') {
        if (rvy_get_option('pending_revision_unpublished')) {
            $statuses = get_post_stati( ['internal' => false], $return );
        }

        return $statuses;
    }

    function fltImplicitEditCaps($replace_caps, $type_obj) {
        global $current_user, $revisionary;
        
        if (rvy_get_option('pending_revision_unpublished') && empty($revisionary->skip_revision_allowance)) {
            $plural_name = (isset($type_obj->plural_name)) ? $type_obj->plural_name : $type_obj->name . 's';

            // Moderation (Workflow) statuses will only be defined if PublishPress Permissions Pro is active with the Status Control module.
            foreach (get_post_stati(['moderation' => true], 'names') as $workflow_status) {
                if ('future' != $workflow_status) {
                    // No need to determine which statuses have custom capabilities enabled for this post type. 
                    // This is just an array of cap names to subtract out of whatever requirements are imposed.
                    $replace_caps[]= str_replace('_posts', "_{$plural_name}", "edit_{$workflow_status}_posts");

                    if (isset($type_obj->cap->edit_others_posts) && !empty($current_user->allcaps[$type_obj->cap->edit_others_posts])) {
                        $replace_caps[]= str_replace('_posts', "_{$plural_name}", "edit_others_{$workflow_status}_posts");
                    }
                }
            }
        }

        return $replace_caps;
    }

    function fltReplacePostEditCaps($caps, $post_type, $post_id) {
        return $this->fltImplicitEditCaps($caps, get_post_type_object($post_type));
    }
}