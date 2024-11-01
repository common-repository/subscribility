<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

/**
 * Display all log files in a table
 */
class Troly_Log_Table extends WP_List_Table
{
	private $logDates = [];

	/**
	 * Process and render filters for the table.
	 *
	 * @return void
	 */
	public function render_filters() {
		$filters = [
			'log_by_operation' => [ $this, 'renderLogByOperationFilter' ],
			'log_by_data_affected'	=> [ $this, 'renderLogByDataAffectedFilter'],
			'log_by_date'	=> [ $this, 'renderLogByDateFilter'],
			'log_by_status'	=> [ $this, 'renderLogByStatusFilter'],
		];

		ob_start();
		foreach ( $filters as $filter_callback ) {
			call_user_func( $filter_callback );
		}
		$output = ob_get_clean();

		echo apply_filters( 'woocommerce_product_filters', $output ); // WPCS: XSS ok.
	}

	protected function renderLogByDataAffectedFilter()
	{
		$logDataTypes = TrolyLogger::LOG_DATA_TYPE; ?>

		<select name="troly_log_by_data_affected" class="troly-wc-select">
			<option value="">Data Affected</option>
			<?php foreach( $logDataTypes as $dataKey => $dataType ) : ?>
				<option value="<?php echo $dataKey; ?>"
				<?php echo isset( $_REQUEST['troly_log_by_data_affected'] )
						&& '' != $_REQUEST['troly_log_by_data_affected'] && (int) $_REQUEST['troly_log_by_data_affected'] === (int) $dataKey ? 'selected' : ''; ?>>
						<?php echo $dataType; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter logs by their type.
	 *
	 * @uses TrolyLogger::LOG_OPERATION_TYPES Log Operation Types
	 * @return void
	 */
	protected function renderLogByOperationFilter()
	{
		$logOptTypes = TrolyLogger::LOG_OPERATION_TYPES; ?>
		<select name="troly_log_by_operation" class="troly-wc-select">
			<option value="">Select Operation</option>
			<?php foreach( $logOptTypes as $optKey => $optType ) : ?>
				<option value="<?php echo $optKey; ?>"
				<?php echo isset( $_REQUEST['troly_log_by_operation'] )
						&& '' != $_REQUEST['troly_log_by_operation'] && (int) $_REQUEST['troly_log_by_operation'] === (int) $optKey ? 'selected' : ''; ?>>
						<?php echo $optType; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter logs by the date of creation.
	 *
	 * @return void
	 */
	protected function renderLogByDateFilter()
	{
		$logDates = $this->getLogDates();
		arsort( $logDates ); ?>
		<select name="troly_log_by_date" id="troly_log_by_date" class="troly-wc-select">
			<option value="">All Dates</option>

			<?php foreach( $logDates as $dateKey => $date ) : ?>
				<option value="<?php echo $dateKey; ?>"
				<?php echo ! empty( $_REQUEST['troly_log_by_date'] )
						&& $_REQUEST['troly_log_by_date'] === $dateKey ? 'selected' : ''; ?>>
						<?php echo $date; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter logs by their status.
	 *
	 * @return void
	 */
	protected function renderLogByStatusFilter()
	{
		$logStatus = TrolyLogger::LOG_STATUS_TYPES; ?>

		<select name="troly_log_by_status" id="troly_log_by_status" class="troly-wc-select">
			<option value="">Select Log Status</option>
			<?php foreach( $logStatus as $key => $status ) : ?>
				<option value="<?php echo $key; ?>"
					<?php echo isset( $_REQUEST['troly_log_by_status'] )
						&& '' != $_REQUEST['troly_log_by_status'] && (int) $_REQUEST['troly_log_by_status'] === (int) $key ? 'selected' : ''; ?>>
						<?php echo $status; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Retrieve log dates for filter.
	 *
	 * @return array $logDates
	 */
	public function getLogDates()
	{
		return $this->logDates;
	}

	/**
	 * Store log dates for filter.
	 * Checks if given date already exist.
	 *
	 * @param string $date
	 * @return void
	 */
	public function setLogDates( string $date )
	{
		try {
			$dateFormat = new DateTime( $date );
			$key = $dateFormat->format( 'd' ) . '_' . $dateFormat->format( 'm' ) . '_' . $dateFormat->format( 'Y' );

			if ( ! array_key_exists( $key, $this->getLogDates() ) ) {
				$this->logDates[ $key ] = $dateFormat->format( 'd F Y' );
			}
		} catch ( Exception $e ) { /* let's leave this as is */ }
	}

	public function display_tablenav( $where )
	{
		echo '<div class="tablenav '. esc_attr( $where ) .'">';
			$this->extra_tablenav( $where );
			$this->pagination( $where );
		echo '</div>';
	}

	/**
	 * Display extra filters on top of the table.
	 *
	 * @param string $where
	 * @return void
	 */
	public function extra_tablenav( $where )
	{
		if ( 'top' === $where ) : ?>
		<div class="alignleft actions">
			<?php $this->render_filters(); ?>
			<?php submit_button( __( 'Filter Logs', 'troly' ), '', 'filter_troly_logs', false, [
				'id' => 'filter_troly_logs'
			] ); ?>
		</div>
		<?php endif;
	}

    /**
     * Prepare the items for the table to process
     *
     * @return void
     */
    public function prepare_items()
    {
		$this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->sort_data($this->table_data());

        $perPage = 15;
        $currentPage = $this->get_pagenum();
		$totalItems = count($data);
		$lol = isset( $_REQUEST['troly_log_by_status'] ) && '' != $_REQUEST['troly_log_by_status'] ? (int) $_REQUEST['troly_log_by_status'] : false;

        $this->set_pagination_args( [
			'total_items' => $totalItems,
			'per_page' => $perPage,
			'troly_log_by_status' => $lol,
		] );
        $data = array_slice($data, (($currentPage-1)*$perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $data;
	}

    /**
     * Columns in the table
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'timestamp' => 'Time (UTC)',
            'operation' => 'Operation',
            'event_summary' => 'Event Summary',
            'details' => 'Details',
        );
        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return array(
            'timestamp' => array('timestamp', false),
            'operation' => array('operation', false)
        );
	}

	public function views()
	{
		//
	}

    /**
     * Get the table data
     *
     * @return array
     */

    private function table_data()
    {
		$logsArray = [];

		if ($handle = opendir(TROLY_LOG_DIR)) {
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'log')
                {
					$allLogs = file_get_contents(TROLY_LOG_DIR . $file, 'r');
					$logs = ! $allLogs || empty( $allLogs ) ? [] : json_decode( $allLogs, true );

					// Define filter variables for further checking.
					$filterLogStatus = isset( $_REQUEST['troly_log_by_status'] ) && '' != $_REQUEST['troly_log_by_status'] ? ( int ) $_REQUEST['troly_log_by_status'] : false;
					$filterLogOperationType = isset( $_REQUEST['troly_log_by_operation'] ) && '' !== $_REQUEST['troly_log_by_operation'] ? ( int ) $_REQUEST['troly_log_by_operation'] : false;
					$filterLogDataAffected = isset( $_REQUEST['troly_log_by_data_affected'] ) && '' !== $_REQUEST['troly_log_by_data_affected'] ? ( int ) $_REQUEST['troly_log_by_data_affected'] : false;
					$filterLogDate = ! empty( $_REQUEST['troly_log_by_date'] ) ? $_REQUEST['troly_log_by_date'] : false;

					foreach ( $logs as $key => $log ) :
						$log['operation_code'] = $log['operation'];
						$log['operation'] = TrolyLogger::LOG_OPERATION_TYPES[ $log['operation'] ];
						$logs[ $key ] = $log;
					endforeach;

					foreach ( $logs as $key => $log ) :
						$this->setLogDates( $log['timestamp'] );


						if ( $filterLogDate ) :
							$dateFormat = new DateTime( $log['timestamp'] );
							$checkTimestamp = $dateFormat->format( 'd' ) . '_' . $dateFormat->format( 'm' ) . '_' . $dateFormat->format( 'Y' );
							if ( $checkTimestamp !== $filterLogDate ) :
								unset( $logs[ $key ] );
							endif;
						endif;

						if ( false !== $filterLogOperationType ) :
							if ( $log['operation_code'] !== $filterLogOperationType ) :
								unset( $logs[ $key ] );
							endif;
						endif;

						if ( false !== $filterLogStatus ) :
							if ( $log['status'] !== $filterLogStatus ) :
								unset( $logs[ $key ] );
							endif;
						endif;

						if ( false !== $filterLogDataAffected ) :
							if ( $log['data_type'] !== $filterLogDataAffected ) :
								unset( $logs[ $key ] );
							endif;
						endif;
					endforeach;

					$logsArray[] = $logs;
                }
            }
            closedir($handle);
		}

        return array_merge( ... $logsArray);
	}

    /**
     * Define what data to show on each column of the table
     *
     * @param  array $item        Data
     * @param  string $column_name - Current column name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name )
    {
		return $item[ $column_name ];
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return mixed
     */
    private function sort_data($data)
    {
        // Set defaults
        $orderby = 'timestamp';
        $order = 'desc';
        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }

        $orderByArray = $timestamp = array();
        foreach ($data as $key => $row) {
            if ($orderby == 'timestamp' && isset($row['timestamp'])) {
                $initTimestramp = explode(' ', $row['timestamp']);
                $timestamp = explode('/', $initTimestramp[0]);
                $convertedTimestamp = $timestamp[1].'/'.$timestamp[0].'/'.$timestamp[2].$initTimestramp[1];
                $orderByArray[$key] = $convertedTimestamp;
            } elseif ( $orderby == 'event_summary' && isset( $row[ 'event_summary' ] ) ) {
                $orderByArray[ $key ] = $row[ 'event_summary' ];
            }
        }
        array_multisort($orderByArray, $order == 'desc' ? SORT_DESC : SORT_ASC, $data);

        return $data;
    }
}
