<?php

use CodeLibrary\Configuration;

require_once DIR_SYSTEM . 'library/code/vendor/autoload.php';

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ControllerCodeCodeApi
 */
class ControllerCodeCodeApi extends Controller
{
    private $codeLib;

    /**
     * ControllerCodeCodeApi constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->codeLib = new Configuration($registry);
    }

    public function updateToken()
    {
        $post = \CodeLibrary\Request::post();

        if (empty($post) || empty($post->token)) {
            return \CodeLibrary\Response::error('1');
        }

        $token = ORM::forTable(DB_PREFIX . 'setting')->where('key', 'config_code_api_token')->find_one();

        if ($token) {
            $token->value = $post->token;
            $token->save();
        } else {
            ORM::for_table(DB_PREFIX . 'setting')->create([
                'store_id'   => $this->config->get('config_store_id'),
                'value'      => $post->token,
                'code'       => 'codemarket',
                'key'        => 'config_code_api_token',
                'serialized' => '0',
            ])->save();
        }

        return \CodeLibrary\Response::success();
    }

    public function getStoreData()
    {
        \CodeLibrary\Request::get();

        return \CodeLibrary\Response::success($this->codeLib->getDataStore(), true);
    }

    public function updateCache()
    {
        $post = \CodeLibrary\Request::post();

        if (empty($post->config)) {
            return \CodeLibrary\Response::error(1);
        }

        $store_id = (int) $this->config->get('config_store_id');

        foreach ($post->config as $config) {
            if (empty($config->config) || empty($config->id_produto)) {
                continue;
            }

            $setting = ORM::forTable(DB_PREFIX . 'setting')->where([
                'key'  => $config->id_produto,
                'code' => 'code_api_cache',
            ])->find_one();

            if (!empty($setting->key)) {
                $setting->value = $config->config;
                $setting->save();
            } else {
                ORM::for_table(DB_PREFIX . 'setting')->create([
                    'store_id'   => $store_id,
                    'value'      => $config->config,
                    'code'       => 'code_api_cache',
                    'key'        => $config->id_produto,
                    'serialized' => '0',
                ])->save();
            }
        }

        $this->load->model('module/code_activemodule');
        $this->model_module_code_activemodule->index();

        return \CodeLibrary\Response::success();
    }

    public function clearCache()
    {
        $post = \CodeLibrary\Request::post();

        $setting = ORM::forTable(DB_PREFIX . 'setting')->where([
            'key'  => $post->id_produto,
            'code' => 'code_api_cache',
        ])->find_one();

        if ($setting) {
            $setting->value = '';
            $setting->save();
        }

        $this->load->model('module/code_activemodule');
        $this->model_module_code_activemodule->index();

        return \CodeLibrary\Response::success();
    }

    public function getVersionApp()
    {
        \CodeLibrary\Request::get();
        $this->load->model('module/code_activemodule');
        $this->model_module_code_activemodule->versionApp();

        return \CodeLibrary\Response::success($this->model_module_code_activemodule->versionApp(), true);
    }

    public function getInfo()
    {
        \CodeLibrary\Request::get();

        return \CodeLibrary\Response::success($this->codeLib->getServerInfo(), true);
    }

    public function test()
    {
        \CodeLibrary\Request::post();

        return \CodeLibrary\Response::success([
            'msg'  => 'Panel Valide',
            'info' => $this->codeLib->getServerInfo(),
        ], true);
    }

    public function autoUpdate()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'options') {
            return \CodeLibrary\Response::success();
        }

        $post = \CodeLibrary\Request::post();

        if (!defined('DIR_UPLOAD') || empty(DIR_UPLOAD) || !defined('DIR_APPLICATION') || empty(DIR_APPLICATION)) {
            return \CodeLibrary\Response::error('config');
        }

        $filename = time() . '.zip';

        /* PHP current path */
        $path = DIR_UPLOAD . 'code_update/';  // absolute path to the directory where zipper.php is in
        @mkdir($path, 0755);

        file_put_contents($path . $filename, fopen($post->url, 'rb'));

        $targetdir = str_replace('catalog/', '', DIR_APPLICATION); // target directory
        $targetzip = $path . $filename; // target zip file

        $zip = new ZipArchive();
        $x = $zip->open($targetzip);  // open the zip file to extract
        if ($x === true) {
            $zip->extractTo($targetdir); // place in the directory with same name
            $zip->close();

            unlink($targetzip);
        }

        return \CodeLibrary\Response::success([
            'message' => 'Your .zip file was uploaded and unpacked.',
        ]);
    }
}
