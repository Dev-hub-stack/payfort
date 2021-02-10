<?php

//require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli(HOST, USER, PASSWORD, DB);

if($conn->connect_errno) {
    echo 'Connection Error';
    exit;
}

function getCart($order_number) {
    global $conn;
    $query = $conn->query('Select * from cart where order_number ="' . $order_number. '"');

    if ($row = $query->fetch_assoc()) {
        return $row;
    } else {
        return null;
    }
}

function getUserBilling($order_number) {
    global $conn;
    $query = $conn->query('SELECT billing.email, billing.first_name, billing.last_name, billing.country, orders.id from orders join billing on billing.order_id = orders.id WHERE orders.order_number = "' . $order_number . '"');
    if($row = $query->fetch_assoc()) {
        return $row;
    }
    return '';
}

function setCartItems($cart_id) {
    global $conn;
    $cart_items = [];
    $cartItemsQuery = $conn->query("Select cart_items.* from cart_items where cart_id = " . $cart_id);

    while ($row = $cartItemsQuery->fetch_assoc()) {
        $productQuery = $conn->query('Select products.id,products.images,products.name as product_name, b.name as brand_name, models.name as model_name, pv.sku as product_variant_sku  from products 
            join brands as b on b.id = products.brand_id 
            join models on models.id = products.model_id 
            join product_variants as pv on pv.product_id = products.id
            where pv.id = ' . $row['product_variant_id']);
        $product = $productQuery->fetch_assoc();
        $item = new \stdClass();
        $item->item_description = "Brand: " .$product['brand_name'] . ", Model: " . $product['model_name'];
        $item->item_name = $product['product_name'];
        $item->item_sku = $product['product_variant_sku'];
        $images = $product['images'];
        $item->item_image = !is_null($images) ?: json_decode($product['images'])[0];
        $item->item_price = $row['price'];
        $item->item_quantity = $row['quantity'];
        $cart_items[] = $item;

        /*echo '<pre>';
        print_r($cart_items);
        exit;*/

    }

    return $cart_items;
}

function confirm_order() {
    // $message = "GET: ".print_r($_GET, 1);
    // $message .= "POST: ".print_r($_POST, 1);
    $message = "REQUEST: ".print_r($_REQUEST, 1);
    displayLog($message);
    
    session_start();
    $session_id = $_SESSION['session_id'];
    $order_number = $_SESSION['order_number'];
    global $conn;
    $paymentMethod = $_REQUEST['payment_option'];
    $card_number = $_REQUEST['card_number'];
    $card_holder_name = isset($_REQUEST['card_holder_name']) ? $_REQUEST['card_holder_name'] : NULL;
    $fort_id = $_REQUEST['fort_id'];
    $paymentType = $_SESSION['paymentType'];
    $outstanding_amount = NULL;

    if($paymentType == 'twenty_percent') {
        $paid_amount = $_SESSION['amount'] * 0.2;
        $outstanding_amount = $_SESSION['amount'] - $paid_amount;
    } else {
        $paid_amount = $_SESSION['amount'];
    }

    try {
        $Query = "UPDATE orders SET 
            status = 1,
            payment_method = '$paymentMethod', 
            card_number = '$card_number', 
            card_holder = '$card_holder_name',
            payment_type = '$paymentType',
            paid_amount = $paid_amount,
            outstanding_amount = $outstanding_amount,
            fort_id = '$fort_id'
            WHERE session_id = '$session_id' AND order_number = '$order_number'";
        displayLog("Query : ".$Query);
        
        $conn->query("
            UPDATE orders SET 
            status = 1,
            payment_method = '$paymentMethod', 
            card_number = '$card_number', 
            card_holder = '$card_holder_name',
            payment_type = '$paymentType',
            paid_amount = $paid_amount,
            outstanding_amount = $outstanding_amount,
            fort_id = '$fort_id'
            WHERE session_id = '$session_id' AND order_number = '$order_number'
        ");


        // $conn->query('UPDATE orders SET status = 1,
        //                         payment_method = "'. $paymentMethod . '", 
        //                         card_number = "'. $card_number .'", 
        //                         card_holder = "'. $card_holder_name .'",
        //                         payment_type = "'. $paymentType .'",
        //                         paid_amount = '. $paid_amount .',
        //                         outstanding_amount = '. $outstanding_amount .',
        //                         fort_id = "'. $fort_id .'"
        //             WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"');
        $conn->query('DELETE from cart WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"');
        
        sendEmail(getOrderId());
        return true;
    } catch (Exception $ex) {
        $message = "Exception: ".print_r($ex, 1);
        displayLog($message);
        // echo '<pre>'; print_r($ex); exit;
        return false;
    }
}

/**
 * [getOrderId description]
 * @param  integer $removeSession [Added By M.Haseeb and pass 1 at the end of completion so that remove all Session data]
 */

function getOrderId( $removeSession = 0) {
    session_start();
    $order_number = $_SESSION['order_number'];
    global $conn;
    $order = $conn->query('SELECT id FROM orders where order_number ="' . $order_number . '"');
    $row = $order->fetch_assoc();
    if($removeSession === 1) {
        unset($_SESSION['paymentType']);
        unset($_SESSION['amount']);
        unset($_SESSION['order_number']);
        unset($_SESSION['session_id']);
    }
    return $row['id'];
}

function sendEmail($order_id) {

    $mes = "Session: ".print_r($_SESSION, 1);
    displayLog($mes);

    $url = API_URL . '/send-order-email';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . "?order_id=" . $order_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
    // var_dump($response->getBody());
}


function calculateTotalAmount($cart) {
    $total = 0;
    if ($cart['vat'] == 0) {
        $total = $cart['sub_total'] + $cart['shipping'] - $cart['discount'];
    } else {
        $total = $cart['total'] + $cart['shipping'] - $cart['discount'];
    }

    return $total;
}

function updateCardPaymentDetails($details, $order_number)
{
    // var_dump($details);
    // var_dump($order_number);
    // exit;
    try {
        global $conn;
        $conn->query('UPDATE orders 
                            SET payment_method = '. $details['payment_option'] . ', 
                                card_number = '.$details['card_number'] .', 
                                card_holder = '. $details['card_holder_name'].',
                                fort_id = '. $details['fort_id'].'
                                WHERE order_number = "' . $order_number . '"');
        return true;
    } catch (Exception $ex) {
        var_dump($ex->getMessage());
        exit;
        return false;
    }
}

/**
 * [cartAddOns]
 * @param  $cart_id
 * @return cartAddOnsItems
 */
function cartAddOns($cart_id) {
    global $conn;
    $cart_items = [];
    $cartAddOns = $conn->query("
        SELECT ca.*, ao.`image`, ao.`title`, ao.`stock_status`, ao.`products`
        FROM `cart_addons` ca
        INNER JOIN add_ons ao ON ca.`addon_id` = ao.`id`
        WHERE ca.cart_id =" . $cart_id
    );

    while ($addOn = $cartAddOns->fetch_assoc()) {
        $item = new \stdClass();
        $item->item_description = "";
        $item->item_name = $addOn['title'];
        $item->item_sku = $addOn['addon_id'];
        $images = $addOn['image'];
        $item->item_image = !is_null($images) ?: json_decode($addOn['images'])[0];
        $item->item_price = $addOn['unit_price'];
        $item->item_quantity = $addOn['quantity'];
        $cart_items[] = $item;

        /*echo '<pre>';
        print_r($cart_items);
        exit;*/

    }

    return $cart_items;
}
 function displayLog($messages) {
    $messages = "========================================================\n\n".$messages."\n\n";
    $file = __DIR__.'/custom.log';
    if (filesize($file) > 907200) {
        $fp = fopen($file, "r+");
        ftruncate($fp, 0);
        fclose($fp);
    }

    $myfile = fopen($file, "a+");
    fwrite($myfile, $messages);
    fclose($myfile);
}
