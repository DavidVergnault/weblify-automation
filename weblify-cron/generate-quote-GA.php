<?php

include 'weblify_api_config.php';

function get_salesman_info( $airtable_config, $crm_record ){
	$sg_ga_id = $crm_record[ 'fields' ][ '(Linked to "Sales agents") - GetAccept User ID' ];
	$sg_ga_template = $crm_record[ 'fields' ][ 'GetAccept Template ID' ];
	$record_id_GA_api = $crm_record[ 'fields' ][ 'GetAccept API info - Record ID' ];

	//GET API INFO -------------
	$info = array(
		"action" => "GET",
		"api_url" => $airtable_config[ 'api_url' ],
		"access_token" => $airtable_config[ 'access_token' ],
		"base" => $airtable_config[ 'apis' ][ 'base' ],
		"table" => rawurlencode('GetAccept'),
		"view" => "",
		"record_id" => $record_id_GA_api,
		"data" => "",
		"params" => array(),
	);	

    $record_api_info = json_decode( airtable_api_request_single_page( $info ), true );

    $access_token = $record_api_info[ 'fields' ][ 'Access Token' ];
	
	$expi_date = new DateTime(); //Get current time
	$expi_date->add(new DateInterval('P60D')); //Add 2 days
	$expi_date = $expi_date->format('Y-m-d\TH:i:s'); //Format the date to fit the format from GA
	
	$salesman_info = array(
	    "sg_ga_id" => $sg_ga_id[0],
	    "sg_ga_template" => $sg_ga_template,
	    "access_token" => $access_token,
	    "expi_date" => $expi_date,
	);
	
	return $salesman_info;
}

function create_ga_quote( $airtable_config, $salesman_info, $value, $country, $slack_access_token_Salesman){
    $customer_email = $value[ 'fields' ][ 'Customer\'s Email' ]; 
    
    if( strpos($customer_email, ',') !== false ){
        $customer_email = explode(",", $customer_email )[0];
    } 
    
    //Generate quote
    switch( $country ){
        case "se":
            $data = '{
    			"name": "Document ' . $value[ 'fields' ][ 'Company' ] . '",
    			"sender_id": "' . $salesman_info[ 'sg_ga_id' ] .'",
    			"recipients": [{
    				"fullname": "' . $value[ 'fields' ][ 'Customer\'s name' ] . '",
    				"email": "' . $customer_email . '",
    				"company_name": "' . $value[ 'fields' ][ 'Company' ] . '",
    				"company_number": "' . $value[ 'fields' ][ 'Organisationsnummer' ] . '",
    				"role": "signer"
    			}],
    			"expiration_date": "' . $salesman_info[ 'expi_date' ] . '",
    			"template_id": "' . $salesman_info[ 'sg_ga_template' ] . '"
    		}'; 
            break;
            
        case "en":
            $data = '{
    			"name": "Document ' . $value[ 'fields' ][ 'Company' ] . '",
    			"sender_id": "' . $salesman_info[ 'sg_ga_id' ] .'",
    			"recipients": [{
    				"fullname": "' . $value[ 'fields' ][ 'Customer\'s name' ] . '",
    				"email": "' . $customer_email . '",
    				"company_name": "' . $value[ 'fields' ][ 'Company' ] . '",
    				"role": "signer"
    			}],
    			"expiration_date": "' . $salesman_info[ 'expi_date' ] . '",
    			"template_id": "' . $salesman_info[ 'sg_ga_template' ] . '"
    		}'; 
            break;
            
        default:
            $data = '{
    			"name": "Document ' . $value[ 'fields' ][ 'Company' ] . '",
    			"sender_id": "' . $salesman_info[ 'sg_ga_id' ] .'",
    			"recipients": [{
    				"fullname": "' . $value[ 'fields' ][ 'Customer\'s name' ] . '",
    				"email": "' . $customer_email . '",
    				"company_name": "' . $value[ 'fields' ][ 'Company' ] . '",
    				"role": "signer"
    			}],
    			"expiration_date": "' . $salesman_info[ 'expi_date' ] . '",
    			"template_id": "' . $salesman_info[ 'sg_ga_template' ] . '"
    		}'; 
    }
    
	$url = 'https://api.getaccept.com/v1/documents';

	$ch = curl_init();
	$headers = array();
	$headers[] = 'Authorization: Bearer ' . $salesman_info[ 'access_token' ];
	$headers[] = 'Content-Type: application/json';
	curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data ); 
	curl_setopt($ch, CURLOPT_POST, 1); 
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$result = curl_exec($ch);
	curl_close($ch);
	
	$result = json_decode( $result, true);
		
	//Patch the Quote created field
	$data = '{
		"fields": {
			"Quote created": true,
			"GetAccept document ID": "' . $result[ 'id' ] . '"
		}
	}';
			
	$info = array(
		"action" => "PATCH",
		"api_url" => $airtable_config[ 'api_url' ],
		"access_token" => $airtable_config[ 'access_token' ],
		"base" => $airtable_config[ 'crm' ][ 'base' ],
		"table" => rawurlencode('Allmänt'),
		"view" => "",
		"record_id" => $value[ 'id' ],
		"data" => $data,
		"params" => array(),
	);	

	$response = airtable_api_request_single_page( $info );
}



//US meetings
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'crm' ][ 'base' ],
	"table" => rawurlencode('Allmänt'),
	"view" => rawurlencode("New meeting (Eastern Time) -> Create a quote"),
	"record_id" => "",
	"data" => "",
	"params" => array(),
);	

$records = airtable_api_request_multiple_page( $info );

foreach ( json_decode( $records, TRUE ) as $key => $value) {
	$meeting_date = new DateTime( $value[ 'fields' ][ 'Start Date & Time for Meeting' ] );
	$current_time = new DateTime();
	$dateTimeZoneUS = new DateTimeZone('America/New_York');
	$dateTimeUS = new DateTime("now", $dateTimeZoneUS);

	$timeOffset = $dateTimeZoneUS->getOffset($dateTimeUS);
	
	$meeting_date->add(new DateInterval('PT'.abs($timeOffset).'S'));

	$seconds = $meeting_date->getTimestamp() - $current_time->getTimestamp();

	if( !isset($value[ 'fields' ][ 'Quote created' ]) && $seconds < 3600 ){

        $salesman_info = get_salesman_info( $airtable_config, $value );
		create_ga_quote( $airtable_config, $salesman_info, $value, "en", $slack_access_token_Salesman );
	}
}



//Swedish meetings
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'crm' ][ 'base' ],
	"table" => rawurlencode('Allmänt'),
	"view" => rawurlencode("New meeting (Swedish Time) -> Create a quote"),
	"record_id" => "",
	"data" => "",
	"params" => array(),
);	

$records = airtable_api_request_multiple_page( $info );

foreach ( json_decode( $records, TRUE ) as $key => $value) {
	$meeting_date = new DateTime( $value[ 'fields' ][ 'Starttid för mötet' ] );
	$current_time = new DateTime();
	$dateTimeZoneSweden = new DateTimeZone('Europe/Stockholm');
	$dateTimeSweden = new DateTime("now", $dateTimeZoneSweden);
	$timeOffset = $dateTimeZoneSweden->getOffset($dateTimeSweden);

	$current_time->add(new DateInterval('PT'.abs($timeOffset).'S'));
	
	$seconds = $meeting_date->getTimestamp() - $current_time->getTimestamp();

	if( !isset($value[ 'fields' ][ 'Quote created' ]) && $seconds < 3600 ){
		$salesman_info = get_salesman_info( $airtable_config, $value );
		create_ga_quote( $airtable_config, $salesman_info, $value, "se", $slack_access_token_Salesman );
	}
}
?>