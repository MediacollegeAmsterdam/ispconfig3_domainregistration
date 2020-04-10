<?php

namespace Domainregistration\Http;

use Domainregistration\Exception\Http\ClientException;

class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * @param string $method
     * @param string $url
     * @param string $data
     * @param array $headers
     * @return string
     * @throws ClientException
     */
    public function request($method, $url, $data, $headers = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (is_bool($response)) {
            throw new ClientException($error);
        }

        return $response;
    }
}
