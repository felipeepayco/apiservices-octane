<?php

namespace App\Events\RestrictiveList\Process;

use App\Models\PreRegister;

class ProcessRestrictiveListSaveLogEvent
{
    /**
     * @var PreRegister|null
     */
    public $preRegister;
    /**
     * @var array
     */
    public $request;
    public $serviceResponse;
    /**
     * @var bool
     */
    public $isClientInLists;
    /**
     * @var int
     */
    public $validationType;

    /**
     * @param PreRegister|null $preRegister
     * @param array $request
     * @param $serviceResponse
     * @param bool $isClientInLists
     */
    public function __construct(
        ?PreRegister $preRegister,
        array $request,
        $serviceResponse,
        bool $isClientInLists,
        int $validationType
    ) {
        $this->preRegister = $preRegister;
        $this->request = $request;
        $this->serviceResponse = $serviceResponse;
        $this->isClientInLists = $isClientInLists;
        $this->validationType = $validationType;
    }
}
