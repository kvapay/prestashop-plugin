<?php

namespace KvaPay\Services;

class ProjectsService extends AbstractService
{

    /**
     * @param string $symbol
     * @param string $network
     * @return mixed
     */
    public function getAddress( string $symbol, string $network)
    {
        $params = [
            'symbol' => $symbol,
            'network' => $network
        ];

        return $this->request('get', '/address', $params);
    }

    /**
     * @param string $symbol
     * @param string $network
     * @return mixed
     */
    public function createAddress(string $symbol, string $network)
    {
        $params = [
            'symbol' => $symbol,
            'network' => $network
        ];

        return $this->request('post', '/addresses', $params);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return mixed
     */
    public function getExchangeRate(string $source, string $destination)
    {
        $params = [
            'source' => $source,
            'destination' => $destination
        ];

        return $this->request('get', '/exchange_rate', $params);
    }

    /**
     * @param string $symbol
     * @return mixed
     */
    public function getBalance(string $symbol)
    {
        $params = [
            'symbol' => $symbol
        ];

        return $this->request('get', '/balance', $params);
    }

    /**
     * @return mixed
     */
    public function createNewOperation()
    {

        return $this->request('post', '/operation');
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function exchange(array $params = [])
    {
        $params['operation'] = $this->createNewOperation()['operationId'];

        return $this->request('post', '/exchange', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function send(array $params = [])
    {
        $params['operation'] = $this->createNewOperation()['operationId'];

        return $this->request('post', '/send', $params);
    }
}
