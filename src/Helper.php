<?php

namespace Laravel\Serverless;

use Psr\Http\Message\StreamInterface;

class Helper
{
    public static function stream_save(StreamInterface $stream, string $path)
    {
        $hndl = fopen($path, 'wb');

        while (!$stream->eof()) {
            fwrite($hndl, $stream->read(4096));
        }

        fclose($hndl);
    }
}
