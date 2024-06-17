<?php

namespace KvaPay\Exception\Api;

use KvaPay\Exception\ApiErrorException;

/**
 * BadAuthToken is thrown when auth token is not valid and HTTP Status: 401 (Unauthorized).
 */
class BadAuthToken extends ApiErrorException
{
}
