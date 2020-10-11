<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php include('header.php') ?>
<?php
require_once 'PayfortIntegration.php';
$objFort = new PayfortIntegration();
require_once 'functions.php';
$objFort = new PayfortIntegration();
if(isset($_GET['session_id'])) {
    session_start();
    $cart = getCart($_SESSION['session_id'], $_SESSION['order_number']);
    $objFort->session_id = $_GET['session_id'];
    if(is_null($cart)) {
        echo 'Cart not found';
        exit;
    }

    $objFort->amount = $cart['total'] + $cart['shipping'] - $cart['discount'];
    $cartItems = setCartItems($cart['id']);
    $objFort->items = $cartItems;
}
$amount =  $objFort->amount;
$currency = $objFort->currency;
$totalAmount = $amount;
$paymentMethod = $_REQUEST['payment_method'];
?>

    <section class="nav">
        <ul>
            <li class="lead" >Payment Method</li>
            <li class="lead active" > Pay</li>
            <li class="lead" > Done</li>
        </ul>
    </section>
    <section class="confirmation">
        <label>Confirm Your Order</label>
    </section>
    <section class="order-info">
        <ul class="items" style="width:100%">
            <span>
                <i class="icon icon-bag"></i>
                <label class="lead" for="">Your Order</label>
            </span>
            <li>
                <?php
                if(isset($objFort->items)) :
                    ?>

                    <table>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                        <?php
                        foreach($objFort->items as $item) :
                            ?>
                            <tr>
                                <td><?= $item->item_description; ?><br>
                                    SKU: <?= $item->item_sku; ?></td>
                                <td><?= $item->item_quantity; ?></td>
                                <td><?= $objFort->currency . ' ' .$item->item_price; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>Sub
                                    Total: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $cart['total']); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>Shipping: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $cart['shipping']); ?></strong>
                            </td>
                        </tr>
                        <?php
                        if ($cart['discount'] > 0) {
                            ?>
                            <tr>
                                <td colspan="3" style="text-align: right">

                                    <strong>Discount: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $cart['discount']); ?></strong>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>Total: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $totalAmount); ?></strong>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </li>
            <!-- <li>Lorem ipsum dolor sit amet, consectetur adipisicing elit. A ex magni delectus aliquam debitis</li> -->
        </ul>

        <ul class="items">
            <span>
                <i class="icon icon-bag"></i>
                <label class="lead" for="">Payment Method</label>
            </span>
            <li><?php echo $objFort->getPaymentOptionName($paymentMethod) ?></li>
        </ul>
    </section>
    <?php if($paymentMethod == 'cc_merchantpage' || $paymentMethod == 'installments_merchantpage') ://merchant page iframe method ?>
        <section class="merchant-page-iframe">
            <?php
                $merchantPageData = $objFort->getMerchantPageData($paymentMethod);
                $postData = $merchantPageData['params'];
                $gatewayUrl = $merchantPageData['url'];
            ?>
            <div class="cc-iframe-display">
                <div id="div-pf-iframe" style="display:none">
                    <div class="pf-iframe-container">
                        <div class="pf-iframe" id="pf_iframe_content">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <div class="h-seperator"></div>

    <section class="actions">
        <a class="back" id="btn_back" href="index.php">Back</a>
    </section>
    <script type="text/javascript" src="vendors/jquery.min.js"></script>
    <script type="text/javascript" src="assets/js/checkout.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            var paymentMethod = '<?php echo $paymentMethod?>';
            //load merchant page iframe
            if(paymentMethod == 'cc_merchantpage' || paymentMethod == 'installments_merchantpage') {
                getPaymentPage(paymentMethod);
            }
        });
    </script>
<?php include('footer.php') ?>
