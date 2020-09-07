<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli(HOST, USER, PASSWORD, DB);

if($conn->connect_errno) {
    echo 'Connection Error';
    exit;
}


function getCart($session_id) {
    $session_id = $_GET['session_id'];
    global $conn;
    $query = $conn->query('Select * from cart where session_id = "' . $session_id . '"');

    if ($row = $query->fetch_assoc()) {
        return $row;
    } else {
        return null;
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
    $session_id = $_SESSION['session_id'];
    echo $session_id;
    exit;
    global $conn;
    $orderQuery = $conn->query('UPDATE orders SET status = 1 WHERE session_id = "' . $session_id . '"');
    if(!$orderQuery) {
        $conn->query('DELETE from cart WHERE session_id = "' . $session_id . '"');
        return false;
    }

    return true;
}


