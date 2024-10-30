jQuery(document).ready(function($) {

	jQuery('.tags-utilizar').click(function(elemento) {

		var valor = jQuery(this).data('tag');
		
		jQuery(this).closest('td').find('input').val(valor);

	});

});