<?php
 
//Import all necessary tools
include 'weblify_api_config.php';


$filename_storage = "old-records-P&PM.json";
$file_path = __DIR__ . "/" . $filename_storage;

//Get old records
$old_records = get_include_contents( $file_path );

//Get current records
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'p&pm' ][ 'base' ],
	"table" => rawurlencode('Allmänt'),
	"view" => rawurlencode('Production - overall'),
	"record_id" => "",
	"data" => '',
	"params" => array(),
);	

$current_records = airtable_api_request_multiple_page( $info );

//Save the current records into a file
$handle = fopen( $file_path, 'w' );
fwrite( $handle, $current_records );
fclose( $handle );

$value_to_check = 'Status';
$compare_status = compare_airtable_records( json_decode( $current_records, TRUE ), json_decode( $old_records , TRUE ) , $value_to_check );

if( !empty( $compare_status ) ){     
	foreach( $compare_status as $key => $value ){
		if ( $value[ "new" ]	== 'Waiting (Developers)*' ){
			//Send all infos to Deployment Airtable
			//Get record infos
			$info = array(
				"action" => "GET",
				"api_url" => $airtable_config[ 'api_url' ],
				"access_token" => $airtable_config[ 'access_token' ],
				"base" => $airtable_config[ 'p&pm' ][ 'base' ],
				"table" => rawurlencode('Allmänt'),
				"view" => "",
				"record_id" => $key ,
				"data" => '',
				"params" => array(),
			);	
			
			$record = json_decode( airtable_api_request_single_page( $info ), TRUE );

			if ( $record[ 'fields' ][ 'Stage' ] == 'Deployment' ){
				$priority = "Priority 1";
				if ( !empty($record[ 'fields' ][ 'Priority deployment' ] ) ){
					$priority = $record[ 'fields' ][ 'Priority deployment' ];
				}
				
				$data = '{
  "fields": {
    "Company": ' . json_encode( $record[ 'fields' ][ 'Company' ], JSON_UNESCAPED_SLASHES ) . ',
    "Priority deployment": "' . $priority . '",
    "Login WordPress Weblify": ' . json_encode( $record[ 'fields' ][ 'Login WordPress Weblify' ], JSON_UNESCAPED_SLASHES ) . ',
	"Client login FTP": ' . json_encode( $record[ 'fields' ][ 'Client login FTP' ], JSON_UNESCAPED_SLASHES ) . ',
    "Client login Webhotel": ' . json_encode( $record[ 'fields' ][ 'Client login Webhotel' ], JSON_UNESCAPED_SLASHES ) . ',
    "Assigned - Deployment": "Nicosur ",
	"P&PM ID": "' . $record[ 'id' ] . '",
    "Domain on the same hosting | Deployment/hosting": ' . json_encode( $record[ 'fields' ][ 'Domain on the same hosting | Deployment/hosting' ], JSON_UNESCAPED_SLASHES ) . '
  }
}';
				//echo $data;
				
				$info = array(
					"action" => "POST",
					"api_url" => $airtable_config[ 'api_url' ],
					"access_token" => $airtable_config[ 'access_token' ],
					"base" => $airtable_config[ 'deployment' ][ 'base' ],
					"table" => rawurlencode('Table 1'),
					"view" => "",
					"record_id" => "",
					"data" => $data,
					"params" => array(),
				);	

				$response = airtable_api_request_single_page( $info );
				
				if ( !empty( json_decode( $response, TRUE )[ 'error' ] ) ){
					error_log( $response );
					error_log( 'The sent data is : ' . $data );
				}
			}
		}
	}
}


