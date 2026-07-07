jQuery( function( $ ) {

	$( '#doaction, #doaction2' ).on( 'click', function( e ) {
		let actionselected = $(this).attr("id").substr(2);
		let action         = $('select[name="' + actionselected + '"]').val();

		if ( $.inArray(action, bazara_wc_order_ajax.bulk_actions) !== -1 ) {
			e.preventDefault();
			let template = action;
			let checked  = [];

			$('tbody th.check-column input[type="checkbox"]:checked').each(
				function() {
					checked.push($(this).val());
				}
			);
			
			if (!checked.length) {
				alert('You have to select order(s) first!');
				return;
			}
			
			let order_ids = checked.join('x');

			if (bazara_wc_order_ajax.ajaxurl.indexOf("?") != -1) {
				url = bazara_wc_order_ajax.ajaxurl+'&action=send_bazara_order&order_ids='+order_ids+'&bulk&_wpnonce='+wpo_wcpdf_ajax.nonce+'&type=order';
			} else {
				url = bazara_wc_order_ajax.ajaxurl+'?action=send_bazara_order&order_ids='+order_ids+'&bulk&_wpnonce='+wpo_wcpdf_ajax.nonce+'&type=order';
			}

			window.open(url,'_blank');
		}
	} );


} );