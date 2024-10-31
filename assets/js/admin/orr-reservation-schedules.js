( function( $ ) {

	$( '.orr-reservation-schedules' ).on( 'change', '.orr-reservation-schedule-table-closed input', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.orr-reservation-schedule' ).find( '.orr-enhanced-select' ).prop( 'disabled', true );
			$( this ).closest( '.online-restaurant-reservation-input-toggle' ).removeClass( 'online-restaurant-reservation-input-toggle--disabled' );
		} else {
			$( this ).closest( '.orr-reservation-schedule' ).find( '.orr-enhanced-select' ).prop( 'disabled', false );
			$( this ).closest( '.online-restaurant-reservation-input-toggle' ).addClass( 'online-restaurant-reservation-input-toggle--disabled' );
		}
	} );

	$( '.orr-reservation-schedules' ).on( 'click', '.orr-reservation-schedule-table-closed', function( e ) {
		e.preventDefault();

		var eventTarget = $( e.target );

		if ( eventTarget.is( 'input' ) ) {
			e.stopPropagation();
			return;
		}

		var $checkbox = $( this ).find( 'input[type="checkbox"]' );
		$checkbox.prop( 'checked', ! $checkbox.prop( 'checked' ) ).change();
	} );

	// ...and make disable on load.
	$( '.orr-reservation-schedule-closed' ).each( function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.orr-reservation-schedule' ).find( '.orr-enhanced-select' ).prop( 'disabled', true );
		}
	} );

})( jQuery );
