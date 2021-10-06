<?php

namespace Jinomial\LaravelDns\Sockets;

use GuzzleHttp\ClientInterface;
use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;

class DnsOverHttps extends Socket implements SocketContract
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The DNS over HTTPS API endpoint.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Create a new DNS socket instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string|null  $endpoint
     * @return void
     */
    public function __construct($name, ClientInterface $client, $endpoint = null)
    {
        $this->name = $name;
        $this->client = $client;
        $this->endpoint = $endpoint ?? 'https://cloudflare-dns.com/dns-query';
    }

    /**
     * Perform a DNS lookup on the endpoint.
     *
     * https://developers.cloudflare.com/1.1.1.1/encrypted-dns/dns-over-https/make-api-requests
     * Cloudflare supports GET requests for JSON or POST for wireformat.
     * GET requests should include a 'ct' param.
     *
     * @param string $name
     * @param string $type
     * @param array $options
     * @return \GuzzleHttp\Psr7\Response
     */
    public function query($name, $type = 'A', array $options = [])
    {
        $headers = [
            'Accept' => 'application/dns-json',
            'Content-Type' => 'application/dns-json',
        ];
        // https://developers.cloudflare.com/1.1.1.1/encrypted-dns/dns-over-https/make-api-requests/dns-json
        $data = [
            'name' => $name,
            'type' => $type,
            'do' => ($options['do'] ?? false) ? true : null, // Don't include DNSSEC records.
            'cd' => ($options['cd'] ?? true) ? true : null, // Do verify DNSSEC by default.
            'ct' => 'application/dns-json',
        ];
        // http_errors throws an exception if HTTP response isn't OK.
        $response = $this->client->request('GET', $this->endpoint, [
            // 'debug' => TRUE,
            'headers' => $headers,
            'http_errors' => true,
            'query' => $data,
        ]);
        $bodyRaw = $response->getBody();
        $body = json_decode($bodyRaw, true, 512, JSON_THROW_ON_ERROR);

        return $body;
    }
}
