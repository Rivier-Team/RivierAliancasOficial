<?php

namespace CodeLibrary;

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class Response
 *
 * @package CodeLibrary
 */
class Response
{
    /**
     * Sucesso, opcional mensagens extra
     *
     * @param null $output
     *
     * @return bool
     */
    public static function success($output = null, $base64 = false)
    {
        if ($output === null) {
            $output = [];
        }

        if (is_object($output)) {
            $output = (array) $output;
        }

        http_response_code(200);

        if ($base64 === false) {
            return self::jsonBody(array_merge($output, [
                'success' => true,
            ]));
        }

        return self::textBody($output);
    }

    /**
     * Retorno de erros em json
     * Obrigatório conter o $errors
     *
     * @param $errors
     *
     * @return bool
     */
    public static function error($errors)
    {
        if (empty($errors)) {
            throw new \RuntimeException('Errors devem ser informados');
        }

        http_response_code(200);

        return self::jsonBody([
                'success' => false,
                'errors'  => $errors,
            ]
        );
    }

    /**
     * Output primario de json, não tem http code definido, retorna json
     *
     * @param null $output
     *
     * @return string
     */
    public static function jsonBody($output = null)
    {
        if ($output === null) {
            $output = [];
        }

        header('Content-Type: application/json');
        if (ob_get_level() > 0) {
            ob_flush();
        }

        echo json_encode($output);
        exit();
    }

    /**
     * Output primario de json, não tem http code definido, retorna text com base64 e um json dentro
     *
     * @param null $output
     *
     * @return string
     */
    public static function textBody($output = null)
    {
        if ($output === null) {
            $output = [];
        }

        header('Content-Type: text/plain');
        if (ob_get_level() > 0) {
            ob_flush();
        }

        echo base64_encode(json_encode($output));
        exit();
    }

    /**
     *
     * @param $errors
     *
     * @return bool
     */
    public static function forbidden($errors)
    {
        if (empty($errors)) {
            throw new \RuntimeException('Errors devem ser informados');
        }

        http_response_code(403);

        return self::jsonBody([
                'success' => false,
                'errors'  => $errors,
            ]
        );
    }
}
