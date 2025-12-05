<?php

declare(strict_types=1);

namespace Xpdeal\Pixphp\Services;

use chillerlan\QRCode\QRCode;

class PixService
{
    public const string ID_PAYLOAD_FORMAT_INDICATOR = '00';
    public const string ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    public const string ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    public const string ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    public const string ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    public const string ID_MERCHANT_CATEGORY_CODE = '52';
    public const string ID_TRANSACTION_CURRENCY = '53';
    public const string ID_TRANSACTION_AMOUNT = '54';
    public const string ID_COUNTRY_CODE = '58';
    public const string ID_MERCHANT_NAME = '59';
    public const string ID_MERCHANT_CITY = '60';
    public const string ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    public const string ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    public const string ID_CRC16 = '63';

    private string $pixKey = '';
    private string $description = '';
    private string $merchantName = '';
    private string $merchantCity = '';
    private string $txId = '';
    private float $amount = 0.0;

    private function getCRC16(string $payload): string
    {
        $payload .= self::ID_CRC16 . '04';

        $polynomial = 0x1021;
        $resultant  = 0xFFFF;

        $length = strlen($payload);

        for ($offset = 0; $offset < $length; $offset++) {
            $resultant ^= (ord($payload[$offset]) << 8);
            for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                if (($resultant <<= 1) & 0x10000) {
                    $resultant ^= $polynomial;
                }
                $resultant &= 0xFFFF;
            }
        }

        return self::ID_CRC16 . '04' . strtoupper(dechex($resultant));
    }

    public function setPixKey(string $pixKey): static
    {
        $this->pixKey = $pixKey;
        return $this;
    }

    public function setDescription(string $description): static
    {
        if (!empty($description)) {
            $this->description = substr($description, 0, 100);
        }
        return $this;
    }

    public function setMerchantName(string $merchantName): static
    {
        $this->merchantName = substr($merchantName, 0, 24);
        return $this;
    }

    public function setMerchantCity(string $merchantCity): static
    {
        $this->merchantCity = substr($merchantCity, 0, 14);
        return $this;
    }

    public function setTxId(string $txId): static
    {
        $this->txId = substr($txId, 0, 7);
        return $this;
    }

    public function setAmount(int|float $amount): static
    {
        $this->amount = (float) number_format($amount, 2, '.', '');
        return $this;
    }


    private function getValue(string $id, string $value): string
    {
        return sprintf(
            "%s%s%s",
            $id,
            str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT),
            $value
        );
    }

    private function getMerchantAccountInformation(): string
    {
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');
        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);
        $description = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description);

        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }

    private function getAdditionalDataFieldTemplate(): string
    {
        $txId = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txId);
        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txId);
    }

    public function getAmountFormat(): float
    {
        return $this->amount;
    }

    public function getPayload(): string
    {
        $payload = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01')
            . $this->getMerchantAccountInformation()
            . $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000')
            . $this->getValue(self::ID_TRANSACTION_CURRENCY, '986')
            . $this->getValue(self::ID_TRANSACTION_AMOUNT, (string) $this->amount)
            . $this->getValue(self::ID_COUNTRY_CODE, 'BR')
            . $this->getValue(self::ID_MERCHANT_NAME, $this->merchantName)
            . $this->getValue(self::ID_MERCHANT_CITY, $this->merchantCity)
            . $this->getAdditionalDataFieldTemplate();

        return $payload . $this->getCRC16($payload);
    }

    /**
     * @return array{payload: string, qrcode: string}
     */
    public function getPayloadAndQrcode(): array
    {
        $payloadQrcode = $this->getPayload();
        $qrcode = (new QRCode())->render($payloadQrcode);

        return ['payload' => $payloadQrcode, 'qrcode' => $qrcode];
    }
}
