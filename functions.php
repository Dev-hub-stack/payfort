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
function getCartAddon($cart_id){
  global $conn;
  $cart_addons     = [];
  $cartItemsQuery = $conn->query('Select cart_addons.*,add_ons.title, add_ons.image from cart_addons
        INNER JOIN add_ons on add_ons.id = cart_addons.addon_id
        where cart_id = ' . $cart_id);
  while ($row = $cartItemsQuery->fetch_assoc()) {
    $item                   = new \stdClass();
    $item->item_name        = $row['title'];
    $images                 = $row['image'];
    $item->item_image       = $images;
    $item->unit_price       = $row['unit_price'];
    $item->total_price       = $row['total_price'];
    $item->item_quantity    = $row['quantity'];
    $cart_addons[]           = $item;
  }
  return $cart_addons;
}

function confirm_order() {
    session_start();
    $session_id = $_SESSION['session_id'];
    $order_number = $_SESSION['order_number'];
    global $conn;
    $paymentMethod = $_GET['payment_option'];
    $card_number = $_GET['card_number'];
    $card_holder_name = isset($_GET['card_holder_name']) ? $_GET['card_holder_name'] : NULL;
    $fort_id = $_GET['fort_id'];
   // $paymentType = $_SESSION['paymentType'];
  //this is encrypted payment_type
    $paymentType = decrypt($_GET['payfort_id']);
    ///$paymentType = $_GET['payment_type'];
    $outstanding_amount = 0;

    if($paymentType == 'twenty_percent') {
        $paid_amount = $_SESSION['amount'] * 0.2;
        $outstanding_amount = $_SESSION['amount'] - $paid_amount;
    } else {
        $paid_amount = $_SESSION['amount'];
    }
    try {
$query = 'UPDATE orders SET status = 1,
                                payment_method = "'. $paymentMethod . '",
                                card_number = "'. $card_number .'",
                                card_holder = "'. $card_holder_name .'",
                                payment_type = "'. $paymentType .'",
                                paid_amount = '. $paid_amount .',
                                outstanding_amount = '. $outstanding_amount .',
                                fort_id = "'. $fort_id .'"
                    WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"';
        $result = $conn->query($query);
        $conn->query('DELETE from cart WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"');
        // echo 'OrderId: 'getOrderId();
        // echo '<br/>';
        // echo '<pre>';
        // print_r($_SESSION);

        // exit;
        sendEmail(getOrderId());
        // unset($_SESSION['paymentType']);
        // unset($_SESSION['amount']);
        // unset($_SESSION['order_number']);
        // unset($_SESSION['session_id']);
        return true;
    } catch (Exception $ex) {
        // echo '<pre>'; print_r($ex); exit;
        return false;
    }
}

function getOrderId() {
    //session_start();
    $order_number = $_SESSION['order_number'];
    global $conn;
    $order = $conn->query('SELECT id FROM orders where order_number ="' . $order_number . '"');
    $row = $order->fetch_assoc();
    return $row['id'];
}

function sendEmail($order_id) {

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
    var_dump($details);
    var_dump($order_number);
    exit;
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
  
  function encrypt($data, $password='ts0102AH'){
    $iv = substr(sha1(mt_rand()), 0, 16);
    $password = sha1($password);
    
    $salt = sha1(mt_rand());
    $saltWithPassword = hash('sha256', $password.$salt);
    
    $encrypted = openssl_encrypt(
      "$data", 'aes-256-cbc', "$saltWithPassword", null, $iv
    );
    $msg_encrypted_bundle = "$iv:$salt:$encrypted";
    return $msg_encrypted_bundle;
  }
  
  
  function decrypt($msg_encrypted_bundle, $password='ts0102AH'){
    $password = sha1($password);
    
    $components = explode( ':', $msg_encrypted_bundle );
    $iv            = $components[0];
    $salt          = hash('sha256', $password.$components[1]);
    $encrypted_msg = $components[2];
    
    $decrypted_msg = openssl_decrypt(
      $encrypted_msg, 'aes-256-cbc', $salt, null, $iv
    );
    
    if ( $decrypted_msg === false )
      return false;
    
    $msg = substr( $decrypted_msg, 41 );
    return $decrypted_msg;
  }
