<?php
/*
	This file is for testing connection to Troly.
*/
function test_url($method, $url, $query, $headers=[]){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, "https://app.troly.io/{$url}" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER,true );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	
	switch( $method ){
		case 'PUT':
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT'  );
			curl_setopt( $ch, CURLOPT_POSTFIELDS   , $query );
			break;
		case 'POST':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			break;
		case 'GET':
		default:
			curl_setopt( $ch, CURLOPT_HTTPGET    , true );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
			break;
	}

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$response = curl_exec( $ch );
	
	$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	
	curl_close( $ch );
	
	return [$response_code, $response];
}

$tests = array(
	[
		'request_type' => 'GET',
		'request_text' => ' GET  no  credentials',
		'endpoint' => 'customers/1.json',
		'query' => null,
		'header' => [],
		'response_code' => 401
	],
	// [
	// 	'request_type' => 'GET',
	// 	'request_text' => ' GET with credentials',
	// 	'endpoint' => 'customers/1.json',
	// 	'query' => null,
	// 	'header' => [],
	// 	'response_code' => 401
	// ],
	[
		'request_type' => 'POST',
		'request_text' => 'POST  no  credentials',
		'endpoint' => 'customers/1.json',
		'query' => null,
		'header' => [],
		'response_code' => 404
	],
	// [
	// 	'request_type' => 'POST',
	// 	'request_text' => 'POST with credentials',
	// 	'endpoint' => 'customers/1.json',
	// 	'query' => null,
	// 	'header' => [],
	// 	'response_code' => 401
	// ],
	[
		'request_type' => 'PUT',
		'request_text' => ' PUT  no  credentials',
		'endpoint' => 'customers/1.json',
		'query' => null,
		'header' => [],
		'response_code' => 401
	],
	// [
	// 	'request_type' => 'PUT',
	// 	'request_text' => ' PUT with credentials',
	// 	'endpoint' => 'customers/1.json',
	// 	'query' => null,
	// 	'header' => [],
	// 	'response_code' => 401
	// ]
);

?>
<html>
	<head>
		<title>Troly Connection Testing</title>
	</head>
	<body>
		<h3>Troly Connection Testing</h3>
		
		<p>Preparing to test your connection, please wait...</p>
		
		<?php
			ob_start();
			
			/* Test 0: Do we have curl? */
			
			if(!function_exists('curl_version')){
				echo "cURL is not installed. Please ensure cURL is installed and your PHP installation uses it.";
				ob_end_clean();
				echo "</body></html>";
				return;
			} else {
				echo "<pre>";
				/* Test 1: Can we GET to Troly? */
				foreach($tests as $test) {
					echo "Testing {$test['request_text']}... ";
					ob_flush();
					$response = test_url($test['request_type'], $test['endpoint'], $test['query']);
					echo ($response[0] == $test['response_code'] ? '[ OK ]' : "[ FAIL ({$response[0]}) ]");
					echo "\n";
					ob_flush();
				}
				echo "</pre>";
				ob_end_clean();
			}
		
		phpinfo();
		?>
		
	</body>

</html>
