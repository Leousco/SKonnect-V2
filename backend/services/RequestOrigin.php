<?php

class RequestOrigin
{
    public static function isSameOrigin(): bool
    {
        $originHeader = $_SERVER['HTTP_ORIGIN'] ?? '';
        $refererHeader = $_SERVER['HTTP_REFERER'] ?? '';
        $hostDetails = parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''));
        $host = strtolower(is_array($hostDetails) ? ($hostDetails['host'] ?? '') : '');
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $scheme = in_array($forwardedProto, ['http', 'https'], true)
            ? $forwardedProto
            : ((!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http');
        $sourceHeader = $originHeader !== '' ? $originHeader : $refererHeader;
        $source = $sourceHeader !== '' ? parse_url($sourceHeader) : false;
        $sourceHost = is_array($source) ? strtolower($source['host'] ?? '') : '';
        $sourceScheme = is_array($source) ? strtolower($source['scheme'] ?? '') : '';
        $sourcePort = is_array($source) ? ($source['port'] ?? null) : null;
        $requestPort = is_array($hostDetails)
            ? ($hostDetails['port'] ?? ($scheme === 'https' ? 443 : 80))
            : ($scheme === 'https' ? 443 : 80);

        return $host !== ''
            && is_array($source)
            && in_array($sourceScheme, ['http', 'https'], true)
            && $sourceScheme === $scheme
            && $sourceHost === $host
            && ($sourcePort ?? ($sourceScheme === 'https' ? 443 : 80)) === $requestPort;
    }
}
