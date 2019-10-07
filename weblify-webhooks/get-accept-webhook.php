<?php 

include 'weblify_api_config.php';

// Takes raw data from the request
$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json, TRUE);

$sender_name = $data[ 'document' ][ 'sender_name' ];
$id = $data[ 'document' ][ 'id' ];
$company_name = $data[ 'recipient' ][ 'company_name' ];
$customer_email = $data[ 'recipient' ][ 'email' ];
$price = $data[ 'document' ][ 'value' ];
$sign_date = $data[ 'document' ][ 'sign_date' ];
$send_date = $data[ 'document' ][ 'send_date' ];
$user_id = $data[ 'document' ][ 'user_id' ];

$message = '{
    "channel": "GMNPZAJ2K",
    "text": "' . print_r($data,TRUE) . '",
}';
send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Weblify );

function get_currency_from_country( $country ){ 
    switch( $country ){
        case "Sweden":
            $currency = "SEK";
            break;

        case "USA":
            $currency = "$";
            break;

        case "Canada":
            $currency = "C$";
            break;

        case "United Kingdom":
            $currency = "£";
            break;

        default:
            $currency = "SEK";
    }
    
    return $currency;
} 
   
    
    
switch( $data[ 'document' ][ 'status' ] ){
    case "signed":
        //Send a slack message to David with all infos
       
        switch ( $user_id ){
            case "xpvd57yp": //Pontus
                $custom_message = "Time to celebrate !";
                break;

            case "8pk6k8rn": //Gadd
                $custom_message = "Walk it Talk it !";
                break;

            case "gnreyq9p": //Gustaf
                $custom_message = "He is Taking Off !";
                break;

            case "gnx9g39p": //Pontus Backman
                $custom_message = "All he does is win !";
                break;

            default:
                $custom_message = "Time to celebrate !";

        }
        if ( $price == 0 ){
            $message = '*' . $sender_name . '*' . ' has just signed with '. $company_name .' !\n' . $custom_message;
        } else {
            $message = '*' . $sender_name . '*' . ' has just signed for ' . number_format( $price/1000 ) . 'K with ' . $company_name . ' !\n' . $custom_message;
        }

        //Send message to Mia
        $message = '{
                "channel": "UF59UN7RB",
                "text": "' . $message . '",
            }';
        send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Weblify );
        
        send_slack_message( $slack_channel_Mia, $message );
        send_slack_message( $slack_channel_Ellen, $message );
        
        $info = array(
            "action" => "GET",
            "api_url" => $airtable_config[ 'api_url' ],
            "access_token" => $airtable_config[ 'access_token' ],
            "base" => $airtable_config[ 'crm' ][ 'base' ],
            "table" => rawurlencode('Allmänt'),
            "view" => "",
            "record_id" => "",
            "data" => "",
            "params" => array( "filterByFormula" => "NOT%28%7BGetAccept%20document%20ID%7D%20%3D%20%27%27%29"),
        );	

        $records = airtable_api_request_multiple_page( $info );	
        $records = json_decode( $records, true );

        $found = false;
        foreach( $records as $key => $value){
            if ( $value[ 'fields' ][ 'GetAccept document ID' ] == $id ){
                $currency = get_currency_from_country( $value[ 'fields' ][ 'The company is based in:' ] );          
                
                
                
                //Update the CRM
                $data = json_encode( array(
                    "fields" => array(
                        "Did the meeting happen?" => "Meeting completed",
                        "Value" => (int) $price,
                        "Signed" => $sign_date,
                        "GetAccept - Date quote sent" => $send_date,
                        "Currency" => $currency,
                        "Customer\'s Email" => $customer_email,
                    )
                ), true);   
                $info = array(
                    "action" => "PATCH",
                    "api_url" => $airtable_config[ 'api_url' ],
                    "access_token" => $airtable_config[ 'access_token' ],
                    "base" => $airtable_config[ 'crm' ][ 'base' ],
                    "table" => rawurlencode('Allmänt'),
                    "view" => "",
                    "record_id" => $value['id'],
                    "data" => $data,
                    "params" => "",
                );
                $record_data = json_decode( airtable_api_request_single_page( $info ), true );                

                $found = true;
                break;
            }
        }

        if ( $found ){
            $message_log = '{
                "channel": "GMNPZAJ2K",
                "text": "Signed quote CRM record *found* : ' . $id . '",
            }';
            send_slack_message_2( "chat.postMessage", $message_log, $slack_access_token_Weblify );
            
            $message = '{
                "channel": "' . $record_data[ 'fields' ][ '(Linked to "Sales agents") - Salesguy Slack ID' ][0] . '",
                "text": "Heyyyyy congratz on your sign with *' . $record_data[ 'fields' ][ 'Company' ] . '* :tada: :tada:!!!\nHave you booked an onboarding meeting with them?",
                "attachments": [
                    {
                        "blocks": [
                            {
                                "block_id": "' . $record_data[ 'id' ] . '",
                                "type": "actions",
                                "elements": [
                                    {
                                        "action_id":"send_onboarding_date",
                                        "type": "datepicker",
                                        "placeholder": {
                                            "type": "plain_text",
                                            "text": "Select a date",
                                            "emoji": true
                                        },
                                        "confirm": {
                                            "text": {
                                                "type": "plain_text",
                                                "text":"Are you sure ?"
                                            }
                                        }
                                    },
                                    {
                                        "action_id":"onboarding_btn",
                                        "type": "button",
                                        "text": {
                                            "type": "plain_text",
                                            "text": "Not booked yet",
                                            "emoji": true
                                        },
                                        "style": "danger",
                                        "value": "no_onboarding_booked"
                                    }
                                ]
                            }		
                        ]
                    }
                ]
            }';
           
            send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
        } else {
            $message = '{
                "channel": "GMNPZAJ2K",
                "text": "Signed quote CRM record *not found* : ' . $id . '",
            }';
            send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Weblify );
            
            //Send message to salesguy
            $info = array(
                "action" => "GET",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'crm' ][ 'base' ],
                "table" => "tblUQoTUBtH0UpwQ3",
                "view" => "",
                "record_id" => "",
                "data" => "",
                "params" => array(),
            );	

            $records = airtable_api_request_multiple_page( $info );	
            $records = json_decode( $records, true );
            
            foreach( $records as $key => $value ){
                if ( $value[ 'fields' ][ 'GetAccept User ID'] == $user_id ){
                    $slack_id = $value[ 'fields' ][ 'Salesguy Slack ID'];
                    break;
                }
            }
            
            if ( !empty( $slack_id ) ){
                $message = '{
                    "channel": "' . $slack_id . '",
                    "text": "Hey, I just saw you sent a quote you created manually. Can you please tell me to which company it correspond? :smile:",
                    "attachments": [
                        {
                            "fallback": "",
                            "callback_id": "' . $id . '",
                            "color": "#F2015A",
                            "attachment_type": "default",
                            "actions": [
                                {
                                    "name": "choice",
                                    "text": "Yes!",
                                    "type": "button",
                                    "value": "rogue_quote",
                                },
                            ]
                        }
                    ]
                }';
                
                send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
            }
        }
     
        break;
        
    case "sent":     
        $info = array(
            "action" => "GET",
            "api_url" => $airtable_config[ 'api_url' ],
            "access_token" => $airtable_config[ 'access_token' ],
            "base" => $airtable_config[ 'crm' ][ 'base' ],
            "table" => rawurlencode('Allmänt'),
            "view" => "",
            "record_id" => "",
            "data" => "",
            "params" => array( "filterByFormula" => "NOT%28%7BGetAccept%20document%20ID%7D%20%3D%20%27%27%29"),
        );	

        $records = airtable_api_request_multiple_page( $info );	
        $records = json_decode( $records, true );

        $found = false;
        foreach( $records as $key => $value){
            if ( $value[ 'fields' ][ 'GetAccept document ID' ] == $id ){
                $currency = get_currency_from_country( $value[ 'fields' ][ 'The company is based in:' ] );
                
                //Update the CRM
                $data = json_encode( array(
                    "fields" => array(
                        "Did the meeting happen?" => "Meeting completed",
                        "Value" => (int) $price,
                        "GetAccept - Date quote sent" => $send_date,
                        "Currency" => $currency,
                    )
                ));  
                $info = array(
                    "action" => "PATCH",
                    "api_url" => $airtable_config[ 'api_url' ],
                    "access_token" => $airtable_config[ 'access_token' ],
                    "base" => $airtable_config[ 'crm' ][ 'base' ],
                    "table" => rawurlencode('Allmänt'),
                    "view" => "",
                    "record_id" => $value['id'],
                    "data" => $data,
                    "params" => "",
                );
                airtable_api_request_single_page( $info );	

                $found = true;
                break;
            }
        }

        if ( $found ){
            $message = '{
                "channel": "GMNPZAJ2K",
                "text": "Sent quote CRM record *found* : ' . $id . '",
            }';
            send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Weblify );
        } else {
            $message = '{
                "channel": "GMNPZAJ2K",
                "text": "Sent quote CRM record *not found* : ' . $id . '",
            }';
            send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Weblify );
            
            //Send message to salesguy
            $info = array(
                "action" => "GET",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'crm' ][ 'base' ],
                "table" => "tblUQoTUBtH0UpwQ3",
                "view" => "",
                "record_id" => "",
                "data" => "",
                "params" => array(),
            );	

            $records = airtable_api_request_multiple_page( $info );	
            $records = json_decode( $records, true );
            
            foreach( $records as $key => $value ){
                if ( $value[ 'fields' ][ 'GetAccept User ID'] == $user_id ){
                    $slack_id = $value[ 'fields' ][ 'Salesguy Slack ID'];
                    break;
                }
            }
            
            if ( !empty( $slack_id ) ){
                $message = '{
                    "channel": "' . $slack_id . '",
                    "text": "Hey, I just saw you sent a quote you created manually. Can you please tell me to which company it correspond? :smile:",
                    "attachments": [
                        {
                            "fallback": "",
                            "callback_id": "' . $id . '",
                            "color": "#F2015A",
                            "attachment_type": "default",
                            "actions": [
                                {
                                    "name": "choice",
                                    "text": "Yes!",
                                    "type": "button",
                                    "value": "rogue_quote",
                                },
                            ]
                        }
                    ]
                }';
                
                send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
            }      
        }
        
        break;
        
    default:
        
}


?>