/* global orr_reservation_params */
jQuery(function ( $ ) {

	// orr_reservation_params is required to continue, ensure the object exists.
	if ( typeof orr_reservation_params === 'undefined' ) {
		return false;
	}

	var orr_reservation_form = {
		$reservation_form: $( 'form.reservation' ),
		init: function () {
			this.init_datepicker();

			// Inline validation.
			this.$reservation_form.on( 'input validate change', '.input-text, select, input:checkbox', this.validate_field );

			$( document.body ).on( 'click', '.reservation-notes-toggle', this.onToggleNotes );
		},
		init_datepicker: function () {
			$( '.date-picker-field, .date-picker' ).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				minDate: 0,
				maxDate: '+1m +1w',
				gotoCurrent: true,
				beforeShowDay: this.disable_days,
				onSelect: function ( date ) {
					orr_reservation_form.on_change_date( date );
				}
			});
		},
		on_change_date: function ( date ) {
			var data = {
				'security': orr_reservation_params.date_changed_nonce,
				'date': date,
				'action': 'online_restaurant_reservation_on_date_select'
			};

			var loader = $( '<button class="orr-loader"/>' ).text( 'Loading....' ).css({
				'width': '100%',
				'background': '#f9f9f9',
				'color': '#000',
				'text-align': 'left'
			});

			$.ajax({
				url: orr_reservation_params.ajax_url,
				data: data,
				type: 'POST',
				beforeSend: function () {
					$('#reservation_time').hide();
					$('#reservation_time_field').append(loader);
				},
				success: function ( response ) {
					if ( typeof response.success === 'boolean' && response.success === true ) {
						var time_slot        = response.data.time_slot;
						var reservation_time = $( '#reservation_time' ).clone().removeAttr('style');

						reservation_time.html( '' );

						for ( var slot_key in time_slot ) {
							reservation_time.append('<option value="' + slot_key + '">' + time_slot[ slot_key ] + '</option>');
						}

						if ( reservation_time.find( 'option' ).length < 1 ) {
							reservation_time.append( '<option value="-1">Time slot not available</option>' );
						}

						$( '#reservation_time' ).remove();
						$( '#reservation_time_field' ).find( '.orr-loader' ).remove();
						$( '#reservation_time_field' ).append( reservation_time );
					}
				}
			});
		},
		disable_days: function ( date ) {
			var day = date.getDay();

			// Closed exceptional day/range highlight.
			if ( orr_reservation_form.is_date_closed_by_exception( date ) ) {
				return [ false, 'ui-datepicker-closed exceptional-days', 'Closed exceptional' ];
			}

			// Closed exceptional day/range highlight.
			if ( orr_reservation_form.is_date_opened_by_exception(date) ) {
				return [ true, 'ui-datepicker-opened', 'Opened exceptional' ];
			}

			// Closed weekday highlight.
			if ( $.inArray(day, orr_reservation_params.closed_days) !== -1 ) {
				return [ false, 'ui-datepicker-closed', 'Closed' ];
			}

			return [ true, 'ui-datepicker-opened', 'Open' ];
		},
		is_date_closed_by_exception: function ( date ) {
			var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
			var current_date = y + '-' + ( m + 1 ) + '-' + d;
			var exceptional_date_ranges = orr_reservation_params.exceptional_closed_days;
			var status = false;

			for ( var ex_start = 0; ex_start < exceptional_date_ranges.length; ex_start++ ) {
				var start_date = exceptional_date_ranges[ ex_start ].start_date;
				var end_date = exceptional_date_ranges[ ex_start ].end_date;

				status = orr_reservation_form.is_date_between( current_date, start_date, end_date );

				if ( status ) {
					break;
				}
			}

			return status;
		},
		is_date_opened_by_exception: function ( date ) {
			var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
			var current_date = y + '-' + ( m + 1 ) + '-' + d;
			var exceptional_date_ranges = orr_reservation_params.exceptional_opened_days;
			var status = false;

			for ( var ex_start = 0; ex_start < exceptional_date_ranges.length; ex_start++ ) {
				var start_date = exceptional_date_ranges[ ex_start ].start_date;
				var end_date = exceptional_date_ranges[ ex_start ].end_date;
				status = orr_reservation_form.is_date_between( current_date, start_date, end_date );

				if ( status ) {
					break;
				}
			}

			return status;
		},
		is_date_between: function ( check_date, start_date, end_date ) {
			var date1      = start_date.split( '-' );
			var date2      = end_date.split( '-' );
			var check      = check_date.split( '-' );
			var from       = new Date( date1[0], parseInt( date1[1], 10 ) - 1, date1[2] );  // -1 because months are from 0 to 11
			var to         = new Date( date2[0], parseInt( date2[1], 10 ) - 1, date2[2] );
			var date_check = new Date( check[0], parseInt( check[1], 10 ) - 1, check[2] );

			return ( date_check >= from && date_check <= to );
		},
		validate_field: function ( e ) {
			var $this             = $( this ),
				$parent           = $this.closest( '.form-row' ),
				validated         = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email    = $parent.is( '.validate-email' ),
				event_type        = e.type;

			if ( 'input' === event_type ) {
				$parent.removeClass( 'orr-invalid orr-invalid-required-field orr-invalid-email orr-validated' );
			}

			if ( 'validate' === event_type || 'change' === event_type ) {

				if ( validate_required ) {
					if ( 'checkbox' === $this.attr( 'type' ) && ! $this.is( ':checked' ) ) {
						$parent.removeClass( 'orr-validated' ).addClass( 'orr-invalid orr-invalid-required-field' );
						validated = false;
					} else if ( $this.val() === '' ) {
						$parent.removeClass( 'orr-validated' ).addClass( 'orr-invalid orr-invalid-required-field' );
						validated = false;
					}
				}

				if ( validate_email ) {
					if ( $this.val() ) {
						/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
						var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

						if ( ! pattern.test( $this.val()  ) ) {
							$parent.removeClass( 'orr-validated' ).addClass( 'orr-invalid orr-invalid-email' );
							validated = false;
						}
					}
				}

				if ( validated ) {
					$parent.removeClass( 'orr-invalid orr-invalid-required-field orr-invalid-email' ).addClass( 'orr-validated' );
				}
			}
		},
		onToggleNotes: function ( event ) {
			event.preventDefault();
			var $fields = $( this ).closest( 'div' );
			$fields.find( '.reservation-notes' ).show();
			$fields.find( '.reservation-notes-toggle' ).hide();
		}
	};

	orr_reservation_form.init();
});
