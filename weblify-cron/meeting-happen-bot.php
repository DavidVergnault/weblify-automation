<?php 
include 'weblify_api_config.php';

$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'crm' ][ 'base' ],
	"table" => rawurlencode('Allmänt'),
	"view" => rawurlencode('David Automation - Check if meeting did happen'),
	"record_id" => "",
	"data" => '',
	"params" => array(),
);	

$records = json_decode( airtable_api_request_multiple_page( $info ), TRUE );

foreach( $records as $key => $value ){
    $meeting_date = new DateTime( $value[ 'fields' ][ 'Start Date & Time for Meeting (Swedish time)' ] ); //US meeting
    $current_time = new DateTime("now", new DateTimeZone('Europe/Stockholm'));
    $seconds = $current_time->getTimestamp() - $meeting_date->getTimestamp();	
    $message = "";
    if ( 3600 < $seconds && $seconds < 7200 ){
        $message = '{
                "channel": "' . $value[ 'fields' ][ '(Linked to "Sales agents") - Salesguy Slack ID' ][0] . '",
                "text": "How did the meeting happen with *' . $value[ 'fields' ][ 'Company' ] . '* go ?",
                "attachments": [
                    {
                        "fallback": "",
                        "callback_id": "' . $value[ 'id' ] . '",
                        "color": "#F2015A",
                        "attachment_type": "default",
                        "actions": [
                            {
                                "name": "choice",
                                "text": "Open the form",
                                "type": "button",
                                "value": "open_form",
                            },
                        ]
                    }
                ]
            }';
    }

    if ( $seconds >= 7200 && ( $seconds % 21600 < 3600 ) ){
        $message = '{
                "channel": "' . $value[ 'fields' ][ '(Linked to "Sales agents") - Salesguy Slack ID' ][0] . '",
                "text": "Hey you didn\'t tell me if the meeting happened with *' . $value[ 'fields' ][ 'Company' ] . '*.\nHow did it go ?",
                "attachments": [
                    {
                        "fallback": "",
                        "callback_id": "' . $value[ 'id' ] . '",
                        "color": "#F2015A",
                        "attachment_type": "default",
                        "actions": [
                            {
                                "name": "choice",
                                "text": "Open the form",
                                "type": "button",
                                "value": "open_form",
                            },
                        ]
                    }
                ]
            }';
    }

    if ( !empty( $message ) ){
        send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
    }
}
 



