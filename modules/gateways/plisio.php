<?php

require_once(dirname(__FILE__) . '/Plisio/PlisioClient.php');
require_once(dirname(__FILE__) . '/Plisio/version.php');

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function plisio_config()
{
    $client = new PlisioClient('');
    $currenciesResponse = $client->getCurrencies();
    $cryptos = [];
    $cryptos[''] = 'Any';
    foreach ($currenciesResponse['data'] as $item) {
        $cryptos[$item['cid']] = $item['name'] . ' (' . $item['currency'] . ')';
    }
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Plisio'
        ),
        'ApiAuthToken' => array(
            'FriendlyName' => 'API Auth Token',
            'Description' => 'API Auth Token from Plisio API',
            'Type' => 'text',
        ),
        'Cryptocurrency' => array(
            'FriendlyName' => 'Cryptocurrency',
            'Type' => 'dropdown',
            'Default' => '',
            'Description' => 'Allow pay order in one or "Any" supported cryptocurrency',
            'Options' => $cryptos,
        )
    );
}

function plisio_createOrder($currency, $params)
{
    $client = new PlisioClient($params['ApiAuthToken']);
    $returnLink = trim($params['systemurl'], "/");

//    if (empty($returnLink)) {
//        $returnLink = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//        $returnLink .= $_SERVER['HTTP_HOST'];
//    }

    $data = array(
        'currency' => $currency,
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
    );
    $response = $client->createTransaction($data);

    if ($response && $response['status'] !== 'error' && !empty($response['data'])) {

        header('Location: ' . $response['data']['invoice_url']);
        return '';

    } else {
        $form = '<h2>' . $response['data']['message'] . '</h2>';
        $form .= '<h3>Please contact merchant for further details</h3>';
        return $form;
    }
}

function plisio_link($params)
{
    if (false === isset($params) || true === empty($params)) {
        die('[ERROR] In modules/gateways/plisio.php::plisio_link() function: Missing $params data.');
    }
    if (isset($_POST) && !empty($_POST) && isset($_POST['currency'])) {
        return plisio_createOrder($_POST['currency'], $params);
    } else {
        $client = new PlisioClient('');
        $currenciesResponse = $client->getCurrencies();
        $form = '<form method="POST">';
        $form .= '<input type="hidden" name="api_key" value="' . $params['ApiAuthToken'] . '" />';
        $form .= '<select name="currency" class="form-control select-inline">';
        foreach ($currenciesResponse['data'] as $item) {
            if (!isset($params['Cryptocurrency']) || empty($params['Cryptocurrency'])) {
                $form .= '<option value="' . $item['cid'] . '">' . $item['name'] . ' (' . $item['currency'] . ')' . '</option>';
            } else {
                if ($item['currency'] == $params['Cryptocurrency']) {
                    $form .= '<option value="' . $item['cid'] . '">' . $item['name'] . ' (' . $item['currency'] . ')' . '</option>';
                }
            }
        }
        $form .= '</select>&nbsp;';
        $form .= '<input type="submit" name="sbmt" value="' . $params['langpaynow'] . '" />';
        $form .= '</form>';
        return $form;
    }
}
