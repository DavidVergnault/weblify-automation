<?php 

include 'weblify_api_config.php';

function format_dropdown_select( $array ){
	$array_select = "";
	
	if( is_array( $array ) ){	
		$array_select = "[";
		foreach( $array as $key => $value){
			$array_select .= '{
				"label": "' . $value . '",
				"value": "' . $key . '"
			},';
		}
		$array_select .= "]";
	}

	return $array_select;
}

function format_dropdown_select_attachement( $array ){
	$array_select = "";
	
	if( is_array( $array ) ){	
		$array_select = "[";
		foreach( $array as $key => $value){
			$array_select .= '{
				"text": {
                    "type": "plain_text",
                    "text": "' . $value . '",
                    "emoji": true
                },
				"value": "' . $key . '"
			},';
		}
		$array_select .= "]";
	}

	return $array_select;
}

if ( $_SERVER['REQUEST_METHOD'] == "POST"){
	$command = $_POST[ 'command' ];
	$channel_id = $_POST[ 'channel_id' ];
	$user_id = $_POST[ 'user_id' ];
	$text = $_POST[ 'text' ];
	$response_url = $_POST[ 'response_url' ];
	$trigger_id = $_POST[ 'trigger_id' ];
	
	if( $command == "/ccform" ){
        $companies = [];
		if( !empty( $text ) ){
            $info = array(
                "action" => "GET",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'crm' ][ 'base' ],
                "table" => "tblUTj3J2tbubKK3f",
                "view" => "",
                "record_id" => $text,
                "data" => "",
                "params" => array()
            );

            $record = json_decode( airtable_api_request_single_page( $info ), true );
            
            if( !empty( $record[ 'error' ] ) ){
                send_slack_message( $response_url, "I can't find any airtable record with that id :disappointed:. Sorry !");
                exit();
            } else {
                $companies[ $record[ 'fields' ][ 'Record ID' ] ] = $record[ 'fields' ][ 'Company' ];
            }
        } else {
        
            $info = array(
                "action" => "GET",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'crm' ][ 'base' ],
                "table" => "tblUTj3J2tbubKK3f",
                "view" => "",
                "record_id" => "",
                "data" => "",
                "params" => array( "filterByFormula" => rawurlencode("AND(AND({(Linked to \"Sales agents\") - Salesguy Slack ID}='{$user_id}',{Status - Funnel}='Won'),DATETIME_DIFF(TODAY(),{Signed},'days')<62)") )
            );

            $records = airtable_api_request_multiple_page( $info );
            $records = json_decode( $records, true );

            if ( count($records) > 0 ){
                foreach( $records as $key => $value ){
                    $companies[ $value[ 'fields' ][ 'Record ID' ] ] = $value[ 'fields' ][ 'Company' ];
                }
            } else {
                send_slack_message( $response_url, "I can't find any signed deal in your name :disappointed: Sorry !");
                exit();
            }
        }
        
        $companies_select = format_dropdown_select( $companies );
        $payment_methods = array(
				"milestones" => "Milestones",
                "support-package" => "Support package",
                "5050" => "50 / 50",
				"recurring" => "Recurring",
				"invoice" => "Normal invoice (end of production)",
			);
			$payment_methods_select = format_dropdown_select( $payment_methods );
        $dialog = '{
                    "trigger_id": "' . $trigger_id . '",
                    "channel": "' . $user_id . '",
                    "dialog": {
                        "title": "Create CC form",
                        "callback_id": "create_ccform",
                        "submit_label": "Create",
                        "notify_on_cancel": true,
                        "elements": [
                            {
                              "label": "Company name",
                              "name": "company_name",
                              "type": "select",
                              "options": ' . $companies_select . '
                            },
                            {
                              "label": "Payment method",
                              "name": "payment_method",
                              "type": "select",
                              "options": ' . $payment_methods_select . '
                            },
                            {
                              "label": "Language",
                              "name": "language",
                              "type": "select",
                              "options": [
                                {
                                    "label": "Swedish",
                                    "value": "se"
                                },
                                {
                                    "label": "English",
                                    "value": "en"
                                },
                              ]
                            },  
                            {
                              "label": "Milestones version",
                              "name": "milestones_version",
                              "type": "select",
                              "optional": true,
                              "options": [
                                {
                                    "label": "New (20%, 30%, 30%, 10%, 10%)",
                                    "value": "n"
                                },
                                {
                                    "label": "Old (10%, 30%, 25%, 15%, 20%)",
                                    "value": "o"
                                },
                              ]
                            }, 
                            {
                              "label": "Recurring duration",
                              "name": "recurring_duration",
                              "type": "select",
                              "optional": true,
                              "options": [
                                {
                                    "label": "12",
                                    "value": "12"
                                },
                                {
                                    "label": "24",
                                    "value": "24"
                                },
                              ]
                            },
                        ]
                     }
                }';
        send_slack_message_2( "dialog.open", $dialog, $slack_access_token_Salesman );
	} elseif ( $command == "/creditcheck" ){
        send_slack_message( $response_url, "Fetching data... Please wait a few seconds" );
        
        if( !( strlen( $text ) == 11 && $text[6] == '-' ) ){
            send_slack_message( $response_url, "This organisationsnummer is wrong or empty: " . $text );
            exit();
        }
        
        $client_id_test = "14";
        $client_id = "4133";
		$url_test = "https://sysfaktura-kundtest.aroskapital.se/api/v2/creditcheck";
        $url = "https://sysfaktura.aroskapital.se/api/v2/creditcheck";
		$test_login = "weblify";
        $login = "weblify";
		$test_password = "foqmxj";
        $password = "YK7eT~uwz!";
		$data = '{
		  "ClientId": ' . $client_id . ',
		  "OrganizationNumber": "' . $text . '",
		  "CountryCode": "SE"
		}';

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $login . ':' . $password);
		curl_setopt($ch, CURLOPT_POST, 1);
		$headers = array();
		$headers[] = 'Content-Type: application/json';
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$response = curl_exec($ch);
		curl_close($ch);

        $response = json_decode($response,true);
        if( !array_key_exists( 'AvailableCredit', $response ) ){
            send_slack_message( $response_url, "No data found for this organisationsnummer:" . $text );
        } else {
            $message = "Information about organisationsnummer: *" . $text . "*\n";
            foreach( $response as $key => $value ){
                $message .= "*" . $key . "* => " . $value . "\n"; 
            }
		    send_slack_message( $response_url, $message );
        }
	} elseif( $command == '/onboarding'){
        $receiver_id = $user_id;
        $admins = [ 'UH2UW9HRC', 'ULX5YTLMU', 'UG9E24TGB' ];
        if( !empty($text) && in_array($user_id, $admins)){
            $receiver_id = $user_id;
            $user_id = $text;
        }

        $info = array(
            "action" => "GET",
            "api_url" => $airtable_config[ 'api_url' ],
            "access_token" => $airtable_config[ 'access_token' ],
            "base" => $airtable_config[ 'p&pm' ][ 'base' ],
            "table" => "tblFkCr7wCsVSkOuu",
            "view" => "viwYzDalmGqoBuNHR",
            "record_id" => "",
            "data" => '',
            "params" => array("filterByFormula" => '%7BSalesguy%20Slack%20ID%7D%3D%22' . $user_id . '%22'),
        );	
        $records = json_decode( airtable_api_request_single_page( $info ), TRUE );
        $choices = array();

        foreach( $records['records'] as $key => $value ){
            if( !empty($value[ 'fields' ][ 'CRM Record ID'])){
                $choices[ $value[ 'fields' ][ 'CRM Record ID' ] ] = $value[ 'fields' ][ 'Company' ];
            }
        }

        if( empty($choices) ){
            send_slack_message( $response_url, "No project has been found on your name, sorry :cry:" );
            exit();
        }  

        $choices_formatted = format_dropdown_select_attachement( $choices ); 

        $message = '{
            "channel": "' . $receiver_id . '",
            "text": "Which company do you want to open the onboarding menu for?",
            "attachments": [
                {
                    "blocks": [
                        {
                            "type": "actions",
                            "elements": [
                                {
                                    "action_id":"onboarding_company",
                                    "type": "static_select",
                                    "placeholder": {
                                        "type": "plain_text",
                                        "text": "Select a company",
                                        "emoji": true
                                    },
                                    "options": ' . $choices_formatted . '
                                },
                                {
                                    "action_id":"onboarding_cancel",
                                    "type": "button",
                                    "text": {
                                        "type": "plain_text",
                                        "text": "Cancel",
                                        "emoji": true
                                    },
                                    "style": "danger",
                                }
                            ]
                        }
                    ]
                }		
            ]
        }';
        send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );    
    } 
    else {
		send_slack_message( $response_url, "I didn't understand your request, please use the hint to see the right command syntax");
	}
}