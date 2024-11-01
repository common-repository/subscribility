<div>
    <h2>Troly Event Log</h2>

	<form method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>">
		<?php
			include_once(WP99234()->plugin_path() . '/includes/admin/controllers/class-wp99234-logs.php');

			$log_table = new Troly_Log_Table();
			$log_table->prepare_items();

			echo '<div class="wp99234_log_table">';
			$log_table->display();
			echo '</div>';
		?>
	</form>

</div>
