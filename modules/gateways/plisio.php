<?php

require_once(dirname(__FILE__) . '/Plisio/PlisioClient.php');
require_once(dirname(__FILE__) . '/Plisio/version.php');

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
    if (substr($params['systemurl'], -1) != "/") {
        $returnlink = $params['systemurl'] . "/";
    } else {
        $returnlink = $params['systemurl'];
    }

    $data = array(
        'currency' => $currency,
        'order_name' => $params['companyname'] . ' Order #' . $params['invoiceid'],
        'order_number' => $params['invoiceid'],
        'description' => $params['description'],
        'source_amount' => number_format($params['amount'], 8, '.', ''),
        'source_currency'  => $params['currency'],
        'cancel_url' => $returnlink . 'clientarea.php',
        'callback_url' => $returnlink . 'modules/gateways/callback/plisio.php',
        'success_url' => $returnlink . 'viewinvoice.php?id=' . $params['invoiceid'],
        'email' => $params['clientdetails']['email'],
        'language' => 'en',
        'plugin' => 'opencart',
        'version' => PLISIO_WHMCS_VERSION
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

    if (!isset($params['Cryptocurrency']) || empty($params['Cryptocurrency'])) {
        if (isset($_POST) && !empty($_POST) && isset($_POST['currency'])) {
            return plisio_createOrder($_POST['currency'], $params);
        } else {
            $client = new PlisioClient('');
            $currenciesResponse = $client->getCurrencies();

            $form = '<form method="POST">';
            $form .= '<input type="hidden" name="api_key" value="' . $params['ApiAuthToken'] . '" />';
            $form .= '<select name="currency" class="form-control select-inline">';
            foreach ($currenciesResponse['data'] as $item) {
                $form .= '<option value="' . $item['cid'] . '">' . $item['name']. ' (' . $item['currency'] . ')' . '</option>';
            }
            $form .= '</select>&nbsp;';
            $form .= '<input type="submit" name="sbmt" value="' . $params['langpaynow'] . '" />';
            $form .= '</form>';
            return $form;
        }
    } else {
        return plisio_createOrder($params['Cryptocurrency'], $params);
    }
}
