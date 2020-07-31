<?php 
// Include configuration file 
include_once 'config.php'; 
 
// Include database connection file 
include_once 'dbConnect.php'; 
 
/* 
 * Read POST data 
 * reading posted data directly from $_POST causes serialization 
 * issues with array data in POST. 
 * Reading raw POST data from input stream instead. 
 */         
$raw_post_data = file_get_contents('php://input'); 
$raw_post_array = explode('&', $raw_post_data); 
$myPost = array(); 
foreach ($raw_post_array as $keyval) { 
    $keyval = explode ('=', $keyval); 
    if (count($keyval) == 2) 
        $myPost[$keyval[0]] = urldecode($keyval[1]); 
} 
 
// Read the post from PayPal system and add 'cmd' 
$req = 'cmd=_notify-validate'; 
if(function_exists('get_magic_quotes_gpc')) { 
    $get_magic_quotes_exists = true; 
} 
foreach ($myPost as $key => $value) { 
    if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
    } else { 
        $value = urlencode($value); 
    } 
    $req .= "&$key=$value"; 
} 
 
/* 
 * Post IPN data back to PayPal to validate the IPN data is genuine 
 * Without this step anyone can fake IPN data 
 */ 
$paypalURL = PAYPAL_URL; 
$ch = curl_init($paypalURL); 
if ($ch == FALSE) { 
    return FALSE; 
} 
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); 
curl_setopt($ch, CURLOPT_POST, 1); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $req); 
curl_setopt($ch, CURLOPT_SSLVERSION, 6); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1); 
 
// Set TCP timeout to 30 seconds 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: company-name')); 
$res = curl_exec($ch); 
 
/* 
 * Inspect IPN validation result and act accordingly 
 * Split response headers and payload, a better way for strcmp 
 */  
$tokens = explode("\r\n\r\n", trim($res)); 
$res = trim(end($tokens)); 
if (strcmp($res, "VERIFIED") == 0 || strcasecmp($res, "VERIFIED") == 0) { 
     
    // Retrieve transaction data from PayPal 
    $paypalInfo = $_POST; 
    $subscr_id = $paypalInfo['subscr_id']; 
    $payer_email = $paypalInfo['payer_email']; 
    $item_name = $paypalInfo['item_name']; 
    $item_number = $paypalInfo['item_number']; 
    $txn_id = !empty($paypalInfo['txn_id'])?$paypalInfo['txn_id']:''; 
    $payment_gross =  !empty($paypalInfo['mc_gross'])?$paypalInfo['mc_gross']:0; 
    $currency_code = $paypalInfo['mc_currency']; 
    $subscr_period = !empty($paypalInfo['period3'])?$paypalInfo['period3']:floor($payment_gross/$itemPrice); 
    $payment_status = !empty($paypalInfo['payment_status'])?$paypalInfo['payment_status']:''; 
    $custom = $paypalInfo['custom']; 
    $subscr_date = !empty($paypalInfo['subscr_date'])?$paypalInfo['subscr_date']:date("Y-m-d H:i:s"); 
    $dt = new DateTime($subscr_date); 
    $subscr_date = $dt->format("Y-m-d H:i:s"); 
    $subscr_date_valid_to = date("Y-m-d H:i:s", strtotime(" + $subscr_period month", strtotime($subscr_date))); 
     
    if(!empty($txn_id)){ 
        // Check if transaction data exists with the same TXN ID 
        $prevPayment = $db->query("SELECT id FROM user_subscriptions WHERE txn_id = '".$txn_id."'"); 
         
        if($prevPayment->num_rows > 0){ 
            exit(); 
        }else{ 
            // Insert transaction data into the database 
            $insert = $db->query("INSERT INTO user_subscriptions(user_id,validity,valid_from,valid_to,item_number,txn_id,payment_gross,currency_code,subscr_id,payment_status,payer_email) VALUES('".$custom."','".$subscr_period."','".$subscr_date."','".$subscr_date_valid_to."','".$item_number."','".$txn_id."','".$payment_gross."','".$currency_code."','".$subscr_id."','".$payment_status."','".$payer_email."')"); 
             
            // Update subscription id in the users table 
            if($insert && !empty($custom)){ 
                $subscription_id = $db->insert_id; 
                $update = $db->query("UPDATE users SET subscription_id = {$subscription_id} WHERE id = {$custom}"); 
            } 
        } 
    } 
} 
die;