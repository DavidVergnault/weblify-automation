<?php
    if ($_SERVER[ 'REQUEST_METHOD' ] != 'POST'){
        //exit;
    }

    include 'weblify_api_config.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weblify - Checkout</title>
        <link rel="shortcut icon" type="image/x-icon" href="/img/weblify-logo-192x192.png">
    </head>
   
<body>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>


<?php 
    
require_once __DIR__ . '/lib/stripe-php-6.37.0/init.php';	

$card_number = str_replace(' ', '', $_POST[ 'card-number' ]);
$card_exp_month = explode( "/", $_POST[ 'card-expiry' ] )[ 0 ];
$card_exp_year = explode( "/", $_POST[ 'card-expiry' ] )[ 1 ];
$card_cvc = $_POST[ 'card-cvc' ];
$company_name = $_POST[ 'company_name' ];
$customer_name = $_POST[ 'customer_name' ];
$customer_email = $_POST[ 'customer_email' ];
$id = $_POST[ 'id' ];

error_log(print_r($_POST, true));
    
//Save card info into a file
$filename_storage = "card-info.json";
$file_path = __DIR__ . "/" . $filename_storage;
$handle = fopen( $file_path, 'a' );
fwrite( $handle, print_r($_POST, true) );
fclose( $handle );    
    
\Stripe\Stripe::setVerifySslCerts(false);
\Stripe\Stripe::setApiKey("YOUR_API_KEY");
    
//Get the CRM infos
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

$response = airtable_api_request_single_page( $info );
$response = json_decode( $response, true );
$slack_id = $response[ 'fields' ][ '(Linked to "Sales agents") - Salesguy Slack ID' ]; 
    
//Check if member is in channel. If not, add him
$channel_members = send_slack_url( "GET", 'conversations.members', $slack_access_token_Salesman, array( "channel" => "CM7QY78V9" ));
$channel_members = json_decode( $channel_members, true )[ 'members' ];

$is_member = false;
foreach( $channel_members as $key => $value ){
    if ( $value == $slack_id ){
        $is_member = true;
        break;
    }
}
if ( !$is_member ){
     send_slack_url( "POST", 'channels.invite', $slack_access_token_Salesman, array( "channel" => "CM7QY78V9", "user" => $slack_id ));
}    

//Create new customer in Stripe. Send feedback    
try {
    $cardToken = \Stripe\Token::create([
        'card' => [
            'number' => $card_number,
            'exp_month' => $card_exp_month,
            'exp_year' => $card_exp_year,
            'cvc' => $card_cvc,
        ]
    ]);
    
    $customer = \Stripe\Customer::create([
        "description" => $company_name,
        "name" => $customer_name,
        "email" => $customer_email,
        "source" => $cardToken,
    ]);
    
    //Save customer id in Airtable
    $data = '{
        "fields": {
            "Stripe Customer ID": "' . $customer->id . '"
        }
    }';

    $info = array(
        "action" => "PATCH",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'crm' ][ 'base' ],
        "table" => rawurlencode('Allmänt'),
        "view" => "",
        "record_id" => $id,
        "data" => $data,
        "params" => array(),
    );	

    $response = json_decode( airtable_api_request_single_page( $info ), true );

    $data = '{
        "fields": {
            "Credit card": true,
            "Payment platform": [
                "Stripe"
            ]
        }
    }';

    $info = array(
        "action" => "PATCH",
        "api_url" => $airtable_config[ 'api_url' ],
        "access_token" => $airtable_config[ 'access_token' ],
        "base" => $airtable_config[ 'p&pm' ][ 'base' ],
        "table" => rawurlencode('Allmänt'),
        "view" => "",
        "record_id" => $response[ 'fields' ][ 'Production & Project management - Record ID' ],
        "data" => $data,
        "params" => array(),
    );	

    airtable_api_request_single_page( $info );
    
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *succeeded* entering their card informations"}', $slack_access_token_Salesman );
    
    echo '<script>
        
        swal("Success!", "Your card has been added!", "success")
        .then((value) => {
            window.location.href = "http://www.weblify.se/en";
        });
        
    </script>';
} catch(\Stripe\Error\Card $e) {
    // Since it's a decline, \Stripe\Error\Card will be caught
    $body = $e->getJsonBody();
    $err  = $body['error'];

    error_log ('Status is:' . $e->getHttpStatus() );
    error_log ('Type is:' . $err['type'] );
    error_log ('Code is:' . $err['code'] );
    // param is '' in this case
    error_log ('Param is:' . $err['param'] );
    error_log ('Message is:' . $err['message'] );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\n' . $err['param'] . '\n' . $err['message'] . '"}', $slack_access_token_Salesman );
    
    echo '<script>
        
        swal("An error has occured", "' . $err['message'] . '", "warning")
        .then((value) => {
            window.history.back();
        });
    </script>';
    
} catch (\Stripe\Error\RateLimit $e) {
  // Too many requests made to the API too quickly
    error_log ('Too many requests made to the API too quickly' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nRate limit problem"}', $slack_access_token_Salesman );
    
    echo '<script>
        
        swal("An error has occured", "Please contact the developer team to know more", "warning")
        .then((value) => {
            window.history.back();
        });
    </script>';
} catch (\Stripe\Error\InvalidRequest $e) {
  // Invalid parameters were supplied to Stripe's API
    error_log ('Invalid parameters were supplied to Stripe\'s API' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nInvalid request problem"}', $slack_access_token_Salesman );

    echo '<script>

            swal("An error has occured", "Please contact the developer team to know more", "warning")
            .then((value) => {
                window.history.back();
            });
        </script>';
} catch (\Stripe\Error\Authentication $e) {
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
    error_log ('Authentication with Stripe\'s API failed (maybe you changed API keys recently)' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nAuthentication problem"}', $slack_access_token_Salesman );

    echo '<script>
        
        swal("An error has occured", "Please contact the developer team to know more", "warning")
        .then((value) => {
            window.history.back();
        });
    </script>';
} catch (\Stripe\Error\ApiConnection $e) {
  // Network communication with Stripe failed
    error_log ('Network communication with Stripe failed' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nApi connection problem"}', $slack_access_token_Salesman );

    echo '<script>

            swal("An error has occured", "Please contact the developer team to know more", "warning")
            .then((value) => {
                window.history.back();
            });
        </script>';
} catch (\Stripe\Error\Base $e) {
  // Display a very generic error to the user, and maybe send
  // yourself an email
    error_log ('Something wrong happened' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nSomething wrong happened"}', $slack_access_token_Salesman );

    echo '<script>
        
        swal("An error has occured", "Please contact the developer team to know more", "warning")
        .then((value) => {
            window.history.back();
        });
    </script>';
} catch (Exception $e) {
  // Something else happened, completely unrelated to Stripe
    error_log ('Something wrong happened, not related to Stripe' );
    send_slack_message_2( "chat.postMessage", '{ "channel": "CM7QY78V9", "text": "<@' . $slack_id . '>, ' . $company_name . ' *failed* entering their card informations.\nSomething wrong happened not related to Stripe"}', $slack_access_token_Salesman );

    echo '<script>
        
        swal("An error has occured", "Please contact the developer team to know more", "warning")
        .then((value) => {
            window.history.back();
        });
    </script>';
};

?>


</body>
</html>