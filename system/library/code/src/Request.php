<?php

namespace CodeLibrary;

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class Request
 *
 * @package CodeLibrary
 */
class Request
{
    /**
     * Confirma a validade da origem dos requests
     *
     * @param $data
     */
    public static function authorize($data)
    {
        $headers = self::getallheaders();

        $config = \ORM::forTable(DB_PREFIX . 'setting')->where([
            'key' => 'config_code_key_api',
        ])->findOne();

        if (empty($config->value) || empty($headers['X-Codeapp-Signature'])) {
            Response::forbidden('2');
            exit();
        }

        $signature = hash_hmac('sha256', $data, $config->value);

        if ($signature !== $headers['X-Codeapp-Signature']) {
            Response::forbidden('3');
            exit();
        }
    }

    /**
     * @param null $field
     *
     * @return array|mixed
     */
    public static function get($field = null)
    {
        self::authorize('');

        if (strtolower($_SERVER['REQUEST_METHOD']) !== 'get') {
            Response::forbidden('1');
            exit();
        }

        if (is_string($field) && !empty($_GET[$field])) {
            return preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET[$field]);
        }

        if ($field === null) {
            $fields = [];
            foreach ($_GET as $index => $value) {
                $fields[$index] = preg_replace('/[^-a-zA-Z0-9_]/', '', $value);
            }

            unset($fields['route']);

            return $fields;
        }

        return [];
    }

    /**
     * @param null $field
     *
     * @return mixed
     */
    public static function post($field = null)
    {
        $data = file_get_contents('php://input');

        self::authorize($data);

        $fields = json_decode($data);

        if (is_string($field)) {
            return $fields->{$field};
        }

        return $fields;
    }

    /**
     * @return array
     */
    private static function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}
