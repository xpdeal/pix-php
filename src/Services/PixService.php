<?php

declare(strict_types=1);

namespace Xpdeal\Pixphp\Services;

class PixService
{
    public const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    public const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    public const ID_MERCHANT_CATEGORY_CODE = '52';
    public const ID_TRANSACTION_CURRENCY = '53';
    public const ID_TRANSACTION_AMOUNT = '54';
    public const ID_COUNTRY_CODE = '58';
    public const ID_MERCHANT_NAME = '59';
    public const ID_MERCHANT_CITY = '60';
    public const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    public const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    public const ID_CRC16 = '63';

    private string $pixKey;
    private string $description;
    private string $merchantName;
    private string $merchantCity;
    private string $txId;
    private float $amount;

    /**
     * https://en.wikipedia.org/wiki/Polynomial
     * https://www.php.net/manual/en/function.ord.php
     * https://www.php.net/manual/en/function.strlen.php
     * @param  string  $payload
     *
     * @return string
     */
    private function getCRC16(string $payload): string
    {

        $payload .= $this::ID_CRC16 . '04';

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

        return $this::ID_CRC16 . '04' . strtoupper(dechex($resultant));
    }

    /**
     * @param  string  $pixKey
     *
     * @return $this
     */
    public function setPixKey(string $pixKey): PixService
    {
        $this->pixKey = $pixKey;
        return $this;
    }

    /**
     * @param  string  $description
     *
     * @return $this
     */
    public function setDescription(string $description): PixService
    {
        if (!empty($description)) {
            $this->description = substr($description, 0, 10);
        }

        return $this;
    }

    /**
     * @param  string  $merchantName
     *
     * @return $this
     */
    public function setMerchantName(string $merchantName): PixService
    {
        $this->merchantName = substr($merchantName, 0, 24);
        return $this;
    }

    /**
     * @param  string  $merchantCity
     *
     * @return $this
     */
    public function setMerchantCity(string $merchantCity): PixService
    {
        $this->merchantCity = substr($merchantCity, 0, 14);
        return $this;
    }

    public function setTxId(string $txId): PixService
    {
        $this->txId = substr($txId, 0, 7);
        return $this;
    }

    /**
     * @param  int|float  $amount
     *
     * @return $this
     */
    public function setAmount(int|float $amount): PixService
    {
        $this->amount = (float) number_format($amount, 2, '.', '');
        return $this;
    }


    /**
     * @param  string  $id
     * @param  string  $value
     *
     * @return string
     */
    private function getValue(
        string $id,
        string $value
    ): string {
        return sprintf(
            "%s%s%s",
            $id,
            str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT),
            $value
        );
    }

    private function getMerchantAccountInformation(): string
    {
        $gui = $this->getValue($this::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');
        $key = $this->getValue($this::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);
        $description = $this->getValue($this::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description);

        return $this->getValue($this::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }

    /**
     * @return string
     */

    private function getAdditionalDataFieldTemplate(): string
    {
        $txId = $this->getValue($this::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txId);
        return $this->getValue($this::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txId);
    }

    /**
     * @return float
     */
    public function getAmountFormat(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        $payload = $this->getValue($this::ID_PAYLOAD_FORMAT_INDICATOR, '01')
            . $this->getMerchantAccountInformation()
            . $this->getValue($this::ID_MERCHANT_CATEGORY_CODE, '0000')
            . $this->getValue($this::ID_TRANSACTION_CURRENCY, '986')
            . $this->getValue($this::ID_TRANSACTION_AMOUNT, (string) $this->amount)
            . $this->getValue($this::ID_COUNTRY_CODE, 'BR')
            . $this->getValue($this::ID_MERCHANT_NAME, $this->merchantName)
            . $this->getValue($this::ID_MERCHANT_CITY, $this->merchantCity)
            . $this->getAdditionalDataFieldTemplate();

        return $payload . $this->getCRC16($payload);
    }
}
