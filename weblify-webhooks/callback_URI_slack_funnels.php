<?php 
include 'weblify_api_config.php';

function track_logs( $name, $user_id, $additional_data, $airtable_config ){
    $data = '{
        "fields": {
            "Name": "' . $name . '",
            "Slack ID": "' . $user_id . '",
            "Time": "' . date('c', time()) . '",
            "Extra data": "' . $additional_data . '"
        }
    }';
    
    $info = array(
        "action" => "POST",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'apis' ][ 'base' ],
        "table" => "tblp1ZVuakuZFUFVy",
        "view" => "",
        "record_id" => "" ,
        "data" => $data,
        "params" => array(),
    );	
    airtable_api_request_single_page( $info );
}


function get_crm_record( $id, $airtable_config ){
    $info = array(
        "action" => "GET",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'crm' ][ 'base' ],
        "table" => rawurlencode('Allmänt'),
        "view" => "",
        "record_id" => $id,
        "data" => "",
        "params" => array(),
    );	
    $record = json_decode ( airtable_api_request_single_page( $info ), true );
    return $record;
}

function get_ppm_record( $crm_id, $airtable_config ){
    $record_crm = get_crm_record( $crm_id, $airtable_config );
    
    $info = array(
        "action" => "GET",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'p&pm' ][ 'base' ],
        "table" => "tblFkCr7wCsVSkOuu",
        "view" => "",
        "record_id" => $record_crm[ 'fields' ][ 'Production & Project management - Record ID' ],
        "data" => "",
        "params" => array(),
    );	
    
    $record = json_decode ( airtable_api_request_single_page( $info ), true );
    
    return $record;
}

function patch_ppm_record( $crm_id, $fields, $airtable_config ){
    $record_id = get_crm_record( $crm_id, $airtable_config )[ 'fields' ][ 'Production & Project management - Record ID' ];
        
    $data = '{
        "fields": {
                ' . $fields . ' 
            }
    }';

    $info = array(
        "action" => "PATCH",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'p&pm' ][ 'base' ],
        "table" => rawurlencode('Allmänt'),
        "view" => "",
        "record_id" => $record_id,
        "data" => $data,
        "params" => array(),
    );
    
    airtable_api_request_single_page( $info );    
}

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

function format_times_to_sections( $times, $block_id, $date ){
    $sections = "";
    
    if( is_array( $times ) ){
        if( !empty( $times ) ){   
            foreach( $times as $time ){
                $timep1 = $time + 1; 
                $full_date = $date . "T" . $time . ":00:00";

                $sections = $sections . '{
                    "block_id": "' . $block_id . '_' . $time . '",
                    "type": "section",
                    "text": {
                        "type": "mrkdwn",
                        "text": "*' . $time . ':00 - ' . $timep1 . ':00 pm*"
                    },
                    "accessory": {
                        "action_id": "onboarding_pm_meeting_timepicker",
                        "type": "button",
                        "text": {
                            "type": "plain_text",
                            "emoji": true,
                            "text": "OK"
                        },
                        "value": "' . $full_date . '"
                    }
                },';
            }
        } else {
            $sections = $sections . '{
                "type": "section",
                "fields": [
                    {
                        "type": "plain_text",
                        "text": "No available date on this time, please pick another one",
                        "emoji": true
                    }
                ]
            },';
        }
    }
    
    $sections .= '{
        "block_id": "' . $block_id . '",
        "type": "actions",
        "elements": [
			{
                "action_id":"onboarding_pm_meeting",
				"type": "button",
				"text": {
                    "type": "plain_text",
                    "text": "Pick another date"
                },
                "style": "danger"
			}
		]
    },';
    
    $sections .= '{
        "type": "actions",
        "elements": [
			{
                "action_id":"onboarding_cancel",
				"type": "button",
				"text": {
                    "type": "plain_text",
                    "text": "Cancel"
                },
                "style": "danger"
			}
		]
    }';
    
    return $sections;
}

if ( $_SERVER['REQUEST_METHOD'] == "POST"){
	$payload = json_decode( $_POST[ 'payload' ], true );

	if( !empty( $payload ) ){
        $response_url = $payload[ 'response_url' ];
		send_slack_message( $response_url, "");  //Close the dialog if it's a dialog
		$callback_id = $payload[ 'callback_id' ];
		$channel = $payload[ 'channel' ][ 'id' ];
		
		$funnels = array(
			"won" => "Won",
			"quote_sent" => "Quote sent",
			"quote_sent_hot" => "Quote sent hot",
			"had_meeting" => "Had meeting",
			"follow_up_booked" => "Follow-up booked",
			"future" => "Future",
			"no_show" => "No-Show",
			"reschedule_agent" => "Reschedule (Agent)",
			"reschedule_client" => "Reschedule (Client)",
			"cancelled_agent" => "Cancelled (Agent)",
			"cancelled_client" => "Cancelled (Client)",
			"lost" => "Lost",
			"unable_to_attend_agent" => "Unable to attend (Agent)",
		);
		
		$did_meeting_happen = array(
			"won" => "Meeting completed",
			"quote_sent" => "Meeting completed",
			"quote_sent_hot" => "Meeting completed",
			"had_meeting" => "Meeting completed",
			"follow_up_booked" => "Meeting completed",
			"future" => "Meeting completed",
			"no_show" => "No Meeting",
			"reschedule_agent" => "Rescheduled",
			"reschedule_client" => "Rescheduled",
			"cancelled_agent" => "No Meeting",
			"cancelled_client" => "No Meeting",
			"lost" => "Meeting completed",
			"unable_to_attend_agent" => "Unable to attend (Agent)",
		);

		$payment_methods = array(
			"milestones" => "Milestones",
            "milestones_old" => "Milestones (old)",
            "milestones_invoice" => "Milestones - Invoice",
			"fifty_fifty" => "50 / 50",
			"recurring" => "Monthly",
			"invoice" => "Invoice",
			"custom" => "Custom",
		);
		
        $payment_platforms = array(
			"stripe" => "Stripe",
			"finqr" => "Finqr",
        );
        
		$currencies = array(
			"sek" => "SEK",
			"usd" => "$",
			"cnd" => "C$",
			"lb" => "£",
		);
		
		$demo_rating = array(
			"1" => ":star:",
			"2" => ":star::star:",
			"3" => ":star::star::star:",
			"4" => ":star::star::star::star:",
			"5" => ":star::star::star::star::star:",
		);
        
		switch( $payload[ 'type' ] ){
            case "block_actions": 
                $action_id =  $payload[ 'actions' ][0][ 'action_id' ];
                $block_id =  $payload[ 'actions' ][0][ 'block_id' ];
                $user_id = $payload[ 'channel' ][ 'id' ];
                $ts = $payload[ 'message' ][ 'ts' ];
                
                if( $action_id == "onboarding_company" ) { //----------------------------------------------------------------- COMPANY DROPDOWN SELECT
                    $selected_company_id = $payload[ 'actions' ][0][ 'selected_option' ][ 'value' ];
                    $selected_company_name = $payload[ 'actions' ][0][ 'selected_option' ][ 'text' ][ 'text' ];
                    track_logs( $action_id, $user_id, $selected_company_name, $airtable_config );
                    $message = '{
                        "channel": "' . $user_id . '",
                        "text": "Select an onboarding action for *' . $selected_company_name . '*",
                        "ts": "' . $ts . '",
                        "attachments": [
                            {
                                "blocks": [
                                    {
                                        "block_id": "' . $selected_company_id . '",
                                        "type": "actions",
                                        "elements": [
                                            {
                                                "action_id":"onboarding_datepicker",
                                                "type": "button",
                                                "text": {
                                                    "type": "plain_text",
                                                    "text": "Date picker",
                                                    "emoji": true
                                                },
                                            },
                                            {
                                                "action_id":"onboarding_pm",
                                                "type": "button",
                                                "text": {
                                                    "type": "plain_text",
                                                    "text": "Payment method",
                                                    "emoji": true
                                                },
                                            },
                                            {
                                                "action_id":"onboarding_cclinks",
                                                "type": "button",
                                                "text": {
                                                    "type": "plain_text",
                                                    "text": "Credit card forms",
                                                    "emoji": true
                                                },
                                            },
                                            {
                                                "action_id":"onboarding_pm_meeting",
                                                "type": "button",
                                                "text": {
                                                    "type": "plain_text",
                                                    "text": "PM meeting",
                                                    "emoji": true
                                                },
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
                    
                    send_slack_message_2( "chat.update", $message, $slack_access_token_Salesman );
                } elseif( $action_id == "send_onboarding_date" ){ //---------------------------------------------------------------------- ONBOARDING DATEPICKER
                    $date = $payload[ 'actions' ][0][ 'selected_date' ];
                    track_logs( $action_id, $user_id, $date, $airtable_config );
                    send_slack_message( $response_url, "Thank you for answering !");
                    
                    //Block ID is Record ID from CRM
                    $fields = '"Onboarding meeting": "' . $date . '"';              

                    patch_ppm_record( $block_id, $fields, $airtable_config );
                } elseif( $action_id == "onboarding_btn" ) { //--------------------------------------------------------------------- ONBOARDING DATEPICKER
                    track_logs( $action_id, $user_id, "Cancelled", $airtable_config );
                    send_slack_message( $response_url, 'As soon as you have a date, please use the _/onboarding_ command to inform us ');
                    
                } elseif( $action_id == "send_onboarding_pp_pm_ok" ) { //----------------------------------------------------------- PAYMENT METHOD 
                    track_logs( $action_id, $user_id, "OK button payment method", $airtable_config  );
                    send_slack_message( $response_url, "Thank you for answering !");
                    
                } elseif( $action_id == "send_onboarding_pp" ) { //----------------------------------------------------------------- PAYMENT METHOD 
                    //Block ID is Record ID from CRM
                    $payment_platform = $payload[ 'actions' ][0][ 'selected_option' ][ 'text' ][ 'text' ];
                    track_logs( $action_id, $user_id, $payment_platform, $airtable_config );
                    $fields = '"Payment platform": [
                                    "' . $payment_platform . '"
                                ]';
                    
                    patch_ppm_record( $block_id, $fields, $airtable_config );
                    
                } elseif( $action_id == "send_onboarding_pm" ) { //----------------------------------------------------------------- PAYMENT METHOD 
                    //Block ID is Record ID from CRM
                    $payment_method = $payload[ 'actions' ][0][ 'selected_option' ][ 'text' ][ 'text' ];
                    track_logs( $action_id, $user_id, $payment_method, $airtable_config );
                    $fields = '"Payment method": "' . $payment_method . '"';
                    
                    patch_ppm_record( $block_id, $fields, $airtable_config );
                   
                } elseif( $action_id == "onboarding_cclinks" ) { //----------------------------------------------------------------- ONBOARDING MENU
                    //Block ID is Record ID from CRM
                    track_logs( $action_id, $user_id, "CC links", $airtable_config );
                    $record = get_crm_record( $block_id, $airtable_config );
                    $language = ($record[ 'fields' ][ 'The company is based in:' ] == "Sweden" ? "se" : "en");
                    
                    send_slack_message( $response_url, "*Milestones - [20% - 30% - 30% - 10% - 10%]*:\nhttps://checkout-weblify.com/payment-v3.php?id=" . $block_id . "&method=milestones&language=" . $language . "\n\n*Milestones (old) - [10% - 30% - 25% - 15% - 20%]*:\nhttps://checkout-weblify.com/payment-v3.php?id=" . $block_id . "&method=milestones&language=" . $language . "&ver=o\n\n*50 / 50*:\nhttps://checkout-weblify.com/payment-v3.php?id=" . $block_id . "&method=5050&language=" . $language . "\n\n*Invoice*:\nhttps://checkout-weblify.com/payment-v3.php?id=" . $block_id . "&method=invoice&language=" . $language . "\n\n*Recurring*:\nhttps://checkout-weblify.com/payment-v3.php?id=" . $block_id . "&method=recurring&language=" . $language);
                    
                } elseif( $action_id == "onboarding_pm_meeting" ) { //----------------------------------------------------------------- ONBOARDING MENU
                    //Block ID is Record ID from CRM
                    track_logs( $action_id, $user_id, "Pick date PM meeting", $airtable_config );
                    $record = get_ppm_record( $block_id, $airtable_config );
                    $company_name = $record[ 'fields' ][ 'Company' ];
                    $project_manager = $record[ 'fields' ][ 'Project manager' ];
                    
                    if( empty($project_manager) ){
                        //Put default PM as Artem and update P&PM Airtable
                        $project_manager = "Artem Raunecker";
                        $fields = '"Project mananger": "' . $project_manager . '"';   
                        
                        patch_ppm_record( $block_id, $fields, $airtable_config );
                    }
                    
                    $message = '{
                        "channel": "' . $user_id . '",
                        "text": "Choose an PM meeting date for *' . $company_name . '* with *' . $project_manager . '*",
                        "ts": "' . $ts . '",
                        "attachments": [
                            {
                                "blocks": [
                                    {
                                        "block_id": "' . $block_id . '",
                                        "type": "actions",
                                        "elements": [
                                            {
                                                "action_id":"onboarding_pm_meeting_datepicker",
                                                "type": "datepicker",
                                                "placeholder": {
                                                    "type": "plain_text",
                                                    "text": "Choose a date",
                                                    "emoji": true
                                                }
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

                    send_slack_message_2( "chat.update", $message, $slack_access_token_Salesman );
                    
                } elseif( $action_id == "onboarding_pm_meeting_datepicker" ) { //---------------------------------------------------------- PM MEETING
                    //Block ID is Record ID from CRM
                    track_logs( $action_id, $user_id, "Time picker PM meeting", $airtable_config );
                    $date = $payload[ 'actions' ][0][ 'selected_date' ];
                    $date_human_readable = date_format(date_create($date), 'l jS F Y');
                    $available_dates = array( "10", "11", "12", "13", "14", "15", "16", "17" );
                    $record = get_ppm_record( $block_id, $airtable_config );
                    $project_manager = $record[ 'fields' ][ 'Project manager' ];
                    $project_manager_email = $record[ 'fields' ][ 'PM Email' ];
                    
                    $events = get_calendar_events( $project_manager_email, $date ."T00:00:00Z", $date ."T23:59:59Z" );
                    $events = json_decode( $events, true)[ 'items' ];
                    
                    if( count($events) > 0){
                        //Remove already taken times
                        foreach( $events as $key => $value ){
                            $event_start_date = date_create( $value[ 'start' ][ 'dateTime' ] );
                            $event_end_date = date_create( $value[ 'end' ][ 'dateTime' ] );
                            $event_duration = $event_end_date->diff($event_start_date);
                            $hours = $event_duration->format('%h');
                            $minutes = $event_duration->format('%i');

                            if( $minutes > 0 ){
                                $hours += 1; //In case event is crossing hours
                            }

                            for($i=0; $i < $hours; $i++ ){
                                $time_to_check = date_format($event_start_date, 'H') + $i;
                                if (($avaialable_dates_key = array_search($time_to_check, $available_dates)) !== false){
                                    unset($available_dates[$avaialable_dates_key]);
                                } 
                            }
                        }
                    }
                    //Create the sections for each available time
                    $formatted_available_dates = format_times_to_sections( $available_dates, $block_id, $date );
                    
                    $message = '{
                        "channel": "' . $user_id . '",
                        "text": "Pick a time for a PM meeting on ' . $date_human_readable . '",
                        "ts": "' . $ts . '",
                        "attachments": [
                            {
                                "blocks": [
                                    ' . $formatted_available_dates . '
                                ]
                            }		
                        ]
                    }';
                    
                    send_slack_message_2( "chat.update", $message, $slack_access_token_Salesman );
                    
                } elseif( $action_id == "onboarding_pm_meeting_timepicker" ) { //---------------------------------------------------------- PM MEETING DATEPICKER
                    track_logs( $action_id, $user_id, "Send time + date PM meeting", $airtable_config );
                    $block_id = substr($block_id, 0, -3);
                    //Block ID is Record ID from CRM
                    $selected_time = $payload[ 'actions' ][0][ 'value' ];
                    $record = get_ppm_record( $block_id, $airtable_config );
                    $selected_company_name = $record[ 'fields' ][ 'Company' ];
                    $project_manager = $record[ 'fields' ][ 'Project manager' ];
                    $project_manager_email = $record[ 'fields' ][ 'PM Email' ];
                    
                    $start_date = date_format(date_create($selected_time, timezone_open('Europe/Stockholm')), 'c');
                    $end_date = date_format(date_add(date_create($selected_time, timezone_open('Europe/Stockholm')), new DateInterval("PT1H")), "c");
                    
                    send_slack_message( $response_url, "A 1h event has been created in the calendar of " . $project_manager . " on " . date_format(date_create($selected_time, timezone_open('Europe/Stockholm')), 'r') . "\nTODO: invite client");
                    
                    $data = '{
                        "summary":"PM meeting - ' . $selected_company_name . '",
                        "description" : "PM meeting with ' . $selected_company_name . '",
                        "start":  {
                            "dateTime" : "' . $start_date . '",
                            "timeZone" : "Europe/Stockholm"
                        },
                        "end": {
                            "dateTime" : "' . $end_date . '",
                            "timeZone" : "Europe/Stockholm"
                        }
                    }';
                    
                    //Create calendar event for pm
                    create_calendar_event($project_manager_email, $data);

                } elseif( $action_id == "onboarding_pm" ) { //---------------------------------------------------------------------------- ONBOARDING MENU
                    track_logs( $action_id, $user_id, "Open payment method menu", $airtable_config );
                    $record = get_crm_record( $block_id, $airtable_config );
                    $selected_company_name = $record[ 'fields' ][ 'Company' ];
                    $payment_method_formatted = format_dropdown_select_attachement( $payment_methods );
                    $payment_platform_formatted = format_dropdown_select_attachement( $payment_platforms );
                    $message = '{
                        "channel": "' . $user_id . '",
                        "text": "Choose a payment method and platform for *' . $selected_company_name . '*",
                        "ts": "' . $ts . '",
                        "attachments": [
                            {
                                "blocks": [
                                    {
                                        "block_id": "' . $block_id . '",
                                        "type": "actions",
                                        "elements": [
                                            {
                                                "action_id":"send_onboarding_pm",
                                                "type": "static_select",
                                                "placeholder": {
                                                    "type": "plain_text",
                                                    "text": "Select an payment method",
                                                    "emoji": true
                                                },
                                                "options": ' . $payment_method_formatted . '
                                            },
                                            {
                                                "action_id":"send_onboarding_pp",
                                                "type": "static_select",
                                                "placeholder": {
                                                    "type": "plain_text",
                                                    "text": "Select an payment platform",
                                                    "emoji": true
                                                },
                                                "options": ' . $payment_platform_formatted . '
                                            },
                                            {
                                                "action_id":"send_onboarding_pp_pm_ok",
                                                "type": "button",
                                                "text": {
                                                    "type": "plain_text",
                                                    "text": "OK",
                                                    "emoji": true
                                                },
                                                "style": "primary",
                                                "value": "send_onboarding_pp_pm_ok"
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
                    send_slack_message_2( "chat.update", $message, $slack_access_token_Salesman );
                    
                } elseif( $action_id == "onboarding_datepicker" ) { //------------------------------------------------------------------- ONBOARDING MENU
                    track_logs( $action_id, $user_id, "Open datepicker onboarding meeting", $airtable_config );
                    $record = get_crm_record( $block_id, $airtable_config );
                    $selected_company_name = $record[ 'fields' ][ 'Company' ];
                    $message = '{
                        "channel": "' . $user_id . '",
                        "text": "Choose an onboarding date for *' . $selected_company_name . '*",
                        "ts": "' . $ts . '",
                        "attachments": [
                            {
                                "blocks": [
                                    {
                                        "block_id": "' . $block_id . '",
                                        "type": "actions",
                                        "elements": [
                                            {
                                                "action_id":"send_onboarding_date",
                                                "type": "datepicker",
                                                "placeholder": {
                                                    "type": "plain_text",
                                                    "text": "Choose a date",
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

                    send_slack_message_2( "chat.update", $message, $slack_access_token_Salesman );
                    
                } elseif( $action_id == "onboarding_cancel" ) {
                    track_logs( $action_id, $user_id, "Cancel onboarding command", $airtable_config );
                    $message = '{
                        "channel": "' . $user_id . '",
                        "ts": "' . $ts . '"
                    }';
                        
                    send_slack_message_2( "chat.delete", $message, $slack_access_token_Salesman );
                    
                } else {
                    exit();
                }
                break;
                
			case "dialog_submission":
				$submission = json_decode( $_POST[ 'payload' ], true )[ 'submission' ];
				$state = json_decode( $_POST[ 'payload' ], true )[ 'state' ];
				
				if ( $callback_id == 'create_ccform'){
					$form_link = "https://checkout-weblify.com/payment-v3.php?id=" . $submission[ 'company_name' ] . "&method=" . $submission[ 'payment_method' ] .  "&language=" . $submission[ 'language' ];
					if( $submission[ 'payment_method' ] == 'recurring' && !empty( $submission[ 'recurring_duration' ] ) ){
						$form_link .= "&months=" . $submission[ 'recurring_duration' ];
					} elseif($submission[ 'payment_method' ] == 'milestones' ){
						$form_link .= "&ver=" . $submission[ 'milestones_version' ];
					} else {
                        //TODO
                    }
                    track_logs( $callback_id, $channel, $form_link, $airtable_config );
					send_slack_message( $response_url, $form_link);
				} elseif ( $callback_id == 'rogue_quote' ) {
                    
					//Get quote data from GA
					//GET API INFO -------------
					$record_id_GA_api = "recBIge4SvZD167t3";
					
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
						"record_id" => $record_id_GA_api,
						"data" => $data,
						"params" => array(),
					);	
					airtable_api_request_single_page( $info );
					$access_token = $new_token;
					
					$state_array = explode(",", $state);
					$quote_id = $state_array[0];
					$response_url_state = $state_array[1];
					track_logs( $callback_id, $channel, $quote_id, $airtable_config );
					$url = 'https://api.getaccept.com/v1/documents/' . $quote_id;
					$ch = curl_init();
					$headers = array();
					$headers[] = 'Authorization: Bearer ' . $access_token;
					$headers[] = 'Content-Type: application/json';
					curl_setopt($ch, CURLOPT_URL, $url );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
					curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
					$result = curl_exec($ch);
					curl_close($ch);

					$result_quote = json_decode( $result, true);
					
					//Patch the CRM
					$data = json_encode( array (
						"fields" => array(
							"GetAccept document ID" => $result_quote[ 'id' ],
							"Value" => $result_quote[ 'value' ],
							"Signed" => $result_quote[ 'sign_date' ],
							"GetAccept - Date quote sent" => $result_quote[ 'send_date' ],
						)	
					));
					
					$info = array(
						"action" => "PATCH",
						"api_url" => $airtable_config[ 'api_url' ],
						"access_token" => $airtable_config[ 'access_token' ],
						"base" => $airtable_config[ 'crm' ][ 'base' ],
						"table" => rawurlencode('Allmänt'),
						"view" => "",
						"record_id" => $submission[ 'airtable_record_id' ],
						"data" => $data,
						"params" => array(),
					);	
					$response = airtable_api_request_single_page( $info );
					
					if ( empty( json_decode($response,true)['error'] ) ){
						send_slack_message( $response_url_state, "Thanks for answering :slightly_smiling_face:!"); //Replace the button message by a Thanks message
					} else {
                        send_slack_message( $response_url, "The process didn't work, try again or ask David :)");  //Close the dialog
                    }
					
				} else {				
					
					$info = array(
						"action" => "GET",
						"api_url" => $airtable_config[ 'api_url' ],
						"access_token" => $airtable_config[ 'access_token' ],
						"base" => $airtable_config[ 'crm' ][ 'base' ],
						"table" => rawurlencode('Allmänt'),
						"view" => "",
						"record_id" => $callback_id,
						"data" => "",
						"params" => array(),
					);	

					$record_crm = json_decode( airtable_api_request_single_page( $info ), true );

					$sg_funnel_base_ID = $record_crm[ 'fields' ][ '(Linked to "Sales agents") - Sellers Funnel Base ID' ][0];
					$sg_funnel_table_ID = $record_crm[ 'fields' ][ '(Linked to "Sales agents") - Sellers Funnel Table ID' ][0];

					$price = ( empty( $submission[ 'value' ] ) ? "null" : $submission[ 'value' ] );
					$touch_comment = ( empty( $submission[ 'touch_comment' ] ) ? "null" : json_encode( $submission[ 'touch_comment' ] ) );
					$next_touch_point = ( empty( $submission[ 'next_touch_point' ] ) ? "null" : json_encode( $submission[ 'next_touch_point' ] ) );
					$payment_method = ( empty( $submission[ 'payment_method' ] ) ? "null" : json_encode( $payment_methods[ $submission[ 'payment_method' ] ] ) );

					if( !empty( $sg_funnel_base_ID ) && !empty( $sg_funnel_table_ID ) ){
						$data = '{
							"fields": {
								"Status - Funnel": "' . $funnels[ $submission[ 'funnel' ] ] . '",
								"Notes after meeting": ' . json_encode( $submission[ 'notes_after_meeting' ] ) . ',
								"Demo Rating": ' . $submission[ 'demo_rating' ] . ',
								"Touch-comment": ' . $touch_comment . ',
								"Value": ' . $price . ',
								"Next touchpoint": ' . $next_touch_point . '
							}
						}';

						$info = array(
							"action" => "GET",
							"api_url" => $airtable_config[ 'api_url' ],
							"access_token" => $airtable_config[ 'access_token' ],
							"base" => $sg_funnel_base_ID,
							"table" => $sg_funnel_table_ID,
							"view" => "",
							"record_id" => "",
							"data" => "",
							"params" => array( "filterByFormula" => "%7BStatus%20-%20Funnel%7D%20%3D%20%27%27"),
						);	

						$records_funnel = airtable_api_request_multiple_page( $info );
						$records_funnel = json_decode( $records_funnel, true );

						foreach( $records_funnel as $key => $value){
							if ( $value[ 'fields' ][ 'Record ID CRM' ] == $callback_id){
								$record_id_funnel =  $value[ 'id' ];
								break;
							}
						}

						$info = array(
							"action" => "PATCH",
							"api_url" => $airtable_config[ 'api_url' ],
							"access_token" => $airtable_config[ 'access_token' ],
							"base" => $sg_funnel_base_ID,
							"table" => $sg_funnel_table_ID,
							"view" => "",
							"record_id" => $record_id_funnel,
							"data" => $data,
							"params" => array(),
						);	

						$reponse_funnel = airtable_api_request_single_page( $info );
					}

					$data = '{
							"fields": {
								"Status - Funnel": "' . $funnels[ $submission[ 'funnel' ] ] . '",
								"Did the meeting happen?": "' . $did_meeting_happen[ $submission[ 'funnel' ] ] . '",
								"Notes after meeting": ' . json_encode( $submission[ 'notes_after_meeting' ] ) . ',
								"Demo Rating": ' . $submission[ 'demo_rating' ] . ',
								"Touch-comment": ' . $touch_comment . ',
								"Value": ' . $price . ',
								"Payment method": ' . $payment_method . ',
								"Next touchpoint": ' . $next_touch_point . ',
								"Currency": "' . $currencies[ $submission[ 'currency' ] ] . '"
							}
						}';
					   track_logs( $callback_id, $channel, $data, $airtable_config );

					$info = array(
						"action" => "PATCH",
						"api_url" => $airtable_config[ 'api_url' ],
						"access_token" => $airtable_config[ 'access_token' ],
						"base" => $airtable_config[ 'crm' ][ 'base' ],
						"table" => rawurlencode('Allmänt'),
						"view" => "",
						"record_id" => $callback_id,
						"data" => $data,
						"params" => array(),
					);	

					$response = airtable_api_request_single_page( $info );

					if ( empty( json_decode( $response, true )[ 'error' ] ) ){
						send_slack_message( $response_url, "");  //Close the dialog
						send_slack_message( $state, "Thanks for answering :slightly_smiling_face:!"); //Replace the button message by a Thanks message
					}
		
				

				}
				break;
				
			case "interactive_message":
				$value = $payload[ 'actions' ][ 0 ][ 'value' ];
				$trigger_id = json_decode( $_POST[ 'payload' ], true )[ 'trigger_id' ];
					   track_logs( $value, $channel, "Open Funnel form", $airtable_config );

				if( $value == 'open_form'){
					$funnels_select = format_dropdown_select( $funnels );
					$payment_methods_select = format_dropdown_select( $payment_methods );
					$currencies_select = format_dropdown_select( $currencies );
					$demo_rating_select = format_dropdown_select( $demo_rating );

					$dialog = '{
						"trigger_id": "' . $trigger_id . '",
						"channel": "' . $channel . '",
						"dialog": {
							"callback_id": "' . $callback_id . '",
							"state": "' . $response_url . '",
							"title": "Meeting informations",
							"submit_label": "Send",
							"notify_on_cancel": true,
							"elements": [
								{
								  "label": "Funnel",
								  "name": "funnel",
								  "type": "select",
								  "options": ' . $funnels_select . '
								},
								{
									"type": "textarea",
									"label": "Notes after meeting",
									"name": "notes_after_meeting"
								},
								{
								  "label": "Demo rating",
								  "name": "demo_rating",
								  "type": "select",
								  "options": ' . $demo_rating_select . '
								},
								{
									"type": "text",
									"label": "Value",
									"name": "value",
									"placeholder": "Only numbers WITHOUT SPACEING OR COMMA",
									"subtype": "number",
								},
								{
									"label": "Currency",
									"name": "currency",
									"type": "select",
									"options": ' . $currencies_select . '
								},
								{
									"type": "text",
									"label": "Next touch point",
									"name": "next_touch_point",
									"optional":true,
									"placeholder":"YYYY-MM-DD   (PLEASE follow this format)",
									"max_lenght": 10
								},
								{
									"type": "textarea",
									"label": "Touch comment",
									"name": "touch_comment",
									"optional": true
								}
							]
						 }
					}';

					send_slack_message_2( "dialog.open", $dialog, $slack_access_token_Salesman );
					
				} elseif ( $value == 'rogue_quote'){
                    track_logs( $value, $user_id, "Open Rogue quote form", $airtable_config );
					$dialog = '{
						"trigger_id": "' . $trigger_id . '",
						"channel": "' . $channel . '",
						"dialog": {
							"callback_id": "rogue_quote",
							"state": "' . $callback_id . ',' . $response_url . '",
							"title": "Quote belongs to?",
							"submit_label": "Send",
							"notify_on_cancel": true,
							"elements": [
								{
									"type": "text",
									"label": "Airtable record ID",
									"name": "airtable_record_id",
									"placeholder": "\"Record ID\" field from Airtable or \"ID\" from Calendar placeholder",
								},
							]
						 }
					}';
								
					send_slack_message_2( "dialog.open", $dialog, $slack_access_token_Salesman ); 
                } else {
                    track_logs( "unknown", $user_id, "???", $airtable_config );
					exit();
				}
				break;
			
			default:
				exit();
		}
	}
}