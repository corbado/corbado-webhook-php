<?php

namespace Corbado\Webhook\Classes;

use Corbado\Webhook\Exceptions\Standard;

class Helper
{
    /**
     * JSON encode
     *
     * @param mixed $data
     * @return string
     * @throws Standard
     */
    public static function jsonEncode($data): string
    {
        $json = \json_encode($data);
        if ($json === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new Standard('json_encode() failed: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * JSON decode
     *
     * @param string $data
     * @return array<mixed>
     * @throws Standard
     * @throws \Corbado\Webhook\Exceptions\Assert
     */
    public static function jsonDecode(string $data): array
    {
        Assert::stringNotEmpty($data);

        $json = \json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Standard('json_decode() failed: ' . json_last_error_msg());
        }

        return $json;
    }
}
