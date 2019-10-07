<?php

if( !defined( 'AUTH' ) ) {
   die('Direct access not permitted');
}

if ( !empty( $_GET['months'] ) ){
    $months = $_GET['months'];
} else {
    $months = 24;
}

if ( !empty( $_GET['language'] ) ){
    if( $_GET['language'] == "se" ){
        require_once 'lang/se.php';
    } else {
        //Default is english
        require_once 'lang/en.php';
    }
} else {
    require_once 'lang/en.php';
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $lang['tab_title'] ?></title>
        <!-- favicon -->
        <link rel="shortcut icon" type="image/x-icon" href="img/weblify-logo-192x192.png">
        <!-- css Files -->
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="css/recurring.css">
        <!-- Font Family -->
        <link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet">
        <!-- font-awesome css -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    </head>
    <body>
        
        <section id="form-here" class="mt-5">
            <div class="container-fluid mt-5">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        
                        <form action="payment-process-v2.php" method="POST" class="my-form pt-5">
                            <h4><?= $lang['title'] ?></h4>
                            <h6 class="full-payment"><?= $lang['payment_desc_recurring'] ?></h6>
                            <hr class="border">
                            <div class="row mt-4">
                                <!-- Left Side -->
                                <div class="col-md-6 my-auto">
                                    
                                    <div class="row in-line mt-1">
                                        <!-- CREDIT CARD INFO -->
                                        <div class="col-md-12 pr-0">
                                            <label for="CCN">
                                                <small><?= $lang['card_number'] ?></small>
                                            </label>
                                            <div class="input-group ">
                                                <input type="text" class="form-control card-number" placeholder="4242 4242 4242 4242" maxlength="19" minlength="19" onkeypress="return isNumberKey(event)" name="card-number" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="card-here pt-3 pb-3">
                                                <img src="img/card.png" alt="">
                                            </div>
                                        </div>
                                        <!-- EXPIRATION DATE -->
                                        <div class="col-md-6 pr-0">
                                            <label for="CCN">
                                                <small><?= $lang['card_expi'] ?></small>
                                            </label>
                                            <div class="input-group ">
                                                <input type="text" onkeypress="return isNumberKey(event)" class="form-control card-expiry" maxlength="7" minlength="7"  placeholder="MM/YY" name="card-expiry" required>
                                            </div>
                                        </div>
                                        <!-- CVC INPUT -->
                                        <div class="col-md-6 pr-0">
                                            <label for="CCN">
                                                <small><?= $lang['card_cvc'] ?></small>
                                            </label>
                                            <div class="input-group ">
                                                <input type="text" class="form-control" onkeypress="return isNumberKey(event)" placeholder="CVC" maxlength="4" minlength="3" name="card-cvc" required>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right-side -->
                                <div class="col-md-6">
                                     <div class="touch-bottom">
                                    <?php
                                        if( $record[ 'fields' ][ 'The company is based in:' ] == "Sweden" ){
                                    ?>
                                        <div class="milestone_price2">
                                            <div class="heading">
                                                <h1><?= $lang['value'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?= $price_formatted ?></h1>
                                            </div>
                                        </div>
                                        <div class="milestone_price2">
                                            <div class="heading">
                                                <h1><?= $lang['vat'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?= $taxes_formated ?></h1>
                                            </div>
                                        </div>
                                        <div class="milestone_price2">
                                            <div class="heading">
                                                <h1><?= $lang['total_value'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?= $total_price_formatted ?></h1>
                                            </div>
                                        </div>
                                        <hr class="border">
                                        <div class="milestone_price">
                                            <div class="heading">
                                                <h1><?= $lang['monthly_cost'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?=  number_format( ($price + $taxes)/$months ) . ' ' . $currency ?></h1>
                                            </div>
                                        </div>
                                    <?php
                                        } else {
                                    ?>
                                        <div class="milestone_price2">
                                            <div class="heading">
                                                <h1><?= $lang['website_price'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?= $price_formatted ?></h1>
                                            </div>
                                        </div>
                                        <hr class="border">
                                        <div class="milestone_price">
                                            <div class="heading">
                                                <h1><?= $lang['monthly_cost'] ?></h1>
                                            </div>
                                            <div class="price">
                                                <h1><?=  number_format( ($price)/$months ) . ' ' . $currency ?></h1>
                                            </div>
                                        </div>   
                                    <?php
                                        }
                                    ?>        
                                        
                                    </div>
                                </div>
                            </div>      
                            
                            <input type="hidden" name="company_name" value="<?php echo $company_name ?>" />
                            <input type="hidden" name="customer_name" value="<?php echo $customer_name; ?>" />
                            <input type="hidden" name="customer_email" value="<?php echo $customer_email ?>" />
                            <input type="hidden" name="id" value="<?php echo $id ?>" />
                            
                            
                            <button type="submit" class="form-btn mt-5"><?= $lang['submit_button'] ?></button>
                        </form>
                        
                    </div>
                    <div class="col-md-8 offset-md-2 mt-4">
                        <p class="incredible font-italic text-center"><?= $lang['testimonials'] ?></p>
                        <p class="sara small font-italic text-center mt-0">- Sara Svirsky, H&M</p>
                    </div>
                </div>
            </div>
        </section>

        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/recurring.js"></script>
        <script src="lib/cleave.min.js"></script>
        <script type="text/javascript">
            
            function isNumberKey(evt){
                var charCode = (evt.which) ? evt.which : event.keyCode
                if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;

                return true;
            }

            var cardExpiry = new Cleave('.card-expiry', {
                date: true,
                datePattern: ['m', 'y']
            });
            
            var cardNumber = new Cleave('.card-number', {
                creditCard: true,
            });
        </script>
    </body>
</html>