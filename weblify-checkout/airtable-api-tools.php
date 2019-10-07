<?php

function airtable_api_request_single_page( $info ){
	//Handle only 100 records
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    
	if( empty( $info[ 'access_token' ] ) ){
		$error_msg = "Airtable request - Incorrect request parameters: Missing bearer access token";
		return array(
            "is_success" => false,
            "error" => $error_msg,
        );
	} 
	
	$headers = array();
	$headers[] = 'Authorization: Bearer ' . $info[ 'access_token' ];

	if ( empty( $info[ 'api_url' ] ) || empty( $info[ 'base' ] ) || empty( $info[ 'table' ] ) ){
		$error_msg = "Incorrect request parameters";
		return array(
            "is_success" => false,
            "error" => $error_msg,
        );
	}
	   
	$url = $info[ 'api_url' ] . '/' . $info[ 'base' ] . '/' . $info[ 'table' ];

	if( !empty( $info[ 'record_id' ] ) ){ 
		$url = $url . '/' . $info[ 'record_id' ];
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		switch( $info[ 'action' ]  ){
			case "GET":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
				break;

			case "POST":
				$error_msg = "Airtable request - Incorrect request parameters: Can't process a POST with a record_id";
				return array(
                    "is_success" => false,
            		"error" => $error_msg,
                );
				break;

			case "PATCH" :
				if ( !empty( $info[ 'data' ] ) ){
					$headers[] = 'Content-Type: application/json';
					curl_setopt($ch, CURLOPT_POSTFIELDS, $info[ 'data' ] ); 
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
				} else {
					$error_msg = "No data to send";
					return array(
                        "is_success" => false,
            			"error" => $error_msg,
                    );
				}
				break;

			case "DELETE" :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
				break;

			default:
				$error_msg = "Airtable request - Incorrect request parameters: Can't process a " . $action . " action";
				return array(
                  	"is_success" => false,
            		"error" => $error_msg,
                );

		}
	} else {
		if ( empty ( $info[ 'view' ] ) ){
			if ( $info[ 'action' ]  != 'POST' ){
				$error_msg = "Airtable request - Incorrect request parameters: Missing \"view\" or \"record_id\"";
				return array(
                    "is_success" => false,
            		"error" => $error_msg,
                );
			} else {
				if ( !empty( $info[ 'data' ] ) ){
					$headers[] = 'Content-Type: application/json';
					curl_setopt($ch, CURLOPT_URL, $url );
					curl_setopt($ch, CURLOPT_POSTFIELDS, $info[ 'data' ] ); 
					curl_setopt($ch, CURLOPT_POST, 1);
				} else {
					$error_msg = "Airtable request - No data to send in Airtable POST request";
					return array(
                        "is_success" => false,
            			"error" => $error_msg,
                    );
				}
			}
			
		} else {
			$url = $url . '?view=' . $info[ 'view' ];

			if ( !empty ( $info[ 'params' ] ) ){
				foreach( $info[ 'params' ] as $key => $value ){
					$url = $url . '&' . $key . '=' . $value;
				}
			}

			curl_setopt($ch, CURLOPT_URL, $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
		}
	} 
	
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

	$result = curl_exec($ch);
	
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    
    curl_close($ch);

    if ( isset( $error_msg ) ) {
        return array(
            "is_success" => false,
            "error" => $error_msg,
			"response" => $result,
        ); 
    } else {
        return array(
            "is_success" => true,
            "response" => $result,
        ); 
    }
}	

function airtable_api_request_multiple_page( $info ){
	$total_records = array();
	
	// Call Airtable records in pages of 100 max
	do {
		// Offset is either inherited from last page's results, or is nothing
		$offset = $records[ 'offset' ] ?: "";

		$info[ 'params' ][ 'offset' ] = $offset;
		
		// Make get request, store result in array
		$request = airtable_api_request_single_page( $info );
		
		if ( $request[ 'is_success' ] === true ){
			$records = json_decode( $request[ 'response' ], TRUE );
		} else {
			return $request;
		}		
		
		foreach( $records[ 'records' ] as $record ){
			array_push($total_records, $record);
		}

	} while( !empty( $records[ 'offset' ] ) ); // If there's an offset value (ie. starting record of next page), do again
	
	return array(
		"is_success" => true,
        "response" => json_encode( $total_records, TRUE ),
    ); 
}

function compare_airtable_records ( $current_records, $old_records, $value_to_check){
	$changes = array();
	
	foreach ($current_records as $a1Key => $a1Record) { 
		$idr1 = $a1Record[ 'id' ];
		
		//Find the same record with the unique ID
		$found = false;
		foreach ($old_records as $a2Key => $a2Record) { 
			$idr2 = $a2Record[ 'id' ];
			
			//If matches record ID, compare the values
			if( $idr2 == $idr1){ 
				$value_to_check1 = $a1Record[ 'fields' ][ $value_to_check ];
				$value_to_check2 = $a2Record[ 'fields' ][ $value_to_check ];
	
				if ( $value_to_check1 != $value_to_check2 ){
					$changes[ $idr1 ] = array( 
						"new" => $value_to_check1,
						"old" => $value_to_check2
					);
				}
				$found = true;
				break;
			}
		}
		
		//In case of a new record
		if ( !$found ){
			//$changes[ $idr1 ] = array( $value_to_check => $value_to_check1);
		}
	}
	
	return $changes;
}

function get_include_contents( $filename ) {
	//Get content of local file
    if ( is_file( $filename ) ) {
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}