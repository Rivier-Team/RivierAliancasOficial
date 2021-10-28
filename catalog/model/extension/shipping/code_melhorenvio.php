<?php

require_once DIR_SYSTEM . 'library/code/code_menvio/vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * Class ModelExtensionShippingCodeMelhorenvio
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 *
 * @property \Cart\Cart cart
 * @property \Session session
 * @property \Loader load
 * @property \ModelModuleCodemarketModule model_module_codemarket_module
 * @property \Cart\Currency currency
 * @property \DB\MySQLi db
 * @property \Cart\Weight weight
 * @property \Cart\Length length
 *
 */
class ModelExtensionShippingCodeMelhorEnvio extends Model
{
    private $conf;
    private $log;
    private $url;
    private $token;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('Code-MelhorEnvio-' . date('m-Y') . '.log');

        try {
            $this->load->model('module/codemarket_module');
        } catch (\Exception $e) {
            die('Model não instalado');
        }

        $this->conf = $this->model_module_codemarket_module->getModulo('524');

        $this->url = 'https://www.melhorenvio.com.br';
        if ((int) $this->conf->env === 0) {
            $this->url = 'https://sandbox.melhorenvio.com.br';
            $this->token = $this->conf->apiTokenSandbox;
        }

        $this->token = $this->conf->apiToken;
    }

    /**
     * @param $address
     *
     * @return array
     * @throws \Exception
     */
    public function getQuote($address)
    {
        $this->log->write("CotarFrete - Passo 1 Dentro da cotação");
        if (empty($this->conf->status)) {
            $this->log->write("CotarFrete - Passo 2 módulo desabilitado");
            //$this->log->write("CotarFrete - Passo 2 Configuração".print_r($this->conf, true));
            return [];
        }

        $this->log->write("CotarFrete - Passo 2 módulo habilitado");

        if (empty($this->conf->geo_zone_id)) {
            $status = true;
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)
                $this->conf->geo_zone_id . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" .
                (int) $address['zone_id'] . "' OR zone_id = '0')
            ");

            if ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        }

        if (empty($status)) {
            $this->log->write("CotarFrete - Passo 3 desabilitado pela região, use na Configuraçào Todas as regiões de preferência");
            return [];
        }

        $products = $this->cart->getProducts();

        foreach ($products as $p => $product) {
            if (!$product['shipping']) {
                unset($products[$p]);
            }
        }
        $services = [];
        foreach ($this->conf->servicos as $svc) {
            if (!empty($svc) && $svc->status === true) {
                $services [] = $svc->id;
            }
        }

        $data = [];
        foreach ($this->conf->servicos as $servico) {
            if (empty($servico) || $servico->status == 0) {
                continue;
            }

            $data[$servico->id] = [
                'to'       => [
                    'postal_code' => preg_replace('/\D/', '', $address['postcode']),
                    'address'     => $address['address_1'],
                    'number'      => preg_replace('/\D/', '', $address['address_1']),
                ],
                'from'     => [
                    'postal_code' => preg_replace('/\D/', '', $this->conf->origem),
                    'address'     => $this->conf->address,
                    'number'      => $this->conf->number,
                ],
                'products' => array_map(function ($product) use ($servico) {
                    $data = [
                        'id'            => $product['product_id'],
                        'weight'        => (float) $this->weight->convert(
                            $product['weight'] / (int) $product['quantity'],
                            $product['weight_class_id'],
                            $this->conf->weight_class_id
                        ),
                        'length'        => (float) $this->length->convert(
                            $product['length'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'width'         => (float) $this->length->convert(
                            $product['width'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'height'        => (float) $this->length->convert(
                            $product['height'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'quantity'      => (int) $product['quantity'],
                        'unitary_value' => (float) $product['price'],
                    ];

                    //DECLARAR VALOR
                    if ($this->hasOption($servico, 'vd')) {
                        if (!empty($this->conf->declarar_tabela) && !empty($product['product_id'])) {
                            $declararGet = $this->db->query("SELECT * FROM " . $this->conf->declarar_tabela . " 
                                WHERE 
                                product_id = '" . (int) $product['product_id'] . "' 
                                LIMIT 1
                            ");
                        }

                        if (!empty($this->conf->declarar_campo) && !empty($declararGet->row[$this->conf->declarar_campo])) {
                            $custo = (float) $declararGet->row[$this->conf->declarar_campo];
                        } else {
                            $custo = (float) $product['price'];
                        }

                        //Caso o preço no carrinho for menor que o custo, usar ele como custo
                        if ($product['price'] < $custo) {
                            $custo = (float) $product['price'];
                        }

                        $data['insurance_value'] = $custo;
                    }

                    return $data;
                }, $products),
                'options'  => [
                    "receipt"  => $this->hasOption($servico, 'ar'),
                    "own_hand" => $this->hasOption($servico, 'mp'),
                    "collect"  => $this->hasOption($servico, 'cl'),
                ],
                'services' => (string) $servico->id,
            ];
        }

        try {
            if (!empty($this->conf->code_post) && $this->conf->code_post == 1) {
                $post = $this->post($data);
            } else {
                $post = $this->post_curl($data);
            }
        } catch (\Exception $e) {
            $this->log->write("CotarFrete - Erro na cotação" . print_r($e, true));
            return [];
        }

        if (empty($post)) {
            $this->log->write("CotarFrete - Sem retorno, verificar dimensões, peso, se o produto está habilitada ou refazer o Token do Melhor Envio" . print_r($post, true));
            $this->log->write("CotarFrete - Pode ir na Configuração da Melhoria -> Melhor Envio e Ativar o Modo Debug para ter mais detalhes no Log");
            return [];
        }

        if (empty($post)) {
            $quote_data = [];
        } else {
            foreach ($post as $quote) {
                if (empty($quote) || !empty($quote->error)) {
                    continue;
                }

                $servico = $this->conf->servicos[$quote->id];
                $title = $servico->title;

                if ($this->conf->servicos[$quote->id]->extraDays > 0) {
                    $deliver = $quote->delivery_time . ' a ' . ($quote->delivery_time + $this->conf->servicos[$quote->id]->extraDays);
                } else {
                    $deliver = $quote->delivery_time;
                }

                if (!empty($this->conf->deliver_message)) {
                    $title = str_replace(['{servico}', '{prazo}'], [$title, $deliver], $this->conf->deliver_message);
                } else {
                    $title = str_replace(['{servico}', '{prazo}'], [$title, $deliver], '{servico} - (Prazo estimado {prazo} dias úteis)');
                }

                if (isset($this->conf->servicos[$quote->id]->extraTax)) {
                    $tax = trim($this->conf->servicos[$quote->id]->extraTax);
                    $tax = explode("%", $tax);

                    if (isset($tax[1])) {
                        $tax = (float) $tax[0];
                        $percentage = (100 + $tax) / 100;
                        $price = (float) $quote->price * $percentage;
                    } else {
                        $tax = (float) $tax[0];
                        $price = (float) $quote->price + $tax;
                    }
                } else {
                    $price = (float) $quote->price;
                }

                $price = round($price, 2);

                if ($price <= 0) {
                    $price = 0;
                }

                $text = $this->currency->format((float) $price, $this->session->data['currency']);

                //VERIFICAR FRETE GRÁTIS
                if (!empty($servico->free)) {
                    $subtotal = $this->cart->getSubTotal();
                    $quantity = $this->cart->countProducts();

                    $free_qtd = !empty($servico->free_qtd) ? (int) $servico->free_qtd : 0;
                    $free_min = !empty($servico->free_min) ? (float) $servico->free_min : 0;
                    $free_max = !empty($servico->free_max) ? (float) $servico->free_max : 0;
                    $free_zone = !empty($servico->free_zone) ? (int) $servico->free_zone : 0;

                    if ($quantity >= $free_qtd && $subtotal >= $free_min && $subtotal <= $free_max
                        && (empty($free_zone) || $free_zone == $address['zone_id'])
                    ) {
                        $price = 0;
                        $text = 'Grátis';
                    }
                }

                $quote_data[$quote->id] = [
                    'code'           => 'code_melhorenvio.' . $quote->id,
                    'melhorenvio_id' => $quote->id,
                    'title'          => $title,
                    'cost'           => $price,
                    'tax_class_id'   => 0,
                    'text'           => $text,
                ];
            }
        }

        if (empty($this->conf->title)) {
            $title = 'Transportadoras';
        } else {
            $title = $this->conf->title;
        }

        if (empty($this->conf->sort_order)) {
            $order = 1;
        } else {
            $order = $this->conf->sort_order;
        }

        $method_data = [];
        if (!empty($quote_data)) {
            //Ordenando o array
            $quote_data_sort = $quote_data;
            $quote_data = [];
            $columns = array_column($quote_data_sort, 'cost');
            array_multisort($columns, SORT_ASC, $quote_data_sort);

            $i = 1;
            foreach ($quote_data_sort as $qd) {
                $qd['code'] = 'code_melhorenvio.' . $i;
                $quote_data[$i] = $qd;
                $i++;
            }

            $method_data = [
                'code'       => 'code_melhorenvio',
                'title'      => $title,
                'quote'      => $quote_data,
                'sort_order' => $order,
                'error'      => false,
            ];

            if (isset($this->session->data['melhor_envio'])) {
                unset($this->session->data['melhor_envio']);
            }

            $this->session->data['melhor_envio']['post'] = $post;

            $this->log->write("CotarFrete - Passo Final Cotação realizada com sucesso");
            /*
            $this->session->data['melhor_envio']['quoted_data'] = $quote_data;
            $this->log->write('CotarFrete - Debug POST: '.print_r($this->session->data['melhor_envio']['post'], true));
            $this->log->write('CotarFrete - Debug Quoted Data: '.print_r($this->session->data['melhor_envio']['quoted_data'], true));
            */
        }

        return $method_data;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function post_curl($data)
    {
        $url = $this->url . '/api/v2/me/shipment/calculate';
        //$this->log->write('post() Data:' . print_r($data, true));

        // Debug
        $debug = !empty($this->conf->code_debug) && $this->conf->code_debug == 1 ? 1 : 0;

        if ($debug) {
            $logDebug = new Log('Code-MelhorEnvio-Debug-' . date('m-Y') . '.log');
            $logDebug->write('post() - Dentro do modo Debug');
        }

        $results = [];

        foreach ($data as $id => $dataService) {
            if ($debug) {
                $logDebug->write('post() - Testando serviço ID: ' . $id);
                $logDebug->write('post() - Dados Serviço ID ' . $id . ': ' . print_r($dataService, true));
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => 17,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_POSTFIELDS     => json_encode($dataService),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => [
                    "Cache-Control: no-cache",
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $this->token,
                ],
            ]);

            //print_r(json_encode($data)); exit();
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                if ($debug) {
                    $logDebug->write('post() URL: ' . $url . ' - Error Curl Serviço ID ' . $id . ': ' . print_r($err, true) . ' e resposta: ' . print_r($response, true));
                    $logDebug->write('post() URL: ' . $url . ' - Token usado Serviço ID ' . $id . ': ' . print_r($this->token, true));
                }
            } else {
                $quote = json_decode($response, true);

                if (!empty($quote[0])) {
                    $quote = $quote[0];
                }

                if (!empty($quote) && empty($quote['error']) && !empty($quote['id']) && !empty($quote['delivery_time']) && !empty($quote['price'])) {
                    $results[$id] = json_decode(json_encode($quote));
                }

                if ($debug) {
                    $logDebug->write('post() URL: ' . $url . ' - Dados retornados Serviço ID ' . $id . ': ' . print_r(json_decode($response, true), true));
                }
            }

            // 0,1s = 10 = 1s
            usleep(100000);
        }

        return $results;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function post($data)
    {
        $url = $this->url . '/api/v2/me/shipment/calculate';

        // Debug
        //$this->log->write('post() Data:' . print_r($data, true));
        $debug = !empty($this->conf->code_debug) && $this->conf->code_debug == 1 ? 1 : 0;

        if ($debug) {
            $logDebug = new Log('Code-MelhorEnvio-Debug-' . date('m-Y') . '.log');
            $logDebug->write('post() - Dentro do modo Debug');

            foreach ($data as $id => $dataService) {
                $logDebug->write('post() - Testando serviço ID: ' . $id);
                $logDebug->write('post() - Dados Serviço ID ' . $id . ': ' . print_r($dataService, true));

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_TIMEOUT        => 17,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => "POST",
                    CURLOPT_POSTFIELDS     => json_encode($dataService),
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_HTTPHEADER     => [
                        "Cache-Control: no-cache",
                        "Accept: application/json",
                        "Content-Type: application/json",
                        "Authorization: Bearer " . $this->token,
                    ],
                ]);

                //print_r(json_encode($data)); exit();
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    $logDebug->write('post() URL: ' . $url . ' - Error Curl Serviço ID ' . $id . ': ' . print_r($err, true) . ' e resposta: ' . print_r($response, true));
                    $logDebug->write('post() URL: ' . $url . ' - Token usado Serviço ID ' . $id . ': ' . print_r($this->token, true));
                } else {
                    $logDebug->write('post() URL: ' . $url . ' - Dados retornados Serviço ID ' . $id . ': ' . print_r(json_decode($response, true), true));
                }
            }
        }

        $requests = [];
        foreach ($data as $id => $dataService) {
            $requests[$id] = new \GuzzleHttp\Psr7\Request('POST', $url, [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'authorization' => 'Bearer ' . $this->token,
            ], json_encode($dataService));
        }

        //print_r($requests);
        $client = new Client();

        $results = [];

        $pool = new \GuzzleHttp\Pool($client, $requests, [
            'concurrency' => count($requests),
            'fulfilled'   => function (\GuzzleHttp\Psr7\Response $response, $serviceId) use (&$results, $data) {
                $content = $response->getBody()->getContents();
                $quote = json_decode($content, true);

                if (!empty($quote[0])) {
                    $quote = $quote[0];
                }

                if (!empty($quote) && empty($quote['error']) && !empty($quote['id']) && !empty($quote['delivery_time']) && !empty($quote['price'])) {
                    $results[$serviceId] = json_decode(json_encode($quote));
                }
            },
        ]);
        // run queue
        $pool->promise()->wait();

        return $results;
    }

    /**
     *
     * @param $servico
     * @param $svc
     *
     * @return bool
     */
    private function hasOption($servico, $svc)
    {
        if (!empty($servico) && !empty($servico->status) && !empty($servico->{$svc})) {
            return true;
        }

        return false;
    }
}
