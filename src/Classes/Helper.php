<?php

namespace Corbado\Webhook\Classes;

use Corbado\Webhook\Exceptions\Standard;

class Helper
{
    /**
     * JSON encode
     *
     * @param $data
     * @return string
     * @throws Standard
     */
    public static function jsonEncode($data): string
    {
        $json = \json_encode($data);
        if ($json === false) {
            throw new Standard('json_encode() failed: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * JSON decode
     *
     * @param string $data
     * @return array
     * @throws Standard
     * @throws \Corbado\Webhook\Exceptions\Assert
     */
    public static function jsonDecode(string $data): array
    {
        Assert::stringNotEmpty($data);

        $json = \json_decode($data, true);
        if ($json === false) {
            throw new Standard('json_decode() failed: ' . json_last_error_msg());
        }

        return $json;
    }
}
