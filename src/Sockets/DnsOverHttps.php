<?php

namespace Jinomial\LaravelDns\Sockets;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;

class DnsOverHttps extends Socket implements SocketContract
{
    /**
     * The name of the option for enabling or disabling asynchronous queries.
     */
    public const OPTION_ASYNC = 'async';
    public const OPTION_ASYNC_DEFAULT = true;

    /**
     * The name of the option for enabling or disabling exceptions.
     */
    public const OPTION_THROW_ON_ERROR = 'throwOnError';
    public const OPTION_THROW_ON_ERROR_DEFAULT = true;

    /**
     * The name of the option for including/excluding DNSSEC data.
     */
    public const OPTION_DO = 'do';
    public const OPTION_DO_DEFAULT = false;

    /**
     * The name of the option for enabling or disabling DNSSEC verification.
     */
    public const OPTION_CD = 'cd';
    public const OPTION_CD_DEFAULT = true;

    /**
     * The name of the option for setting the accept header.
     */
    public const OPTION_ACCEPT = 'accept';
    public const OPTION_ACCEPT_DEFAULT = 'application/dns-json';

    /**
     * The name of the option for setting the content-type header.
     */
    public const OPTION_CT = 'ct';
    public const OPTION_CT_DEFAULT = 'application/dns-json';

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
     * @param string|array $name
     * @param string $type
     * @param array $options
     * @return array
     */
    public function query($name, $type = 'A', array $options = [])
    {
        if (! is_array($name)) {
            $name = [['name' => $name, 'type' => $type]];
        }

        return $this->sendRequests($name, $options);
    }

    /**
     * Parse a response into JSON.
     *
     * @param \GuzzleHttp\PSR7\Response $response
     * @return array
     */
    public function unwrap(array $promises, $throwOnError = null)
    {
        $responses = Promise\Utils::unwrap($promises);
        $results = array_map(
            fn ($response) => $this->handleResponse($response, $throwOnError),
            $responses
        );

        return $results;
    }

    /**
     * Parse a response into JSON.
     *
     * @param \GuzzleHttp\PSR7\Response $response
     * @return array|false
     */
    public function handleResponse(Response $response, $throwOnError = true)
    {
        if ($response->getReasonPhrase() !== 'OK') {
            return null;
        }

        $bodyRaw = $response->getBody();
        $body = json_decode(
            $bodyRaw,
            true,
            512,
            $throwOnError ? JSON_THROW_ON_ERROR : 0
        );

        return $body;
    }

    /**
     * Make HTTP requests for each lookup question.
     *
     * @param array $questions
     * @param array $options
     * @return array
     */
    protected function sendRequests(array $questions, array $options)
    {
        $async = $options[DnsOverHttps::OPTION_ASYNC] ??
            DnsOverHttps::OPTION_ASYNC_DEFAULT;
        $throwOnError = $options[DnsOverHttps::OPTION_THROW_ON_ERROR] ??
            DnsOverHttps::OPTION_THROW_ON_ERROR_DEFAULT;
        $clientOptions = [
            // 'debug' => TRUE,
            'http_errors' => $throwOnError,
        ];

        $results = [];
        foreach ($this->messages($questions, $options) as $request) {
            if ($async) {
                $results[] = $this->client->sendAsync($request, $clientOptions);
            } else {
                $response = $this->client->send($request, $clientOptions);
                $results[] = $this->handleResponse($response, $throwOnError);
            }
        }

        return $results;
    }

    /**
     * Create a PSR-7 Request with the query options.
     *
     * @param string $name
     * @param string $type
     * @param bool $do
     * @param bool $cd
     * @param string|null $accept
     * @param string|null $ct
     * @see https://developers.cloudflare.com/1.1.1.1/encrypted-dns/dns-over-https/make-api-requests/dns-json
     */
    protected function makeMessage(
        $name,
        $type,
        $do,
        $cd,
        $accept = null,
        $ct = null
    ) {
        $headers = [
            'Accept' => $accept ?? DnsOverHttps::OPTION_ACCEPT_DEFAULT,
            'Content-Type' => $ct ?? DnsOverHttps::OPTION_CT_DEFAULT,
        ];
        $data = array_filter([
            'name' => $name,
            'type' => $type,
            'do' => $do,
            'cd' => $cd,
            'ct' => $ct ?? DnsOverHttps::OPTION_CT_DEFAULT,
        ]);
        $uri = Uri::withQueryValues(new Uri($this->endpoint), $data);
        $message = new Request('GET', $uri, $headers, null, '2.0');

        return $message;
    }

    /**
     * Yield PSR7 requests for each question.
     *
     * @param array $questions
     * @param array $options
     * @return \Generator
     */
    protected function messages(array $questions, array $options = [])
    {
        // Get options or set them to their default value.
        $throwOnError = $options[DnsOverHttps::OPTION_THROW_ON_ERROR] ??
            DnsOverHttps::OPTION_THROW_ON_ERROR_DEFAULT;
        $do = $options[DnsOverHttps::OPTION_DO] ??
            DnsOverHttps::OPTION_DO_DEFAULT;
        $cd = $options[DnsOverHttps::OPTION_CD] ??
            DnsOverHttps::OPTION_CD_DEFAULT;
        $accept = $options[DnsOverHttps::OPTION_ACCEPT] ??
            DnsOverHttps::OPTION_ACCEPT_DEFAULT;
        $ct = $options[DnsOverHttps::OPTION_CT] ??
            DnsOverHttps::OPTION_CT_DEFAULT;

        for ($i = 0, $n = count($questions); $i < $n; $i++) {
            $name = $questions[$i]['name'] ?? null;
            $type = $questions[$i]['type'] ?? 'A';

            if (! $name && $throwOnError) {
                throw new InvalidArgumentException(
                    'Cannot resolve an empty name.'
                );
            }

            $request = $this->makeMessage($name, $type, $do, $cd, $accept, $ct);
            yield $request;
        }
    }
}
