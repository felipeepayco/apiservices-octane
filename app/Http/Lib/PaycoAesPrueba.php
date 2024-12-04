<?php namespace app\Http\Lib;


class PaycoAesPrueba
{

    private $_cipher = AES-256-CBC;
    private $_mode = MCRYPT_MODE_CBC;
    private $_key;
    private $_initializationVectorSize;

    public function __construct($key,$iv)
    {
        $this->_key = $key;
        $this->iv=$iv;
        $this->_initializationVectorSize = mcrypt_get_iv_size($this->_cipher, $this->_mode);

        if (strlen($key) > ($keyMaxLength = mcrypt_get_key_size($this->_cipher, $this->_mode))) {
            throw new \InvalidArgumentException("The key length must be less or equal than $keyMaxLength.");
        }
    }


    public function encrypt($data)
    {

        $encript= mcrypt_encrypt(
            $this->_cipher,
            $this->_key,
            $this->addpadPKCS7($data,$this->_initializationVectorSize),
            $this->_mode,
            $this->iv
        );
        return base64_encode($encript);
    }

    public function decrypt($encryptedData)
    {

        $data =  @mcrypt_decrypt(
            $this->_cipher,
            $this->_key,
            base64_decode($encryptedData),
            $this->_mode,
            $this->iv
        );
        //return $data;
        return $this->unpadPKCS7($data);
    }

    private function addpadPKCS7($data, $block_size) {
        $pad = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($pad), $pad);
        return $data;
    }
    private function unpadPKCS7($data) {
        $last = substr($data, -1);
        return substr($data, 0, strlen($data) - ord($last));
    }

}