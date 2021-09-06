<?php

require_once(dirname(__FILE__) . '/Plisio/PlisioClient.php');
require_once(dirname(__FILE__) . '/Plisio/version.php');

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function plisio_config()
{
    $client = new PlisioClient('');
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Plisio'
        ),
        'ApiAuthToken' => array(
            'FriendlyName' => 'API Auth Token',
            'Description' => 'API Auth Token from Plisio API',
            'Type' => 'text',
        )
    );
}

function get_plisio_receive_currencies ($source_currency, $api_key) {
    $client = new PlisioClient($api_key);
    $currencies = $client->getCurrencies($source_currency);
    return array_reduce($currencies, function ($acc, $curr) {
        $acc[$curr['cid']] = $curr;
        return $acc;
    }, []);
}

function plisio_createOrder($params)
{
    $client = new PlisioClient($params['ApiAuthToken']);
    $returnLink = trim($params['systemurl'], "/");

    $plisio_receive_currencies = get_plisio_receive_currencies($params['currency'], $params['ApiAuthToken']);
    $plisio_receive_cids = array_keys($plisio_receive_currencies);

    $data = array(
        'currency' => $plisio_receive_cids[0],
        'order_name' => $params['companyname'] . ' Order #' . $params['invoiceid'],
        'order_number' => $params['invoiceid'],
        'description' => $params['description'],
        'source_amount' => number_format($params['amount'], 8, '.', ''),
        'source_currency' => $params['currency'],
        'cancel_url' => $returnLink . '/clientarea.php',
        'callback_url' => $returnLink . '/modules/gateways/callback/plisio.php',
        'success_url' => $returnLink . '/viewinvoice.php?id=' . $params['invoiceid'],
        'email' => $params['clientdetails']['email'],
        'language' => 'en',
        'plugin' => 'whmcs',
        'version' => PLISIO_WHMCS_VERSION,
        'whmcs_version' => $params['whmcsVersion'],
        'return_existing' => true
    );
    $response = $client->createTransaction($data);

    if ($response && $response['status'] !== 'error' && !empty($response['data'])) {

        header('Location: ' . $response['data']['invoice_url']);
        return '';

    } else {
        $form = '<h2>' . json_decode($response['data']['message'], true)['amount'] . '</h2>';
        return $form;
    }
}

function plisio_link($params)
{
    if (false === isset($params) || true === empty($params)) {
        die('[ERROR] In modules/gateways/plisio.php::plisio_link() function: Missing $params data.');
    }
    if ((isset($_POST) && !empty($_POST) && isset($_POST['sbmt'])) or $_GET['a'] == 'complete') {
        return plisio_createOrder($params);
    } else {
        $form = '<form method="POST">';
        $form .= '<input type="submit" name="sbmt" value="' . $params['langpaynow'] . '" />';
        $form .= '</form>';
        return $form;
    }
}
