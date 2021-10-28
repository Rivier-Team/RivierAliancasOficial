<?php

namespace Code\MelhorEnvio;

use DB\MySQLi;
use GuzzleHttp\Client;
use function _\findIndex;

/**
 * Class MelhorEnvio
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 *
 * @package Code\MelhorEnvio
 *
 *
 */
class MelhorEnvio
{
    /**
     * @var \Loader
     */
    private $load;
    /**
     * @var \Registry
     */
    private $registry;
    /**
     * @var MySQLi
     */
    private $db;
    /**
     * @var \ModelModuleCodemarketModule
     */
    private $codeModel;
    /**
     * @var object
     */
    public $conf;
    /**
     * @var \Config
     */
    private $config;
    /**
     * @var \Cart\Weight
     */
    private $weight;
    /**
     * @var \Cart\Length
     */
    private $length;
    /**
     * @var object
     */
    private $quote;
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * MelhorEnvio constructor.
     *
     * @param \Registry $registry
     *
     * @throws \Exception
     */
    public function __construct(\Registry $registry)
    {
        $this->registry = $registry;
        $this->load = $registry->get('load');
        $this->db = $registry->get('db');

        $this->load->model('module/codemarket_module');
        $codeModel = $registry->get('model_module_codemarket_module');
        $this->conf = $codeModel->getModulo('524');

        $this->conf->baseUri = 'https://melhorenvio.com.br';
        if ((int) $this->conf->env === 0) {
            $this->conf->apiToken = $this->conf->apiTokenSandbox;
            $this->conf->baseUri = 'https://sandbox.melhorenvio.com.br';
        }

        $this->client = new Client([
            'base_uri' => $this->conf->baseUri,
            'headers'  => [
                'accept:'       => 'application/json',
                'content-type'  => 'application/json',
                'authorization' => 'Bearer ' . $this->conf->apiToken,
            ],
        ]);
    }

    /**
     * @param $orderId
     *
     * @param bool $assoc
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function getQuote($orderId, $assoc = false)
    {
        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio WHERE order_id = ' . (int) $orderId);

        if ($query->row) {
            $quote = json_decode($query->row['data'], $assoc);
            $this->quote = json_decode($query->row['data']);

            return $quote;
        }

        return null;
    }

    public function getPost($orderId, $package = [])
    {
        $this->load->model('checkout/order');
        $this->load->model('catalog/product');

        if (empty($this->quote)) {
            $this->getQuote($orderId);
        }

        /**
         * @var \ModelSaleOrder $modelOrder
         */
        $modelOrder = $this->registry->get('model_checkout_order');

        $order = $modelOrder->getOrder($orderId);

        $total = 0;

        $post = [
            'service' => $this->quote->id,
            'from'    => [
                'name'             => !empty($this->conf->name) ? $this->conf->name : '',
                'phone'            => !empty($this->conf->phone) ? $this->conf->phone : '',
                'email'            => !empty($this->conf->email) ? $this->conf->email : '',
                'company_document' => !empty($this->conf->document) ? $this->conf->document : '',
                'state_register'   => !empty($this->conf->state_register) ? $this->conf->state_register : '',
                'address'          => !empty($this->conf->address) ? $this->conf->address : '',
                'complement'       => !empty($this->conf->complement) ? $this->conf->complement : '',
                'number'           => !empty($this->conf->number) ? $this->conf->number : '',
                'district'         => !empty($this->conf->district) ? $this->conf->district : '',
                'city'             => !empty($this->conf->city) ? $this->conf->city : '',
                'state_abbr'       => !empty($this->conf->state) ? $this->conf->state : '',
                'country_id'       => 'BR',
                'postal_code'      => $this->conf->origem,
            ],
            'to'      => [
                'notes'       => $orderId,
                'name'        => implode(' ', [$order['shipping_firstname'], $order['shipping_lastname']]),
                'phone'       => preg_replace('/\D/', '', $order['telephone']),
                'email'       => $order['email'],
                'address'     => $order['shipping_address_1'],
                'complement'  => !empty($order['shipping_custom_field'][$this->conf->customer_complement]) ? $order['shipping_custom_field'][$this->conf->customer_complement] : '',
                'number'      => !empty($order['shipping_custom_field'][$this->conf->customer_number]) ? preg_replace('/\D/',
                    '',
                    $order['shipping_custom_field'][$this->conf->customer_number]) : '',
                'district'    => $order['shipping_address_2'],
                'city'        => $order['shipping_city'],
                'state_abbr'  => $order['shipping_zone_code'],
                'country_id'  => 'BR',
                'postal_code' => preg_replace('/\D/', '', $order['shipping_postcode']),
            ],

            'options' => [
                'receipt'        => !empty($this->quote->additional_services->receipt),
                'own_hand'       => !empty($this->quote->additional_services->own_hand),
                'collect'        => !empty($this->quote->additional_services->collect),
                'non_commercial' => false,
                'platform'       => 'OpenCart Codemarket',
            ],
        ];

        if (!empty($package)) {
            $post['package'] = [
                'weight' => (float) $package->weight,
                'width'  => (float) $package->width,
                'length' => (float) $package->length,
                'height' => (float) $package->height,
            ];
        }

        if (!empty($package->products)) {
            $post['products'] = array_map(function ($product) use (&$total) {
                $total += (float) $product->price;

                return [
                    'name'          => $product->name,
                    'quantity'      => 1,
                    'unitary_value' => (float) $product->price,
                ];
            }, $package->products);
        }

        if (!empty($package->nota->key)) {
            $post['options']['invoice'] = [
                'key' => $package->nota->key,
            ];
        }

        //Caso o campo CPF e CNPJ sejam os mesmos campos
        if (!empty($this->conf->customer_cnpj) && !empty($this->conf->customer_cpf) &&
            $this->conf->customer_cnpj == $this->conf->customer_cpf && !empty($order['custom_field'][$this->conf->customer_cnpj])) {
            $doc = $order['custom_field'][$this->conf->customer_cnpj];
            $doc = preg_replace("/[^0-9]/", '', $doc);

            if (strlen($doc) == 14) {
                $post['to']['company_document'] = $doc;
            } else {
                $post['to']['document'] = $doc;
            }
            //Caso seja CNPJ
        } else if (!empty($this->conf->customer_cnpj) && !empty($order['custom_field'][$this->conf->customer_cnpj])) {
            $doc = $order['custom_field'][$this->conf->customer_cnpj];
            $doc = preg_replace("/[^0-9]/", '', $doc);
            $post['to']['company_document'] = $doc;
            //Caso seja CPF
        } else if (!empty($this->conf->customer_cpf) && !empty($order['custom_field'][$this->conf->customer_cpf])) {
            $doc = $order['custom_field'][$this->conf->customer_cpf];
            $doc = preg_replace("/[^0-9]/", '', $doc);
            $post['to']['document'] = $doc;
        } else {
            $post['to']['document'] = '';
        }

        if (empty($post['options']['invoice']['key'])) {
            $post['options']['non_commercial'] = true;
        }

        if (!empty($this->conf->agencies[$this->quote->company->id])) {
            $post['agency'] = $this->conf->agencies[$this->quote->company->id]->id;
        }

        if (!empty($package->declarar)) {
            $total = (float) $package->declarar;
        }

        $post['options']['insurance_value'] = (float) (number_format($total, 2, '.', ''));

        return $post;
    }

    /**
     * Envia automaticamente o pedido ao carrinho
     *
     * @param $orderId
     *
     * @return mixed
     * @throws \Exception
     */
    public function autoPost($orderId)
    {
        $quote = $this->getQuote($orderId);

        $package = new \stdClass();
        $package->weight = $quote->packages[0]->weight;
        $package->width = $quote->packages[0]->dimensions->width;
        $package->length = $quote->packages[0]->dimensions->length;
        $package->height = $quote->packages[0]->dimensions->height;

        $post = $this->getPost($orderId, $package);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->conf->baseUri . '/api/v2/me/cart',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS      => 1,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode($post),
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'accept: application/json',
                'authorization: Bearer ' . $this->conf->apiToken,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        echo $response;
    }

    public function comparEtiqueta($order, $package)
    {
        $post = $this->getPost($order, $package);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->conf->baseUri . '/api/v2/me/cart',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS      => 1,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode($post),
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'accept: application/json',
                'authorization: Bearer ' . $this->conf->apiToken,
            ],
        ]);
        $response = curl_exec($curl);
        //print_r($response); exit();
        //$err = curl_error($curl);
        curl_close($curl);

        return json_decode($response);
    }

    public function checkCart($package)
    {
        if (empty($package->cart->id)) {
            return false;
        }

        $response = $this->client->get('/api/v2/me/cart');

        $cart = json_decode($response->getBody()->getContents());

        if (empty($cart->data)) {
            return false;
        }

        $index = -1;
        foreach ($cart->data as $i => $cart) {
            if ($cart->id == $package->cart->id) {
                $index = $i;
                break;
            }
        }

        return $index >= 0;
    }

    /**
     * Rastreio Postagem
     *
     * @param $package
     *
     * @return false
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function trackPackage($package, $log, $debugUrl)
    {
        if (empty($package->cart->id)) {
            $log->write("trackPackage() - Falha no Rastreio sem o ID do Carrinho");
            return false;
        }

        $response = $this->client->post('/api/v2/me/shipment/tracking', [
            'form_params' => [
                'orders' => [$package->cart->id],
            ],
        ]);

        $response = json_decode($response->getBody()->getContents());

        if (!empty($response) && !empty($response->{$package->cart->id})) {
            // Usando o Melhor Rastreio para Consultar

            if ($debugUrl) {
                echo "<h3>trackPackage() - Retorno Melhor Envio Rastreio</h3>";
                print_r($response->{$package->cart->id});
            }

            // Atualizando o Status com base no Melhor Rastreio
            if (!empty($response->{$package->cart->id}->tracking)) {
                try {
                    $response2 = $this->client->get('https://api.melhorrastreio.com.br/api/v1/trackings/' . $response->{$package->cart->id}->tracking);
                    $response2 = json_decode($response2->getBody()->getContents());

                    if ($debugUrl) {
                        echo "<h3>trackPackage() - Retorno Melhor Rastreio Rastreio</h3>";
                        print_r($response2);
                    }

                    if (!empty($response2->data) && !empty($response2->data->status) && !empty($response2->data->tracking) &&
                        $response2->data->tracking == $response->{$package->cart->id}->tracking) {
                        $response->{$package->cart->id}->status = $response2->data->status;
                    }
                } catch (Exception $e) {
                    $log->write("trackPackage() - Erro na Rastreio do Frete!, erro: " . print_r($e, true));
                    //print_r($e);
                    //exit();
                    // Não faz nada
                }
            }

            $log->write("trackPackage() - Rastreio do Frete realizada com sucesso!");
            return $response->{$package->cart->id};
        }

        if ($debugUrl) {
            echo "<h3>trackPackage() - Falha na Rastreio, retorno: </h3>";
            print_r($response);
        }
        $log->write("trackPackage() - Falha na Rastreio do Frete!, retorno: " . print_r($response, true));
        return false;
    }

    public function removeFromCart($package)
    {
        if (empty($package->cart->id)) {
            return false;
        }

        $response = $this->client->delete('/api/v2/me/cart/' . $package->cart->id);

        return $response->getStatusCode() === 200;
    }

    public function removeCartFromPackage($order_id, $package)
    {
        if (empty($package->cart->id)) {
            return false;
        }

        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio WHERE order_id = ' . (int) $order_id);

        if (!$query->row) {
            return false;
        }

        $packages = json_decode($query->row['packages'], true);

        foreach ($packages as $i => $pkg) {
            if (empty($pkg->cart->id)) {
                unset($packages[$i]);
            }
        }

        $index = self::findIndex($packages, 'cart.id', $package->cart->id);

        if ($index >= 0) {
            unset($packages[$index]['cart']);

            $packages = json_encode($packages, JSON_PRETTY_PRINT);

            $this->db->query('UPDATE ' . DB_PREFIX . "code_melhorenvio SET packages = '" . $this->db->escape($packages) . "' WHERE order_id = " . $order_id);
        }

        return true;
    }

    /**
     * cart.product.options
     *
     * @param $haystack
     * @param $path
     * @param $value
     *
     * @return int|mixed
     */
    public static function findIndex($haystack, $path, $value)
    {
        $getValue = function ($haystack, $path) {
            $segments = explode('.', $path);

            foreach ($segments as $segment) {
                if (!is_array($haystack) || !isset($haystack[$segment])) {
                    return null;
                }

                $haystack = $haystack[$segment];
            }

            return $haystack;
        };

        foreach ($haystack as $i => $item) {
            if ($getValue($item, $path) == $value) {
                return $i;
            }
        }

        return -1;
    }
}
