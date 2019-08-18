<?php

namespace Laravel\Serverless;

class HeadersParser
{
    public static function parse(array $headerLines) : array
    {
        $headers = [];
        foreach ($headerLines as $headerLine) {
            if (false === strpos($headerLine, ':')) {
                throw new \RuntimeException('Malformed header');
            }

            list($headerName, $headerValue) = explode(':', $headerLine, 2);
            $headers[ trim(strtolower($headerName)) ][] = trim($headerValue);
        }
        return $headers;
    }
}
