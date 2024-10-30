<?php
class Custom_Content_Types_Dashboard_Widget {

	public function __construct() {
		add_action( 'current_screen', array( $this, 'form_redirect' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
		wp_enqueue_style( 'the_tooltip', plugins_url( '/css/the-tooltip.css', __FILE__ ) );
		wp_enqueue_script( 'the_tooltip', plugins_url( '/js/the-tooltip.min.js', __FILE__ ) );
	}

	function form_redirect( $screen ) {
		global $custom_content_types;

		if( $screen->base != 'dashboard' )
			return;

		if( isset( $_POST['custom_content_types_dashboard'], $_POST['post_type'], $_POST['content-type'] ) ) {
			
			$post = get_default_post_to_edit( esc_attr( $_POST['post_type'] ), true );
			$custom_content_types->set_post_format( $post, esc_attr( $_POST['content-type'] ) );

			$url = get_edit_post_link( $post->ID, 'url' );
			wp_safe_redirect( $url );
			exit;
		}
	}

	function add_dashboard_widgets() {
		wp_add_dashboard_widget( 'custom_content_types_widget', __('Quick add', 'pixelpillow-custom-content-types'), array( $this, 'dashboard_widget' ) );
	}

	// base = dashboard
	function dashboard_widget() {
		echo '<form method="post">';
		echo '<input type="hidden" name="custom_content_types_dashboard" value="1" />';
		echo '<input type="hidden" name="post_type" value="post" />';

		$post_type = 'post';
		$terms = get_terms( esc_attr( 'content_type_' . $post_type ), array( 'hide_empty' => false ) );

		foreach( $terms as $term ) {
			echo '<div class="term_selectbox the-tooltip top left auto-width black">';
			echo '<input name="content-type" type="submit" value="' . $term->term_id . '" style="background-image: url(' . Custom_Content_Types_Taxonomy::get_icon( $term, true ) . ')">';
			echo '<span>' . $term->name . '</span>';
			echo '</div>';
		}

		echo '<div class="clear"></div>';
		echo '</form>';
	}
}

new Custom_Content_Types_Dashboard_Widget();