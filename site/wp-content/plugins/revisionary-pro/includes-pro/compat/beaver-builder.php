<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/**
 * @package     PublishPress\Revisions\RevisionaryBeaverBuilder
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryBeaverBuilder
{		
	private $beaver_builder_entry;
    private $revisionary;
    private $bb_data = [];
    private $bb_data_settings = [];

	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function __construct($revisionary) {
        $this->revisionary = $revisionary;

		add_action('wp_head', [$this, 'act_scripts']);

		add_filter('fl_builder_is_post_editable', [$this, 'flt_editable']);
		add_filter('fl_builder_ui_bar_publish', [$this, 'flt_publish_caption']);
        add_action('fl_builder_before_save_layout', [$this, 'flt_before_save_layout'], 10, 4);
        
        add_filter('revisionary_apply_revision_allowance', [$this, 'flt_revisionary_apply_revision_allowance'], 10, 2);
        add_filter('revisionary_pending_revision_intercept', [$this, 'flt_interrupt_pending_revision'], 10, 4);
        add_filter('revisionary_limit_revision_fields', [$this, 'flt_limit_revision_fields'], 10, 3);
        add_filter('revisionary_do_revision_notice', [$this, 'flt_do_revision_notice'], 10, 3);
    }

    function act_scripts() {
		global $post, $current_user;

		if (!isset($_REQUEST['fl_builder']) || empty($post) || is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_AJAX') && DOING_AJAX)) {
			return;
		}
		
		if (!$published_post_id = rvy_post_id($post->ID)) { // if we are editing a revision, consider published post permissions instead
			return;
		}

		if ( agp_user_can( 'edit_post', $published_post_id, '', array( 'skip_revision_allowance' => true ) ) ) {
			return;
		}

		if ($post->ID == $published_post_id) {
			$last_revision = $this->revisionary->get_last_revision($published_post_id, $current_user->ID);
			$last_revision_id = ($last_revision) ? $last_revision->ID : 1;
		} else {
			$last_revision_id = (int) $post->ID - 1;
		}

		if (rvy_get_option('revision_preview_links')) {
			$preview_link = add_query_arg('get_new_revision', $last_revision_id, get_permalink($published_post_id));  // Beaver Builder overrides our front end preview JS
		} else {
			$preview_link = get_permalink($published_post_id);
		}
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			$(document).on('click', 'span.fl-builder-button-primary[data-action=publish], button.fl-builder--menu-item[data-event=publishAndRemain]', function() {
				$(location).attr('href', '<?php echo $preview_link;?>');
			});
		});
		/* ]]> */
		</script>
		<?php
	}

    function flt_revisionary_apply_revision_allowance($apply, $post_id) {
        // If a post has not been previously edited with Beaver Builder, we cannot safely filter it for pending revision submission.
		if (($post_id > 0) 
			&& isset( $_REQUEST['fl_builder'] ) 
			&& empty( $this->beaver_builder_entry ) 
			&& !get_site_transient( "_rvy_have_bb_data_{$post_id}" ) 
			&& !get_post_meta( $post_id, '_fl_builder_data', true ) 
		) {
            $apply = false;
        }

        return $apply;
    }
    
    function flt_interrupt_pending_revision($return, $data, $postarr, $published_post) {
        // Deals with Beaver Builder calling wp_insert_post() on transition from wp-admin editor to front end editor.
		if ( ! empty( $_REQUEST['fl-builder-redirect'] ) && did_action('admin_menu') ) {
			// Skip pending revision creation if no changes were made prior to transition to front end editor.
			$changes = false;
			
			foreach( array( 'post_content', 'post_title' ) as $property ) {
				if ( $postarr[$property] != $published_post->$property ) {
					$changes = true;
					break;
				}
			}
		
			// If changes were made prior to transition to front end, allow them to be saved as a separate pending revision.
			// However, these changes will not be carried over to the Beaver front end editor for further immediate editing. 
			if ( ! $changes ) {
				return $data;
			}
		}
        
        if ( ! empty( $this->beaver_builder_entry ) ) {
            // If this is a Beaver Builder post update, transfer new published post data and settings, restore original to published post
            $this->bb_data = get_post_meta( $published_post->ID, '_fl_builder_data', true );
            $this->bb_data_settings = get_post_meta( $published_post->ID, '_fl_builder_data_settings', true );

            if ( ! empty( $this->beaver_builder_entry['data'] ) ) {
                update_post_meta( $published_post->ID, '_fl_builder_data', $this->beaver_builder_entry['data'] );
            }

            if ( ! empty( $this->beaver_builder_entry['data_settings'] ) ) {
                update_post_meta( $published_post->ID, '_fl_builder_data_settings', $this->beaver_builder_entry['data_settings'] );
            }

            add_action('revisionary_created_revision', [$this, 'act_created_revision']);

        } elseif ( false === $this->beaver_builder_entry ) {
            // If published post has never been edited with Beaver Builder, we can't safely filter it
            return $data;
        }

        return $return;
    }

    function act_created_revision($revision) {
        if ( ! empty( $this->bb_data ) && ! $this->revisionary->doing_rest ) {
			// If this is a Beaver Builder post update, store edited data, settings to revision
			update_post_meta( $revision->ID, '_fl_builder_data', $this->bb_data );
			update_post_meta( $revision->ID, '_fl_builder_data_settings', $this->bb_data_settings );
		}
    }

    function flt_limit_revision_fields($limit_fields, $revision, $published_post) {
        return $limit_fields || $this->beaver_builder_entry;
    }

    function flt_do_revision_notice($do_it, $revision, $published_post) {
        return $do_it && empty($_REQUEST['fl-builder-redirect']);
    }

	function flt_editable($editable) {
		global $wp_the_query;

		if (!$editable && is_singular() && isset($wp_the_query->post) && class_exists('FLBuilderUserAccess')) {
			$post = $wp_the_query->post;

			if ($post && in_array($post->post_type, FLBuilderModel::get_post_types()) && FLBuilderUserAccess::current_user_can('builder_access')) {
				if ($type_obj = get_post_type_object($post->post_type)) {
					return agp_user_can($type_obj->cap->edit_post, $post->ID, '', array('skip_revision_allowance' => false));
				}
			}
		}

		return $editable;
	}

	function flt_publish_caption($caption) {
		global $post;

		if ( $post &&  ! is_content_administrator_rvy() ) {
			$type_obj = get_post_type_object( $post->post_type );
			if ( $type_obj && ! agp_user_can( $type_obj->cap->edit_post, $post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
				$caption = __('Submit Revision', 'revisionary');
			}
		}

		return $caption;
	}

	function flt_before_save_layout( $post_id, $publish, $data, $settings ) {
		if ( $publish ) {
			if ( ! $_data = get_post_meta( $post_id, '_fl_builder_data', true ) ) {
				// If published post has never been edited with Beaver Builder, we can't safely filter it
				$this->beaver_builder_entry = false;

			} elseif(empty($this->beaver_builder_entry)) {
				set_site_transient( "_rvy_have_bb_data_{$post_id}", true );

				$_settings = get_post_meta($post_id, '_fl_builder_data_settings', true);

				$this->beaver_builder_entry = array( 'data' => $_data, 'data_settings' => $_settings );
			}
		}
    }

} // end RevisionaryBeaverBuilder class
