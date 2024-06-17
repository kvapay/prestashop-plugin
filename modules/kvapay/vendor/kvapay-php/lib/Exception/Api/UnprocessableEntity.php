<?php

namespace KvaPay\Exception\Api;

use KvaPay\Exception\ApiErrorException;

/**
 * UnprocessableEntity is thrown when HTTP Status: 422 (Unprocessable Entity).
 */
class UnprocessableEntity extends ApiErrorException
{
}
