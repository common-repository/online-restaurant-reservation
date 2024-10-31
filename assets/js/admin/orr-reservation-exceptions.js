/* global reservationExceptionsLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
	$( function() {
		var $table          = $( '.orr-reservation-exceptions' ),
			$tbody          = $( '.orr-reservation-exception-rows' ),
			$save_button    = $( '.orr-reservation-exception-save' ),
			$row_template   = wp.template( 'orr-reservation-exception-row' ),
			$blank_template = wp.template( 'orr-reservation-exception-row-blank' ),

			// Backbone model
			ReservationException     = Backbone.Model.extend({
				changes: {},
				logChanges: function( changedRows ) {
					var changes = this.changes || {};

					_.each( changedRows, function( row, id ) {
						changes[ id ] = _.extend( changes[ id ] || { exception_id : id }, row );
					} );

					this.changes = changes;
					this.trigger( 'change:exceptions' );
				},
				discardChanges: function( id ) {
					var changes      = this.changes || {},
						set_position = null,
						exceptions   = _.indexBy( this.get( 'exceptions' ), 'exception_id' );

					// Find current set position if it has moved since last save
					if ( changes[ id ] && changes[ id ].exception_order !== undefined ) {
						set_position = changes[ id ].exception_order;
					}

					// Delete all changes
					delete changes[ id ];

					// If the position was set, and this exception does exist in DB, set the position again so the changes are not lost.
					if ( set_position !== null && exceptions[ id ] && exceptions[ id ].exception_order !== set_position ) {
						changes[ id ] = _.extend( changes[ id ] || {}, { exception_id : id, exception_order : set_position } );
					}

					this.changes = changes;

					// No changes? Disable save button.
					if ( 0 === _.size( this.changes ) ) {
						reservationExceptionView.clearUnloadConfirmation();
					}
				},
				save: function() {
					if ( _.size( this.changes ) ) {
						$.post( ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=online_restaurant_reservation_exceptions_save_changes', {
							orr_reservation_exceptions_nonce : data.orr_reservation_exceptions_nonce,
							changes                 : this.changes
						}, this.onSaveResponse, 'json' );
					} else {
						reservationException.trigger( 'saved:exceptions' );
					}
				},
				onSaveResponse: function( response, textStatus ) {
					if ( 'success' === textStatus ) {
						if ( response.success ) {
							reservationException.set( 'exceptions', response.data.reservation_exceptions );
							reservationException.trigger( 'change:exceptions' );
							reservationException.changes = {};
							reservationException.trigger( 'saved:exceptions' );
						} else {
							window.alert( data.strings.save_failed );
						}
					}
				}
			} ),

			// Backbone view
			ReservationExceptionView = Backbone.View.extend({
				rowTemplate: $row_template,
				initialize: function() {
					this.listenTo( this.model, 'change:exceptions', this.setUnloadConfirmation );
					this.listenTo( this.model, 'saved:exceptions', this.clearUnloadConfirmation );
					this.listenTo( this.model, 'saved:exceptions', this.render );
					$tbody.on( 'change', { view: this }, this.updateModelOnChange );
					$tbody.on( 'sortupdate', { view: this }, this.updateModelOnSort );
					$( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
					$save_button.on( 'click', { view: this }, this.onSubmit );
					$( document.body ).on( 'click', '.orr-reservation-exception-add', { view: this }, this.onAddNewRow );
				},
				block: function() {
					$( this.el ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				},
				unblock: function() {
					$( this.el ).unblock();
				},
				render: function() {
					var exceptions = _.indexBy( this.model.get( 'exceptions' ), 'exception_id' ),
						view       = this;

					this.$el.empty();
					this.unblock();

					if ( _.size( exceptions ) ) {
						// Sort exceptions
						exceptions = _( exceptions )
							.chain()
							.sortBy( function ( exception ) { return parseInt( exception.exception_id, 10 ); } )
							.sortBy( function ( exception ) { return parseInt( exception.exception_order, 10 ); } )
							.value();


						// Populate $tbody with the current exceptions
						$.each( exceptions, function( id, rowData ) {
							if ( 'yes' === rowData.is_closed ) {
								rowData.closed_icon = '<span class="online-restaurant-reservation-input-toggle online-restaurant-reservation-input-toggle--enabled">' + data.strings.yes + '</span>';
							} else {
								rowData.closed_icon = '<span class="online-restaurant-reservation-input-toggle online-restaurant-reservation-input-toggle--disabled">' + data.strings.no + '</span>';
							}

							view.renderRow( rowData );
						} );

					} else {
						view.$el.append( $blank_template );
					}

					view.initRows();
				},
				renderRow: function( rowData ) {
					var view = this;
					view.$el.append( view.rowTemplate( rowData ) );
					view.initRow( rowData );
				},
				initRow: function( rowData ) {
					var view = this;
					var $tr = view.$el.find( 'tr[data-id="' + rowData.exception_id + '"]');

					// Trigger select2
					$( document.body ).trigger( 'orr-enhanced-select-init' );

					// Support select boxes
					$tr.find( 'select' ).each( function() {
						var attribute = $( this ).data( 'attribute' );
						$( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );

						if ( $( this ).hasClass( 'orr-enhanced-select' ) ) {
							$( this ).val( rowData[ attribute ] ).trigger( 'change' );
							reservationExceptionView.clearUnloadConfirmation();
						}
					} );

					// Disable select2 if closed
					if ( 'yes' === $tr.data( 'closed' ) ) {
						$tr.find( '.orr-enhanced-select' ).prop( 'disabled', true );
						$tr.find( '.orr-reservation-exception-time' ).addClass( 'orr-closed' );
					}

					// Support range datepicker
					var dates = $tr.find( '.range_datepicker' ).datepicker({
						changeMonth: true,
						changeYear: true,
						defaultDate: '',
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 1,
						minDate: 0,
						maxDate: '+1Y',
						showButtonPanel: true,
						showOn: 'focus',
						buttonImageOnly: true,
						onSelect: function() {
							var option = $( this ).is( '.from' ) ? 'minDate' : 'maxDate',
								date   = $( this ).datepicker( 'getDate' );

							dates.not( this ).datepicker( 'option', option, date );
							dates.trigger( 'change' );
						}
					} );

					// Make the rows function
					$tr.find( '.view' ).show();
					$tr.find( '.edit' ).hide();
					$tr.find( '.orr-reservation-exception-edit' ).on( 'click', { view: this }, this.onEditRow );
					$tr.find( '.orr-reservation-exception-delete' ).on( 'click', { view: this }, this.onDeleteRow );
					$tr.find( '.editing .orr-reservation-exception-edit' ).trigger( 'click' );
					$tr.find( '.orr-reservation-exception-cancel-edit' ).on( 'click', { view: this }, this.onCancelEditRow );
					$tr.find( '.orr-reservation-exception-closed a' ).on( 'click', { view: this }, this.onToggleClosed );
					$tr.find( '.orr-exception-date-range-toggle' ).on( 'click', { view: this }, this.onToggleRange );

					// Editing?
					if ( true === rowData.editing ) {
						$tr.addClass( 'editing' );
						$tr.find( '.orr-reservation-exception-edit' ).trigger( 'click' );
					}
				},
				initRows: function() {
					// Stripe
					if ( 0 === ( $( 'tbody.orr-reservation-exception-rows tr' ).length % 2 ) ) {
						$table.find( 'tbody.orr-reservation-exception-rows' ).next( 'tbody' ).find( 'tr' ).addClass( 'odd' );
					} else {
						$table.find( 'tbody.orr-reservation-exception-rows' ).next( 'tbody' ).find( 'tr' ).removeClass( 'odd' );
					}
					// Tooltips
					$( '#tiptip_holder' ).removeAttr( 'style' );
					$( '#tiptip_arrow' ).removeAttr( 'style' );
					$( '.tips' ).tipTip({ 'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });
				},
				onSubmit: function( event ) {
					event.data.view.block();
					event.data.view.model.save();
					event.preventDefault();
				},
				onAddNewRow: function( event ) {
					event.preventDefault();

					var view       = event.data.view,
						model      = view.model,
						exceptions = _.indexBy( model.get( 'exceptions' ), 'exception_id' ),
						changes    = {},
						size       = _.size( exceptions ),
						newRow     = _.extend( {}, data.default_reservation_exception, {
							exception_id: 'new-' + size + '-' + Date.now(),
							editing     : true,
							newRow      : true
						} );

					// Append at last row
					newRow.exception_order = 1 + _.max(
						_.pluck( exceptions, 'exception_order' ),
						function ( val ) {
							// Cast them all to integers, because strings compare funky. Sighhh.
							return parseInt( val, 10 );
						}
					);

					changes[ newRow.exception_id ] = newRow;

					model.logChanges( changes );
					view.renderRow( newRow );
					$( '.orr-reservation-exceptions-blank-state' ).remove();
				},
				onEditRow: function( event ) {
					event.preventDefault();
					$( this ).closest( 'tr' ).addClass( 'editing' );
					$( this ).closest( 'tr' ).find( '.view' ).hide();
					$( this ).closest( 'tr' ).find( '.edit' ).show();
					event.data.view.model.trigger( 'change:exceptions' );
				},
				onDeleteRow: function( event ) {
					var view         = event.data.view,
						model        = view.model,
						exceptions   = _.indexBy( model.get( 'exceptions' ), 'exception_id' ),
						changes      = {},
						exception_id = $( this ).closest( 'tr' ).data( 'id' );

					event.preventDefault();

					if ( exceptions[ exception_id ] ) {
						delete exceptions[ exception_id ];
						changes[ exception_id ] = _.extend( changes[ exception_id ] || {}, { deleted : 'deleted' } );
						model.set( 'exceptions', exceptions );
						model.logChanges( changes );
					}

					view.render();
					event.data.view.model.trigger( 'change:exceptions' );
				},
				onCancelEditRow: function( event ) {
					var view         = event.data.view,
						model        = view.model,
						row          = $( this ).closest( 'tr' ),
						exception_id = $( this ).closest( 'tr' ).data( 'id' ),
						exceptions   = _.indexBy( model.get( 'exceptions' ), 'exception_id' );

					event.preventDefault();
					model.discardChanges( exception_id );

					if ( exceptions[ exception_id ] ) {
						exceptions[ exception_id ].editing = false;
						row.after( view.rowTemplate( exceptions[ exception_id ] ) );
						view.initRow( exceptions[ exception_id ] );
					}

					row.remove();
				},
				onToggleClosed: function( event ) {
 					var view         = event.data.view,
						$target      = $( event.target ),
						model        = view.model,
						exceptions   = _.indexBy( model.get( 'exceptions' ), 'exception_id' ),
						exception_id = $target.closest( 'tr' ).data( 'id' ),
						closed       = $target.closest( 'tr' ).data( 'closed' ) === 'yes' ? 'no' : 'yes',
						changes      = {};

					event.preventDefault();

					if ( exceptions[ exception_id ] ) {
						closed = exceptions[ exception_id ].is_closed === 'yes' ? 'no' : 'yes';

						// Toggle button status.
						if ( 'yes' === closed ) {
							$( this ).closest( 'tr' ).find( '.orr-enhanced-select' ).prop( 'disabled', true );
							$( this ).closest( 'tr' ).find( '.orr-reservation-exception-time' ).addClass( 'orr-closed' );
							$( this ).find( '.online-restaurant-reservation-input-toggle' ).removeClass( 'online-restaurant-reservation-input-toggle--disabled' );
						} else {
							$( this ).closest( 'tr' ).find( '.orr-enhanced-select' ).prop( 'disabled', false );
							$( this ).closest( 'tr' ).find( '.orr-reservation-exception-time' ).removeClass( 'orr-closed' );
							$( this ).find( '.online-restaurant-reservation-input-toggle' ).addClass( 'online-restaurant-reservation-input-toggle--disabled' );
						}

						exceptions[ exception_id ].is_closed = closed;
						changes[ exception_id ] = _.extend( changes[ exception_id ] || {}, { is_closed : closed } );
						model.set( 'exceptions', exceptions );
						model.logChanges( changes );
					}
				},
				onToggleRange: function() {
					event.preventDefault();
					var $tr = $( this ).closest( 'tr' );
					$tr.find( '.orr-exception-date-range' ).show();
					$tr.find( '.orr-exception-date-range-toggle' ).hide();
				},
				setUnloadConfirmation: function() {
					this.needsUnloadConfirm = true;
					$save_button.prop( 'disabled', false );
				},
				clearUnloadConfirmation: function() {
					this.needsUnloadConfirm = false;
					$save_button.prop( 'disabled', true );
				},
				unloadConfirmation: function( event ) {
					if ( event.data.view.needsUnloadConfirm ) {
						event.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
				},
				updateModelOnChange: function( event ) {
					var model        = event.data.view.model,
						$target      = $( event.target ),
						exception_id = $target.closest( 'tr' ).data( 'id' ),
						attribute    = $target.data( 'attribute' ),
						value        = $target.val(),
						exceptions   = _.indexBy( model.get( 'exceptions' ), 'exception_id' ),
			 			changes      = {};

					if ( ! exceptions[ exception_id ] || exceptions[ exception_id ][ attribute ] !== value ) {
						if(typeof  changes[ exception_id ] === 'undefined') {
							changes[ exception_id ] = {};
						}
						changes[ exception_id ][ attribute ] = value;
					}



					model.logChanges( changes );
				},
				updateModelOnSort: function( event ) {
					var view         = event.data.view,
						model        = view.model,
						exceptions   = _.indexBy( model.get( 'exceptions' ), 'exception_id' ),
						changes      = {};

					_.each( exceptions, function( exception ) {
						var old_position = parseInt( exception.exception_order, 10 );
						var new_position = parseInt( $table.find( 'tr[data-id="' + exception.exception_id + '"]' ).index() + 1, 10 );

						if ( old_position !== new_position ) {
							exceptions[ exception.exception_id ].exception_order = new_position;
							changes[ exception.exception_id ] = _.extend( changes[ exception.exception_id ] || {}, { exception_order : new_position } );
						}
					} );

					if ( _.size( changes ) ) {
						model.logChanges( changes );
					}
				}
			} ),
			reservationException = new ReservationException({
				exceptions: data.exceptions
			} ),
			reservationExceptionView = new ReservationExceptionView({
				model:    reservationException,
				el:       $tbody
			} );

			reservationExceptionView.render();

			$tbody.sortable({
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: 'td.orr-reservation-exception-sort',
				scrollSensitivity: 40
			});
	});
})( jQuery, reservationExceptionsLocalizeScript, wp, ajaxurl );
