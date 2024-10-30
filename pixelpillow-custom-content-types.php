<?php
/*
Plugin Name: Custom Content Types by Pixelpillow
Plugin URI: http://www.pixelpillow.nl/wordpress/plugins/
Description: Create posts based on content type in a Tumblr like way. Add your own custom content types with your own icons that also shown in the post overview for optimal visual distinction.
Version: 1.0
Author: Pixelpillow
Author URI: http://www.pixelpillow.nl/
License: GPL2

Copyright 2012  Pixelpillow  (email: info@pixelpillow.nl)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

include 'ajax-methods.php';
include 'dashboard-widget.php';
include 'taxonomy-settings.php';

class Custom_Content_Types {

	public function __construct() {
		add_action( 'init', array( &$this, '_register_content_types' ) );
		add_action( 'admin_enqueue_scripts', array( $this, '_editscreen_functionality' ), 1 );

		add_filter( 'wp_insert_post_data', array( $this, '_new_title' ), 10, 2 );
		add_action( 'save_post', array( $this, '_metabox_selector_save' ), 10, 2 );
		
		load_plugin_textdomain( 'pixelpillow-custom-content-types', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	private function available_post_types() {
		$args = array(
			'show_ui' => true
		);

		return get_post_types( $args );
	}

	private function used_post_types() {
		return array( 'post' );
	}

	private function get_post_content_type( $post ) {
		if( ! $post )
			return;

		$post = get_post( $post );

		if ( ! in_array( $post->post_type, $this->used_post_types() ) )
			return false;

		$_format = get_the_terms( $post->ID, esc_attr( 'content_type_' . $post->post_type ) );

		if ( is_wp_error( $_format ) ||  empty( $_format ) )
			return false;

		$type = array_shift( $_format );

		return $type;
	}

	public function set_post_format( $post, $format ) {
		$post = $_post = get_post( $post );

		if ( $post && $post->post_type == 'revision' )
			$post = get_post( $post->post_parent );

		if ( empty( $post ) )
			return new WP_Error( 'invalid_post', __( 'Invalid post' , 'pixelpillow-custom-content-types') );

		$term = get_term_by( 'id', absint( $format ), 'content_type_' . $post->post_type );

		if ( ! $term  )
			return new WP_Error( 'invalid_format', __( 'Invalid format' , 'pixelpillow-custom-content-types') );

		return wp_set_post_terms( $post->ID, array( absint( $format ) ), 'content_type_' . $post->post_type );
	}


	/**
	 *
	 * Methods used by hooks
	 *
	 **/
	function _register_content_types() {
		$post_types = $this->used_post_types();

		foreach( $post_types as $post_type ) {
			$labels = array(
				'name' => __( 'Content types', 'custom-content-types' , 'pixelpillow-custom-content-types'),
				'singular_name' => __( 'Content type', 'taxonomy singular name' , 'pixelpillow-custom-content-types'),
				'search_items' =>  __( 'Search content types' , 'pixelpillow-custom-content-types'),
				'all_items' => __( 'All content types' , 'pixelpillow-custom-content-types'),
				'edit_item' => __( 'Edit content type' , 'pixelpillow-custom-content-types'), 
				'update_item' => __( 'Update content type' , 'pixelpillow-custom-content-types'),
				'add_new_item' => __( 'Add new content type' , 'pixelpillow-custom-content-types'),
				'new_item_name' => __( 'New content type Name' , 'pixelpillow-custom-content-types'),
				'menu_name' => __( 'Content types' , 'pixelpillow-custom-content-types'),
			);

			/**
			 * @todo not multiple post type support because of static slug
			 **/
			$args = array(
				'hierarchical' => false,
				'labels' => $labels,
				//'show_ui' => false,
				'show_in_nav_menus' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'content-type' )
			);

			if( class_exists( 'Acf' ) ) {
				$args['hierarchical'] = true;
			}

			register_taxonomy(
				esc_attr( 'content_type_' . $post_type ),
				array( $post_type ),
				$args
			);
		}
	}

	function _editscreen_functionality() {
		global $post, $title;

		$screen = get_current_screen();
		
		if( 'dashboard' == $screen->base )
			add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );

		if( ! in_array( $screen->post_type, $this->used_post_types() ) )
			return;

		if( 'edit' == $screen->base ) {
			add_filter( 'manage_' . $screen->post_type . '_posts_columns', array( $this, '_post_table_columns' ) );
			add_action( 'manage_' . $screen->post_type . 's_custom_column', array( $this, '_post_table_column' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );
		}
		else if( 'post' == $screen->base ) {
			$content_type = $this->get_post_content_type( $post );

			if( $content_type ) {
				$title = $content_type->name;

				echo '<style type="text/css" media="all">.wrap #icon-edit, .wrap #icon-post { background: url(' . Custom_Content_Types_Taxonomy::get_icon( $content_type, true ) . ') center center no-repeat; background-size: 100%; }</style>';

				remove_meta_box( 'tagsdiv-content_type_' . $screen->post_type, $screen->post_type, 'side' );
			}
			else {
				add_action( 'admin_head', array( $this, '_metabox_select_setup' ) );
				add_filter( 'get_user_option_screen_layout_' . $screen->id, array( $this, '_change_editscreen_columns' ) );
				add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );
			}
		}
	}

	function _metabox_select_setup() {
		global $wp_meta_boxes, $_wp_post_type_features;

		$screen = get_current_screen();

		unset( $_wp_post_type_features[ $screen->post_type ]['title'] );
		unset( $_wp_post_type_features[ $screen->post_type ]['editor'] );

		$wp_meta_boxes[ $screen->id ] = array();
		add_meta_box(
			'select-content-type',
			__( 'Select content type', 'pixelpillow-custom-content-types'),
			array( $this, '_metabox_selector' ),
			$screen->post_type,
			'advanced',
			'high'
		);
	}

	function _metabox_selector( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'cct_metabox_selector' );

		$terms = get_terms( esc_attr( 'content_type_' . $post->post_type ), array( 'hide_empty' => false ) );

		foreach( $terms as $term ) {
			echo '<div class="term_selectbox">';
			echo '<input name="content-type" type="submit" value="' . $term->term_id . '" style="background-image: url(' . Custom_Content_Types_Taxonomy::get_icon( $term, true ) . ')" />';
			echo '<div class="title">' . $term->name . '</div>';
			echo '</div>';
		}

		echo '<div class="clear"></div>';
	}

	function _change_editscreen_columns( $columns ) {
		return 1;
	}


	function _new_title( $data, $postarr ) {
		if( isset( $_POST['content-type'] ) ) {
			$data['post_title'] = '';
			$data['post_name']  = sanitize_title( $data['post_title'] );
		}

		return $data;
	}

	function _metabox_selector_save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( ! isset( $_POST['cct_metabox_selector'] ) || ! wp_verify_nonce( $_POST['cct_metabox_selector'], plugin_basename( __FILE__ ) ) )
			return;

		if ( 'page' == $_POST['post_type'] ) 
		{
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		}
		else
		{
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}

		if( isset( $_POST['content-type'] ) ) {
			$this->set_post_format( $post, esc_attr( $_POST['content-type'] ) );
		}
	}



	function _post_table_columns( $columns ) {
		$columns['image'] = __('Image', 'pixelpillow-custom-content-types');

		return $columns;
	}

	function _post_table_column( $column_name, $post_id ) {
		if( 'image' == $column_name ) {
			$type = $this->get_post_content_type( $post_id );
			echo Custom_Content_Types_Taxonomy::get_icon( $type );
		}
	}



	function _enqueue_scripts() {
		wp_enqueue_style( 'custom_content_types', plugins_url( '/css/admin.css', __FILE__ ) );
		wp_enqueue_script( 'custom_content_types', plugins_url( '/js/effects.js', __FILE__ ), array( 'jquery' ) );
	}
}

$GLOBALS['custom_content_types'] = new Custom_Content_Types();