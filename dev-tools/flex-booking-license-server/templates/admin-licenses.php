<?php
/**
 * Admin licenses list and detail.
 *
 * @package FlexBookingLicenseServer
 *
 * @var FBLS_License_Repository $repo
 * @var array<string,mixed>|null $license
 * @var array<int,array<string,mixed>> $activations
 * @var array{items:array<int,array<string,mixed>>,total:int} $page_data
 * @var string $search
 * @var int $paged
 * @var string $notice
 */

defined( 'ABSPATH' ) || exit;

$api_url = rest_url( 'flex-booking/v1/license' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Flex Booking Licenses', 'flex-booking-license-server' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( 'created' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License created.', 'flex-booking-license-server' ); ?></p></div>
	<?php elseif ( 'updated' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License updated.', 'flex-booking-license-server' ); ?></p></div>
	<?php elseif ( 'deleted' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License deleted.', 'flex-booking-license-server' ); ?></p></div>
	<?php elseif ( 'activation_removed' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Site activation removed.', 'flex-booking-license-server' ); ?></p></div>
	<?php endif; ?>

	<div class="card" style="max-width:720px;padding:12px 16px;margin:16px 0;">
		<p class="description" style="margin:0;">
			<strong><?php esc_html_e( 'API endpoint:', 'flex-booking-license-server' ); ?></strong>
			<code><?php echo esc_html( $api_url ); ?></code>
		</p>
		<p class="description" style="margin:8px 0 0;">
			<?php esc_html_e( 'Flex Booking plugin clients call this URL when activating keys under Settings → License.', 'flex-booking-license-server' ); ?>
		</p>
	</div>

	<?php if ( $license ) : ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=fbls-licenses' ) ); ?>">&larr; <?php esc_html_e( 'All licenses', 'flex-booking-license-server' ); ?></a></p>

		<h2><?php esc_html_e( 'License details', 'flex-booking-license-server' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'License key', 'flex-booking-license-server' ); ?></th>
				<td><code style="font-size:15px;user-select:all;"><?php echo esc_html( (string) $license['license_key'] ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Activations', 'flex-booking-license-server' ); ?></th>
				<td>
					<?php
					$count = $repo->count_activations( (int) $license['id'] );
					$limit = (int) $license['activation_limit'];
					echo esc_html( $count . ( $limit > 0 ? ' / ' . $limit : ' / ∞' ) );
					?>
				</td>
			</tr>
		</table>

		<form method="post" action="">
			<?php wp_nonce_field( 'fbls_admin', 'fbls_nonce' ); ?>
			<input type="hidden" name="fbls_action" value="update" />
			<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>" />
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="fbls_customer_email"><?php esc_html_e( 'Customer email', 'flex-booking-license-server' ); ?></label></th>
					<td><input class="regular-text" type="email" name="customer_email" id="fbls_customer_email" value="<?php echo esc_attr( (string) $license['customer_email'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fbls_status"><?php esc_html_e( 'Status', 'flex-booking-license-server' ); ?></label></th>
					<td>
						<select name="status" id="fbls_status">
							<option value="active" <?php selected( $license['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'flex-booking-license-server' ); ?></option>
							<option value="suspended" <?php selected( $license['status'], 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'flex-booking-license-server' ); ?></option>
							<option value="revoked" <?php selected( $license['status'], 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'flex-booking-license-server' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="fbls_activation_limit"><?php esc_html_e( 'Activation limit', 'flex-booking-license-server' ); ?></label></th>
					<td>
						<input type="number" min="0" name="activation_limit" id="fbls_activation_limit" value="<?php echo esc_attr( (string) $license['activation_limit'] ); ?>" />
						<p class="description"><?php esc_html_e( '0 = unlimited sites.', 'flex-booking-license-server' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="fbls_expires_days"><?php esc_html_e( 'Extend expiry', 'flex-booking-license-server' ); ?></label></th>
					<td>
						<select name="expires_days" id="fbls_expires_days">
							<option value="keep"><?php esc_html_e( 'Keep current expiry', 'flex-booking-license-server' ); ?></option>
							<option value="lifetime"><?php esc_html_e( 'Lifetime (no expiry)', 'flex-booking-license-server' ); ?></option>
							<option value="365"><?php esc_html_e( '1 year from today', 'flex-booking-license-server' ); ?></option>
							<option value="30"><?php esc_html_e( '30 days from today', 'flex-booking-license-server' ); ?></option>
						</select>
						<?php if ( ! empty( $license['expires_at'] ) && '0000-00-00 00:00:00' !== $license['expires_at'] ) : ?>
							<p class="description"><?php esc_html_e( 'Current expiry:', 'flex-booking-license-server' ); ?> <?php echo esc_html( gmdate( 'Y-m-d', strtotime( (string) $license['expires_at'] . ' UTC' ) ) ); ?></p>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'Current: Lifetime', 'flex-booking-license-server' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="fbls_notes"><?php esc_html_e( 'Notes', 'flex-booking-license-server' ); ?></label></th>
					<td><textarea class="large-text" rows="3" name="notes" id="fbls_notes"><?php echo esc_textarea( (string) $license['notes'] ); ?></textarea></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save license', 'flex-booking-license-server' ) ); ?>
		</form>

		<form method="post" action="" style="margin-top:1em;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this license permanently?', 'flex-booking-license-server' ) ); ?>');">
			<?php wp_nonce_field( 'fbls_admin', 'fbls_nonce' ); ?>
			<input type="hidden" name="fbls_action" value="delete" />
			<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>" />
			<?php submit_button( __( 'Delete license', 'flex-booking-license-server' ), 'delete' ); ?>
		</form>

		<h2><?php esc_html_e( 'Active sites', 'flex-booking-license-server' ); ?></h2>
		<?php if ( empty( $activations ) ) : ?>
			<p><?php esc_html_e( 'No sites have activated this key yet.', 'flex-booking-license-server' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Site URL', 'flex-booking-license-server' ); ?></th>
						<th><?php esc_html_e( 'Plugin version', 'flex-booking-license-server' ); ?></th>
						<th><?php esc_html_e( 'Activated', 'flex-booking-license-server' ); ?></th>
						<th><?php esc_html_e( 'Last seen', 'flex-booking-license-server' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $activations as $act ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( (string) $act['site_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $act['site_url'] ); ?></a></td>
							<td><?php echo esc_html( (string) $act['plugin_version'] ); ?></td>
							<td><?php echo esc_html( (string) $act['activated_at'] ); ?></td>
							<td><?php echo esc_html( (string) $act['last_seen_at'] ); ?></td>
							<td>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'fbls_admin', 'fbls_nonce' ); ?>
									<input type="hidden" name="fbls_action" value="delete_activation" />
									<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>" />
									<input type="hidden" name="activation_id" value="<?php echo esc_attr( (string) $act['id'] ); ?>" />
									<button type="submit" class="button button-small"><?php esc_html_e( 'Remove', 'flex-booking-license-server' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php else : ?>

		<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
			<div class="card" style="flex:1;min-width:280px;max-width:420px;padding:16px;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Generate license', 'flex-booking-license-server' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'fbls_admin', 'fbls_nonce' ); ?>
					<input type="hidden" name="fbls_action" value="create" />
					<p>
						<label for="fbls_new_email"><strong><?php esc_html_e( 'Customer email', 'flex-booking-license-server' ); ?></strong></label><br />
						<input class="regular-text" type="email" name="customer_email" id="fbls_new_email" />
					</p>
					<p>
						<label for="fbls_new_limit"><strong><?php esc_html_e( 'Activation limit', 'flex-booking-license-server' ); ?></strong></label><br />
						<input type="number" min="0" name="activation_limit" id="fbls_new_limit" value="1" style="width:80px;" />
						<span class="description"><?php esc_html_e( 'sites (0 = unlimited)', 'flex-booking-license-server' ); ?></span>
					</p>
					<p>
						<label for="fbls_new_expires"><strong><?php esc_html_e( 'Validity', 'flex-booking-license-server' ); ?></strong></label><br />
						<select name="expires_days" id="fbls_new_expires">
							<option value="lifetime"><?php esc_html_e( 'Lifetime', 'flex-booking-license-server' ); ?></option>
							<option value="365"><?php esc_html_e( '1 year', 'flex-booking-license-server' ); ?></option>
							<option value="30"><?php esc_html_e( '30 days', 'flex-booking-license-server' ); ?></option>
						</select>
					</p>
					<p>
						<label for="fbls_new_notes"><strong><?php esc_html_e( 'Notes (optional)', 'flex-booking-license-server' ); ?></strong></label><br />
						<textarea class="large-text" rows="2" name="notes" id="fbls_new_notes"></textarea>
					</p>
					<?php submit_button( __( 'Generate key', 'flex-booking-license-server' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<div style="flex:2;min-width:320px;">
				<form method="get" style="margin-bottom:12px;">
					<input type="hidden" name="page" value="fbls-licenses" />
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search key or email…', 'flex-booking-license-server' ); ?>" />
					<?php submit_button( __( 'Search', 'flex-booking-license-server' ), 'secondary', '', false ); ?>
				</form>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'License key', 'flex-booking-license-server' ); ?></th>
							<th><?php esc_html_e( 'Email', 'flex-booking-license-server' ); ?></th>
							<th><?php esc_html_e( 'Status', 'flex-booking-license-server' ); ?></th>
							<th><?php esc_html_e( 'Sites', 'flex-booking-license-server' ); ?></th>
							<th><?php esc_html_e( 'Created', 'flex-booking-license-server' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $page_data['items'] ) ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No licenses yet. Generate one above.', 'flex-booking-license-server' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $page_data['items'] as $row ) : ?>
								<?php
								$act_count = $repo->count_activations( (int) $row['id'] );
								$limit     = (int) $row['activation_limit'];
								$detail    = add_query_arg( array( 'page' => 'fbls-licenses', 'license_id' => (int) $row['id'] ), admin_url( 'admin.php' ) );
								?>
								<tr>
									<td><a href="<?php echo esc_url( $detail ); ?>"><code><?php echo esc_html( (string) $row['license_key'] ); ?></code></a></td>
									<td><?php echo esc_html( (string) $row['customer_email'] ); ?></td>
									<td><?php echo esc_html( ucfirst( (string) $row['status'] ) ); ?></td>
									<td><?php echo esc_html( $act_count . ( $limit > 0 ? '/' . $limit : '' ) ); ?></td>
									<td><?php echo esc_html( substr( (string) $row['created_at'], 0, 10 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<?php
				$total_pages = (int) ceil( $page_data['total'] / 20 );
				if ( $total_pages > 1 ) :
					?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo wp_kses_post(
								paginate_links(
									array(
										'base'    => add_query_arg( 'paged', '%#%' ),
										'format'  => '',
										'current' => $paged,
										'total'   => $total_pages,
									)
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>
</div>
