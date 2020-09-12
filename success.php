<?php
require_once 'contants.php';
require_once 'functions.php';
if(!confirm_order()) {
    header('Location: ' . WEB_URL . '/cancel-order');
} else {
    $orderId = getOrderId();
    header('Location: ' . WEB_URL . '/thank-you?order_id=' . $orderId);
}
?>
<?php include('header.php') ?>

    <section class="nav">
        <ul>
            <li class="lead" > Payment Method</li>
            <li class="lead" > Pay</li>
            <li class="active lead" > Done</li>
        </ul>
    </section>
    <section class="confirmation">
        <label class="success" for="" >Success</label>
        <!-- <label class="failed" for="" >Failed</label> -->
        <small>Thank You For Your Order</small>
    </section>

    <section class="order-confirmation">
        <label for="" class="lead">FORT ID : fort_id </label>
    </section>

    <div class="h-seperator"></div>
    
    <!--<section class="details">
        <h3>Response Details</h3>
        <br/>
        <table>
            <tr>
                <th>
                    Parameter Name
                </th>
                <th>
                    Parameter Value
                </th>
            </tr>
        </table>
    </section>-->
    
    <div class="h-seperator"></div>
    
    <section class="actions">
        <a class="btm" href="https://wheel.ahmadhassan.design">New Order</a>
    </section>
<?php include('footer.php') ?>
