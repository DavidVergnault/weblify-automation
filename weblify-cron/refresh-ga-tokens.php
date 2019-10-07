
<?php

include 'weblify_api_config.php';

function refresh_GA_token( $record_id, $airtable_config ){
    //GET API INFO -------------
    $info = array(
        "action" => "GET",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'apis' ][ 'base' ],
        "table" => rawurlencode('GetAccept'),
        "view" => "",
        "record_id" => $record_id,
        "data" => "",
        "params" => array(),
    );	

    $record_api_info = json_decode( airtable_api_request_single_page( $info ), true );

    $access_token = $record_api_info[ 'fields' ][ 'Access Token' ];
    $email = $record_api_info[ 'fields' ][ 'email' ];
    $password = $record_api_info[ 'fields' ][ 'Password' ];
    //-------------

    //Refresh access token
    $data = '{
                "email": ' . $email . ',
                "password": ' . $password . '
            }';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.getaccept.com/v1/refresh' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); //GET
    $headers = array();
    $headers[] = 'Authorization: Bearer ' . $access_token;
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result_ = curl_exec($ch);
    curl_close($ch);

    $new_token = json_decode( $result_, TRUE )[ 'access_token' ];	

    $data = '{
        "fields": {
            "Access Token": "' . $new_token . '"
        }
    }';

    $info = array(
        "action" => "PATCH",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'apis' ][ 'base' ],
        "table" => rawurlencode('GetAccept'),
        "view" => "",
        "record_id" => $record_id,
        "data" => $data,
        "params" => array(),
    );	

    airtable_api_request_single_page( $info );
}

refresh_GA_token( "recBIge4SvZD167t3", $airtable_config);
refresh_GA_token( "recPtfKwDvSU2zXrP", $airtable_config);
?>