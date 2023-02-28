# Pix PHP

Solução simples e muito rápida para gerar payloads (PIX) de pagamento únicos

Gostou? faz um pix 😜 Chave: jordan@vaivo.com (Precisa de ajuda? email jordan@xpdeal.com)

 Via composer

    composer require xpdeal/pix-php


# Uso

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


Em breve

 - Validação de dados (CPF, Amount, ...)
 - QRCode Base64 generator
 - Webhook (PSP Banco emissor)
