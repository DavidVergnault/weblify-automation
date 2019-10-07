<?php

//Import all necessary tools
include 'weblify_api_config.php';

$filename_storage = "old-records-revision.json";
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
	"view" => rawurlencode('David - automations'),
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
		if ( $value[ "new" ] == 'Waiting (PM)' && $value[ "old" ] == 'In progress (Designer)' ){
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

			if ( $record[ 'fields' ][ 'Stage' ] == 'Revisions' ){
				send_slack_message( $slack_channel_revisionDone, '*' . $record[ 'fields' ][ 'Company' ] . '* is done with revision ! :bananadance:' );
			}
		}
	}
}