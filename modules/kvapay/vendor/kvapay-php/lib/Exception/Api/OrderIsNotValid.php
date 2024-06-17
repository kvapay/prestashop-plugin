<?php

namespace KvaPay\Exception\Api;

use KvaPay\Exception\ApiErrorException;

/**
 * OrderIsNotValid is thrown when order is not valid and HTTP Status: 422 (Unprocessable Entity).
 */
class OrderIsNotValid extends ApiErrorException
{
}
