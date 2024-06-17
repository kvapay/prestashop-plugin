<?php

namespace KvaPay\Exception\Api;

use KvaPay\Exception\ApiErrorException;

/**
 * Unauthorized is thrown when HTTP Status: 401 (Unauthorized).
 */
class Unauthorized extends ApiErrorException
{
}
