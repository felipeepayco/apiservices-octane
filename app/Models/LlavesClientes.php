<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $private_key
 * @property string $private_key_decrypt
 * @property string $public_key
 * @property \Carbon\Carbon $fechacreacion
 * @property \Carbon\Carbon $fechaactualizacion
 * @property int $cliente_id
 */
class LlavesClientes extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'llaves_clientes';

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return LlavesClientes
     */
    public function setId(int $id): LlavesClientes
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->private_key;
    }

    /**
     * @param string $private_key
     * @return LlavesClientes
     */
    public function setPrivateKey(string $private_key): LlavesClientes
    {
        $this->private_key = $private_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrivateKeyDecrypt(): string
    {
        return $this->private_key_decrypt;
    }

    /**
     * @param string $private_key_decrypt
     * @return LlavesClientes
     */
    public function setPrivateKeyDecrypt(string $private_key_decrypt): LlavesClientes
    {
        $this->private_key_decrypt = $private_key_decrypt;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->public_key;
    }

    /**
     * @param string $public_key
     * @return LlavesClientes
     */
    public function setPublicKey(string $public_key): LlavesClientes
    {
        $this->public_key = $public_key;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getFechacreacion(): \Carbon\Carbon
    {
        return $this->fechacreacion;
    }

    /**
     * @param \Carbon\Carbon $fechacreacion
     * @return LlavesClientes
     */
    public function setFechacreacion(\Carbon\Carbon $fechacreacion): LlavesClientes
    {
        $this->fechacreacion = $fechacreacion;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getFechaactualizacion(): \Carbon\Carbon
    {
        return $this->fechaactualizacion;
    }

    /**
     * @param \Carbon\Carbon $fechaactualizacion
     * @return LlavesClientes
     */
    public function setFechaactualizacion(\Carbon\Carbon $fechaactualizacion): LlavesClientes
    {
        $this->fechaactualizacion = $fechaactualizacion;
        return $this;
    }

    /**
     * @return int
     */
    public function getClienteId(): int
    {
        return $this->cliente_id;
    }

    /**
     * @param int $cliente_id
     * @return LlavesClientes
     */
    public function setClienteId(int $cliente_id): LlavesClientes
    {
        $this->cliente_id = $cliente_id;
        return $this;
    }

    /**
     * @var array
     */
    protected $fillable = ['id', 'private_key', 'public_key', 'fechacreacion',
        'fechaactualizacion', 'cliente_id', 'private_key_decrypt'];

    /**
     * @var array
     */
    protected $dates = ['fechacreacion', 'fechaactualizacion'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
