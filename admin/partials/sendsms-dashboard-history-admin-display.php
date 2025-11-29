<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'extension' . DIRECTORY_SEPARATOR . 'sendsms-dashboard-history.php';
$table = new Sendsms_Dashboard_History();

?>
<div class="wrap">
	<h2><?php echo __( 'SendSMS - Historic', 'sendsms-dashboard' ); ?></h2>
	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php
		$table->prepare_items();
		$table->views();
		$table->search_box( __( 'Search', 'sendsms-dashboard' ), 'key' );
		$table->display();
		?>
	</form>
</div>
