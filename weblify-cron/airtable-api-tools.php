<?php

function airtable_api_request_single_page( $info ){
	//Handle only 100 records
	$ch = curl_init();
    	
	$headers = array();
	$headers[] = 'Authorization: Bearer ' . $info[ 'access_token' ];
	   
	$url = $info[ 'api_url' ] . '/' . $info[ 'base' ] . '/' . $info[ 'table' ];
	
	if( !empty( $info[ 'record_id' ] ) ){ 
		$url = $url . '/' . $info[ 'record_id' ];
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		switch( $info[ 'action' ]  ){
			case "GET":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
				break;

			case "PATCH" :
				if ( !empty( $info[ 'data' ] ) ){
					$headers[] = 'Content-Type: application/json';
					curl_setopt($ch, CURLOPT_POSTFIELDS, $info[ 'data' ] ); 
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
				} 
				break;

			case "DELETE" :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
				break;

			default:

		}
	} else {
		if ( $info[ 'action' ] == 'POST' ){
			if ( !empty( $info[ 'data' ] ) ){
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $info[ 'data' ] ); 
				curl_setopt($ch, CURLOPT_POST, 1);
			} 

		} else {
			if ( $info[ 'action' ] == 'GET' ){
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
	} 
	
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$result = curl_exec($ch);
    curl_close($ch);
	return $result;
}	

function airtable_api_request_multiple_page( $info ){
	$total_records = array();
	
	// Call Airtable records in pages of 100 max
	do {
		// Offset is either inherited from last page's results, or is nothing
		if( isset($records) ){
            $offset = $records[ 'offset' ] ?: "";

            $info[ 'params' ][ 'offset' ] = $offset;
        }
        
		// Make get request, store result in array
		$request = airtable_api_request_single_page( $info );
		$records = json_decode( $request, TRUE );
			
		foreach( $records[ 'records' ] as $record ){
			array_push($total_records, $record);
		}

	} while( !empty( $records[ 'offset' ] ) ); // If there's an offset value (ie. starting record of next page), do again
	
	return json_encode( $total_records, TRUE );
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
				$value_to_check1 = "";
                if( isset( $a1Record[ 'fields' ][ $value_to_check ] ) ){
                    $value_to_check1 = $a1Record[ 'fields' ][ $value_to_check ];
                }
				$value_to_check2 = "";
                if( isset( $a2Record[ 'fields' ][ $value_to_check ] ) ){
                    $value_to_check2 = $a2Record[ 'fields' ][ $value_to_check ];
                }
                
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