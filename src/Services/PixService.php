<?php
declare(strict_types=1);
namespace Xpdeal\Pixphp\Services;

use JetBrains\PhpStorm\Pure;

class PixService
{
    const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    const ID_MERCHANT_CATEGORY_CODE = '52';
    const ID_TRANSACTION_CURRENCY = '53';
    const ID_TRANSACTION_AMOUNT = '54';
    const ID_COUNTRY_CODE = '58';
    const ID_MERCHANT_NAME = '59';
    const ID_MERCHANT_CITY = '60';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    const ID_CRC16 = '63';

    private $pixKey;
    private $description;
    private $merchantName;
    private $merchantCity;
    private $txId;
    private $amount;

    /**
     * @param  string  $payload
     *
     * @return string
     */
    private function getCRC16(string $payload): string
    {

        $payload .= self::ID_CRC16 . '04';

        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        if (($length = strlen($payload)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $resultado ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }


        return self::ID_CRC16 . '04' . strtoupper(dechex($resultado));
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
     */public function setMerchantCity(string $merchantCity): PixService
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
     */public function setAmount(int|float $amount): PixService
    {
        $this->amount = (string) number_format($amount, 2, '.', '');
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
        $size = str_pad((string)strlen($value), 2, '0',  STR_PAD_LEFT);
        return $id . $size . $value;
    }

    #[Pure]
    private function getMerchantAccountInformation(): string
    {
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');
        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);
        $description = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description);

        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }

    /**
     * @return string
     */
    #[Pure]
    private function getAdditionalDataFieldTemplate(): string
    {
        $txId = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txId);
        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txId);
    }

    /**
     * @return mixed
     */
    public function getAmountFormat(): mixed
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        $payload = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01')
            . $this->getMerchantAccountInformation()
            . $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000')
            . $this->getValue(self::ID_TRANSACTION_CURRENCY, '986')
            . $this->getValue(self::ID_TRANSACTION_AMOUNT, $this->amount)
            . $this->getValue(self::ID_COUNTRY_CODE, 'BR')
            . $this->getValue(self::ID_MERCHANT_NAME, $this->merchantName)
            . $this->getValue(self::ID_MERCHANT_CITY, $this->merchantCity)
            . $this->getAdditionalDataFieldTemplate();

        return $payload . $this->getCRC16($payload);
    }

}
