<?php

/**
 * @copyright Copyright PayFort 2012-2016 
 */
if(!isset($_REQUEST['r'])) {
    echo 'Page Not Found!';
    exit;
}
require_once 'PayfortIntegration.php';
$objFort = new PayfortIntegration();
session_start();
$cart = getCart($_SESSION['session_id'], $_SESSION['order_number']);
$objFort->amount = $cart['total'] + $cart['shipping'] - $cart['discount'];
if($_REQUEST['r'] == 'getPaymentPage') {
    $objFort->processRequest(htmlspecialchars($_REQUEST['paymentMethod'], ENT_QUOTES, 'UTF-8'));
}
elseif($_REQUEST['r'] == 'merchantPageReturn') {
    $objFort->processMerchantPageResponse();
}
elseif($_REQUEST['r'] == 'processResponse') {
    $objFort->processResponse();
}
else{
    echo 'Page Not Found!';
    exit;
}
?>

