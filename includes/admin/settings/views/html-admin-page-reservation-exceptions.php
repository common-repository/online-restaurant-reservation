<?php
/**
 * Reservation exceptions admin
 *
 * @package Online_Restaurant_Reservation/Admin/Reservation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$time_range = orr_get_time_range();

?>

<h2>
	<?php esc_html_e( 'Reservation exceptions', 'online-restaurant-reservation' ); ?>
	<?php echo orr_help_tip( __( 'Reservation exceptions can be used to define special opening hours for holidays, events or other needs.', 'online-restaurant-reservation' ) ); // @codingStandardsIgnoreLine ?>
</h2>

<table class="orr-reservation-exceptions widefat">
	<thead>
		<tr>
			<th class="orr-reservation-exception-sort"><?php echo orr_help_tip( __( 'Drag and drop to re-order your custom exceptions.', 'online-restaurant-reservation' ) ); ?></th>
			<th class="orr-reservation-exception-name"><?php esc_html_e( 'Reservation exception', 'online-restaurant-reservation' ); ?></th>
			<th class="orr-reservation-exception-date"><?php esc_html_e( 'Date(s)', 'online-restaurant-reservation' ); ?></th>
			<th class="orr-reservation-exception-closed"><?php esc_html_e( 'Closed', 'online-restaurant-reservation' ); ?></th>
			<th class="orr-reservation-exception-time"><?php esc_html_e( 'Time range', 'online-restaurant-reservation' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="5">
				<button type="submit" name="save" class="button button-primary orr-reservation-exception-save" value="<?php esc_attr_e( 'Save exceptions', 'online-restaurant-reservation' ); ?>" disabled><?php esc_html_e( 'Save exceptions', 'online-restaurant-reservation' ); ?></button>
				<a class="button button-secondary orr-reservation-exception-add" href="#"><?php esc_html_e( 'Add new exception', 'online-restaurant-reservation' ); ?></a>
			</td>
		</tr>
	</tfoot>
	<tbody class="orr-reservation-exception-rows"></tbody>
</table>

<script type="text/html" id="tmpl-orr-reservation-exception-row-blank">
	<tr>
		<td class="orr-reservation-exceptions-blank-state" colspan="5">
			<p><?php esc_html_e( 'No reservation exceptions have been created.', 'online-restaurant-reservation' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-orr-reservation-exception-row">
	<tr data-id="{{ data.exception_id }}" data-closed="{{ data.is_closed }}">
		<td width="1%" class="orr-reservation-exception-sort"></td>
		<td class="orr-reservation-exception-name">
			<div class="view">
				{{ data.formatted_exception_name }}
				<div class="row-actions">
					<a class="orr-reservation-exception-edit" href="#"><?php esc_html_e( 'Edit', 'online-restaurant-reservation' ); ?></a> | <a href="#" class="orr-reservation-exception-delete"><?php esc_html_e( 'Remove', 'online-restaurant-reservation' ); ?></a>
				</div>
			</div>
			<div class="edit">
				<input type="text" name="exception_name[{{ data.exception_id }}]" data-attribute="exception_name" value="{{ data.exception_name }}" placeholder="<?php esc_attr_e( 'Reservation exception name', 'online-restaurant-reservation' ); ?>" />
				<div class="row-actions">
					<a class="orr-reservation-exception-cancel-edit" href="#"><?php esc_html_e( 'Cancel changes', 'online-restaurant-reservation' ); ?></a>
				</div>
			</div>
		</td>
		<td class="orr-reservation-exception-date">
			<div class="view">{{ data.start_date }}<# if ( data.start_date !== data.end_date ) { #> &mdash; {{ data.end_date }}<# } #></div>
			<div class="edit">
				<input type="text" class="range_datepicker from" name="start_date[{{ data.exception_id }}]" data-attribute="start_date" value="{{ data.start_date }}" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'online-restaurant-reservation' ); ?> YYYY-MM-DD" maxlength="10" pattern="<?php echo esc_attr( apply_filters( 'online_restaurant_reservation_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />
				<# if ( ! data.end_date || data.start_date === data.end_date ) { #>
					<a class="orr-exception-date-range-toggle" href="#"><?php esc_html_e( 'Range', 'online-restaurant-reservation' ); ?></a>
				<# } #>
				<input type="text" class="orr-exception-date-range range_datepicker to" name="end_date[{{ data.exception_id }}]" data-attribute="end_date" value="{{ data.end_date }}" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'online-restaurant-reservation' ); ?>  YYYY-MM-DD" maxlength="10" pattern="<?php echo esc_attr( apply_filters( 'online_restaurant_reservation_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />
			</div>
		</td>
		<td width="1%" class="orr-reservation-exception-closed"><a href="#">{{{ data.closed_icon }}}</a></td>
		<td class="orr-reservation-exception-time">
			<div class="orr-time-range">
				<select name="start_time[{{ data.exception_id }}]" data-attribute="start_time" class="orr-enhanced-select" data-placeholder="<?php echo esc_attr( 'Start time', 'online-restaurant-reservation' ); ?>" style="width: 45%;">
					<?php
					foreach ( $time_range as $time_key => $time ) {
						echo '<option value="' . esc_attr( $time_key ) . '">' . esc_html( $time ) . '</option>';
					}
					?>
				</select>
				<span class="divider">&mdash;</span>
				<select name="end_time[{{ data.exception_id }}]" data-attribute="end_time" class="orr-enhanced-select" data-placeholder="<?php echo esc_attr( 'End time', 'online-restaurant-reservation' ); ?>" style="width: 45%;">';
					<?php
					foreach ( $time_range as $time_key => $time ) {
						echo '<option value="' . esc_attr( $time_key ) . '">' . esc_html( $time ) . '</option>';
					}
					?>
				</select>
			</div>
			<p class="orr-time-range-closed"><?php esc_html_e( 'All day closed.', 'online-restaurant-reservation' ); ?></p>
		</td>
	</tr>
</script>
