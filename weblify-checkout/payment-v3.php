<?php

define( 'AUTH', TRUE );

$method = $_GET[ 'method' ];
$id = $_GET[ 'id' ];

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
		if ( empty ( $info[ 'view' ] ) ){
			if ( $info[ 'action' ] == 'POST' ){
				if ( !empty( $info[ 'data' ] ) ){
					$headers[] = 'Content-Type: application/json';
					curl_setopt($ch, CURLOPT_URL, $url );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $info[ 'data' ] ); 
					curl_setopt($ch, CURLOPT_POST, 1);
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
    curl_close($ch);

    return $result;
}

$airtable_config = array(
    "api_url" => "https://api.airtable.com/v0",
    "access_token" => "keyvRHcm5s0zpuk7H",
    "crm" => array(
		"base" => "apprx7JtXLnshReTF",
	),
);

if ( !empty( $id ) && !empty( $method ) ){

    //Get current records
    $info = array(
        "action" => "GET",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'crm' ][ 'base' ],
        "table" => rawurlencode('Allmänt'),
        "view" => "",
        "record_id" => $id,
        "data" => '',
        "params" => array(),
    );	

    $record = json_decode( airtable_api_request_single_page( $info ), true);

    if( !empty( $record[ 'error' ]) ){
        exit();
    }

    $currency = $record[ 'fields' ][ 'Currency' ];
    $price = $record[ 'fields' ][ 'Value' ];
    $taxes = $price * 0.25;                              
    switch( $record[ 'fields' ][ 'The company is based in:' ] ){
        case "Sweden":
            $price_formatted = number_format( $price ) . ' ' . $currency; 
            $taxes_formated = number_format( $taxes ) . ' ' . $currency; 
            $total_price_formatted = number_format ( $price + $taxes ) . ' ' . $currency;
            break;
            
        default:
            $price_formatted = $currency . ' ' . number_format( $price ); 
    }

    $company_name = $record[ 'fields' ][ 'Company' ];
    $customer_name = $record[ 'fields' ][ 'Customer\'s name' ];
    $customer_firstname = explode(' ', $customer_name)[0];
    $customer_lastname = substr(strstr($customer_name," "), 1);
    $customer_email = $record[ 'fields' ][ 'Customer\'s Email'];
    $pastel_link = $record[ 'fields' ][ 'Sales meeting - Pastel link'];
    $signed_date = $record[ 'fields' ][ 'Signed' ];
    $support_package_price = $record[ 'fields' ][ 'Support package price' ];

    switch( $method ){
        case "5050":
            include 'forms/5050.php';
            break;
        case "invoice":
            include 'forms/invoice.php';
            break;
        case "recurring":
            include 'forms/recurring.php';
            break;
        case "milestones":
            include 'forms/milestones.php';
            break;
        case "milestones-dev":
            include 'forms/milestones-dev.php';
            break;
        case "support-package":
            include 'forms/support-package.php';
            break;
        default:
            exit('Invalid payment method');
    }
}


?>