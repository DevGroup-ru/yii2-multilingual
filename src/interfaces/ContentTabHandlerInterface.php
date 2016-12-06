<?php


namespace DevGroup\Multilingual\interfaces;


interface ContentTabHandlerInterface
{
    /**
     * @param int $contextId
     *
     * @return array [$key => $data]
     */
    public function contextData($contextId);
}