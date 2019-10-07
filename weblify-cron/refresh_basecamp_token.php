<?php

include 'weblify_api_config.php';

//Get Airtable record data ----------------
$airtable_table_basecamp = "tbln5OOqdZGrRAtcV";
$record_id = "rec8UopyU21u3xQt3"; //TODO : Change the id to the real account
$request = $client_airtable->request('GET', "v0/" . $airtable_config[ 'apis' ][ 'base' ] . '/' . $airtable_table_basecamp . '/' . $record_id, [
    'headers' => [
        'Authorization' => 'Bearer ' . $airtable_config[ 'access_token' ],
    ],
]);
check_200_code( $request, "Failed to get record data for " . $record_id . " ID, in " . $airtable_config[ 'apis' ][ 'base' ] . "base, table " . $airtable_table_basecamp );
$response = json_decode( $request->getBody(), true );
//-----------
    
//Refresh the token ----------
$request = $client_basecamp_launchpad->request('POST', "authorization/token", [
    'query' => [
        "refresh_token" => $response['fields'][ 'Refresh Token' ],
        "client_id" => $response['fields'][ 'Client ID' ],
        "client_secret" => $response['fields'][ 'Client Secret' ],
        "grant_type" => "refresh_token",
        "type" => "refresh",
    ],
]);
check_200_code( $request, "Failed to refresh Basecamp token" );
$response = json_decode( $request->getBody(), true );
//------------------
    
    
//Update Airtable ------------
$request = $client_airtable->request('PATCH', "v0/" . $airtable_config[ 'apis' ][ 'base' ] . '/' . $airtable_table_basecamp . '/' . $record_id, [
    'headers' => [
        'Authorization' => 'Bearer ' . $airtable_config[ 'access_token' ],
    ],
    'json' => [
        'fields' => [
            "Access Token" => $response['access_token'],
        ], 
    ],
]);
check_200_code( $request, "Failed to update record data for " . $record_id . " ID, in " . $airtable_config[ 'apis' ][ 'base' ] . "base, table " . $airtable_table_basecamp );
//------------
    
?>