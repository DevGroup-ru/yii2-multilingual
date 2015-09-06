<?php

namespace DevGroup\Multilingual\validators;

use yii\validators\Validator;

/**
 * Validates if string is a valid ip
 * @package DevGroup\Multilingual\validators
 */
class IpValidator extends Validator
{
    /**
     * @var bool Filter for IPV4 only
     */
    public $ipv4 = false;
    /**
     * @var bool Filter for IPV6 only
     */
    public $ipv6 = false;

    /**
     * @var bool Disallow IPs from the private range
     */
    public $noPrivRange = false;

    /**
     * @var bool Disallow IPs from the reserved range
     */
    public $noResRange = false;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $flags = null;
        if ($this->ipv4 === true) {
            $flags |= FILTER_FLAG_IPV4;
        }
        if ($this->ipv6 === true) {
            $flags |= FILTER_FLAG_IPV6;
        }
        if ($this->noPrivRange === true) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if ($this->noResRange === true) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }
        return filter_var($value, FILTER_VALIDATE_IP, $flags) !== false;
    }
}