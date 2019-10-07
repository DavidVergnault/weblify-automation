<?php 
include 'weblify_api_config.php';

$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'p&pm' ][ 'base' ],
	"table" => "tblFkCr7wCsVSkOuu",
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(AND(Stage%3D'New%20production'%2C%20NOT(%7BCredit%20card%7D))%2C%20NOT(%7BDCD%7D))"),
);	

$records = json_decode( airtable_api_request_multiple_page( $info ), TRUE );

foreach( $records as $key => $value ){
   if( empty( $value[ 'fields' ][ 'Onboarding meeting'] ) && !$value[ 'fields' ][ 'Onboarding done'] ){
        $message = '{
            "channel": "' . $value[ 'fields' ][ 'Salesguy Slack ID' ] . '",
            "text": "Hi ! Could you please book an *onboarding meeting* with *' . $value[ 'fields' ][ 'Company' ] . '* asap?\nPlease use the _/onboarding_ when you get a date.",
            "attachments": [
                {
                    "blocks": [
                        {
                            "type": "divider",
                        }		
                    ]
                }
            ]
        }';
        send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
   }
}

$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'p&pm' ][ 'base' ],
	"table" => "tblFkCr7wCsVSkOuu",
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(IS_BEFORE(%7BOnboarding%20meeting%7D%2CTODAY()%20)%2C%20NOT(%7BDCD%7D))"),
	
);	

$records = json_decode( airtable_api_request_multiple_page( $info ), TRUE );
foreach( $records as $key => $value ){
	//if( $value[ 'fields' ][ 'Salesguy Slack ID'] == "ULX5YTLMU"){
		if( empty( $value[ 'fields' ][ 'Payment method' ] )){
			$message = '{
					"channel": "' . $value[ 'fields' ][ 'Salesguy Slack ID' ] . '",
					"text": "Hi ! You didn\'t let us know the *payment method* for *' . $value[ 'fields' ][ 'Company' ] . '*\nPlease use the _/onboarding_ command to let us know :slightly_smiling_face:.",
					"attachments": [
						{
							"blocks": [
								{
									"type": "divider",
								}		
							]
						}
					]
				}';
			send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
		} else {
		    if(!$value[ 'fields' ][ 'Credit card' ] && !empty($value[ 'fields' ][ 'Payment platform' ])){
    			if(in_array("Stripe", $value[ 'fields' ][ 'Payment platform' ]) ){
    				$message = '{
    						"channel": "' . $value[ 'fields' ][ 'Salesguy Slack ID' ] . '",
    						"text": "Hi ! *' . $value[ 'fields' ][ 'Company' ] . '* is supposed to pay by *card* but we still don\'t have it.\nPlease *contact a PM asap* to inform us the problem.",
    						"attachments": [
    							{
    								"blocks": [
    									{
    										"type": "divider",
    									}		
    								]
    							}
    						]
    					}';
    				send_slack_message_2( "chat.postMessage", $message, $slack_access_token_Salesman );
    			}
		    }
		}
	//}
}