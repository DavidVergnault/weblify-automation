<?php

include 'weblify_api_config.php';

$info = array(
    "action" => "GET",
    "api_url" => $airtable_config[ 'api_url' ],
    "access_token" => $airtable_config[ 'access_token' ],
    "base" => $airtable_config[ 'sections_for_elementor' ][ 'base' ],
    "table" => rawurlencode('Search'),
    "view" => "",
    "record_id" => "",
    "data" => '',
    "params" => array("filterByFormula" => "%7BName%7D"),
);
$section_records = airtable_api_request_multiple_page( $info );


//Hero info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7BHERO%7D)%2C%20%7BHero%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == $value[ 'fields' ][ 'Hero Section' ] ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "HERO": ' . json_encode( $section_value[ 'fields' ][ 'HERO' ] ) . '
                }					
            }';
            //Patch the design informations for the corresponding record 
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	

            $result = airtable_api_request_single_page( $info );
            break;
        }
    } 
}

//Services info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7BSERVICES%20VISUALLY%7D)%2C%20%7BServices%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == $value[ 'fields' ][ 'Services Section' ] ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "NUMBER OF SERVICES": ' . json_encode( $section_value[ 'fields' ][ 'NUMBER OF SERVICES' ] ) . ',
                    "SERVICES VISUALLY": ' . json_encode( $section_value[ 'fields' ][ 'SERVICES VISUALLY' ] ) . '
                }					
            }';
            
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	

            $result = airtable_api_request_single_page( $info );
            break;
        }
    }    
}

//Testimonial info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7BTESTIMONIALS%7D)%2C%20%7BTestimonial%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == "TESTIMONIALS 3 - Cards + Slide Logo" ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "TESTIMONIALS": ' . json_encode( $section_value[ 'fields' ][ 'TESTIMONIALS' ] ) . '
                }					
            }';
            
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	
            $result = airtable_api_request_single_page( $info );
            break;
        }
    }    
}

//50/50 info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7B50%2F50%20-%20TWO%20COLUMNS%7D)%2C%20%7B50%2F50%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == $value[ 'fields' ][ '50/50 Section' ] ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "50/50 - TWO COLUMNS": ' . json_encode( $section_value[ 'fields' ][ '50/50 - TWO COLUMNS' ] ) . '
                }					
            }';
            
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	
            $result = airtable_api_request_single_page( $info );
            break;
        }
    }    
}

//Gallery info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7BGALLERY%7D)%2C%20%7BGallery%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == $value[ 'fields' ][ 'Gallery Section' ] ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "GALLERY": ' . json_encode( $section_value[ 'fields' ][ 'GALLERY' ] ) . '
                }					
            }';
            
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	
            $result = airtable_api_request_single_page( $info );
            break;
        }
    }    
}

//Contact Form info
$info = array(
	"action" => "GET",
	"api_url" => $airtable_config[ 'api_url' ],
	"access_token" => $airtable_config[ 'access_token' ],
	"base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
	"table" => rawurlencode('Demo Bucket'),
	"view" => "",
	"record_id" => "",
	"data" => '',
	"params" => array("filterByFormula" => "AND(NOT(%7BCONTACT%20FORM%7D)%2C%20%7BContact%20Form%20Section%7D)"),
);	
$current_records = airtable_api_request_multiple_page( $info );

foreach( json_decode($current_records, true) as $key => $value ){
    foreach( json_decode( $section_records, TRUE ) as $section_key => $section_value ){
        //Match the corresponding Section
        if( $section_value[ 'fields' ][ 'Name' ] == $value[ 'fields' ][ 'Contact Form Section' ] ){
            //Get the design information from the section
            $data = '{
                "fields": {
                    "CONTACT FORM": ' . json_encode( $section_value[ 'fields' ][ 'CONTACT FORM' ] ) . '
                }					
            }';
            
            $info = array(
                "action" => "PATCH",
                "api_url" => $airtable_config[ 'api_url' ],
                "access_token" => $airtable_config[ 'access_token' ],
                "base" => $airtable_config[ 'new_demo_bucket' ][ 'base' ],
                "table" => rawurlencode('Demo Bucket'),
                "view" => "",
                "record_id" => $value['id'],
                "data" => $data,
                "params" => array(),
            );	
            $result = airtable_api_request_single_page( $info );
            break;
        }
    }    
}
?>