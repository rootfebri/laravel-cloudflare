<?php

namespace Monicahq\Cloudflare;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use UnexpectedValueException;

class CloudflareProxies
{
    /**
     * Create a new instance of CloudflareProxies.
     */
    public function __construct(
        protected Repository $config,
        protected HttpClient $http
    ) {}

    /**
     * Retrieve Cloudflare proxies list.
     */
    public function load(): array
    {
        return $this->retrieve($this->config->get('laravelcloudflare.path'));
    }

    /**
     * Retrieve requested proxy list by name.
     */
    protected function retrieve(string $name): array
    {
        try {
            $url = Str::of($this->config->get('laravelcloudflare.url', 'https://api.cloudflare.com'))->finish('/').$name;
            $response = Http::get($url)->throw();
        } catch (Exception $e) {
            throw new UnexpectedValueException('Failed to load trust proxies from Cloudflare server.', 1, $e);
        }

        return $this->parseIps($response->json());
    }

    /**
     * Parses and combines IPv4 and IPv6 CIDRs from the given JSON array.
     *
     * @param array{
     *     result: array{
     *         ipv4_cidrs: string[],
     *         ipv6_cidrs: string[],
     *         etag: string,
     *     },
     *     success: bool,
     *     errors: array,
     *     messages: array
     * } $data The input array containing 'ipv4_cidrs' and 'ipv6_cidrs' keys.
     * @return array The combined array of IPv4 and IPv6 CIDRs.
     */
    protected function parseIps(array $data): array
    {
        return collect(array_merge($data['result']['ipv4_cidrs'], $data['result']['ipv6_cidrs']))
            ->unique()
            ->values();
    }
}
