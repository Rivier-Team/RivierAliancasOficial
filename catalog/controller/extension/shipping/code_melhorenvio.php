<?php

require_once DIR_SYSTEM . 'library/code/code_menvio/vendor/autoload.php';
require_once DIR_SYSTEM . 'library/code/vendor/autoload.php';

use Code\MelhorEnvio\MelhorEnvio;

/**
 * Class ControllerExtensionShippingCodeMelhorenvio
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 *
 * @property \DB\MySQLi db
 * @property \Request request
 * @property \Response response
 * @property \Loader load
 */
class ControllerExtensionShippingCodeMelhorenvio extends Controller
{
    private $conf;
    private $log;
    private $codeCache;

    public function __construct($registry)
    {
        parent::__construct($registry);

        if (empty($this->request->get['route'])) {
            die('route not found');
        }

        try {
            $this->load->model('module/codemarket_module');
        } catch (\Exception $e) {
            die('Model não instalado');
        }

        $this->conf = $this->model_module_codemarket_module->getModulo('524');
        $this->log = new log('Code-MelhorEnvio-Rastreio-' . date('m-Y') . '.log');

        if ($this->request->get['route'] == 'extension/shipping/code_melhorenvio/get_all') {
            \CodeLibrary\Request::get();
        } else if ($this->request->get['route'] == 'extension/shipping/code_melhorenvio/tracking') {
        } else if ($this->request->get['route'] != 'extension/shipping/code_melhorenvio/get_quotes' && !isset($this->session->data['user_id'])) {
            die('unauthorized');
        }

        // 3 dias para expirar o Cache
        $this->codeCache = new Cache('file', 259200);
    }

    public function get_quote()
    {
        header('Content-Type: application/json');

        $order_id = !empty($this->request->get['order_id']) ? $this->request->get['order_id'] : null;

        if (!$order_id) {
            return json_encode(['success' => false]);
        }

        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio WHERE order_id = ' . (int) $order_id);

        if ($query->row) {
            $data = $query->row;

            return array_merge($data['data'], ['etiqueta' => $query->row['etiqueta']]);
        }

        return json_encode(['success' => false]);
    }

    /**
     * Consulta os Status de Envio dos Pedidos mais atuais e chama os métodos para alterar os Status
     * Roda para cada Pacote ligado a um Pedido
     * URL: /index.php?route=extension/shipping/code_melhorenvio/tracking&token=&day=8
     *
     * @throws \Exception
     */
    public function tracking()
    {
        if (empty($this->conf->code_token) || $this->conf->code_token != $this->request->get['token']) {
            exit("Informe um Token válido!");
        }

        $this->load->model('checkout/order');
        $dayIntervalLimit = !empty($this->request->get['day']) ? (int) $this->request->get['day'] : 5;
        $debugURL = !empty($this->request->get['debug']) ? 1 : 0;
        $limit = !empty($this->request->get['limit']) ? (int) $this->request->get['limit'] : 35;

        $lib = new MelhorEnvio($this->registry);

        $query = $this->db->query("
            SELECT o.order_id, o.order_status_id, o.shipping_code, o.date_modified, me.packages
            FROM `" . DB_PREFIX . "order` o
            INNER JOIN " . DB_PREFIX . "code_melhorenvio me ON (o.order_id = me.order_id)
            WHERE
            o.shipping_code LIKE '%code_melhorenvio%'  AND
            o.order_status_id != 0 AND 
            (NOW() - INTERVAL '" . (int) $dayIntervalLimit . "' DAY) <= o.date_modified AND
            me.packages IS NOT NULL AND CHAR_LENGTH(me.packages) > 80
            ORDER BY o.date_modified DESC
             LIMIT " . $limit . "
        ")->rows;

        //print_r($query);
        //exit();
        if (empty($query[0]['order_id'])) {
            $this->log->write('tracking() - Sem Pedidos retornados');
            exit("<h3>Sem Pedidos retornados - Horário: " . date('i:s') . "</h3>");
        }

        foreach ($query as $me) {
            //print_r($me['packages']);
            $this->log->write('tracking() - Verificando o Pedido: ' . $me['order_id']);
            echo "<h3>Pedido: " . $me['order_id'] . " sendo verificado - Horário: " . date('i:s') . "</h3>";

            $packages = empty($me['packages']) ? [] : json_decode($me['packages']);

            foreach ($packages as $key => $package) {
                if (!empty($package->cart->id)) {
                    //print_r($package);
                    $tracking = $lib->trackPackage($package, $this->log, $debugURL);

                    if (!empty($this->conf->code_debug) && $this->conf->code_debug == 1) {
                        $this->log->write('tracking() - Modo Debug, Retorno do Rastreio: ' . print_r($tracking, true));
                    }

                    if ($debugURL) {
                        echo "<h3>Dados da Consulta</h3>";
                        print_r($tracking);
                    }

                    if (empty($tracking->status)) {
                        $this->log->write('Sem o Status no Pacote do Pedido: ' . $me['order_id']);
                        continue;
                    }

                    //print_r($tracking);
                    /*
                    * stdClass Object
                    (
                    [id] => b1bd57e0-2583-47be-ae68-6e8f6fd50c7c
                    [protocol] => ORD-20210570347
                    [status] => released
                    [tracking] =>
                    [melhorenvio_tracking] =>
                    [created_at] => 2021-05-28 19:01:39
                    [paid_at] => 2021-05-28 19:02:11
                    [generated_at] =>
                    [posted_at] =>
                    [delivered_at] =>
                    [canceled_at] =>
                    [expired_at] =>
                    )
                    Depois de postado
                    [status] => posted
                    [tracking] => ME21001IA37BR
                    [melhorenvio_tracking] => ME21001IA37BR
                    */

                    if ($debugURL) {
                        echo "<h3>Mudando no Pedido o Status</h3>";
                    }

                    $this->changeStatusOrder($me, $tracking);
                }
            }

            $this->log->write('tracking() - Pedido: ' . $me['order_id'] . ' verificado com sucesso');
            echo "<h3>Pedido: " . $me['order_id'] . " verificado com sucesso - Horário: " . date('i:s') . "</h3>";
        }
    }

    /**
     * Verifica os Status de Entrega e chama o método para mudar o Status
     *
     * @param $me
     * @param $tracking
     */
    private function changeStatusOrder($me, $tracking)
    {
        $comment = '';
        $status = strtolower($tracking->status);
        $etiqueta_ordem = !empty($tracking->protocol) ? trim($tracking->protocol) : '';

        /*
        if (!empty($tracking->protocol)) {
            $comment .= '<b>Código de Envio:</b> ' . $tracking->protocol . '<br>';
        }
        */

        if (!empty($this->conf->code_debug) && $this->conf->code_debug == 1) {
            $this->log->write('changeStatusOrder() - Modo Debug, Status Melhor Envio: ' . print_r($status, true));
        }

        switch ($status) {
            case 'pending':
                $order_status_id = (int) $this->conf->status_pending;
                break;
            case 'released':
                $order_status_id = (int) $this->conf->status_released;

                if (!empty($this->conf->code_msg_released)) {
                    $comment = str_replace(['{etiqueta_ordem}'], [$etiqueta_ordem], $this->conf->code_msg_released);
                } else if (!empty(($etiqueta_ordem))) {
                    //$comment .= '<b>Código de Envio:</b> ' . $etiqueta_ordem . '<br>';
                }

                break;
            case 'movement':
            case 'posted':
                $order_status_id = (int) $this->conf->status_posted;

                $codigo_rastreio = !empty($tracking->tracking) ? trim($tracking->tracking) : '';
                $codigo_melhorenvio = !empty($tracking->melhorenvio_tracking) ? trim($tracking->melhorenvio_tracking) : '';
                $link = 'https://www.melhorrastreio.com.br/rastreio/' . $codigo_rastreio;

                if (!empty($this->conf->code_msg_posted)) {
                    $comment = str_replace(
                        ['{link}', '{codigo_rastreio}', '{codigo_melhorenvio}', '{etiqueta_ordem}'],
                        [$link, $codigo_rastreio, $codigo_melhorenvio, $etiqueta_ordem],
                        $this->conf->code_msg_posted
                    );
                } else {
                    //$comment .= '<b>Código de Rastreio:</b> ' . $tracking->tracking . '<br><br>';
                    //$comment .= '<a href="' . $link . '" target="_blank">Rastrear o seu Pedido</a><br>';
                }

                break;
            case 'delivered':
                $order_status_id = (int) $this->conf->status_delivered;
                break;
            case 'canceled':
                $order_status_id = (int) $this->conf->status_canceled;
                break;
            case 'undelivered':
                $order_status_id = (int) $this->conf->status_undelivered;
                break;
        }

        $name_cache = 'codeme524_history_' . $me['order_id'] . '_' . $order_status_id;
        if (!empty($this->codeCache->get($name_cache))) {
            $this->log->write('tracking() - Pedido: ' . $me['order_id'] . ' retornado do Cache já atualizado');
            echo "<h3>Pedido: " . $me['order_id'] . " já atualizado, retorno do Cache - Horário: " . date('i:s') . "</h3>";
            return true;
        }

        if (!empty($this->conf->code_debug) && $this->conf->code_debug == 1) {
            $this->log->write('changeStatusOrder() - Modo Debug, Status ID: ' . print_r($order_status_id, true));
            $this->log->write('changeStatusOrder() - Modo Debug, Comentário: ' . print_r($comment, true));
        }

        if (!empty($order_status_id) && $order_status_id > 0) {
            $this->addOrderHistoryVerify($me['order_id'], $order_status_id, $comment, true);
        } else {
            echo "<h3>Pedido: " . $me['order_id'] . " sem o Status  - Horário: " . date('i:s') . "</h3>";
            $this->log->write("changeStatusOrder() - Pedido: " . $me['order_id'] . " sem o Status");
        }
    }

    /**
     *
     * Salva no banco de dados o Status do Pedido
     * Bling -> Opencart
     *
     * @param $order_id
     * @param $order_status_id
     * @param string $comment
     *
     * @return bool
     */
    private function addOrderHistoryVerify($order_id, $order_status_id, $comment = '', $verifyStatus = true)
    {
        $this->log->write("addOrderHistoryVerify() - Pedido: " . $order_id . " e Status " . $order_status_id . ", notificando");

        //Verifica se foi usado o Status
        $query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)
            $order_id . "' AND 
            order_status_id = '" . (int) $order_status_id . "'
            LIMIT 1
        ")->row;

        if (!empty($query['order_id']) && $verifyStatus == true && (empty($this->request->get['test']) || $this->request->get['test'] == 0)) {
            echo "<h3>Pedido: " . $order_id . " e Status " . $order_status_id . ", já notificado - Horário: " . date('i:s') . "</h3>";
            $this->log->write("addOrderHistoryVerify() - Pedido: " . $order_id . " e Status " . $order_status_id . ", já notificado");
            return true;
        }

        if (!empty($this->conf->code_alertar_status) && $this->conf->code_alertar_status == 1) {
            $status_alertar = true;
        } else {
            $status_alertar = false;
        }

        if (empty($this->request->get['test']) || $this->request->get['test'] == 0) {
            $this->model_checkout_order->addOrderHistory(
                (int) $order_id,
                (int) $order_status_id,
                $comment,
                $status_alertar
            );

            $name_cache = 'codeme524_history_' . $order_id . '_' . $order_status_id;
            $this->codeCache->set($name_cache, 1);

            echo "<h3>Pedido: " . $order_id . " e Status " . $order_status_id . ", notificado com sucesso - Horário: " . date('i:s') . "</h3>";
            $this->log->write("addOrderHistoryVerify() - Pedido: " . $order_id . " e Status " . $order_status_id . ", notificado com sucesso");
        } else {
            echo "<h3>Modo Teste sem verificação de Status - Comentário: </h3>";
            echo $comment;
            echo "<h3>Modo Teste - Pedido: " . $order_id . " e Status " . $order_status_id . ", notificado com sucesso - Horário: " . date('i:s') . "</h3>";
        }

        return true;
    }

    public function order()
    {
        $lib = new MelhorEnvio($this->registry);

        $this->response->addHeader('Content-Type: application/json');

        $order_id = !empty($this->request->get['id']) ? $this->request->get['id'] : null;

        if (!$order_id) {
            return $this->response->setOutput(json_encode(['success' => false]));
        }

        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio WHERE order_id = ' . (int) $order_id);

        if ($query->row) {
            $data = $query->row;

            $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . "order_product WHERE order_id = '" . (int) $order_id . "'");
            $products = $query->rows;

            //print_r($products);
            $total = 0;
            $insuranceValue = 0;
            $insuranceValueProduct = 0;

            $productsK = [];
            foreach ($products as $k => $product) {
                $total += $product['quantity'];

                //Adicionando o nome da Opção ao nome do Produto
                $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . "order_option WHERE order_id = '" . (int) $order_id . "' AND order_product_id = '" . (int) $product['order_product_id'] . "'");
                $orderOption = $query->row;
                if (!empty($orderOption['name']) && $orderOption['value']) {
                    $product['name'] .= ' - ' . $orderOption['name'] . ': ' . $orderOption['value'];
                }

                $productsK[$product['order_product_id']] = [
                    'id'       => $product['product_id'],
                    'name'     => html_entity_decode($product['name']),
                    'model'    => $product['model'],
                    'price'    => (float) $product['price'],
                    'quantity' => (int) $product['quantity'],
                ];

                //DECLARAR VALOR
                $totalPrice = $product['price'] * $product['quantity'];

                if (!empty($this->conf->declarar_tabela2) && !empty($product['product_id'])) {
                    $declararGet = $this->db->query("SELECT * FROM " . $this->conf->declarar_tabela2 . " 
                        WHERE 
                        product_id = '" . (int) $product['product_id'] . "' 
                        LIMIT 1
                    ");
                }

                if (!empty($this->conf->declarar_campo2) && !empty($declararGet->row[$this->conf->declarar_campo2])) {
                    $insuranceValueProduct = ((float) $declararGet->row[$this->conf->declarar_campo2] * $product['quantity']);
                } else {
                    $insuranceValueProduct = (float) $totalPrice;
                }

                //Caso o preço no carrinho for menor que o custo, usar ele como custo
                if ($totalPrice < $insuranceValue) {
                    $insuranceValueProduct = (float) $totalPrice;
                }

                $insuranceValue += $insuranceValueProduct;
            }
            //print_r($productsK); exit();

            $packages = empty($data['packages']) ? [] : json_decode($data['packages']);

            foreach ($packages as $package) {
                if (!empty($package->cart->id)) {
                    $isOnCart = $lib->checkCart($package);
                    $tracking = $lib->trackPackage($package, $this->log, 0);

                    if (!$isOnCart || empty($tracking) || (!empty($tracking->status) && $tracking->status === 'canceled')) {
                        $lib->removeCartFromPackage($order_id, $package);
                    }

                    $package->isOnCart = $isOnCart;
                    $package->tracking = $tracking;
                } else {
                    $package->isOnCart = false;
                    $package->tracking = false;
                }
            }

            //print_r(json_decode($data['data']));

            return $this->response->setOutput(json_encode([
                'quote'          => json_decode($data['data']),
                'packages'       => $packages,
                'conf'           => $lib->conf,
                'products'       => $productsK,
                'totalProducts'  => $total,
                'insuranceValue' => $insuranceValue,
            ]));
        }

        return $this->response->setOutput(json_encode(['success' => false]));
    }

    public function get_quotes()
    {
        $this->response->addHeader('Content-type: application/json');

        try {
            $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio ORDER BY date_created DESC LIMIT 30');

            if ($query->rows) {
                $results = [];
                foreach ($query->rows as $i => $row) {
                    if (empty($row['data'])) {
                        continue;
                    }

                    $results[$i] = json_decode($row['data'], true);
                    $results[$i]['order'] = $row['order_id'];
                    $results[$i]['status'] = $row['status'];
                    $results[$i]['date'] = date('d/m/Y H:i:s', strtotime($row['date_created']));
                }

                $this->response->setOutput(json_encode([
                    'success' => true,
                    'results' => $results,
                ]));

                return true;
            }
        } catch (\Exception $e) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]));

            return true;
        }

        $this->response->setOutput(json_encode([
            'success' => false,
            'results' => null,
        ]));

        return true;
    }

    public function get_all()
    {
        \CodeLibrary\Request::get();

        $this->response->addHeader('Content-type: application/json');

        $limit = !empty($this->request->get['limit']) ? $this->request->get['limit'] : 30;
        $status = !empty($this->request->get['status']) ? $this->request->get['status'] : '';

        try {
            if (!empty($this->request->get['status'])) {
                $sql = 'SELECT * FROM ' . DB_PREFIX . "code_melhorenvio
                WHERE status = '" . $this->db->escape($status) . "'
                ORDER BY date_created DESC LIMIT  " . ($limit) . ' ';
            } else {
                $sql = 'SELECT * FROM ' . DB_PREFIX . 'code_melhorenvio
                ORDER BY date_created DESC LIMIT  ' . ($limit) . ' ';
            }

            $query = $this->db->query($sql);

            if ($query->rows) {
                $results = [];
                foreach ($query->rows as $i => $row) {
                    $results[$i]['data'] = json_decode($row['data'], true);
                    $results[$i]['packages'] = json_decode($row['packages'], true);
                    $results[$i]['status'] = $row['status'];
                    $results[$i]['order'] = $row['order_id'];
                    $results[$i]['date'] = date('d/m/Y H:i:s', strtotime($row['date_created']));
                }

                $this->response->setOutput(json_encode([
                    'success' => true,
                    'results' => $results,
                ]));

                return true;
            }
        } catch (\Exception $e) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]));

            return true;
        }

        $this->response->setOutput(json_encode([
            'success' => false,
            'results' => null,
        ]));

        return true;
    }

    public function record()
    {
        $etiqueta = $this->request->post['etiqueta'] === 'null' ? null : $this->request->post['etiqueta'];
        $order = (int) $this->request->post['orderId'] === 'null' ? null : $this->request->post['orderId'];

        if ($order) {
            $this->db->query('UPDATE ' . DB_PREFIX . "code_melhorenvio SET etiqueta = '" . $etiqueta . "' WHERE order_id = " . $order);
        } else {
            $this->db->query('UPDATE ' . DB_PREFIX . "code_melhorenvio SET etiqueta = '" . $etiqueta . "' WHERE etiqueta = " . $etiqueta);
        }
    }

    public function add()
    {
        $lib = new MelhorEnvio($this->registry);

        $this->response->addHeader('Content-Type: application/json');

        $post = json_decode(file_get_contents('php://input'));

        foreach ($post->packages as &$package) {
            if (isset($package)) {
                unset($package->currentProductSelection);
            }
            if (isset($package)) {
                unset($package->newProducts);
            }
        }

        $order = (int) $post->order;

        if ($order) {
            foreach ($post->packages as &$package) {
                if (empty($package->isOnCart) && $post->buy == true) {
                    $cart = $lib->comparEtiqueta($order, $package);

                    if (!empty($cart->error)) {
                        if (!empty($cart->message)) {
                            $cart->error = $cart->message . ' ' . $cart->error;
                        }

                        return $this->response->setOutput(json_encode(['success' => false, 'error' => $cart->error]));
                    }

                    if (!empty($cart->errors)) {
                        if (!empty($cart->message)) {
                            $cart->errors = $cart->message . ' ' . print_r($cart->errors, true);
                        } else {
                            $cart->errors = print_r($cart->errors, true);
                        }

                        return $this->response->setOutput(json_encode(['success' => false, 'error' => $cart->errors]));
                    }

                    $package->cart = $cart;
                }
            }

            $packages = json_encode($post->packages, JSON_PRETTY_PRINT);

            $this->db->query('UPDATE ' . DB_PREFIX . "code_melhorenvio SET packages = '" . $this->db->escape($packages) . "' WHERE order_id = " . $order);

            return $this->response->setOutput(json_encode(['success' => true]));
        }

        return $this->response->setOutput(json_encode(['success' => false]));
    }

    public function remove()
    {
        $lib = new MelhorEnvio($this->registry);

        $this->response->addHeader('Content-Type: application/json');

        $post = json_decode(file_get_contents('php://input'));

        foreach ($post->packages as &$package) {
            if (isset($package)) {
                unset($package->currentProductSelection);
            }
            if (isset($package)) {
                unset($package->newProducts);
            }
        }

        // remove from melhor envio
        if ($post->package->isOnCart == true) {
            $lib->removeFromCart($post->package);
        }

        $order = (int) $post->order;

        if ($order) {
            $packages = json_encode($post->packages, JSON_PRETTY_PRINT);

            $this->db->query('UPDATE ' . DB_PREFIX . "code_melhorenvio SET packages = '" . $this->db->escape($packages) . "' WHERE order_id = " . $order);

            return $this->response->setOutput(json_encode(['success' => true]));
        }

        return $this->response->setOutput(json_encode(['success' => false]));
    }

    public function config()
    {
        $menvio = new MelhorEnvio($this->registry);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($menvio->conf));
    }
    /*
    public function test()
    {
        $carts = [
            [
                'cart' => [
                    'id' => 'teste',
                ],
            ],
            [
                'cart' => [
                    'id' => 'teste 2',
                ],
            ],
        ];

        ChromePhp::log(MelhorEnvio::findIndex($carts, 'cart.id', 'teste 2'));
    }

    public function testb()
    {
        $me = new MelhorEnvio($this->registry);

        $me->autoPost(1812);
    }
    */
}
