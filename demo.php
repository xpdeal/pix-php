<?php

require_once './vendor/autoload.php';

use Xpdeal\Pixphp\Services\PixService;

$payload= (new PixService())
        ->setPixKey('chave-pix')
        ->setDescription('venda de sapato')
        ->setMerchantName('Fulano da Silva')
        ->setMerchantCity('')
        ->setTxId('000.000.000-00')
        ->setAmount(120);


echo $payload->getPayload();
