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
include './functions.php';

if($_REQUEST['r'] == 'getPaymentPage') {
    $cart = getCart($_REQUEST['order_number']);
    $billingDetail = getUserBilling($_REQUEST['order_number']);
    $objFort->customerEmail = $billingDetail['email'];
    $objFort->name = $billingDetail['first_name'] . ' ' . $billingDetail['last_name'];

    if(isset($_GET['paymentType']) && $_GET['paymentType'] == 'twenty_percent') {
        $objFort->amount = calculateTotalAmount($cart) * 0.2;
    } else {
        $objFort->amount = calculateTotalAmount($cart);
    }


    $objFort->orderNumber = $_REQUEST['order_number'];
    $objFort->processRequest(htmlspecialchars($_REQUEST['paymentMethod'], ENT_QUOTES, 'UTF-8'), $_REQUEST['order_number'], $_GET['paymentType']);
}
elseif($_REQUEST['r'] == 'merchantPageReturn') {

    $cart = getCart($_REQUEST['order_number']);
    $billingDetail = getUserBilling($_REQUEST['order_number']);
    $objFort->customerEmail = $billingDetail['email'];
    $objFort->name = $billingDetail['first_name'] . ' ' . $billingDetail['last_name'];
    if(isset($_GET['paymentType']) && $_GET['paymentType'] == 'twenty_percent') {
        $objFort->amount = calculateTotalAmount($cart) * 0.2;
    } else {
        $objFort->amount = calculateTotalAmount($cart);
    }

    $objFort->orderNumber = $_REQUEST['order_number'];
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

