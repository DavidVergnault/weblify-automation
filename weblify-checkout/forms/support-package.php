<?php

if( !defined( 'AUTH' ) ) {
   die('Direct access not permitted');
}
									
if ( !empty( $_GET['ver'] ) ){
    if( $_GET['ver'] == "o" ){
         $milestones_percent = [ "10", "30", "25", "15", "20"];
    } else {
        //Default is new
        $milestones_percent = [ "20", "30", "30", "10", "10" ];
    }
} else {
    $milestones_percent = [ "20", "30", "30", "10", "10" ];
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
        <link rel="stylesheet" type="text/css" href="css/milestones.css">
        <!-- Font Family -->
        <link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet">
        <!-- font-awesome css -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    </head>
    <body>
        
        <section id="form-here" class="mt-5">
            <div class="container-fluid mt-5">
                <img src="https://checkout.weblify.se/img/sweden.png" id="se-flag" class="m-1 pointer float-right" height="30px" width="30px"/>
                <img src="https://checkout.weblify.se/img/united-states.png" height="30px" class="m-1 pointer float-right" width="30px" id="en-flag"/>
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        
                        <form action="payment-process-v2.php" method="POST" class="my-form pt-5">
                            <h1><?= $company_name ?></h1>
                            <h4><?= $lang['support_package'] ?></h4>
                            
                            <div class="col-md-6 pr-0">
                            </div>
                            
                            <div class="row in-line mt-1">
                                <!-- CREDIT CARD INFO -->
                                <div class="col-md-6 pr-0">
                                    <label for="CCN">
                                        <small><?= $lang['card_number'] ?></small>
                                    </label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control card-number" placeholder="4242 4242 4242 4242" maxlength="19" minlength="19" onkeypress="return isNumberKey(event)" name="card-number" required>
                                    </div>
                                </div>
                                <!-- EXPIRATION DATE -->
                                <div class="col-md-3 pr-0">
                                    <label for="CCN">
                                        <small><?= $lang['card_expi'] ?></small>
                                    </label>
                                    <div class="input-group ">
                                        <input type="text" onkeypress="return isNumberKey(event)" class="form-control card-expiry" maxlength="7" minlength="7"  placeholder="MM/YY" name="card-expiry" required>
                                    </div>
                                </div>
                                <!-- CVC INPUT -->
                                <div class="col-md-3 pr-0">
                                    <label for="CCN">
                                        <small><?= $lang['card_cvc'] ?></small>
                                    </label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control" onkeypress="return isNumberKey(event)" placeholder="CVC" maxlength="4" minlength="3" name="card-cvc" required>
                                    </div>
                                </div>
                            </div>
                            <div class="card-here pt-2">
                                <img src="img/card.png" alt="">
                            </div>

                            <?php
                                if( $record[ 'fields' ][ 'The company is based in:' ] == "Sweden" ){
                            ?>
                            <hr class="border">
                            <div class="milestone_price">
                                <div class="heading">
                                    <h1><?= $lang['per_month'] ?></h1>
                                </div>
                                <div class="price">
                                    <h1><?= number_format( $support_package_price ) . ' ' . $currency ?>*</h1>
                                </div>
                            </div>
                            <?php
                                } else {
                            ?>
                            <hr class="border">
                            <div class="milestone_price">
                                <div class="heading">
                                    <h1><?= $lang['per_month'] ?></h1>
                                </div>
                                <div class="price">
                                    <h1><?= $currency . ' ' . number_format( $support_package_price ) ?>*</h1>
                                </div>
                            </div>
                            <?php
                                }
                            ?>  
                            
                            <input type="hidden" name="company_name" value="<?php echo $company_name ?>" />
                            <input type="hidden" name="customer_name" value="<?php echo $customer_name; ?>" />
                            <input type="hidden" name="customer_email" value="<?php echo $customer_email ?>" />
                            <input type="hidden" name="id" value="<?php echo $id ?>" />
                            
                            <button type="submit" class="form-btn"><?= $lang['secure_payment'] ?></button>
                            <?php
                                if( $record[ 'fields' ][ 'The company is based in:' ] == "Sweden" ){
                            ?>
                            <div class="milestone_price2">
                                <div class="heading">
                                    <h1 class='star-info'>*<?= $lang['star_ex_moms'] ?></h1>
                                </div>
                            </div>
                            <?php
                                }
                            ?>
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
        <script src="js/milestones.js"></script>
        <script src="lib/cleave.min.js"></script>
        <script type="text/javascript">
            
            $(".pointer").css("cursor", "pointer");
            $("#se-flag").on("click", function(){
                changeLang('se');                      
            });  
            $("#en-flag").on("click", function(){
                changeLang('en');                      
            }); 
            
            function changeLang( lang ){
                var searchParams = new URLSearchParams(window.location.search)
                searchParams.set('language', lang)
                var newParams = searchParams.toString()
                window.location.href = "payment-v3.php?" + newParams
            }
            
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