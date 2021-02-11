<?php
ini_set('display_errors', 0);
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
    $cart = getCart($_SESSION['order_number']);
    $objFort->session_id = $_GET['session_id'];
    if(is_null($cart)) {
        echo 'Cart not found';
        exit;
    }
    $user = getUserBilling($_SESSION['order_number']);
    if(!empty($user)){
        $objFort->customerEmail = $user['email'];
    }
    $objFort->amount = calculateTotalAmount($cart);
    $_SESSION['amount'] = $objFort->amount;
    $cartItems = setCartItems($cart['id']);
  $cartAddon = getCartAddon($cart['id']);
  $objFort->items = $cartItems;
  $objFort->addons = $cartAddon;

}
$user = getUserBilling($_SESSION['order_number']);
if(!empty($user)){
    $objFort->customerEmail = $user['email'];
}
$objFort->amount = calculateTotalAmount($cart);
$_SESSION['amount'] = $objFort->amount;
$cartItems = setCartItems($cart['id']);
$objFort->items = $cartItems;

// Code Added By Haseeb for addOns -- Start
//$cartAddOnItems = cartAddOns($cart['id']);
//$objFort->addonItems = $cartAddOnItems;
// Code Added By Haseeb for addOns -- End

$amount =  $objFort->amount;
$currency = $objFort->currency;
$totalAmount = $amount;
$paymentMethod = $_REQUEST['payment_method'];
$_SESSION['payment_method'] = $_REQUEST['payment_method'];
$_SESSION['paymentType'] = $_GET['paymentType'];
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
                      <?php foreach ($objFort->addons as $item) :
                        ?>
                          <tr>
                              <td><?= $item->item_name; ?></td>
                              <td><?= $item->item_quantity; ?></td>
                              <td><?= $objFort->currency . ' ' . $item->total_price; ?></td>
                          </tr>
                      <?php endforeach; ?>


                       
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>Sub
                                    Total: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $cart['sub_total']); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>VAT: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $cart['vat']); ?></strong>
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
                                <strong>Total: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $objFort->amount); ?></strong>
                            </td>
                        </tr>
                        <?php
                        if($_GET['paymentType'] == 'twenty_percent'): ?>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>20% Amount: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $objFort->amount * 0.2); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right">
                                <strong>Outstanding Amount: <?= $objFort->currency; ?> <?php echo sprintf("%.2f", $objFort->amount - $objFort->amount * 0.2); ?></strong>
                            </td>
                        </tr>
                        <?php endif; ?>
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
                $merchantPageData = $objFort->getMerchantPageData($paymentMethod, $_GET['order_number']);
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
                getPaymentPage(paymentMethod, '<?= $_GET['order_number']; ?>', '<?= $_GET['paymentType']; ?>');
            }
        });
    </script>
<?php include('footer.php') ?>
