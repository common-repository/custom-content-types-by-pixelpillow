<?php
class Custom_Content_Types_Taxonomy {
	private $taxonomy;
	private static $icons;
	private $default_icons;

	public function __construct() {
		add_action( 'current_screen', array( $this, 'load' ) );
		add_action( 'admin_init', array( $this, '_load_icons' ) );

		add_action( 'created_term', array( $this, '_save_tax' ), 10, 3 );
	}

	function load() {
		$screen = get_current_screen();

		if( 'edit-tags' == $screen->base && strpos( $screen->taxonomy, 'content_type_' ) === 0 ) {
			$this->taxonomy = $screen->taxonomy;

			add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );

			add_action( 'content_type_post_add_form_fields', array( $this, '_add_tax' ), 10, 2 );
			add_action( 'content_type_post_edit_form_fields', array( $this, '_edit_tax' ), 10, 2 );

			add_action( 'edited_term', array( $this, '_save_tax' ), 10, 3 );

			add_filter( 'manage_edit-' . $screen->taxonomy . '_columns', array( $this, 'taxonomy_table_columns' ) );
			add_filter( 'manage_' . $screen->taxonomy . '_custom_column', array( $this, 'taxonomy_table_column' ), 10, 3 );
		}
	}

	function _load_icons() {
		$icons = array(
			'photo' => plugins_url( 'images/content-types/photo.png', __FILE__ ),
			'video' => plugins_url( 'images/content-types/video.png', __FILE__ ),
			'quote' => plugins_url( 'images/content-types/quote.png', __FILE__ ),
			'text' => plugins_url( 'images/content-types/text.png', __FILE__ ),
			'link' => plugins_url( 'images/content-types/link.png', __FILE__ ),
			'presentation' => plugins_url( 'images/content-types/presentation.png', __FILE__ ),
			'audio' => plugins_url( 'images/content-types/audio.png', __FILE__ ),
			'map' => plugins_url( 'images/content-types/map.png', __FILE__ ),
			'download' => plugins_url( 'images/content-types/download.png', __FILE__ ),
			'chat' => plugins_url( 'images/content-types/chat.png', __FILE__ ),
			'gallery' => plugins_url( 'images/content-types/gallery.png', __FILE__ ),
			'event' => plugins_url( 'images/content-types/event.png', __FILE__ ),
		);
		$icons = $this->default_icons = apply_filters( 'custom-content-types-default-icons', $icons );

		$images = (array) get_option( 'content-type-images' );
		foreach( $images as $image ) {
			$image_url = wp_get_attachment_image_src( $image, 'thumbnail' );

			if( $image_url )
				$icons[ $image ] = $image_url[0];
		}

		self::$icons = $icons;
	}

	public static function get_icon( $term, $no_html = false ) {
		if( is_object( $term ) ) {
			$image_value = get_option( 'content-type-' . $term->taxonomy . '-' . $term->term_id );

			if( isset( self::$icons[ $image_value ] ) ) {
				if( $no_html )
					return self::$icons[ $image_value ];

				return '<img class="term_image" src="' . self::$icons[ $image_value ] . '" />';
			}
		}
	}

	function _enqueue_scripts() {
		wp_enqueue_script( 'custom_content_types', plugins_url( '/js/admin.js', __FILE__ ), array( 'jquery', 'thickbox' ) );
		wp_enqueue_style( 'custom_content_types', plugins_url( '/css/admin.css', __FILE__ ), array( 'thickbox' ) );
	}

	function _add_tax( $taxonomy ) {
		$this->_edit_tax( false, $taxonomy );
	}

	function _edit_tax( $term, $taxonomy ) {
		$image_value = '';

		if( $term && is_object( $term ) && $taxonomy )
			$image_value = get_option( 'content-type-' . $taxonomy . '-' . $term->term_id );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="color"><?php _e( 'Type', 'custom-content-types' , 'pixelpillow-custom-content-types') ?></label></th>
			<td>
				<input type="hidden" id="cct_term_image" name="cct_term_image" value="<?php echo $image_value; ?>" />

				<div id="cct_images_holder" class="cct_term_images">
				<?php
				foreach( self::$icons as $alt => $src ) {
					echo '<span id="cct-image-' . $alt . '" ' . $this->checked_helper( $image_value, $alt, 'class', 'active' ) . '>';
					echo '<img src="' . $src . '" alt="' . $alt . '" />';

					if( ! isset( $this->default_icons[ $alt ] ) ) {
						echo '<span class="delete" image-id="' . $alt . '" >' . __( 'Delete' , 'pixelpillow-custom-content-types') . '</span>';
					}

					echo '</span>';
				}
				?>
				</div>
				<div class="cct_term_images add">
					<span class="cct_term_images_add"><img src="<?php echo plugins_url( '/images/add.png', __FILE__ ) ?>" alt="<?php _e('Add image', 'pixelpillow-custom-content-types') ?>" /><?php _e('Add image', 'pixelpillow-custom-content-types') ?></span>
				</div>
			</td>
		</tr>
		<?php
	}

	function _save_tax( $term_id, $taxonomy_id, $taxonomy ) {
		if( isset( $_POST['cct_term_image'] ) && strpos( $taxonomy, 'content_type_' ) === 0 ) {
			update_option( 'content-type-' . $taxonomy . '-' . $term_id, esc_attr( $_POST['cct_term_image'] ) );
		}
	}


	function taxonomy_table_columns( $columns ) {
		$columns['image'] = __('Image', 'pixelpillow-custom-content-types');

		return $columns;
	}

	function taxonomy_table_column( $value, $column, $term_id ) {
		if( 'image' == $column ) {
			$image_value = get_option( 'content-type-' . $this->taxonomy . '-' . $term_id );

			if( isset( self::$icons[ $image_value ] ) ) {
				return '<img class="term_image" src="' . self::$icons[ $image_value ] . '" />';
			}
		}
	}



	private function checked_helper( $helper, $current, $type, $value, $echo = false ) {
		if ( (string) $helper === (string) $current )
			$result = " $type='$value'";
		else
			$result = '';

		if ( $echo )
			echo $result;

			return $result;
	}
}

new Custom_Content_Types_Taxonomy();