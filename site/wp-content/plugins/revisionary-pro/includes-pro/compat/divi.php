<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

/**
 * @package     PublishPress\Revisions\RevisionaryDivi
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2020 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryDivi
{		
    var $revisionary;
	var $revision_id = 0;
	var $post_id = 0;

	function __construct($revisionary) {
        $this->revisionary = $revisionary;

        add_filter('revisionary_do_revision_notice', [$this, 'flt_do_revision_notice'], 10, 3);

		add_filter('revisionary_flag_as_post_update', [$this, 'fltFlagAsPostUpdate'], 10, 5);
		
		add_action('revisionary_created_revision', [$this, 'actLogRevisionID']);
		
		add_filter('et_fb_get_asset_helpers', [$this, 'fltDiviAssetHelpers'], 11, 2);

		if (!is_admin()) {
			add_action('wp_print_footer_scripts', [$this, 'actFrontScripts'], 100);

		} else {
			add_action('admin_print_scripts', [$this, 'actAdminScripts'], 100);
			add_action('load-post.php', [$this, 'actAdminRevisionSubmissionRedirect']);
			add_action('post_submitbox_misc_actions', [$this, 'actClearFlags']);
		}

		add_filter('revisionary_creation_options', [$this, 'fltRevisionCreationOptions']);

		add_filter('revisionary_bypass_revision_creation', [$this, 'fltBypassRevisionCreation'], 10, 3);
		add_action('revisionary_get_rev_msg', [$this, 'actRevisionCreatedMessage'], 10, 2);

		add_filter('revisionary_future_rev_submit_data', [$this, 'fltScheduledRevisionEntryData'], 10, 2);
		add_filter('revisionary_future_rev_creation_data', [$this, 'fltScheduledRevisionCreationData'], 10, 2);
		add_filter('revisionary_future_rev_return_data', [$this, 'fltScheduledRevisionReturnData'], 10, 3);

		add_action('posts_selection', [$this, 'actRevisionaryFront']);

		add_action('load-index.php', [$this, 'actAjaxHandler']);  // or 'current_screen'

		add_filter('et_fb_ajax_save_verification_result', [$this, 'fltDiviSaveVerification']);
	}

	public function fltBypassRevisionCreation($bypass_data, $data, $published_post) {
		global $current_user;

		if (get_post_meta($published_post->ID, '_rvy_skip_revision_save', true)
		|| get_transient("_rvy_skip_revision_save_front_{$current_user->ID}_{$published_post->ID}")
		) {
			delete_post_meta($published_post->ID, '_rvy_skip_revision_save');

			// return currently stored published post data
			$bypass_data = array_intersect_key((array) get_post($published_post->ID), $data);
		}

		return $bypass_data;
	}

	public function actRevisionCreatedMessage($revision_id, $args) {
		global $current_user;
		rvy_delete_post_meta(rvy_post_id($revision_id), "_pending_revision_saved_{$current_user->ID}");
	}

	public function fltScheduledRevisionEntryData($data, $published_post) {
		global $current_user;

		if (rvy_get_post_meta($published_post->ID, "_new_scheduled_revision_{$current_user->ID}", true)
		|| rvy_get_post_meta($published_post->ID, "_save_as_revision_{$current_user->ID}", true)
		) {
			set_transient("_rvy_scheduled_revision_submission_{$current_user->ID}_{$published_post->ID}", $data['post_content'], 10);

			if ($restore_content = get_transient("_rvy_scheduled_revision_bypass_{$current_user->ID}_{$published_post->ID}")) {
				$data['post_content'] = $restore_content;
			} else {
				set_transient("_rvy_scheduled_revision_bypass_{$current_user->ID}_{$published_post->ID}", $published_post->post_content, 10);
			}
		}

		return $data;
	}

	public function fltScheduledRevisionCreationData($data, $published_post) {
		global $current_user;

		// Retrieve archived new post_content submission for scheduled revision
		if ($submission_content = get_transient("_rvy_scheduled_revision_submission_{$current_user->ID}_{$published_post->ID}")) {
			$data = array_merge($data, ['post_content' => $submission_content]);
		}

		return $data;
	}

	public function fltScheduledRevisionReturnData($data, $published_post, $revision_id) {
		// Due to Divi processing, need to return published post data, replace with archived scheduled revision submission downstream
		if ($revision_id) {
			$data = array_intersect_key( (array) $published_post, array_fill_keys( array( 'ID', 'post_type', 'post_name', 'post_status', 'post_parent', 'post_author', 'post_content' ), true ) );
		}

		return $data;
	}

	public function fltRevisionCreationOptions($options) {
		$options['min_seconds'] = 20;
		return $options;
	}

	public function actClearFlags() {
		global $current_user;
		$post_id = rvy_detect_post_id();

		delete_post_meta( $post_id, "_new_scheduled_revision_{$current_user->ID}" );
		delete_post_meta( $post_id, "_save_as_revision_{$current_user->ID}" );
		update_postmeta_cache($post_id);
	}

	public function actAdminScripts() {
		$post_id = rvy_detect_post_id();
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			//$('#et_pb_toggle_builder').click(function() {
			$('#et_pb_toggle_builder').click(function() {
				var data = {'rvy_ajax_field': 'skip_revision_save', 'rvy_ajax_value': 1, 'post_id': <?php echo $post_id;?>};
				$.ajax({
					url: '<?php echo admin_url('');?>', 
					data: data,
					dataType: "html", 
					success: function(response){
					}, 
					error: function(data){}
					}
				);
			});

			$('#et_pb_fb_cta').click(function() {
				var data = {'rvy_ajax_field': 'skip_revision_save_front', 'rvy_ajax_value': 1, 'post_id': <?php echo $post_id;?>};
				$.ajax({
					url: '<?php echo admin_url('');?>', 
					data: data,
					dataType: "html", 
					success: function(response){
					}, 
					error: function(data){}
					}
				);
			});
		});
		/* ]]> */
		</script>
		<?php
	}

	public function actAdminRevisionSubmissionRedirect() {		
		global $current_user;

		$post_id = rvy_detect_post_id();

		if ($post_id && empty($_REQUEST['published_post'])) {
			if (!$revision_id = rvy_get_post_meta($post_id, "_pending_revision_saved_{$current_user->ID}", true)) {
				$revision_id = get_transient("_pending_revision_detected_{$current_user->ID}_{$post_id}");
			}
		
			if ($revision_id && empty($_REQUEST['published_post'])) {
				rvy_delete_post_meta($post_id, "_pending_revision_saved_{$current_user->ID}");
				delete_transient("_pending_revision_detected_{$current_user->ID}_{$post_id}");

				if (!defined('REVISIONARY_DISABLE_SUBMISSION_REDIRECT') && apply_filters('revisionary_do_submission_redirect', true)) {
					global $revisionary;
					$msg = $revisionary->get_revision_msg( $revision_id, compact( 'data', 'post_id', 'object_type', 'future_date' ) );
					rvy_halt( $msg, __('Pending Revision Created', 'revisionary') );
				}
			}
		}
	}

	public function fltDiviSaveVerification($verified) {
		//global $current_user;

		// Short circuit Divi verification only for revision submissions
		//return ($this->revision_id  || ($this->post_id && get_transient("_rvy_scheduled_revision_bypass_{$current_user->ID}_{$this->post_id}")))
		//(!agp_user_can('edit_post', rvy_post_id($this->revision_id), '', ['skip_revision_allowance' => true]) || ('future-revision' == get_post_field('post_status', $this->revision_id))))
		//? true : $verified;

		return true;
	}

	public function actRevisionaryFront() {
		global $current_user, $wp_query;

		if (!is_admin() && (!defined('REST_REQUEST') || ! REST_REQUEST)) {
			$this->post_id = (!empty($wp_query) && !empty($wp_query->queried_object)) ? $wp_query->queried_object->ID : 0;
			rvy_delete_post_meta($this->post_id, "_pending_revision_saved_{$current_user->ID}");
		}
	}

	function actFrontScripts() {
		if ($this->post_id && !empty($_REQUEST['et_fb']) && current_user_can('edit_post', $this->post_id) && !agp_user_can('edit_post', $this->post_id, '', ['skip_revision_allowance' => true])) :
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			var RvyRedirectCheckSaveInterval = setInterval( function() {
				var current_href = window.location.href;

				if (current_href.indexOf(':blank') == -1) {
					var data = {'rvy_ajax_field': 'is_revision_saved', 'rvy_ajax_value': 0, 'post_id': <?php echo $this->post_id;?>};
					$.ajax({
						url: '<?php echo admin_url('');?>', 
						data: data,
						dataType: "html", 
						success: function(response){
							if (response) {
								if (response.indexOf("src='") == -1) {
									clearInterval(RvyRedirectCheckSaveInterval);
									$(location).attr("href", response);
								}
							}
						}, 
						error: function(data){}
						}
					);
				}
			}, 5000 );
		});
		/* ]]> */
		</script>
		<?php endif;
	}

	function actAjaxHandler() {
		global $current_user;

		if (!empty($_REQUEST['rvy_ajax_field']) && ('set_future_date' == $_REQUEST['rvy_ajax_field']) && !empty($_REQUEST['post_id'])) {
			if ($_REQUEST['rvy_ajax_value']) {
				rvy_update_post_meta((int) $_REQUEST['post_id'], "_new_scheduled_revision_{$current_user->ID}", true);
			} else {
				rvy_delete_post_meta((int) $_REQUEST['post_id'], "_new_scheduled_revision_{$current_user->ID}");
			}
			
			exit;
		}

		if (!empty($_REQUEST['rvy_ajax_field']) && ('save_as_pending' == $_REQUEST['rvy_ajax_field']) && !empty($_REQUEST['post_id'])) {
			if ($_REQUEST['rvy_ajax_value']) {
				rvy_update_post_meta((int) $_REQUEST['post_id'], "_save_as_revision_{$current_user->ID}", true);
			} else {
				rvy_delete_post_meta((int) $_REQUEST['post_id'], "_save_as_revision_{$current_user->ID}");
			}
			
			exit;
		}

		if (!empty($_REQUEST['rvy_ajax_field']) && ('skip_revision_save' == $_REQUEST['rvy_ajax_field']) && !empty($_REQUEST['post_id'])) {
			rvy_update_post_meta((int) $_REQUEST['post_id'], '_rvy_skip_revision_save', true);
			exit;
		}

		if (!empty($_REQUEST['rvy_ajax_field']) && ('skip_revision_save_front' == $_REQUEST['rvy_ajax_field']) && !empty($_REQUEST['post_id'])) {
			set_transient("_rvy_skip_revision_save_front_{$current_user->ID}_{" . intval($_REQUEST['post_id']), true, 10);
			exit;
		}

		if (!empty($_REQUEST['rvy_ajax_field']) && ('is_revision_saved' == $_REQUEST['rvy_ajax_field']) && !empty($_REQUEST['post_id'])) {
			if ($revision_id = rvy_get_post_meta(intval($_REQUEST['post_id']), "_pending_revision_saved_{$current_user->ID}", true)) {
				if ($revision = get_post($revision_id)) {
					if ($redirect = rvy_preview_url($revision)) {
						rvy_delete_post_meta(rvy_post_id($revision_id), "_pending_revision_saved_{$current_user->ID}");

						set_transient("_pending_revision_detected_{$current_user->ID}_" . intval($_REQUEST['post_id']), $revision_id, 10);

						echo $redirect;
					}
				}
			}
			exit;
		}
	}

	function fltDiviAssetHelpers($content, $post_type) {
		if (!empty($_REQUEST['et_post_id'])) {
			if (!agp_user_can('edit_post', (int) $_REQUEST['et_post_id'], '', ['skip_revision_allowance' => true])) {
				$content = str_replace('saveButtonText":"' . __('Save') . '"', 'saveButtonText":"' . __('Submit Revision', 'revisionary') . '"', $content);
			}

			if (rvy_is_revision_status(get_post_field('post_status', (int) $_REQUEST['et_post_id']))) {
				$content = str_replace('publishButtonText":"' . __('Submit') . '"', 'publishButtonText":"' . '' . '"', $content);
			}
		}

		return $content;
	}

	function actLogRevisionID($revision) {
		global $current_user;

		$this->revision_id = $revision->ID;

		rvy_update_post_meta(rvy_post_id($revision->ID), "_pending_revision_saved_{$current_user->ID}", $revision->ID);
	}

    function fltFlagAsPostUpdate($flag, $post_id, $reqd_caps, $args, $internal_args) {
        if (!empty($_REQUEST['action']) && ('et_fb_ajax_save' == $_REQUEST['action'])) {
            return true;
        }

        return $flag;
    }

    function flt_do_revision_notice($do_it, $revision, $published_post) {
        return $do_it && (empty($_REQUEST['action']) || ('et_fb_ajax_save' != $_REQUEST['action']));
    }
} // end RevisionaryDivi class
