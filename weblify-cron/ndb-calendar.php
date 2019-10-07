<?php

include 'weblify_api_config.php';

//Trigger on "Finished"
$filename_storage = "old-records-ndb.json";
$file_path = __DIR__ . "/" . $filename_storage;

//Get old records
$old_records = get_include_contents( $file_path );

//Get current records
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => "tblQv097iyMSTludD",
	"view" => "viwsDB9f2qZBBVKEQ",
	"record_id" => "",
	"data" => '',
	"params" => array(),
);	

$current_records = airtable_api_request_multiple_page( $info );

//Save the current records into a file
$handle = fopen( $file_path, 'w' );
fwrite( $handle, $current_records );
fclose( $handle );

$value_to_check = 'Status - Demo';
$compare_done = compare_airtable_records( json_decode( $current_records, TRUE ), json_decode( $old_records , TRUE ) , $value_to_check );

if( !empty( $compare_done ) ){     
	foreach( $compare_done as $key => $value ){
		if ( $value[ 'new' ] == 'Finished' ){
			//Get record infos
			$info = array(
				"action" => "GET",
				"api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
				"table" => rawurlencode('Demo Bucket'),
				"view" => "",
				"record_id" => $key ,
				"data" => '',
				"params" => array(),
			);	
			$record = json_decode( airtable_api_request_single_page( $info ), true );

			send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "*' . $record [ 'fields' ][ 'Company' ] . '* is finished. Now sending the information to calendar..."}', $slack_access_token_Weblify );
			send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "*' . $record [ 'fields' ][ 'Company' ] . '* is finished. Now sending the information to calendar..."}', $slack_access_token_Weblify );
			         
			$pifalls = "";
			if ( !empty( $record[ 'fields' ][ 'Pitfalls - We solve' ] ) ){
				foreach( $record[ 'fields' ][ 'Pitfalls - We solve' ] as $key => $value ){
					$pifalls .=  "  -" . $value . '\n';
				}
				$pifalls .= '\n';
			}              

			if ( !empty( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] ) ){
				foreach( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] as $key => $value ){
					$pifalls .=  "  -" . $value . '\n';
				}
			}
			
			$tag = "----------";
			$description_slack = $tag . "\n*Exisiting site :* " . $record[ 'fields' ][ 'Link to existing site' ] . "\n" . $record[ 'fields' ]['Emergency Prices' ] . "\n\n" . $record[ 'fields' ][ 'MINIMUM Estimated Price' ] . " - " . $record[ 'fields' ][ 'MAXIMUM Estimated Price' ] . "\n*Pastel link :* " . $record[ 'fields' ][ 'Sales meeting - Pastel link' ] . "\n*Demo site :* " . $record[ 'fields' ][ 'Link Demo-site' ] . "\n*Note :* " . $record[ 'fields' ][ 'Comments on Prices' ] . "\n*Pitfalls :* " . $pifalls . "\n*Monthly payment info :* " . $record[ 'fields' ][ 'Recurring Payment' ] ."\n*Designer :* ". $record[ 'fields' ][ 'Assigned - Demo' ] . "\n". $tag;
			
			$crm_record_id = $record[ 'fields' ][ 'CRM Record ID' ];
			
			//Get the salesguy Email in the CRM (which is up to date, whereas NDB is not)
			$info = array(
				"action" => "GET",
				"api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'crm' ][ 'base' ],
				"table" => "tblUTj3J2tbubKK3f",
				"view" => "",
				"record_id" => $crm_record_id,
				"data" => '',
				"params" => array(),
			);	

			$record_crm = json_decode( airtable_api_request_single_page( $info ), true );

            $calendar_id = $record_crm[ 'fields' ][ '(Linked to "Sales agents") - Weblify.se Email' ][0];
			//$calendar_id = 'david.vergnault@weblify.se';
            
			if ( empty( $calendar_id ) ){
			    send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Process was cancelled because *' . $record [ 'fields' ][ 'Company' ] . '* has no corresponding record in the CRM.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
				send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Process was cancelled because *' . $record [ 'fields' ][ 'Company' ] . '* has no corresponding record in the CRM.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
				exit();
            }
			
			if( empty( $record [ 'fields' ][ 'Sales meeting - Pastel link' ] ) ){
			    send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Process was cancelled because *' . $record [ 'fields' ][ 'Company' ] . '* has no pastel link.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
			    send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Process was cancelled because *' . $record [ 'fields' ][ 'Company' ] . '* has no pastel link.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
				exit();
			}
            
			//Find the right Event with the Record ID and the salesguy info.
            $events = json_decode( get_calendar_events( $calendar_id ), TRUE )[ 'items' ];
			
			if ( count( $events ) == 0 ){
			    send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Process was cancelled because there are *no Calendar events* for this email address: *' . $calendar_id . '*.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
			    send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Process was cancelled because there are *no Calendar events* for this email address: *' . $calendar_id . '*.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
                exit();
			}
			
			$has_matched = false;
			
			foreach( $events as $key => $value ){
				if ( !empty( $value[ 'description' ] ) ){
                    $description =  $value[ 'description' ];
					preg_match('/ID: rec.*/', $description, $matches);
					if ( !empty( $matches[ 0 ] ) ){
						if ( substr($matches[ 0 ] , strlen('ID: '), 17) == $crm_record_id ){
						    
						    $has_matched = true;
                            //Send infos to Calendar
							
                            preg_match('/' . $tag . '(.*?)' . $tag . '/', $description, $matches);
							
							//Check if infos are already there, if yes, replace it using the delimiters, if not, add it 
							if ( !empty( $matches[ 0 ] ) ){
							    $pifalls = '<br>';
                                              
                                if ( !empty( $record[ 'fields' ][ 'Pitfalls - We solve' ] ) ){
                                    foreach( $record[ 'fields' ][ 'Pitfalls - We solve' ] as $key => $value_pitfalls ){
                                        $pifalls .=  "&emsp;" . $value_pitfalls . '<br>';
                                    }
                                    $pifalls .= '<br>';
                                }              
                                
                                if ( !empty( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] ) ){
                                    foreach( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] as $key => $value_pitfalls ){
                                        $pifalls .=  "&emsp;" . $value_pitfalls . '<br>';
                                    }
                                }

								$replacement = $tag . '<br><b>Exisiting site : </b>' . $record[ 'fields' ][ 'Link to existing site' ] . '<br>' . $record[ 'fields' ]['Emergency Prices' ] . '<br><br>' . $record[ 'fields' ][ 'MINIMUM Estimated Price' ] . ' - ' . $record[ 'fields' ][ 'MAXIMUM Estimated Price' ] . '<br><b>Pastel link : </b>' . $record[ 'fields' ][ 'Sales meeting - Pastel link' ] . '<br><b>Demo site : </b>' . $record[ 'fields' ][ 'Link Demo-site' ] . '<br><b>Note : </b>' . $record[ 'fields' ][ 'Comments on Prices' ] . '<br><b>Pitfalls : </b>' . $pifalls . '<br><b>Monthly payment info :</b>' . $record[ 'fields' ][ 'Recurring Payment' ] . '<br><b>Designer : </b>' . $record[ 'fields' ][ 'Assigned - Demo' ] . '<br>' . $tag;
								
								$description = str_replace( $matches[ 0 ], $replacement, $description);
							} else {
							    $pifalls = '<br>';
                                              
                                if ( !empty( $record[ 'fields' ][ 'Pitfalls - We solve' ] ) ){
                                    foreach( $record[ 'fields' ][ 'Pitfalls - We solve' ] as $p1key => $p1value ){
                                        $pifalls .=  "&emsp;" . $p1value . '<br>';
                                    }
                                    $pifalls .= '<br>';
                                }              
                                
                                if ( !empty( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] ) ){
                                    foreach( $record[ 'fields' ][ 'Pitfalls - Strativ case' ] as $p2key => $p2value ){
                                        $pifalls .=  "&emsp;" . $p2value . '<br>';
                                    }
                                }
                                
								$description = $tag . '<br><b>Exisiting site : </b>' . $record[ 'fields' ][ 'Link to existing site' ] . '<br>' . $record[ 'fields' ]['Emergency Prices' ] . '<br><br>' . $record[ 'fields' ][ 'MINIMUM Estimated Price' ] . ' - ' . $record[ 'fields' ][ 'MAXIMUM Estimated Price' ] . '<br><b>Pastel link : </b>' . $record[ 'fields' ][ 'Sales meeting - Pastel link' ] . '<br><b>Demo site : </b>' . $record[ 'fields' ][ 'Link Demo-site' ] . '<br><b>Note : </b>' . $record[ 'fields' ][ 'Comments on Prices' ] . '<br><b>Pitfalls : </b>' . $pifalls . '<br><b>Monthly payment info :</b>' . $record[ 'fields' ][ 'Recurring Payment' ] . '<br><b>Designer : </b>' . $record[ 'fields' ][ 'Assigned - Demo' ] . '<br>' . $tag . '<br>' . $description;
							}
							
                            $data = '{
                              "description": ' . json_encode( $description ) . '
                            }';

                            //Handle errors
							if( empty( $record [ 'fields' ][ 'Emergency Prices' ] ) 
                			   || empty( $record [ 'fields' ][ 'MINIMUM Estimated Price' ] ) 
                			   || empty( $record [ 'fields' ][ 'MAXIMUM Estimated Price' ] ) ){
                		        send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "*' . $record [ 'fields' ][ 'Company' ] . '* has no prices.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
                		        send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "*' . $record [ 'fields' ][ 'Company' ] . '* has no prices.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
                				exit();
                			}
                			
							$request = patch_calendar_event( $calendar_id, $value[ 'id' ], $data );   
							if ( empty( json_decode( $request, TRUE )[ 'error' ] ) ){
			                    send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Success! :dancingpenguin:"}', $slack_access_token_Weblify );
			                    send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Success! :dancingpenguin:"}', $slack_access_token_Weblify );

							} else {
			                    send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Failure....\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
			                    send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Failure....\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
							}
                        }
                    }  
                }
            } 
            
            if ( !$has_matched){
                send_slack_message_2( "chat.postMessage", '{"channel": "UF59UN7RB","text": "Failure... There is *no placeholder* in Calendar of *' . $record_crm[ 'fields' ][ 'Salesguy' ] . '* matching the ID in New demo bucket for *' . $record [ 'fields' ][ 'Company' ] . '*.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
                send_slack_message_2( "chat.postMessage", '{"channel": "GMR7RE61K","text": "Failure... There is *no placeholder* in Calendar of *' . $record_crm[ 'fields' ][ 'Salesguy' ] . '* matching the ID in New demo bucket for *' . $record [ 'fields' ][ 'Company' ] . '*.\nHere are the other info :\n' . $description_slack . '"}', $slack_access_token_Weblify );
            }
        } 
    }
}
