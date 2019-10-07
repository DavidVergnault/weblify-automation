<?php
 
//Import all necessary tools
include 'weblify_api_config.php';

$filename_storage = "old-records-Deployment.json";
$file_path = __DIR__ . "/" . $filename_storage;

//Get old records
$old_records = get_include_contents( $file_path );

//Get current records
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'deployment' ][ 'base' ],
	"table" => rawurlencode('Table 1'),
	"view" => rawurlencode('Ellen2'),
	"record_id" => "",
	"data" => '',
	"params" => array(),
);	

$current_records = airtable_api_request_multiple_page( $info );

//Save the current records into a file
$handle = fopen( $file_path, 'w' );
fwrite( $handle, $current_records );
fclose( $handle );

$value_to_check = 'Done | Deployment';
$compare_done = compare_airtable_records( json_decode( $current_records, TRUE ), json_decode( $old_records , TRUE ) , $value_to_check );

if( !empty( $compare_done ) ){     
	foreach( $compare_done as $key => $value ){
		if ( $value[ "new" ] === true ){
			//Send all infos to Deployment Airtable
			//Get record infos
			$info = array(
				"action" => "GET",
				"api_url" => $airtable_config[ 'api_url' ],
				"access_token" => $airtable_config[ 'access_token' ],
				"base" => $airtable_config[ 'deployment' ][ 'base' ],
				"table" => rawurlencode('Table 1'),
				"view" => "",
				"record_id" => $key ,
				"data" => '',
				"params" => array(),
			);	
			
			$record = json_decode( airtable_api_request_single_page( $info ), TRUE );
			
			//Re-format arrays in JSON cause json_encode isn't working proprely in that case
			$decoded_array_to_JSON = "";
			for ( $i = 0; $i < count( $record[ 'fields' ][ 'Zipped backups of the current site and the new site | Deployment' ] ); $i++ ){
				$decoded_array_to_JSON .= "{";
				$attachment_url = '"url": "' . str_replace("\\/", "/", $record[ 'fields' ][ 'Zipped backups of the current site and the new site | Deployment' ][ $i ][ 'url' ] ) . '"';
				$attachment_name = '"filename": "' . $record[ 'fields' ][ 'Zipped backups of the current site and the new site | Deployment' ][ $i ][ 'filename' ] . '"';
				
				$decoded_array_to_JSON .= $attachment_url;
				$decoded_array_to_JSON .= ', ';
				$decoded_array_to_JSON .= $attachment_name;
				
				$decoded_array_to_JSON .= "}";
				if ( $i != count( $record[ 'fields' ][ 'Zipped backups of the current site and the new site | Deployment' ] ) - 1 ){
					$decoded_array_to_JSON .= ",";
				}
			}
									
			if ( $record[ 'fields' ][ 'Assigned - Deployment' ] == 'Nicosur ' || $record[ 'fields' ][ 'Assigned - Deployment' ] == 'Ellen' ){
				$deployment_time = 10800;
				if ( !empty( $record[ 'fields' ][ 'Deployment time' ] ) ){
					$deployment_time = $record[ 'fields' ][ 'Deployment time' ];
				}
				
				$data = '{
  "fields": {
    "Login WordPress Weblify": ' . json_encode( $record[ 'fields' ][ 'Login WordPress Weblify' ], JSON_UNESCAPED_SLASHES ) . ',
	"Client login FTP": ' . json_encode( $record[ 'fields' ][ 'Client login FTP' ], JSON_UNESCAPED_SLASHES ) . ',
	"Client login Webhotel": ' . json_encode( $record[ 'fields' ][ 'Client login Webhotel' ], JSON_UNESCAPED_SLASHES ) . ',
	"Zipped backups of the current site and the new site | Deployment": [' . $decoded_array_to_JSON . '],
	"Live": true,
	"Status": "Waiting (Designer)",
	"Date site live": "' . date('Y-m-d') . '",
	"Comments to Developers | Deployment": ' . json_encode( $record[ 'fields' ][ 'Comments to Developers | Deployment' ], JSON_UNESCAPED_SLASHES ) . ',
	"Deployment time": ' . $deployment_time . ',
    "Domain on the same hosting | Deployment/hosting": ' . json_encode( $record[ 'fields' ][ 'Domain on the same hosting | Deployment/hosting' ], JSON_UNESCAPED_SLASHES ) . '
  }
}';

				//echo $data;
				$info = array(
					"action" => "PATCH",
					"api_url" => $airtable_config[ 'api_url' ],
					"access_token" => $airtable_config[ 'access_token' ],
					"base" => $airtable_config[ 'p&pm' ][ 'base' ],
					"table" => rawurlencode('Allmänt'),
					"view" => "",
					"record_id" => $record[ 'fields' ][ 'P&PM ID' ],
					"data" => $data,
					"params" => array(),
				);	

				$response = airtable_api_request_single_page( $info );
				
				if ( empty( json_decode( $response, TRUE )[ 'error' ] ) ){
					$response_decoded = json_decode( $response, TRUE );
					
					$message = '*Deployment done !*\nCompany name : ' . $response_decoded[ 'fields' ][ 'Company' ] . '\nAssigned - Delivery : ' . $response_decoded[ 'fields' ][ 'Assigned - Delivery' ];
					send_slack_message( $slack_channel_doneDeployment, $message);

				} else {
					error_log( $response );
					error_log( 'The sent data is : ' . $data );
				}
			}
		}
	}
}


