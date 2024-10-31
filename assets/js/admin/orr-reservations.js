/* global orr_reservations_params */
jQuery( function( $ ) {

	if ( typeof orr_reservations_params === 'undefined' ) {
		return false;
	}

	/**
	 * ReservationsTable class.
	 */
	var ReservationsTable = function() {
		$( document )
			.on( 'click', '.post-type-table_reservation .wp-list-table tbody td', this.onRowClick )
			.on( 'click', '.reservation-preview:not(.disabled)', this.onPreview );
	};

	/**
	 * Click a row.
	 */
	ReservationsTable.prototype.onRowClick = function( e ) {
		if ( $( e.target ).filter( 'a' ).length ) {
			return true;
		}

		var $row = $( this ).closest( 'tr' ),
			href = $row.find( 'a.reservation-view' ).attr( 'href' );

		if ( href.length ) {
			window.location = href;
		}
	};

	/**
	 * Preview a reservation.
	 */
	ReservationsTable.prototype.onPreview = function() {
		var $previewButton     = $( this ),
			$reservation_id    = $previewButton.data( 'reservation-id' );

		if ( $previewButton.data( 'reservation-data' ) ) {
			$( this ).ORRBackboneModal({
				template: 'orr-modal-view-reservation',
				variable : $previewButton.data( 'reservation-data' )
			});
		} else {
			$previewButton.addClass( 'disabled' );

			$.ajax({
				url:     orr_reservations_params.ajax_url,
				data:    {
					reservation_id : $reservation_id,
					action         : 'online_restaurant_reservation_get_reservation_details',
					security       : orr_reservations_params.preview_nonce
				},
				type:    'GET',
				success: function( response ) {
					$( '.reservation-preview' ).removeClass( 'disabled' );

					if ( response.success ) {
						$previewButton.data( 'reservation-data', response.data );

						$( this ).ORRBackboneModal({
							template: 'orr-modal-view-reservation',
							variable : response.data
						});
					}
				}
			});
		}
		return false;
	};

	/**
	 * Init ReservationsTable.
	 */
	new ReservationsTable();
} );
