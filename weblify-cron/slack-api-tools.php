<?php

function send_slack_message( $webhook, $message ){
	$ch = curl_init();
	$data = '{
				"text": "' . $message . '"
			}';
	curl_setopt($ch, CURLOPT_URL, $webhook );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($ch, CURLOPT_POST, 1);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$response = curl_exec($ch);
	
	return $response;
	curl_close($ch);
}

function send_slack_message_custom( $webhook, $data ){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $webhook );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($ch, CURLOPT_POST, 1);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$response = curl_exec($ch);
	
	curl_close($ch);

	return $response;
}

function send_slack_message_2( $method, $data, $token ){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://slack.com/api/" . $method);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($ch, CURLOPT_POST, 1);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer ' . $token;
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$response = curl_exec($ch);

	curl_close($ch);

	return $response;
}