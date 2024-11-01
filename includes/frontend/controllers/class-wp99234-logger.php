<?php

/**
 * Logger class for Troly.
 *
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 */
class TrolyLogger {
	const LOG_STATUS_TYPES = [
		'Error',
		'Success',
		'Warning',
	];

	const LOG_OPERATION_TYPES = [
		'Plugin Configuration',
		'Data Sent to Troly',
		'Data Received from Troly',
		'Data Requested from Troly',
	];

	const LOG_DATA_TYPE = [
		'Customers',
		'Orders',
		'Products',
		'Club Definitions',
		'Others',
	];

	public function __construct()
	{
		if ( ! wp_next_scheduled( 'wp99234_rotate_log_files_action' ) ) {
			wp_schedule_event( time(), 'daily', 'wp99234_rotate_log_files_action' );
		}

		/**
		 * Hook into that action that'll fire every day
		 */
		add_action( 'wp99234_rotate_log_files_action', [$this, 'wp99234_rotate_log_files_cron'] );
	}

	/**
	 * Rotate log files and retain files within 1 month ago
	 * @since 2.9
	 * @package Troly
	 */
	public function wp99234_rotate_log_files_cron()
	{
		// Get 1 month ago
		$month_ago = date("Y-m-d", strtotime("-1 month"));

		// Open troly log file
		if ($handle = opendir(TROLY_LOG_DIR)) {
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..") {
					// Read all files with "log" extension and delete if older than 1 month
					if (strtolower(substr($file, strrpos($file, '.') + 1)) == 'log') {
						if ($created = substr($file, 10, 9)) {
							$created = date("Y-m-d", strtotime($created));
							// Delete file more than 1 month ago
							if ($created < $month_ago) {
								if (!unlink(TROLY_LOG_DIR . $file)) {
									echo("Error deleting $file");
								}
							}
						}
					// Read all files with txt extension and delete if older than 1 month
					} elseif (strtolower(substr($file, strrpos($file, '.') + 1)) == 'txt') {
						if ($created = substr($file, 4, -4)) {
							$created = date("Y-m-d", strtotime($created));

							// Delete file more than 1 month ago
							if ($created < $month_ago) {
								if (!unlink(TROLY_LOG_DIR . $file)) {
									echo("Error deleting $file");
								}
							}
						}
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 * Detail logger for Troly.
	 * Logs operation data in "log" file based on the provided data.
	 *
	 * @param integer $status Refer to CONSTANT above.
	 * @param integer $operation Refer to CONSTANT above.
	 * @param string $eventSummary
	 * @param string $details
	 * @return void
	 */
	public function wp99234_log_troly( int $status, int $operation, int $dataType, string $eventSummary, string $details = null ) {

		$date = date( 'Y-m-d' );
		$logFile = TROLY_LOG_DIR . "troly_log_{$date}.log";

		if ( ! file_exists( $logFile ) ) fopen( $logFile, 'a' );

		$logEncodedString = file_get_contents( $logFile );
		$logData = json_decode( $logEncodedString, true );
		$logData = $logData ? $logData : [];
		$statusText = self::LOG_STATUS_TYPES[ $status ];
		$statusClass = strtolower( $statusText );
		$logData[] = [
			'timestamp' => date('m/d/y g:i:s A'),
			'data_type' => (int ) $dataType,
			'operation' => ( int ) $operation,
			'event_summary' => '<span class="'.$statusClass.'">'.$statusText.'</span>' . $eventSummary,
			'details' => $details,
			'status' => ( int ) $status,
		];

		file_put_contents( $logFile, json_encode( $logData ) );
	}
}

/**
 * This is redundant and temporary.
 * Better off using classes.
 *
 * @todo Remove this.
 */
function wp99234_log_troly( int $status, int $operation, int $dataType, string $eventSummary, string $details = null ) {
	$loggerClass = new TrolyLogger;

	return $loggerClass->wp99234_log_troly( $status, $operation, $dataType, $eventSummary, $details );
}