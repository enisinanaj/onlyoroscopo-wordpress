<?php

require_once dirname(__FILE__) . '/helpers.php';

class BlocksyExtensionProductReviews {
	public function __construct() {
		add_action(
			'blocksy:hero:title:before',
			function () {
				if (! is_singular('blc-product-review')) {
					return;
				}

				$atts = apply_filters(
					'blocksy:ext:product-reviews:frontend:atts',
					blocksy_get_post_options(null, [
						'meta_id' => 'blocksy_product_review_options'
					]),
					get_the_ID()
				);

				$maybe_schema = blocksy_schema_org_definitions('itemReviewed', [
					'to_merge' => [
						'itemtype' => "https://schema.org/" . blocksy_akg(
							'product_review_entity',
							$atts,
							'Thing'
						)
					]
				]);

				if (! $maybe_schema) {
					return;
				}

				echo '<div ' . $maybe_schema . '>';
				echo '<meta itemprop="name" content="' . get_the_title() . '">';
				if (get_the_post_thumbnail_url()) {
					echo '<meta itemprop="image" content="' . get_the_post_thumbnail_url() . '">';
				}
			}
		);

		add_action(
			'blocksy:hero:title:after',
			function () {
				if (! is_singular('blc-product-review')) {
					return;
				}

				$maybe_schema = blocksy_schema_org_definitions('itemReviewed');

				if (! $maybe_schema) {
					return;
				}

				echo '</div>';
			}
		);

		add_filter(
			'blocksy:options:cpt:page-title-args',
			function ($args, $cpt) {
				if ($cpt === 'blc-product-review') {
					$args['has_hero_type'] = false;
				}

				return $args;
			},
			10, 2
		);

		add_action(
			'customize_preview_init',
			function () {
				if (! function_exists('get_plugin_data')){
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_enqueue_script(
					'blocksy-product-reviews-customizer-sync',
					BLOCKSY_URL . 'framework/extensions/product-reviews/static/bundle/sync.js',
					[ 'ct-scripts', 'customize-preview' ],
					$data['Version'],
					true
				);
			}
		);

		add_action('blocksy:global-dynamic-css:enqueue', function ($args) {
			blocksy_theme_get_dynamic_styles(array_merge([
				'path' => dirname( __FILE__ ) . '/global.php',
				'chunk' => 'global'
			], $args));
		}, 10, 3);

		add_action('init', [$this, 'declare_cpt']);

		add_action('load-post.php', [$this, 'init_metabox']);
		add_action('load-post-new.php', [$this, 'init_metabox']);

		add_filter('blocksy:single:has-default-hero', function ($def) {
			if (! is_singular('blc-product-review')) {
				return $def;
			}

			return false;
		});

		add_filter(
			'blocksy:hero:type-1:default-alignment',
			function ($default, $prefix) {
				if ($prefix === 'blc-product-review_single') {
					return 'center';
				}

				return $default;
			},
			10, 2
		);

		add_filter('blocksy:archive:render-card-layers', function ($layers, $prefix) {
			if ($prefix !== 'blc-product-review_archive') {
				return $layers;
			}

			$layers['overall_score'] = blocksy_get_product_review_overall_score();

			return $layers;
		}, 10, 2);

		add_filter('blocksy:posts-listing:archive-order:default', function ($default, $prefix) {
			if ($prefix !== 'blc-product-review_archive') {
				return $default;
			}

			$default[] = [
				'id' => 'overall_score',
				'enabled' => true
			];

			return $default;
		}, 10, 2);

		add_filter('blocksy:options:posts-listing-archive-order', function ($option, $prefix) {
			if ($prefix !== 'blc-product-review_archive') {
				return $option;
			}

			$option['value'][] = [
				'id' => 'overall_score',
				'enabled' => true
			];

			$option['settings']['overall_score'] = [
				'label' => __('Overall Score', 'blc'),
					/*
				'options' => [
					'excerpt_length' => [
						'label' => __('Length', 'blc'),
						'type' => 'ct-number',
						'design' => 'inline',
						'value' => 40,
						'min' => 10,
						'max' => 100,
					],
				],
				*/
			];

			return $option;
		}, 10, 2);

		add_action(
			'blocksy:template:before',
			function () {
				if (! is_singular('blc-product-review')) {
					return;
				}

				echo blc_call_fn(
					['fn' => 'blocksy_render_view'],
					dirname(__FILE__) . '/views/single-top.php',
					[]
				);
			}
		);

		add_action('wp_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (is_admin()) {
				return;
			}

			wp_enqueue_style(
				'blocksy-ext-product-reviews-styles',
				BLOCKSY_URL . 'framework/extensions/product-reviews/static/bundle/main.min.css',
				['ct-main-styles'],
				$data['Version']
			);
		});

		add_filter('blocksy_single_posts_post_elements_start', function ($options, $prefix) {
			if ($prefix !== 'blc-product-review_single') {
				return $options;
			}

			$options[$prefix . '_reviews_summary'] = [
				'label' => __( 'Review Summary', 'blc' ),
				'type' => 'ct-panel',
				'setting' => [ 'transport' => 'postMessage' ],
				'inner-options' => [

					blocksy_rand_md5() => [
						'title' => __( 'General', 'blc' ),
						'type' => 'tab',
						'options' => [

							$prefix . '_product_scores_width' => [
								'label' => __( 'Scores Box Width', 'blc' ),
								'type' => 'ct-slider',
								'min' => 0,
								'max' => 1200,
								'value' => 800,
								// 'responsive' => true,
								'setting' => [ 'transport' => 'postMessage' ],
							],

							$prefix . '_has_read_more' => [
								'label' => __('Read More Button', 'blc'),
								'type' => 'ct-switch',
								'value' => 'yes',
								'divider' => 'top',
								'sync' => blocksy_sync_single_post_container([
									'prefix' => $prefix,
									'loader_selector' => '.ct-product-actions-group'
								])
							],

							$prefix . '_has_buy_now' => [
								'label' => __('Buy Now Button', 'blc'),
								'type' => 'ct-switch',
								'value' => 'yes',
								'divider' => 'top',
								'sync' => blocksy_sync_single_post_container([
									'prefix' => $prefix,
									'loader_selector' => '.ct-product-actions-group'
								])
							],

						],
					],

					blocksy_rand_md5() => [
						'title' => __( 'Design', 'blc' ),
						'type' => 'tab',
						'options' => [

							$prefix . '_star_rating_color' => [
								'label' => __( 'Star Rating Color', 'blc' ),
								'type'  => 'ct-color-picker',
								'design' => 'inline',
								'setting' => [ 'transport' => 'postMessage' ],

								'value' => [
									'default' => [
										'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
									],

									'inactive' => [
										'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
									],
								],

								'pickers' => [
									[
										'title' => __( 'Active', 'blc' ),
										'id' => 'default',
										'inherit' => '#FDA256'
									],

									[
										'title' => __( 'Inactive', 'blc' ),
										'id' => 'inactive',
										'inherit' => '#F9DFCC'
									],
								],
							],

							$prefix . '_overall_score_text' => [
								'label' => __( 'Overll Score Text', 'blc' ),
								'type'  => 'ct-color-picker',
								'design' => 'inline',
								'divider' => 'top',
								'setting' => [ 'transport' => 'postMessage' ],

								'value' => [
									'default' => [
										'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
									],
								],

								'pickers' => [
									[
										'title' => __( 'Active', 'blc' ),
										'id' => 'default',
										'inherit' => '#ffffff'
									],
								],
							],

							$prefix . '_overall_score_backgroud' => [
								'label' => __( 'Overll Score Background', 'blc' ),
								'type'  => 'ct-color-picker',
								'design' => 'inline',
								'setting' => [ 'transport' => 'postMessage' ],

								'value' => [
									'default' => [
										'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
									],
								],

								'pickers' => [
									[
										'title' => __( 'Active', 'blc' ),
										'id' => 'default',
										'inherit' => '#1A202C'
									],
								],
							],

						],
					],

				]
			];

			return $options;
		}, 10, 2);
	}

	public function init_metabox() {
		add_action('add_meta_boxes', [$this, 'setup_meta_box']);
		add_action('save_post', [$this, 'save_meta_box']);
	}

	public function declare_cpt() {
		$settings = $this->get_settings();

		register_post_type('blc-product-review', [
			'label' => __('Product Reviews', 'blc'),
			'description' => __( 'Product Reviews', 'blc'),
			'menu_icon' => 'dashicons-star-filled',
			'labels' => [
				'name' => __('Product Reviews', 'blc'),
				'singular_name' => __('Product Review', 'blc'),
				'menu_name' => __('Product Reviews', 'blc'),
				'parent_item_colon' => __('Parent Product Review', 'blc'),
				'all_items' => __('All Reviews', 'blc'),
				'view_item' => __('View Product Review', 'blc'),
				'add_new_item' => __('Add New Product Review', 'blc'),
				'add_new' => __('Add New Review', 'blc'),
				'edit_item' => __('Edit Product Review', 'blc'),
				'update_item' => __('Update Product Review', 'blc'),
				'search_items' => __('Search Product Review', 'blc'),
				'not_found' => __('Not Found', 'blc'),
				'not_found_in_trash' => __('Not found in Trash', 'blc')
			],
			'supports' => [
				'comments',
				'title', 'editor', 'excerpt',
				'author', 'thumbnail', 'revisions',
				'custom-fields'
			],
			'show_in_rest' => true,
			'public' => true,
			'hierarchical' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'has_archive' => true,
			'can_export' => true,
			'exclude_from_search' => false,
			// 'taxonomies' => array('post_tag'),
			'publicly_queryable' => true,
			'capability_type' => 'page',
			'rewrite' => [
				'slug' => $settings['single_slug']
			],
		]);

		register_taxonomy(
			'blc-product-review-categories',
			[
				'blc-product-review'
			],
			[
				'hierarchical' => true,
				'labels' => [
					'name' => __('Categories', 'blc'),
					'singular_name' => __('Category', 'blc'),
					'search_items' =>  __('Search Category', 'blc'),
					'all_items' => __('All Categories', 'blc'),
					'parent_item' => __('Parent Category', 'blc'),
					'parent_item_colon' => __('Parent Category:', 'blc'),
					'edit_item' => __('Edit Category', 'blc'),
					'update_item' => __('Update Category', 'blc'),
					'add_new_item' => __('Add New Category', 'blc'),
					'new_item_name' => __('New Category Name', 'blc'),
					'menu_name' => __('Categories', 'blc'),
				],
				'show_ui' => true,
				'show_admin_column' => true,
				'query_var' => true,
                'show_in_rest' => true,
				'rewrite' => [
					'slug' => $settings['category_slug']
				],
			]
		);
	}

	public function setup_meta_box() {
		add_meta_box(
			'blocksy_settings_meta_box',
			sprintf(
				// Translators: %s is the theme name.
				__( '%s Settings', 'blc' ),
				__( 'Blocksy', 'blc' )
			),
			function ($post) {
				$values = get_post_meta($post->ID, 'blocksy_product_review_options');

				if (empty($values)) {
					$values = [[]];
				}

				$options = blc_call_fn(
					['fn' => 'blocksy_get_options'],
					dirname(__FILE__) . '/metabox.php',
					[],
					false
				);

				/**
				 * Note to code reviewers: This line doesn't need to be escaped.
				 * Function blocksy_output_options_panel() used here escapes the value properly.
				 */
				echo blocksy_output_options_panel(
					[
						'options' => $options,
						'values' => $values[0],
						'id_prefix' => 'ct-post-meta-options',
						'name_prefix' => 'blocksy_product_review_options',
						'attr' => [
							'class' => 'ct-meta-box',
							'data-disable-reverse-button' => 'yes'
						]
					]
				);

				wp_nonce_field(basename(__FILE__), 'blocksy_settings_meta_box');
			},
			'blc-product-review', 'normal', 'default'
		);
	}

	public function save_meta_box($post_id) {
		$is_autosave = wp_is_post_autosave($post_id);
		$is_revision = wp_is_post_revision($post_id);
		$is_valid_nonce = !! (
			isset($_POST['blocksy_settings_meta_box']) && wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['blocksy_settings_meta_box'])),
				basename(__FILE__)
			)
		);

		if ($is_autosave || $is_revision || !$is_valid_nonce) {
			return;
		}

		$values = [];

		if (isset($_POST['blocksy_product_review_options'][blocksy_post_name()])) {
			$values = json_decode(
				wp_unslash($_POST['blocksy_product_review_options'][blocksy_post_name()]),
				true
			);
		}

		update_post_meta(
			$post_id,
			'blocksy_product_review_options',
			$values
		);
	}

	public function get_settings() {
		if (wp_doing_ajax()) {
			$maybe_input = json_decode(file_get_contents('php://input'), true);

			if (
				$maybe_input
				&&
				isset($maybe_input['extension'])
				&&
				$maybe_input['extension'] === 'product-reviews'
				&&
				isset($maybe_input['extAction'])
				&&
				$maybe_input['extAction']['type'] === 'persist'
			) {
				return $maybe_input['extAction']['settings'];
			}
		}

		return get_option('blocksy_ext_product_reviews_settings', [
			'single_slug' => 'product-review',
			'category_slug' => 'product-review-category',
		]);
	}

	public function set_settings($value) {
		update_option('blocksy_ext_product_reviews_settings', $value);
	}
}
