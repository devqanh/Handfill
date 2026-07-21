<?php

namespace Botble\HandmadeWorkflow\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checks that the photo links on an order actually lead somewhere, without pulling
 * the files down. Only the headers are fetched, and many at a time, so validating a
 * 50-line sheet costs seconds rather than minutes.
 */
class ImageLinkChecker
{
    /** A link that opens now still opens a minute later; no need to ask twice. */
    public const CACHE_TTL = 600;

    /** Nothing shows a photo — dead host, 404, expired share page. */
    public const BROKEN = 'broken';

    /** Answers with an actual image, so it can be shown as a thumbnail. */
    public const IMAGE = 'image';

    /**
     * Opens fine but is a web page, not a file — a Lightshot or Drive share link.
     * Staff can still click it, so this is a caution rather than a rejection.
     */
    public const PAGE = 'page';

    protected const CONCURRENCY = 10;

    protected const TIMEOUT = 8;

    protected const USER_AGENT = 'Mozilla/5.0 (compatible; HandfillBot/1.0)';

    /**
     * @param  array<int, string>  $urls
     * @return array<string, string> url => one of BROKEN / IMAGE / PAGE
     */
    public function check(array $urls): array
    {
        $results = [];
        $pending = [];

        foreach (array_unique($urls) as $url) {
            if (! $this->isFetchable($url)) {
                $results[$url] = self::BROKEN;

                continue;
            }

            $cached = Cache::get($this->cacheKey($url));

            if ($cached) {
                $results[$url] = $cached;
            } else {
                $pending[] = $url;
            }
        }

        foreach (array_chunk($pending, self::CONCURRENCY) as $batch) {
            foreach ($this->probe($batch) as $url => $status) {
                Cache::put($this->cacheKey($url), $status, self::CACHE_TTL);
                $results[$url] = $status;
            }
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $urls
     * @return array<string, string>
     */
    protected function probe(array $urls): array
    {
        // HEAD first: it is the cheapest question we can ask.
        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (string $url) => $pool->as($url)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::TIMEOUT)
                ->head($url),
            $urls
        ));

        $results = [];
        $retry = [];

        foreach ($urls as $url) {
            $response = $responses[$url] ?? null;

            // Plenty of hosts refuse HEAD (405/501) or sign their URLs for GET only
            // (403). That is not a broken link — ask again the expensive way.
            if ($response instanceof Response && in_array($response->status(), [403, 405, 501], true)) {
                $retry[] = $url;

                continue;
            }

            $results[$url] = $this->interpret($response);
        }

        return $retry ? $results + $this->probeWithRange($retry) : $results;
    }

    /**
     * Ask for the first byte only: enough to read the content type, cheap enough that
     * a server ignoring the Range header still sends us almost nothing.
     *
     * @param  array<int, string>  $urls
     * @return array<string, string>
     */
    protected function probeWithRange(array $urls): array
    {
        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (string $url) => $pool->as($url)
                ->withHeaders(['User-Agent' => self::USER_AGENT, 'Range' => 'bytes=0-0'])
                ->timeout(self::TIMEOUT)
                ->get($url),
            $urls
        ));

        $results = [];

        foreach ($urls as $url) {
            $results[$url] = $this->interpret($responses[$url] ?? null);
        }

        return $results;
    }

    protected function interpret(mixed $response): string
    {
        // A pool entry is an exception object when the request never completed.
        if (! $response instanceof Response) {
            return self::BROKEN;
        }

        if ($response->status() >= 400) {
            return self::BROKEN;
        }

        return Str::startsWith((string) $response->header('Content-Type'), 'image/')
            ? self::IMAGE
            : self::PAGE;
    }

    /**
     * Reject anything we should not be asking our own server to fetch: other schemes,
     * and hosts that resolve inside the network.
     */
    protected function isFetchable(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || ! $host) {
            return false;
        }

        try {
            $ip = gethostbyname($host);
        } catch (Throwable) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    protected function cacheKey(string $url): string
    {
        return 'handmade-link-check:' . sha1($url);
    }
}
