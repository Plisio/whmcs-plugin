<?php

include('../../../includes/functions.php');
include('../../../includes/gatewayfunctions.php');
include('../../../includes/invoicefunctions.php');

if (file_exists('../../../dbconnect.php'))
    include '../../../dbconnect.php';
else if (file_exists('../../../init.php'))
    include '../../../init.php';
else
    die('[ERROR] In modules/gateways/callback/plisio.php: include error: Cannot find dbconnect.php or init.php');

$gatewaymodule = 'plisio';
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) {
    logTransaction($GATEWAY['name'], $_POST, 'Not activated');
    die('[ERROR] In modules/gateways/callback/plisio.php: Plisio module not activated.');
}


$order_id = $_POST['order_number'];

$invoice_id = checkCbInvoiceID($order_id, $GATEWAY['plisio']);

if (!$invoice_id)
    throw new Exception('Order #' . $order_id . ' does not exists');

$trans_id = $_POST['txn_id'];

checkCbTransID($trans_id);

$fee = 0;
$amount = $_POST['source_amount'];

$response = json_encode($_POST);
if (verifyCallbackData($_POST, $GATEWAY['ApiAuthToken'])) {
    switch ($_POST['status']) {
        case 'completed':
        case 'mismatch':
            addInvoicePayment($invoice_id, $trans_id, $amount, $fee, $gatewaymodule);
            logTransaction($GATEWAY['name'], $response, 'Payment is confirmed by the network, and has been credited to the merchant. Purchased goods/services can be securely delivered to the buyer. ' . $_POST['comment']);
            break;
        case 'new':
            logTransaction($GATEWAY['name'], $response, 'Buyer selected payment currency. Awaiting payment.');
            break;
        case 'pending':
            logTransaction($GATEWAY['name'], $response, 'Buyer transferred the payment for the invoice. Awaiting blockchain network confirmation.');
            break;
        case 'expired':
            if ($amount > 0){
                addInvoicePayment($invoice_id, $trans_id, $amount, 0, $gatewaymodule);
                logTransaction($GATEWAY['name'], $response, $_POST['comment']);
            } else {
                logTransaction($GATEWAY['name'], $response, 'Buyer did not pay within the required time and the invoice expired.');
            }
            break;
        case 'error':
            logTransaction($GATEWAY['name'], $response, 'Payment rejected by the network or did not confirm.');
            break;
//    case 'refunded':
//        logTransaction($GATEWAY['name'], $response, 'Payment was refunded to the buyer.');
//        break;
    }
} else {
    logTransaction($GATEWAY['name'], $response, 'Callback data looks compromised');
}

function verifyCallbackData($post, $apiKey)
{
    if (!isset($post['verify_hash'])) {
        return false;
    }

    $verifyHash = $post['verify_hash'];
    unset($post['verify_hash']);
    ksort($post);
    $postString = serialize($post);
    $checkKey = hash_hmac('sha1', $postString, $apiKey);
    if ($checkKey != $verifyHash) {
        return false;
    }

    return true;
}