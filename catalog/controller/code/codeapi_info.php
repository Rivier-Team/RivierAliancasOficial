<?php

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ControllerCodeCodeApiInfo
 */
class ControllerCodeCodeApiInfo extends Controller
{
    public function index()
    {
        $opencartVersion = VERSION;
        $phpVersion = PHP_VERSION;
        $curlVersion = curl_version();

        header('Content-Type: application/json');

        if (ob_get_level() > 0) {
            ob_flush();
        }

        echo json_encode(
            [
                'opencartVersion' => $opencartVersion,
                'phpVersion'      => $phpVersion,
                'curlVersion'     => $curlVersion['version'],
                'pdo'             => !empty(class_exists('PDO')) ? true : false,
            ]
        );

        exit();
    }

    public function verifyPDO()
    {
        if (class_exists('PDO') != 1) {
            echo "Sem extensão PDO no PHP! - Instale a extensão PDO e php_mysqli pelo Cpanel ou verifique com sua hospedagem";
        } else {
            echo "Extensão PDO instalada";
        }
    }
}
