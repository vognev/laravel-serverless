<?php

namespace Laravel\Serverless;

// naїve and rough parser
// needs more checks and tests
use Illuminate\Http\UploadedFile;

class MultipartParser
{
    private $formData = [];
    private $files = [];

    const READ_BOUNDARY = 1;
    const PREPARE_ENTRY = 2;
    const READ_HEADER = 3;
    const READ_BODY = 4;
    const COMMIT_ENTRY = 5;

    const CHUNKSIZE = 4096;

    const CRLF = "\r\n";

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function __construct($body)
    {
        rewind($body);
        $this->parse($body);
    }

    private function parse($body)
    {
        $entries = [];
        $buffer = '';
        $length = 0;

        $feed = function () use (&$buffer, &$length, &$body) {
            if ($length < self::CHUNKSIZE) {
                $chunk   = fread($body, self::CHUNKSIZE - $length);
                $length += strlen($chunk);
                $buffer .= $chunk;
            }
        };

        $discard = function ($amount) use (&$buffer, &$length) {
            $chunk  = substr($buffer, 0, $amount);
            $buffer = substr($buffer, $amount);
            $length -= $amount;
            return $chunk;
        };

        $state      = self::READ_BOUNDARY;
        $boundary   = self::CRLF;

        while (true) {
            $feed();

            if ($buffer === self::CRLF) {
                if (isset($entry)) $entries[] = $entry;
                break;
            }

            switch ($state) {
                case self::READ_BOUNDARY:
                    for ($j = 0; $j < $length && $buffer[$j] != self::CRLF[0]; $j++) {
                        $boundary .= $buffer[$j];
                    }
                    if ($j + 1 >= $length || $buffer[$j + 1] != self::CRLF[1]) {
                        throw new \RuntimeException('Unable to find boundary');
                    }
                    $discard($j + 2); $state = self::PREPARE_ENTRY;
                    break;

                case self::PREPARE_ENTRY:
                    $entry = [
                        'headers' => [],
                        'content' => tmpfile()
                    ];
                    $state = self::READ_HEADER;
                    break;

                case self::READ_HEADER:
                    $header = '';
                    for ($j = 0; $j < $length && $buffer[$j] != self::CRLF[0]; $j++) {
                        $header .= $buffer[$j];
                    }
                    if ($j + 1 >= $length || $buffer[$j + 1] != self::CRLF[1]) {
                        throw new \RuntimeException('Unable to parse headerline');
                    }

                    if ("" === $header) {
                        $state = self::READ_BODY;
                    } else {
                        $entry['headers'][] = $header;
                    }

                    $discard(strlen($header) + 2);
                    break;

                case self::READ_BODY:
                    if (!isset($entry)) {
                        throw new \RuntimeException('Invalid state');
                    }

                    $index = strpos($buffer, $boundary);
                    if (false === $index) {
                        fwrite($entry['content'], $discard($length - strlen($boundary)));
                    } else {
                        fwrite($entry['content'], $discard($index));
                        $discard(strlen($boundary) + 2);
                        $state = self::COMMIT_ENTRY;
                    }
                    break;

                case self::COMMIT_ENTRY:
                    if (!isset($entry)) {
                        throw new \RuntimeException('Invalid state');
                    }

                    $entries[] = $entry;
                    $state = self::PREPARE_ENTRY;
                    break;

                default:
                    throw new \RuntimeException('Invalid state');
            }
        }

        $this->transform($entries);
    }

    private function transform(array &$entries)
    {
        while (count($entries)) {
            $entry      = array_shift($entries);
            $headers    = HeadersParser::parse($entry['headers']);

            if (!array_key_exists('content-disposition', $headers)) {
                throw new \RuntimeException('Missing Content-Disposition header');
            }

            $contentDisposition = explode(';', $headers['content-disposition'][0]);
            $contentDisposition = array_map('trim', $contentDisposition);

            $formData = null; $fieldName = null; $fileName = null;
            foreach ($contentDisposition as $disposition) switch (true) {
                case 'form-data' === strtolower($disposition) : $formData = true; break;
                case 0 === strpos(strtolower($disposition), 'name='):
                    $fieldName = substr($disposition, 6, -1); break;
                case 0 === strpos(strtolower($disposition), 'filename='):
                    $fileName = substr($disposition, 10, -1); break;
            }

            if (is_null($formData) || is_null($fieldName) || empty($fieldName)) {
                throw new \RuntimeException('Malformed header');
            }

            if (is_null($fileName)) {
                rewind($entry['content']);
                VarMerger::merge($this->formData, $fieldName, stream_get_contents($entry['content']));
                fclose($entry['content']);
            } else {
                $metadata = stream_get_meta_data($entry['content']);
                VarMerger::merge($this->files, $fieldName, $this->createUploadedFile(
                    $metadata['uri'],
                    $fileName,
                    $headers['content-type'][0] ?? 'application/octet-stream',
                    "" === $fieldName ? UPLOAD_ERR_NO_FILE : UPLOAD_ERR_OK
                ));
            }
        }
    }

    private function createUploadedFile(string $tempFilename, string $clientFilename, string $clientMediaType = null, int $error = null)
    {
        return new UploadedFile($tempFilename, $clientFilename, $clientMediaType, $error, true);
    }
}
