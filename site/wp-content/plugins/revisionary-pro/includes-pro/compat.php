<?php

class RevisionaryCompat {
    private $saved_meta_keys = [];
    private $rest_buffer_controller = [];
    private $rest_method = '';
    private $rest_params = false;

    function __construct() {
        if (defined('FL_BUILDER_VERSION')) {
            add_action('rvy_init', function($revisionary) {
                require_once(dirname(__FILE__).'/compat/beaver-builder.php');
                new RevisionaryBeaverBuilder($revisionary);
            });
        }

        if (defined('ET_BUILDER_PLUGIN_VERSION') || (false !== stripos(get_template(), 'divi'))) {
            add_action('rvy_init', function($revisionary) {
                global $current_user;

                if (!empty($current_user->ID)) {
                    require_once(dirname(__FILE__).'/compat/divi.php');
                    new RevisionaryDivi($revisionary);
                }
            });
        }

        // WPML
        if ( defined('ICL_SITEPRESS_VERSION') ) {
            require_once(RVY_ABSPATH . '/includes-pro/compat/wpml.php');
        }

        // ACF
        add_filter('revisionary_copy_core_postmeta', [$this, 'flt_copy_core_postmeta'], 10, 4);

        // ACF: ensure custom fields are stored to pending revision, not published post
	    add_filter('acf/pre_update_metadata', [$this, 'fltACFpreUpdateMetaData'], 50, 5);

        // ACF: ensure custom fields are stored to archive after pending / scheduled revision publication
        add_action('revision_applied', [$this, 'actRevisionApplied'], 20, 2);

        add_action('revisionary_save_revision', [$this, 'act_save_revision']);
        add_action('revisionary_saved_revision', [$this, 'act_save_revision_followup'], 5);

		// todo: move to admin file
        add_filter('revisionary_diff_ui', [$this, 'flt_revision_diff_ui'], 10, 4);

        add_filter('revisionary_compare_meta_fields', [$this, 'flt_compare_meta_fields']);

        // ACF Extended: prevent published post author being forced as revision author
        add_action('revisionary_create_revision', [$this, 'act_create_revision']);

        // Pro
        if (class_exists('ACFE')) {
            add_action('wp_loaded', [$this, 'addACFEsupport']);
        }

        // WP Rest Cache plugin compat
        add_filter('register_post_type_args', [$this, 'archive_post_type_rest_controller'], 9, 2);
        add_filter('register_post_type_args', [$this, 'restore_post_type_rest_controller_args'], 11, 2);

        add_filter("update_post_metadata", [$this, 'fltUpdatePostMetadata'], 99, 5);
        add_filter("add_post_metadata", [$this, 'fltAddPostMetadata'], 99, 5);
        add_filter("get_post_metadata", [$this, 'fltGetPostMetadata'], 10, 4);

        add_action('init', [$this, 'podsFilters'], 50);
    }

    function archive_post_type_rest_controller($args, $post_type) {
        $this->rest_buffer_controller[$post_type] = isset( $args['rest_controller_class'] ) ? $args['rest_controller_class'] : null;
        return $args;
    }

    function restore_post_type_rest_controller_args($args, $post_type) {
        if (strpos($_SERVER['REQUEST_URI'], 'wp-json/wp/') && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
            if (!empty($args['rest_controller_class']) && isset($this->rest_buffer_controller[$post_type])) {
                if (false !== strpos($args['rest_controller_class'], 'WP_Rest_Cache_Plugin\Includes\Controller\Post_Controller')) {
                    $args['rest_controller_class'] = $this->rest_buffer_controller[$post_type];
                }
            }
        }

        return $args;
    }

    public function podsFilters() {
        if (class_exists('PodsMeta')) {
            if (!empty(PodsMeta::$instance)) {
                remove_filter('save_post', [PodsMeta::$instance, 'save_post'], 10, 3);
            }

            add_filter('save_post', [$this, 'podsSavePostWrapper'], 10, 3);
        }
    }

    function podsSavePostWrapper($object_id, $post, $update = null) {
        global $revisionary, $current_user;

        if (empty(PodsMeta::$instance) || !method_exists(PodsMeta::$instance, 'save_post') || !is_callable([PodsMeta::$instance, 'save_post'])) {
            return;
        }

        if (!empty($revisionary->last_revision[$object_id])) {
            $revision_id = $revisionary->last_revision[$object_id];
        } else {
            $revision_id = get_transient("_rvy_pending_revision_{$current_user->ID}_{$object_id}");
        }

        if ($revision_id) {
            $object_id = $revision_id;
        }

        PodsMeta::$instance->save_post($object_id, $post, $update);
    }

    /* --- Pro: support ACF Extended single_meta --- */
    function addACFEsupport() {
        // Pro: support ACF Extended single_meta
        if (function_exists('acf_get_setting') && acf_get_setting('acfe/modules/single_meta') && function_exists('acf_get_metadata')) {
            add_filter('revisionary_compare_meta_from', [$this, 'fltACFEcompareFrom'], 10, 2);
            add_filter('revisionary_compare_meta_to', [$this, 'fltACFEcompareTo'], 10, 2);
            add_filter('revisionary_compare_extra_fields', [$this, 'fltACFEadjustExtraFields'], 10, 2);
        }
    }

    function fltACFEcompareFrom($from_meta, $post_id) {
        $acf_from = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_from)) ? array_merge($from_meta, $acf_from) : $from_meta;
    }

    function fltACFEcompareTo($to_meta, $post_id) {
        $acf_to = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_to)) ? array_merge($to_meta, $acf_to) : $to_meta;
    }

    function fltACFEadjustExtraFields($extra_fields, $post_id) {
        unset($meta['acf']);
        unset($meta['_acf']);
        $acf_to = acf_get_metadata($post_id, 'acf');

        return array_merge($extra_fields, array_fill_keys(array_keys($acf_to), true));
    }
    /* ------------------------------------------- */

    function fltAddPostMetadata($interrupt, $object_id, $meta_key, $meta_value, $unique) {
        return $this->fltUpdatePostMetadata($interrupt, $object_id, $meta_key, $meta_value, $unique, true);
    }

    function fltUpdatePostMetadata($interrupt, $object_id, $meta_key, $meta_value, $prev_value, $add_meta = false) {
        global $current_user, $revisionary;
        static $busy;
        static $unfiltered_meta_keys;

        if (!isset($unfiltered_meta_keys)) {
            $unfiltered_meta_keys = array_keys(revisionary_unrevisioned_postmeta());
        }

        if (!empty($busy)) {
            return $interrupt;
        }

        $busy = true;

        if (!empty($revisionary->last_revision[$object_id])) {
            $revision_id = $revisionary->last_revision[$object_id];
        } else {
            $revision_id = get_transient("_rvy_pending_revision_{$current_user->ID}_{$object_id}");
        }

        if ($revision_id) {
            if (!in_array($meta_key, $unfiltered_meta_keys)) {
                if ($add_meta) {
                    add_metadata('post', $revision_id, $meta_key, $meta_value, $prev_value);
                } else {
                    update_metadata('post', $revision_id, $meta_key, $meta_value, $prev_value);
                }

                $this->saved_meta_keys[$object_id][$meta_key] = true;

                $interrupt = true;
            }
        }

        $busy = false;
        return $interrupt;
    }

    // For meta keys that have already been updated for revision, filter get_post_meta requests for published post to revision value.  
    // Needed for LearnDash and possibly other plugins that combine custom fields into a single meta array, otherwise updated portions of array are wiped back to published post value by subsequent third party update_meta wrapper
    function fltGetPostMetadata($interrupt, $object_id, $meta_key, $single) {
        global $current_user, $revisionary;
        static $busy;

        if (!empty($busy)) {
            return $interrupt;
        }

        $busy = true;
        
        if (!empty($this->saved_meta_keys[$object_id][$meta_key])) {
            if (!empty($revisionary->last_revision[$object_id])) {
                $revision_id = $revisionary->last_revision[$object_id];
            } else {
                $revision_id = get_transient("_rvy_pending_revision_{$current_user->ID}_{$object_id}");
            }

            if ($revision_id) {
                if ($check = get_metadata('post', $revision_id, $meta_key, false)) {
                    if ( $single && is_array( $check ) ) {
                        $revision_value = $check[0];
                    } else {
                        $revision_value = $check;
                    }
                }

                if (!empty($revision_value)) {
                    $interrupt = $check;
                }
            }
        }

        $busy = false;

        return $interrupt;
    }

    function act_create_revision($data) {
        global $current_user;

        if (isset($_POST['acf']) && isset($_POST['acf']['acfe_author'])) {
            $_POST['acf']['acfe_author'] = $current_user->ID;
        }
    }

    function flt_revision_diff_ui($return, $compare_from, $compare_to, $args) {
        if (!is_array($args)) {
            return $return;
        }
        
        $to_meta = (isset($args['to_meta'])) ? apply_filters('revisionary_compare_meta_to', $args['to_meta'], $compare_to->ID) : [];
        $meta_fields = (isset($args['meta_fields'])) ? $args['meta_fields'] : [];
        $native_fields = (isset($args['native_fields'])) ? $args['native_fields'] : [];
        $strip_tags = (isset($args['strip_tags'])) ? $args['strip_tags'] : [];

        // Display other scalar meta fields
        $from_meta = ($compare_from) ? apply_filters('revisionary_compare_meta_from', get_post_meta($compare_from->ID), $compare_from->ID) : [];

        $extra_fields = $to_meta; //($compare_from) ? array_merge($from_meta, $to_meta) : $to_meta;

        $extra_fields = array_diff_key($extra_fields, $native_fields, $meta_fields, revisionary_unrevisioned_postmeta());
        $extra_fields = apply_filters('revisionary_compare_extra_fields', array_fill_keys(array_keys($extra_fields), true), $compare_to->ID);

        $key_captions = apply_filters('revisionary_meta_key_captions', ['_yoast_wpseo_' => 'Yoast SEO ', '_thumbnail_id' => __('Featured Image', 'revisionary'), ''], $compare_to);
        $caption_keys = array_keys($key_captions);
        $caption_values = array_values($key_captions);

        ksort($extra_fields);

        foreach($extra_fields as $field => $name) {
            if ($skip_meta_prefixes = apply_filters('revisionary_unrevisioned_prefixes', ['_save_as_revision'], $compare_to)) {
                foreach($skip_meta_prefixes as $prefix) {
                    if (0 === strpos($field, $prefix)) {
                        continue 2;
                    }
                }
            }

            $content_to = (isset($to_meta[$field])) ? $to_meta[$field] : '';
		    $content_to = maybe_unserialize($content_to);

		    // ===== TO META =====
            if (is_array($content_to)) {
                $any_nonscalar = false;
                foreach($content_to as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_to) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_to[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_to = implode(', ', $content_to);
                }
            }

            if (!is_scalar($content_to)) {
                continue;
            }
		   // =======================

		   // ===== FROM META =====
            if ($compare_from) {
                $content_from = (isset($from_meta[$field])) ? $from_meta[$field] : '';
            } else {
                $content_from = '';
            }

            if (is_array($content_from)) {
                $any_nonscalar = false;
                foreach($content_from as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_from) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_from[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_from = implode(', ', $content_from);
                }
            }

            if (!is_scalar($content_from)) {
                continue;
            }
		   // =======================

            $args = array(
                'show_split_view' => true,
            );

            $args = apply_filters( 'revision_text_diff_options', $args, $field, $compare_from, $compare_to );

            if ($strip_tags) {
                $content_from = strip_tags($content_from);
                $content_to = strip_tags($content_to);
            }

            if ('_thumbnail_id' == $name) {
                $content_from = ($content_from) ? "$content_from (" . wp_get_attachment_image_url($content_from, 'full') . ')' : '';
                $content_to = ($content_to) ? "$content_to (" . wp_get_attachment_image_url($content_to, 'full') . ')' : '';
            }

            if ($name !== true) {
                // field label applied by filter
                $field_name = $name;
            } else {
            $field_name = str_replace($caption_keys, $caption_values, $field);

                if ($field_name == $field) {
                    $field_name = trim(ucwords(str_replace('_', ' ', $field)));
                }
            }

            if ($diff = wp_text_diff( $content_from, $content_to, $args )) {
                $return[] = array(
                    'id'   => $field,
                    'name' => $field_name,
                    'diff' => $diff,
                );
            }
        }

        return $return;
    }

    function act_save_revision_followup($revision) {
        // To ensure no postmeta is dropped from revision, copy any missing keys from published post
        revisionary_copy_postmeta(rvy_post_id($revision->ID), $revision->ID, false, true);
    }

    function flt_copy_core_postmeta($do_core_meta, $revision, $post, $apply_empty = false) {
        revisionary_copy_postmeta($revision->ID, $post->ID, $apply_empty);
        return false;
    }

    // When ACF tries to store a pending / scheduled revision's custom fields to published post, block that operation and update for revision instead
	function fltACFpreUpdateMetaData($interrupt, $post_id, $name, $value, $hidden) {
		global $current_user;
		static $busy;

		if (!empty($busy)) {
			return $interrupt;
		}

		$busy = true;

		if ($revision_id = get_transient("_rvy_pending_revision_{$current_user->ID}_{$post_id}")) {
			if ($revision_id != $post_id) {
				if (function_exists('acf_update_metadata')) {
					acf_update_metadata($revision_id, $name, $value, $hidden);
				}
			}

			$interrupt = true;
		}

		$busy = false;

		return $interrupt;
	}

    function actRevisionApplied($post_id, $revision) {
        if (!function_exists('acf_save_post_revision')) {
            return;
        }

        if ($_post = get_post($post_id)) {
            if (!rvy_is_revision_status($_post->post_status) && ('inherit' != $_post->post_status)) {
                acf_save_post_revision($post_id, $revision->ID);
            }
        }
    }

    function act_save_revision($revision) {
        if (!empty($_POST)) {
        	$_POST['ID'] = $revision->ID;
        }

        if (!empty($_REQUEST['post_ID']) && !defined('RVY_DISABLE_HTTP_REQUEST_SET')) {
            $_REQUEST['post_ID'] = $revision->ID;
        }

        if (!empty($_POST['post_ID']) && !defined('RVY_DISABLE_HTTP_POST_SET')) {
            // need this for ACF compat
            $_POST['post_ID'] = $revision->ID;
        }

        if (!empty($_GET['post_ID']) && !defined('RVY_DISABLE_HTTP_GET_SET')) {
            $_GET['post_ID'] = $revision->ID;
        }
    }

    function flt_compare_meta_fields($meta_fields) {
        $meta_fields['_requested_slug'] = __('Requested Slug', 'revisionary');
        
        if (defined('FL_BUILDER_VERSION') && defined('REVISIONARY_BEAVER_BUILDER_DIFF')) {
            $meta_fields['_fl_builder_data'] = __('Beaver Builder Data', 'revisionary');
            $meta_fields['_fl_builder_data_settings'] = __('Beaver Builder Settings', 'revisionary');
        }
    
        if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
            $meta_fields['ppma_authors_name'] = __('Author(s)', 'revisionary');
        }

        return $meta_fields;
    }
}

function revisionary_copy_postmeta( $from_post_id, $to_post_id, $apply_empty = false, $empty_target_only = false ) {
	global $post, $wpdb;

	if ( ! $to_post_id )
		return;
	
	//if (false===$skip_meta_keys) {
	//	$skip_meta_keys = array();
		
	//} elseif(!is_array($skip_meta_keys)) {
		$skip_meta_keys = (array) rvy_get_option( 'unrevisioned_postmeta' );
		$skip_meta_keys = array_filter( array_merge( $skip_meta_keys, revisionary_unrevisioned_postmeta() ) );
	//}

    if ($apply_empty || $empty_target_only) {
        $can_remove_empty_fields = apply_filters('revisionary_removable_meta_fields', [], $to_post_id);
	}

    if ( $_post = $wpdb->get_row( 
        $wpdb->prepare(
            "SELECT * FROM $wpdb->posts WHERE ID = %d",
            $from_post_id
        ) 
    ) ) {
		global $wpdb;
		
		$source_meta = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d GROUP BY meta_key",  
                $from_post_id
            ),
            ARRAY_A
        );
        
        $target_meta = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT meta_key, meta_value, meta_id FROM $wpdb->postmeta WHERE post_id = %d GROUP BY meta_key",
                $to_post_id
            ), 
            ARRAY_A 
        );

        $source_by_key = array();
        foreach($source_meta as $row) {
            $source_by_key[$row['meta_key']] = $row;
        }

        $target_by_key = array();
        foreach($target_meta as $row) {
            $target_by_key[$row['meta_key']] = $row;
        }

        foreach( $source_meta as $row ) {
            if (! empty($skip_meta_keys[$row['meta_key']])) {
                continue;
            }
            
            // Are we copying missing values only? Exclude fields which are removable from this bypass.
            if ($empty_target_only && isset($target_by_key[$row['meta_key']]) && !in_array($row['meta_key'], $can_remove_empty_fields)) {
                continue;
            }
            
            $row['post_id'] = $to_post_id;

            if ( isset( $target_by_key[ $row['meta_key'] ] ) ) {
                if ( $target_by_key[ $row['meta_key'] ]['meta_value'] === $row['meta_value'] ) {
                    continue;
                } else {
                    $wpdb->update( $wpdb->postmeta, $row, array( 'meta_id' => $target_by_key[ $row['meta_key'] ]['meta_id'] ) );
                }
            } else {
                $wpdb->insert( $wpdb->postmeta, $row );
            }
        }

        foreach( $target_meta as $row ) {
            if (! empty($skip_meta_keys[$row['meta_key']])) {
                continue;
            }

            // Are we copying missing values only? Exclude fields which are removable from this bypass.
            if ($empty_target_only && !in_array($row['meta_key'], $can_remove_empty_fields)) {
                continue;
            }

            if ( !isset($source_by_key[ $row['meta_key'] ]) && $apply_empty 
            && in_array($row['meta_key'], $can_remove_empty_fields)
            && !defined('REVISIONARY_PRESERVE_META') 
            && !defined('REVISIONARY_PRESERVE_' . strtoupper($_post->post_type) . '_META' ) 
            ) {
                $wpdb->delete( $wpdb->postmeta, array( 'meta_id' => $row['meta_id'] ) );
            }
        }
    }

    // Also copy Pods relationship fields
    if (defined('PODS_VERSION')) {
        $pods_table = "{$wpdb->prefix}podsrel";

        $qry = $wpdb->prepare(
            "SELECT * FROM $pods_table WHERE item_id = %d",
            $from_post_id
        );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $pods_table WHERE item_id = %d",
                $from_post_id
            )
        );

        foreach($results as $row) {
            $rel_data = array_diff_key(
                (array) $row,
                array_fill_keys(['id', 'pod_id', 'field_id', 'item_id'], true)
            );

            $rel_data = array_map('intval', $rel_data);

            $match_data = [
                'pod_id' => (int) $row->pod_id,
                'field_id' => (int) $row->field_id,
                'item_id' => (int) $to_post_id
            ];

            if ($rel_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM $pods_table WHERE pod_id = %d AND field_id = %d AND item_id = %d",
                        $match_data['pod_id'],
                        $match_data['field_id'],
                        $match_data['item_id']
                    )
                )
            ) {
                $wpdb->update(
                    $pods_table,
                    $rel_data,
                    ['id' => $rel_id],
                    '%d',
                    '%d'
                );
            } else {
                $wpdb->insert(
                    $pods_table,
                    array_merge($rel_data, $match_data),
                    '%d'
                );
            }
        }
    }

    wp_cache_flush();
}

function revisionary_unrevisioned_postmeta() {
	$exclude = array_fill_keys( array( '_rvy_base_post_id', '_rvy_has_revisions', '_rvy_published_gmt', '_archive__thumbnail_id', '_archive__wp_page_template', '_pp_last_parent', '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_attached_file', '_menu_item_classes', '_menu_item_menu_item_parent', '_menu_item_object', '_menu_item_object_id', '_menu_item_target', '_menu_item_type', '_menu_item_url', '_menu_item_xfn', '_rs_file_key', '_scoper_custom', '_scoper_last_parent', '_wp_attachment_backup_sizes', '_wp_attachment_metadata', '_wp_trash_meta_status', '_wp_trash_meta_time', '_last_attachment_ids', '_last_category_ids', '_encloseme', '_pingme' ), true );
	return apply_filters( 'revisionary_unrevisioned_postmeta', $exclude );
}
