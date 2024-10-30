jQuery(document).ready(function($) {
	var formfield = null;

	$('.cct_term_images span').not('.cct_term_images_add').not('.delete').click(function( evt ) {
		$('#cct_term_image').val( $('img', this).attr('alt') );
		$('.cct_term_images span').removeClass('active');
		$(this).addClass('active');
	});

	$(document).on("click", ".cct_term_images_add", function(evt){
		evt.preventDefault();
		window.send_to_editor_original = window.send_to_editor;

		formfield = this;
		tb_show( 'Selecteer afbeelding', 'media-upload.php?TB_iframe=true&post_id=0' );

		return false;
	});

	$(document).on("click", ".cct_term_images .delete", function(evt){
		evt.preventDefault();
		evt.stopPropagation();

		var object = this;

		var data = {
			action: 'cct_remove_image',
			image_id: $(this).attr('image-id'),
			taxonomy: $('input[name=taxonomy]').val()
		};

		$.post( ajaxurl, data, function(response) {
			if( response.success ) {
				$( object ).parent().remove();
			}
		});
	});

	window.send_to_editor = function(html) {
		var imgurl = $('img',html).attr('src');
		if( ! imgurl ) {
			alert( 'Kies een afbeelding' );
			return;
		}

		var imgClass = jQuery('img',html).attr('class');
		var imageID = imgClass.substring(imgClass.lastIndexOf('wp-image-')+9);


		$('#cct_term_image').val(imageID);
		$('.cct_term_images span').removeClass('active');
		$('#cct_images_holder').append('<span class="active"><img src="' + imgurl + '" alt="' + imageID + '" /><span class="delete" image-id="' + imageID + '" >Delete</span></span>');

		var data = {
			action: 'cct_add_image',
			image_id: imageID,
			taxonomy: $('input[name=taxonomy]').val()
		};

		$.post( ajaxurl, data, function(response) {
			
		});

		formfield = null;

		tb_remove();

		window.send_to_editor = window.send_to_editor_original;
	}
});