<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForcePublicApplicationUrl
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $this->publicHost($request);

        if ($host !== null) {
            $scheme = $this->publicScheme($request);
            URL::forceRootUrl($scheme.'://'.$host);
            URL::forceScheme($scheme);
        }

        return $next($request);
    }

    private function publicHost(Request $request): ?string
    {
        $forwarded = $request->header('X-Forwarded-Host');

        if (is_string($forwarded) && $forwarded !== '') {
            $host = strtolower(trim(explode(',', $forwarded)[0]));

            if ($this->isUsableHost($host)) {
                return $host;
            }
        }

        $host = strtolower($request->getHost());

        if (! $this->isUsableHost($host)) {
            return null;
        }

        $scheme = $this->publicScheme($request);
        $port = $request->getPort();
        $defaultPort = $scheme === 'https' ? 443 : 80;

        if ($port && $port !== $defaultPort) {
            return $host.':'.$port;
        }

        return $host;
    }

    private function publicScheme(Request $request): string
    {
        $forwarded = $request->header('X-Forwarded-Proto');

        if (is_string($forwarded) && $forwarded !== '') {
            $scheme = strtolower(trim(explode(',', $forwarded)[0]));

            if (in_array($scheme, ['http', 'https'], true)) {
                return $scheme;
            }
        }

        return $request->getScheme();
    }

    private function isUsableHost(string $host): bool
    {
        $host = explode(':', $host)[0];

        if ($host === '' || $host === '0.0.0.0') {
            return false;
        }

        if (in_array($host, ['kotbean-web', 'kotbean-app', 'kotbean-db'], true)) {
            return false;
        }

        return true;
    }
}
