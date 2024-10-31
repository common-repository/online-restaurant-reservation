jQuery( function( $ ) {

	/**
	 * Reservation Data Panel
	 */
	var orr_meta_boxes_reservation = {
		init: function() {
			this.init_datepicker();

			// Edit details.
			$( 'a.edit_details' ).click( this.edit_details );
		},
		init_datepicker: function() {
			$( '.date-picker-field, .date-picker' ).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				minDate: 0,
				maxDate: '+1m +1w',
				gotoCurrent: true,
				showButtonPanel: true
			});
		},
		edit_details: function( e ) {
			e.preventDefault();

			var $this          = $( this ),
				$wrapper       = $this.closest( '.reservation_data_column' ),
				$edit_details  = $wrapper.find( 'div.edit_details' ),
				$details       = $wrapper.find( 'div.details' );

			$details.hide();
			$this.parent().find( 'a' ).toggle();

			$edit_details.show();
		}
	};

	orr_meta_boxes_reservation.init();
});
