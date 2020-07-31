<!-- paypal_subscriptions_payment_with_php -->

<?php

// Product information 
$itemName = 'Membership Subscription'; 
$itemNumber = 'MS123456'; 
 
// Subscription price for one month 
$itemPrice = 25.00; 
   
// PayPal configuration  
define('PAYPAL_ID', 'profwork.sp18-facilitator@gmail.com');  
define('PAYPAL_SANDBOX', TRUE); //TRUE or FALSE  
  
define('PAYPAL_RETURN_URL', 'http://localhost/Project/Laravel/Laravel%20DEMO/Paypal_Subscriptions_payment_with_php/success.php');  
define('PAYPAL_CANCEL_URL', 'http://localhost/Project/Laravel/Laravel%20DEMO/Paypal_Subscriptions_payment_with_php/cancel.php');  
define('PAYPAL_NOTIFY_URL', 'http://localhost/Project/Laravel/Laravel%20DEMO/Paypal_Subscriptions_payment_with_php/paypal_ipn.php');  
define('PAYPAL_CURRENCY', 'USD');  
  
// Database configuration  
define('DB_HOST', 'localhost');  
define('DB_USERNAME', 'root');  
define('DB_PASSWORD', '');  
define('DB_NAME', 'paypal_subscriptions_payment_with_php');  
  
// Change not required  
define('PAYPAL_URL', (PAYPAL_SANDBOX == true)?"https://www.sandbox.paypal.com/cgi-bin/webscr":"https://www.paypal.com/cgi-bin/webscr");

?>