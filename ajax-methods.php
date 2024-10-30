<?php

class Custom_Content_Types_Ajax {
	function __construct() {
		add_action( 'wp_ajax_cct_add_image', array( $this, '_add_image' ) );
		add_action( 'wp_ajax_cct_remove_image', array( $this, '_remove_image' ) );
	}

	function _add_image() {
		header( "Content-Type: application/json" );

		if( isset( $_POST['image_id'], $_POST['taxonomy'] ) ) {
			$images = get_option( 'content-type-images' );
			if( ! $images ) { $images = array(); }

			array_push( $images, absint( $_POST['image_id'] ) );
			update_option( 'content-type-images', $images );


			$images_tax = get_option( 'content-type-images-' . esc_attr( $_POST['taxonomy'] ) );
			if( ! $images_tax ) { $images_tax = array(); }

			array_push( $images_tax, absint( $_POST['image_id'] ) );
			update_option( 'content-type-images-' . esc_attr( $_POST['taxonomy'] ), $images_tax );
		}

		echo json_encode( array( 'success' => true ) );
		exit;
	}

	function _remove_image() {
		header( "Content-Type: application/json" );

		if( isset( $_POST['image_id'], $_POST['taxonomy'] ) ) {
			$image_id = absint( $_POST['image_id'] );

			$images = get_option( 'content-type-images' );
			if( ! $images ) { $images = array(); }

			if( ( $key = array_search( $image_id, $images ) ) !== false ) {
				unset( $images[ $key ] );
			}
			update_option( 'content-type-images', $images );


			$images_tax = get_option( 'content-type-images-' . esc_attr( $_POST['taxonomy'] ) );
			if( ! $images_tax ) { $images_tax = array(); }

			if( ( $key = array_search( $image_id, $images_tax ) ) !== false ) {
				unset( $images_tax[ $key ] );
			}
			update_option( 'content-type-images-' . esc_attr( $_POST['taxonomy'] ), $images_tax );

			echo json_encode( array( 'success' => true ) );
		}
		else {
			echo json_encode( array( 'success' => false ) );
		}

		exit;
	}
}

new Custom_Content_Types_Ajax();

?>