<?php

use CodeLibrary\Configuration;

require_once DIR_SYSTEM . 'library/code/vendor/autoload.php';

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ModelModuleCodemarketModule
 */
class ModelModuleCodemarketModule extends Model
{
    /**
     * @var \CodeLibrary\Configuration
     */
    private $codeLib;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->codeLib = new Configuration($registry);
    }

    public function getModulo($id)
    {
        if (empty($id)) {
            return false;
        }

        if (defined('TOKEN_DEV') && !empty(TOKEN_DEV)) {
            $setting = ORM::forTable(DB_PREFIX . 'setting')->where('key', 'config_code_config_cache')->find_one();

            if (empty($setting->value)) {
                $config = self::updateCache($id);

                return $config;
            }
        }
        $config = $this->codeLib->getConfig($id);

        return $config;
    }

    //---------------------------- AUXILIARES ----------------------------
    public function addExtensionStore($type, $code)
    {
        if (empty($type) || empty($code)) {
            return false;
        }

        return $this->codeLib->addExtensionStore($type, $code);
    }

    public function editSettingStore($type, $code, $key, $value, $store_id = 0)
    {
        if (empty($type) || empty($code) || empty($key) || !isset($value)) {
            return false;
        }

        return $this->codeLib->editSettingStore($type, $code, $key, $value, $store_id = 0);
    }

    //---------------------------- DESENVOLVEDOR ----------------------------
    public function updateCache($id_produto)
    {
        $url = 'https://api.codemarket.com.br/app/module/get/config';

        $data = ['id_produto' => $id_produto];
        $getConfig = self::post($url, $data);

        $getConfig = json_decode($getConfig);

        if (empty($getConfig->config) || empty($getConfig->id_produto)) {
            return null;
        }

        $setting = ORM::forTable(DB_PREFIX . 'setting')->where([
            'key'  => $getConfig->id_produto,
            'code' => 'code_api_cache',
        ])->find_one();

        if (!empty($setting->key)) {
            $setting->value = $getConfig->config;
            $setting->save();
        } else {
            ORM::for_table(DB_PREFIX . 'setting')->create([
                'store_id'   => 0,
                'value'      => $getConfig->config,
                'code'       => 'code_api_cache',
                'key'        => $getConfig->id_produto,
                'serialized' => '0',
            ])->save();
        }

        return json_decode(base64_decode($getConfig->config));
    }

    /**
     * Usar &code_send_data=false se quiser que pare de enviar os dados da Loja para o domínio de desenvolvimento
     * ou &code_config_cache=true , caso queira usar o modo cache (vindo do banco de dados) os dados da config dos
     * módulos, só usar se já carregou eles
     *
     * @throws \Exception
     */
    public function sendData()
    {
        if (isset($this->request->get['code_config_cache'])) {
            $setting = ORM::forTable(DB_PREFIX . 'setting')->where('key', 'config_code_config_cache')->find_one();

            if ($setting) {
                $setting->value = $this->request->get['code_config_cache'];
                $setting->save();
            } else {
                ORM::for_table(DB_PREFIX . 'setting')->create([
                    'store_id'   => 0,
                    'value'      => $this->request->get['code_config_cache'],
                    'code'       => 'config',
                    'key'        => 'config_code_config_cache',
                    'serialized' => '0',
                ])->save();
            }
        }

        if (isset($this->request->get['code_send_data']) and $this->request->get['code_send_data'] == 'false') {
            return;
        }

        //Admin Custom Fields
        $dataStore = $this->codeLib->getDataStore();
        $data['data'] = $dataStore;

        $url = 'https://api.codemarket.com.br/app/module/data/store';
        $send = json_decode(self::post($url, $data));

        if (!empty($send->error)) {
            return $send->error;
        }

        return $send->message;
    }

    private function post($url, $data)
    {
        $token = defined('TOKEN_DEV') ? TOKEN_DEV : null;

        if (!$token) {
            die('sem token');
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 1,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                "Authorization: " . $token,
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            print_r($err);
            exit();
        } else {
            return $response;
        }
    }
}
