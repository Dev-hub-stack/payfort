<?php

//require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli(HOST, USER, PASSWORD, DB);

if($conn->connect_errno) {
    echo 'Connection Error';
    exit;
}

function getCart($session_id, $order_number) {
    global $conn;
    $query = $conn->query('Select * from cart where session_id = "' . $session_id . '" AND order_number ="' . $order_number. '"');

    if ($row = $query->fetch_assoc()) {
        return $row;
    } else {
        return null;
    }
}

function getUserEmail($session_id) {
    global $conn;
    $query = $conn->query('SELECT billing.email, orders.id from orders join billing on billing.order_id = orders.id WHERE orders.session_id = "' . $session_id . '"');
    if($row = $query->fetch_assoc()) {
        return $row['email'];
    }
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
    try {
        session_start();
        $session_id = $_SESSION['session_id'];
        $order_number = $_SESSION['order_number'];
        global $conn;
        $conn->query('UPDATE orders SET status = 1 WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"');
        $conn->query('DELETE from cart WHERE session_id = "' . $session_id . '" AND order_number = "' . $order_number . '"');
        sendEmail(getOrderId());
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function getOrderId() {
    session_start();
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

