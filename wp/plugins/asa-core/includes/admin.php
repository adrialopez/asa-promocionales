<?php
/** Panel de leads, reporte por canal y ajustes. */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_menu_page( 'Leads ASA', 'Leads ASA', 'manage_asa_leads', 'asa-leads', 'asa_page_leads', 'dashicons-groups', 3 );
	add_submenu_page( 'asa-leads', 'Leads', 'Leads', 'manage_asa_leads', 'asa-leads', 'asa_page_leads' );
	add_submenu_page( 'asa-leads', 'Reporte por canal', 'Reporte', 'manage_asa_leads', 'asa-report', 'asa_page_report' );
	add_submenu_page( 'asa-leads', 'Ajustes ASA', 'Ajustes y catálogo', 'manage_options', 'asa-settings', 'asa_page_settings' );
} );

add_action( 'admin_head', function () {
	echo '<style>.asa-badge{display:inline-block;padding:2px 9px;border-radius:99px;background:#e5ece9;font-weight:600}.asa-s-convertido{background:#dcefb0}.asa-s-perdido{background:#f6dada}.asa-s-nuevo{background:#fbeec0}.asa-s-clic{background:#eee;color:#666}
	.asa-cols{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:20px}.asa-box{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;margin-bottom:16px}.asa-box dt{font-weight:600}.asa-box dd{margin:0 0 8px}.asa-box label{display:block;margin-bottom:12px;font-weight:600}.asa-box label>*{display:block;width:100%;margin-top:4px}
	.asa-kpis{display:flex;gap:12px;flex-wrap:wrap;margin:16px 0}.asa-kpis div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 18px;min-width:130px}.asa-kpis b{display:block;font-size:22px;color:#004035}@media(max-width:900px){.asa-cols{grid-template-columns:1fr}}</style>';
} );

function asa_money( $n ) {
	return '$' . number_format( (float) $n, 2 );
}
function asa_badge( $status ) {
	return '<span class="asa-badge asa-s-' . esc_attr( $status ) . '">' . esc_html( 'clic' === $status ? 'Clic WhatsApp' : ( ASA_STATUSES[ $status ] ?? $status ) ) . '</span>';
}
function asa_options( array $options, $selected, $all = '' ) {
	$html = $all ? '<option value="">' . esc_html( $all ) . '</option>' : '';
	foreach ( $options as $k => $v ) {
		$html .= '<option value="' . esc_attr( $k ) . '"' . selected( (string) $selected, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	return $html;
}

/** Filtros compartidos por el listado y las exportaciones. */
function asa_where() {
	global $wpdb;
	$w = array( '1=1' );
	foreach ( array( 'status', 'channel', 'type' ) as $f ) {
		if ( ! empty( $_GET[ $f ] ) ) {
			$w[] = $wpdb->prepare( "$f = %s", sanitize_text_field( wp_unslash( $_GET[ $f ] ) ) );
		}
	}
	if ( empty( $_GET['status'] ) && empty( $_GET['s'] ) ) {
		$w[] = "status <> 'clic'";
	}
	if ( ! empty( $_GET['s'] ) ) {
		$like = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%';
		$w[]  = $wpdb->prepare( '(folio LIKE %s OR name LIKE %s OR company LIKE %s OR email LIKE %s OR phone LIKE %s)', $like, $like, $like, $like, $like );
	}
	return implode( ' AND ', $w );
}

function asa_page_leads() {
	global $wpdb;
	if ( isset( $_GET['lead'] ) ) {
		asa_page_lead( (int) $_GET['lead'] );
		return;
	}
	$t     = asa_table();
	$paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
	$where = asa_where();
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $where" );
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE " . str_replace( '%', '%%', $where ) . ' ORDER BY created DESC LIMIT 30 OFFSET %d', ( $paged - 1 ) * 30 ) );
	$clics = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE status='clic'" );
	$get   = fn( $k ) => sanitize_text_field( wp_unslash( $_GET[ $k ] ?? '' ) );
	$query = array_filter( array( 'status' => $get( 'status' ), 'channel' => $get( 'channel' ), 'type' => $get( 'type' ), 's' => $get( 's' ) ) );
	$csv   = fn( $kind ) => wp_nonce_url( add_query_arg( array_merge( $query, array( 'action' => 'asa_export', 'kind' => $kind ) ), admin_url( 'admin-post.php' ) ), 'asa_export' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Leads</h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=asa-leads&lead=0' ) ); ?>">Registrar lead manual</a>
		<a class="page-title-action" href="<?php echo esc_url( $csv( 'leads' ) ); ?>">Exportar CSV</a>
		<a class="page-title-action" href="<?php echo esc_url( $csv( 'google' ) ); ?>">Conversiones para Google Ads</a>
		<form method="get" style="margin:14px 0">
			<input type="hidden" name="page" value="asa-leads">
			<select name="status"><?php echo asa_options( ASA_STATUSES, $get( 'status' ), 'Todos los estados confirmados' ); ?></select>
			<select name="channel"><?php echo asa_options( array_combine( ASA_CHANNELS, ASA_CHANNELS ), $get( 'channel' ), 'Todos los orígenes' ); ?></select>
			<select name="type"><?php echo asa_options( ASA_TYPES, $get( 'type' ), 'Todos los tipos' ); ?></select>
			<input type="search" name="s" value="<?php echo esc_attr( $get( 's' ) ); ?>" placeholder="Folio, nombre, empresa">
			<button class="button">Filtrar</button>
			<?php if ( $clics ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=asa-leads&status=clic' ) ); ?>" style="margin-left:10px"><?php echo (int) $clics; ?> clics de WhatsApp sin confirmar</a><?php endif; ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th>Folio</th><th>Fecha</th><th>Contacto</th><th>Pidió</th><th>Origen</th><th>Estado</th><th>Venta</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="7">Todavía no hay leads con estos filtros.</td></tr><?php endif; ?>
			<?php foreach ( $rows as $r ) :
				$items = (array) json_decode( (string) $r->items, true ); ?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=asa-leads&lead=' . $r->id ) ); ?>"><strong><?php echo esc_html( $r->folio ); ?></strong></a><br><small><?php echo esc_html( ASA_TYPES[ $r->type ] ?? $r->type ); ?></small></td>
					<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $r->created ) ); ?></td>
					<td><?php echo esc_html( $r->name ?: 'Sin datos todavía' ); ?><br><small><?php echo esc_html( $r->company ); ?></small></td>
					<td><?php echo $items ? esc_html( $items[0]['name'] . ( count( $items ) > 1 ? ' y ' . ( count( $items ) - 1 ) . ' más' : '' ) ) : esc_html( wp_trim_words( (string) $r->message, 8 ) ); ?></td>
					<td><?php echo esc_html( $r->channel ); ?><br><small><?php echo esc_html( $r->campaign ?: $r->source ); ?></small></td>
					<td><?php echo asa_badge( $r->status ); ?></td>
					<td><?php echo $r->amount > 0 ? esc_html( asa_money( $r->amount ) ) : ''; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => max( 1, (int) ceil( $total / 30 ) ) ) ) . '</div></div>'; ?>
	</div>
	<?php
}

function asa_page_lead( $id ) {
	global $wpdb;
	$r = $id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . asa_table() . ' WHERE id=%d', $id ) ) : null;
	if ( $id && ! $r ) {
		echo '<div class="wrap"><p>Lead no encontrado.</p></div>';
		return;
	}
	$v       = fn( $k, $d = '' ) => $r->$k ?? $d;
	$items   = (array) json_decode( (string) $v( 'items' ), true );
	$history = (array) json_decode( (string) $v( 'history' ), true );
	?>
	<div class="wrap">
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=asa-leads' ) ); ?>">&larr; Volver a los leads</a></p>
		<h1><?php echo $r ? esc_html( $r->folio . ' · ' . ( $r->company ?: $r->name ?: 'Sin datos' ) ) . ' ' . asa_badge( $r->status ) : 'Nuevo lead manual'; ?></h1>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success"><p>Cambios guardados.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['need_amount'] ) ) : ?><div class="notice notice-error"><p>Para marcar como Convertido escribe el importe de la venta.</p></div><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="asa-cols">
			<input type="hidden" name="action" value="asa_save_lead"><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<?php wp_nonce_field( 'asa_save_lead_' . $id ); ?>
			<div>
				<div class="asa-box"><h2>Contacto</h2>
					<?php foreach ( array( 'name' => 'Nombre', 'company' => 'Empresa', 'email' => 'Correo', 'phone' => 'Teléfono', 'state' => 'Estado de la República' ) as $k => $label ) : ?>
						<label><?php echo esc_html( $label ); ?><input name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v( $k ) ); ?>"></label>
					<?php endforeach; ?>
					<?php if ( $r && $r->message ) : ?><p><strong>Mensaje</strong><br><?php echo nl2br( esc_html( $r->message ) ); ?></p><?php endif; ?>
					<?php if ( $r && $r->needed_date ) : ?><p><strong>Lo necesita el</strong> <?php echo esc_html( $r->needed_date ); ?></p><?php endif; ?>
					<?php if ( $r && $r->file ) : ?><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=asa_file&id=' . $r->id ), 'asa_file_' . $r->id ) ); ?>">Descargar logotipo</a></p><?php endif; ?>
				</div>
				<?php if ( $items ) : ?>
				<div class="asa-box"><h2>Productos solicitados</h2><table class="widefat striped"><thead><tr><th>Producto</th><th>Cantidad</th><th>Color</th><th>Técnica</th><th>Precio de referencia</th></tr></thead><tbody>
					<?php foreach ( $items as $i ) :
						$prices = array_filter( array_column( asa_data( $i['id'] )['variants'] ?? array(), 'price' ) ); ?>
						<tr><td><a href="<?php echo esc_url( get_permalink( $i['id'] ) ); ?>" target="_blank"><?php echo esc_html( $i['name'] ); ?></a><br><small><?php echo esc_html( $i['sku'] ); ?></small></td><td><?php echo (int) $i['qty']; ?></td><td><?php echo esc_html( $i['color'] ); ?></td><td><?php echo esc_html( $i['tech'] ); ?></td><td><?php echo $prices ? esc_html( asa_money( min( $prices ) ) . ' MXN' ) : ''; ?></td></tr>
					<?php endforeach; ?></tbody></table><p class="description">Precio de proveedor según la API, solo visible aquí.</p></div>
				<?php endif; ?>
				<?php if ( $r ) : ?>
				<div class="asa-box"><h2>Origen</h2><dl>
					<?php foreach ( array( 'channel' => 'Canal', 'first_channel' => 'Primer contacto', 'source' => 'Fuente', 'medium' => 'Medio', 'campaign' => 'Campaña', 'term' => 'Término', 'landing' => 'Página de entrada', 'page' => 'Página donde solicitó', 'how_known' => 'Cómo dice que nos conoció', 'device' => 'Dispositivo', 'gclid' => 'GCLID' ) as $k => $label ) :
						if ( $r->$k ) : ?><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $r->$k ); ?></dd><?php endif;
					endforeach; ?>
					<dt>Aceptó la medición</dt><dd><?php echo $r->consent ? 'Sí' : 'No (solo la sesión actual)'; ?></dd>
				</dl></div>
				<?php endif; ?>
			</div>
			<div>
				<div class="asa-box"><h2>Seguimiento</h2>
					<label>Estado<select name="status"><?php echo asa_options( ASA_STATUSES, $v( 'status', 'nuevo' ) ); ?></select></label>
					<label>Importe de la venta (MXN)<input type="number" step="0.01" min="0" name="amount" value="<?php echo esc_attr( $v( 'amount' ) > 0 ? $v( 'amount' ) : '' ); ?>"></label>
					<label>Motivo si se perdió<input name="lost_reason" value="<?php echo esc_attr( $v( 'lost_reason' ) ); ?>"></label>
					<label>Asesor<input name="advisor" value="<?php echo esc_attr( $v( 'advisor', wp_get_current_user()->display_name ) ); ?>"></label>
					<?php if ( ! $r ) : ?><label>Origen<select name="channel"><?php echo asa_options( array_combine( ASA_CHANNELS, ASA_CHANNELS ), 'Referencia' ); ?></select></label>
					<label>Cómo nos conoció<select name="how_known"><?php echo asa_options( array_combine( ASA_HOW, ASA_HOW ), '', 'Selecciona' ); ?></select></label><?php endif; ?>
					<label>Nota<textarea name="note" rows="3"></textarea></label>
					<button class="button button-primary button-large">Guardar</button>
				</div>
				<?php if ( $history ) : ?><div class="asa-box"><h2>Historial</h2><?php foreach ( array_reverse( $history ) as $h ) : ?><p><small><?php echo esc_html( $h['at'] . ' · ' . $h['by'] ); ?></small><br><?php echo nl2br( esc_html( $h['text'] ) ); ?></p><?php endforeach; ?></div><?php endif; ?>
			</div>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_asa_save_lead', function () {
	global $wpdb;
	$id = (int) ( $_POST['id'] ?? 0 );
	if ( ! current_user_can( 'manage_asa_leads' ) || ! check_admin_referer( 'asa_save_lead_' . $id ) ) {
		wp_die( 'No autorizado' );
	}
	$p    = wp_unslash( $_POST );
	$text = fn( $k ) => mb_substr( sanitize_text_field( $p[ $k ] ?? '' ), 0, 190 );
	$old  = $id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . asa_table() . ' WHERE id=%d', $id ) ) : null;
	if ( $id && ! $old ) {
		wp_die( 'Lead no encontrado' );
	}
	$status = isset( ASA_STATUSES[ $p['status'] ?? '' ] ) ? $p['status'] : 'nuevo';
	$amount = round( max( 0, (float) ( $p['amount'] ?? 0 ) ), 2 );
	if ( 'convertido' === $status && $amount <= 0 && $id ) {
		wp_safe_redirect( admin_url( "admin.php?page=asa-leads&lead=$id&need_amount=1" ) );
		exit;
	}
	$row = array(
		'name' => $text( 'name' ), 'company' => $text( 'company' ), 'email' => sanitize_email( $p['email'] ?? '' ), 'phone' => $text( 'phone' ),
		'state' => $text( 'state' ), 'status' => $status, 'amount' => $amount, 'lost_reason' => $text( 'lost_reason' ), 'advisor' => $text( 'advisor' ),
		'updated' => current_time( 'mysql' ),
		'converted_at' => 'convertido' === $status ? ( $old->converted_at ?? current_time( 'mysql' ) ) : null,
	);
	$history = $old ? (array) json_decode( (string) $old->history, true ) : array();
	$entry   = array();
	if ( ! $old || $old->status !== $status ) {
		$entry[] = 'Estado: ' . ASA_STATUSES[ $status ] . ( 'convertido' === $status ? ' por ' . asa_money( $amount ) : '' );
	}
	if ( ! empty( $p['note'] ) ) {
		$entry[] = sanitize_textarea_field( $p['note'] );
	}
	if ( $entry ) {
		$history[] = array( 'at' => current_time( 'd/m/Y H:i' ), 'by' => wp_get_current_user()->display_name, 'text' => implode( "\n", $entry ) );
	}
	$row['history'] = wp_json_encode( $history );
	if ( $old ) {
		$wpdb->update( asa_table(), $row, array( 'id' => $id ) );
	} else {
		$channel = in_array( $p['channel'] ?? '', ASA_CHANNELS, true ) ? $p['channel'] : 'Referencia';
		asa_insert_lead( array_merge( $row, array( 'type' => 'manual', 'channel' => $channel, 'first_channel' => $channel, 'how_known' => $text( 'how_known' ) ) ) );
		$id = $wpdb->get_var( 'SELECT MAX(id) FROM ' . asa_table() );
	}
	wp_safe_redirect( admin_url( "admin.php?page=asa-leads&lead=$id&saved=1" ) );
	exit;
} );

add_action( 'admin_post_asa_file', function () {
	global $wpdb;
	$id = (int) ( $_GET['id'] ?? 0 );
	if ( ! current_user_can( 'manage_asa_leads' ) || ! check_admin_referer( 'asa_file_' . $id ) ) {
		wp_die( 'No autorizado' );
	}
	$r    = $wpdb->get_row( $wpdb->prepare( 'SELECT folio, file FROM ' . asa_table() . ' WHERE id=%d', $id ) );
	$path = $r && $r->file ? wp_upload_dir()['basedir'] . '/asa-leads/' . basename( $r->file ) : '';
	if ( ! $path || ! is_readable( $path ) ) {
		wp_die( 'Archivo no encontrado' );
	}
	nocache_headers();
	header( 'Content-Type: application/octet-stream' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="logo-' . $r->folio . '.' . pathinfo( $path, PATHINFO_EXTENSION ) . '"' );
	readfile( $path );
	exit;
} );

add_action( 'admin_post_asa_export', function () {
	global $wpdb;
	if ( ! current_user_can( 'manage_asa_leads' ) || ! check_admin_referer( 'asa_export' ) ) {
		wp_die( 'No autorizado' );
	}
	$google = 'google' === ( $_GET['kind'] ?? '' );
	$where  = $google ? "status='convertido' AND gclid<>''" : asa_where();
	$rows   = $wpdb->get_results( 'SELECT * FROM ' . asa_table() . " WHERE $where ORDER BY created DESC", ARRAY_A );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . ( $google ? 'conversiones-google-ads' : 'leads-asa' ) . '-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	// Evita que una celda se interprete como fórmula al abrir el archivo.
	$safe = fn( $c ) => preg_match( '/^[=+\-@\t\r]/', (string) $c ) ? "'" . $c : $c;
	if ( $google ) {
		fputcsv( $out, array( 'Google Click ID', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency' ), ',', '"', '' );
		foreach ( $rows as $r ) {
			$time = ( new DateTime( $r['converted_at'], wp_timezone() ) )->format( 'Y-m-d H:i:sP' );
			fputcsv( $out, array( $safe( $r['gclid'] ), 'Venta ASA', $time, $r['amount'], 'MXN' ), ',', '"', '' );
		}
	} else {
		fwrite( $out, "\xEF\xBB\xBF" );
		$cols = array( 'folio', 'created', 'type', 'status', 'name', 'company', 'email', 'phone', 'state', 'channel', 'first_channel', 'source', 'medium', 'campaign', 'landing', 'how_known', 'amount', 'lost_reason', 'advisor', 'converted_at' );
		fputcsv( $out, $cols, ',', '"', '' );
		foreach ( $rows as $r ) {
			fputcsv( $out, array_map( fn( $c ) => $safe( $r[ $c ] ), $cols ), ',', '"', '' );
		}
	}
	exit;
} );

function asa_page_report() {
	global $wpdb;
	$month = preg_match( '/^\d{4}-\d{2}$/', $_GET['month'] ?? '' ) ? $_GET['month'] : '';
	$spend = (array) get_option( 'asa_spend', array() );
	if ( $month && isset( $_POST['spend'] ) && current_user_can( 'manage_asa_leads' ) && check_admin_referer( 'asa_spend' ) ) {
		$spend[ $month ] = array_map( fn( $n ) => max( 0, (float) $n ), array_intersect_key( wp_unslash( (array) $_POST['spend'] ), array_flip( ASA_CHANNELS ) ) );
		update_option( 'asa_spend', $spend, false );
	}
	$where = $month ? $wpdb->prepare( 'AND created LIKE %s', $month . '%' ) : '';
	$rows  = $wpdb->get_results( 'SELECT channel, COUNT(*) leads, SUM(status IN ("cotizado","convertido")) quoted, SUM(status="convertido") won, SUM(IF(status="convertido",amount,0)) sales FROM ' . asa_table() . " WHERE status<>'clic' $where GROUP BY channel", OBJECT_K );
	$inv   = array();
	foreach ( $month ? array( $month => $spend[ $month ] ?? array() ) : $spend as $m ) {
		foreach ( $m as $c => $n ) {
			$inv[ $c ] = ( $inv[ $c ] ?? 0 ) + $n;
		}
	}
	$sum = fn( $k ) => array_sum( array_map( fn( $r ) => (float) $r->$k, $rows ) );
	?>
	<div class="wrap"><h1>Reporte por canal</h1>
		<form method="get"><input type="hidden" name="page" value="asa-report"><label>Mes de entrada del lead <input type="month" name="month" value="<?php echo esc_attr( $month ); ?>"></label> <button class="button">Ver</button> <a href="<?php echo esc_url( admin_url( 'admin.php?page=asa-report' ) ); ?>">Todo el periodo</a></form>
		<div class="asa-kpis">
			<div>Leads<b><?php echo (int) $sum( 'leads' ); ?></b></div><div>Cotizados<b><?php echo (int) $sum( 'quoted' ); ?></b></div>
			<div>Convertidos<b><?php echo (int) $sum( 'won' ); ?></b></div><div>Ventas<b><?php echo esc_html( asa_money( $sum( 'sales' ) ) ); ?></b></div>
			<div>Cierre<b><?php echo $sum( 'leads' ) ? esc_html( round( 100 * $sum( 'won' ) / $sum( 'leads' ) ) . '%' ) : '0%'; ?></b></div>
		</div>
		<form method="post"><?php wp_nonce_field( 'asa_spend' ); ?>
		<table class="widefat striped"><thead><tr><th>Canal</th><th>Leads</th><th>Cotizados</th><th>Convertidos</th><th>Ventas</th><th>Inversión</th><th>Costo por lead</th><th>Venta por peso invertido</th></tr></thead><tbody>
		<?php foreach ( ASA_CHANNELS as $c ) :
			$r = $rows[ $c ] ?? null; $i = $inv[ $c ] ?? 0; ?>
			<tr><td><strong><?php echo esc_html( $c ); ?></strong></td><td><?php echo (int) ( $r->leads ?? 0 ); ?></td><td><?php echo (int) ( $r->quoted ?? 0 ); ?></td><td><?php echo (int) ( $r->won ?? 0 ); ?></td><td><?php echo esc_html( asa_money( $r->sales ?? 0 ) ); ?></td>
			<td><?php if ( $month ) : ?><input type="number" step="0.01" min="0" name="spend[<?php echo esc_attr( $c ); ?>]" value="<?php echo esc_attr( $i ?: '' ); ?>" style="width:120px"><?php else : echo $i ? esc_html( asa_money( $i ) ) : ''; endif; ?></td>
			<td><?php echo $i && ! empty( $r->leads ) ? esc_html( asa_money( $i / $r->leads ) ) : ''; ?></td><td><?php echo $i ? esc_html( asa_money( ( $r->sales ?? 0 ) / $i ) ) : ''; ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php if ( $month ) : ?><p><button class="button button-primary">Guardar inversión de <?php echo esc_html( $month ); ?></button></p><?php else : ?><p class="description">Elige un mes para capturar la inversión.</p><?php endif; ?>
		</form>
		<p class="description">Los clics de WhatsApp sin confirmar no cuentan como lead.</p>
	</div>
	<?php
}

function asa_page_settings() {
	if ( isset( $_POST['asa_settings'] ) && check_admin_referer( 'asa_settings' ) ) {
		$p = wp_unslash( $_POST['asa_settings'] );
		update_option( 'asa_settings', array(
			'whatsapp' => preg_replace( '/\D/', '', $p['whatsapp'] ?? '' ), 'notify_email' => sanitize_email( $p['notify_email'] ?? '' ),
			'phone' => sanitize_text_field( $p['phone'] ?? '' ), 'email' => sanitize_email( $p['email'] ?? '' ),
		) );
		echo '<div class="notice notice-success"><p>Ajustes guardados.</p></div>';
	}
	if ( isset( $_POST['asa_run'] ) && check_admin_referer( 'asa_run' ) ) {
		wp_schedule_single_event( time(), 'catalogo' === $_POST['asa_run'] ? 'asa_sync_catalog' : 'asa_sync_stock' );
		echo '<div class="notice notice-info"><p>Sincronización programada; se ejecuta en los próximos minutos.</p></div>';
	}
	?>
	<div class="wrap"><h1>Ajustes y catálogo</h1>
		<form method="post"><?php wp_nonce_field( 'asa_settings' ); ?><table class="form-table">
			<?php foreach ( array( 'whatsapp' => 'WhatsApp (52 + 10 dígitos)', 'notify_email' => 'Correo que recibe los leads', 'phone' => 'Teléfono público', 'email' => 'Correo público' ) as $k => $label ) : ?>
				<tr><th><?php echo esc_html( $label ); ?></th><td><input class="regular-text" name="asa_settings[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( asa_opt( $k ) ); ?>"></td></tr>
			<?php endforeach; ?>
		</table><button class="button button-primary">Guardar</button></form>
		<h2>Catálogo de Promo Opción</h2>
		<table class="widefat striped" style="max-width:800px">
			<?php foreach ( (array) get_option( 'asa_sync_status', array() ) as $k => $s ) : ?>
				<tr><th><?php echo esc_html( ucfirst( $k ) ); ?></th><td><?php echo esc_html( $s['at'] ); ?></td><td><?php echo esc_html( $s['info'] ); ?></td></tr>
			<?php endforeach; ?>
		</table>
		<form method="post"><?php wp_nonce_field( 'asa_run' ); ?><p>
			<button class="button" name="asa_run" value="catalogo">Sincronizar catálogo</button>
			<button class="button" name="asa_run" value="existencias">Sincronizar existencias</button></p></form>
	</div>
	<?php
}
