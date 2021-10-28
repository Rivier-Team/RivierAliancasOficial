<?php

namespace CodeLibrary;

use ORM;

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class Configuration
 *
 * @package CodeLibrary
 */
class Configuration
{
    /**
     * Configuration constructor.
     *
     * @param $reg
     */
    public function __construct($reg)
    {
        $this->registry = $reg;
        $this->session = $reg->get('session');
        $this->db = $reg->get('db');
        $this->request = $reg->get('request');
        $this->config = $reg->get('config');

        $this->load = new \Loader($reg);

        if (defined('DB_PORT') && !empty(DB_PORT)) {
            $dbPort = DB_PORT;
        } else {
            $dbPort = '3306';
        }

        ORM::configure('mysql:host=' . DB_HOSTNAME . ';port=' . $dbPort . ';dbname=' . DB_DATABASE);
        ORM::configure('username', DB_USERNAME);
        ORM::configure('password', DB_PASSWORD);
        ORM::configure('return_result_sets', true);
        ORM::configure('logging', false);
        ORM::configure('caching', false);
        //ORM::configure('caching_auto_clear', true);

        ORM::configure('id_column_overrides', [
            DB_PREFIX . 'setting'                        => 'setting_id',
            DB_PREFIX . 'address'                        => 'address_id',
            DB_PREFIX . 'api'                            => 'api_id',
            DB_PREFIX . 'api_ip'                         => 'api_ip_id',
            DB_PREFIX . 'api_session'                    => 'api_session_id',
            DB_PREFIX . 'attribute'                      => 'attribute_id',
            DB_PREFIX . 'attribute_description'          => 'attribute_description_id',
            DB_PREFIX . 'attribute_group'                => 'attribute_group_id',
            DB_PREFIX . 'attribute_group_description'    => 'attribute_group_description_id',
            DB_PREFIX . 'banner'                         => 'banner_id',
            DB_PREFIX . 'banner_image'                   => 'banner_image_id',
            DB_PREFIX . 'cart'                           => 'cart_id',
            DB_PREFIX . 'category'                       => 'category_id',
            DB_PREFIX . 'category_description'           => 'category_description_id',
            DB_PREFIX . 'category_filter'                => 'category_filter_id',
            DB_PREFIX . 'category_path'                  => 'category_path_id',
            DB_PREFIX . 'category_to_layout'             => 'category_to_layout_id',
            DB_PREFIX . 'category_to_store'              => 'category_to_store_id',
            DB_PREFIX . 'country'                        => 'country_id',
            DB_PREFIX . 'coupon'                         => 'coupon_id',
            DB_PREFIX . 'coupon_category'                => 'coupon_category_id',
            DB_PREFIX . 'coupon_history'                 => 'coupon_history_id',
            DB_PREFIX . 'coupon_product'                 => 'coupon_product_id',
            DB_PREFIX . 'currency'                       => 'currency_id',
            DB_PREFIX . 'customer'                       => 'customer_id',
            DB_PREFIX . 'customer_activity'              => 'customer_activity_id',
            DB_PREFIX . 'customer_affiliate'             => 'customer_affiliate_id',
            DB_PREFIX . 'customer_approval'              => 'customer_approval_id',
            DB_PREFIX . 'customer_group'                 => 'customer_group_id',
            DB_PREFIX . 'customer_group_description'     => 'customer_group_description_id',
            DB_PREFIX . 'customer_history'               => 'customer_history_id',
            DB_PREFIX . 'customer_ip'                    => 'customer_ip_id',
            DB_PREFIX . 'customer_login'                 => 'customer_login_id',
            DB_PREFIX . 'customer_online'                => 'customer_online_id',
            DB_PREFIX . 'customer_reward'                => 'customer_reward_id',
            DB_PREFIX . 'customer_search'                => 'customer_search_id',
            DB_PREFIX . 'customer_transaction'           => 'customer_transaction_id',
            DB_PREFIX . 'customer_wishlist'              => 'customer_wishlist_id',
            DB_PREFIX . 'custom_field'                   => 'custom_field_id',
            DB_PREFIX . 'custom_field_customer_group'    => 'custom_field_customer_group_id',
            DB_PREFIX . 'custom_field_description'       => 'custom_field_description_id',
            DB_PREFIX . 'custom_field_value'             => 'custom_field_value_id',
            DB_PREFIX . 'custom_field_value_description' => 'custom_field_value_description_id',
            DB_PREFIX . 'download'                       => 'download_id',
            DB_PREFIX . 'download_description'           => 'download_description_id',
            DB_PREFIX . 'event'                          => 'event_id',
            DB_PREFIX . 'extension'                      => 'extension_id',
            DB_PREFIX . 'extension_install'              => 'extension_install_id',
            DB_PREFIX . 'extension_path'                 => 'extension_path_id',
            DB_PREFIX . 'filter'                         => 'filter_id',
            DB_PREFIX . 'filter_description'             => 'filter_description_id',
            DB_PREFIX . 'filter_group'                   => 'filter_group_id',
            DB_PREFIX . 'filter_group_description'       => 'filter_group_description_id',
            DB_PREFIX . 'geo_zone'                       => 'geo_zone_id',
            DB_PREFIX . 'information'                    => 'information_id',
            DB_PREFIX . 'information_description'        => 'information_description_id',
            DB_PREFIX . 'information_to_layout'          => 'information_to_layout_id',
            DB_PREFIX . 'information_to_store'           => 'information_to_store_id',
            DB_PREFIX . 'language'                       => 'language_id',
            DB_PREFIX . 'layout'                         => 'layout_id',
            DB_PREFIX . 'layout_module'                  => 'layout_module_id',
            DB_PREFIX . 'layout_route'                   => 'layout_route_id',
            DB_PREFIX . 'length_class'                   => 'length_class_id',
            DB_PREFIX . 'length_class_description'       => 'length_class_description_id',
            DB_PREFIX . 'location'                       => 'location_id',
            DB_PREFIX . 'manufacturer'                   => 'manufacturer_id',
            DB_PREFIX . 'manufacturer_to_store'          => 'manufacturer_to_store_id',
            DB_PREFIX . 'marketing'                      => 'marketing_id',
            DB_PREFIX . 'modification'                   => 'modification_id',
            DB_PREFIX . 'module'                         => 'module_id',
            DB_PREFIX . 'option'                         => 'option_id',
            DB_PREFIX . 'option_description'             => 'option_description_id',
            DB_PREFIX . 'option_value'                   => 'option_value_id',
            DB_PREFIX . 'option_value_description'       => 'option_value_description_id',
            DB_PREFIX . 'order'                          => 'order_id',
            DB_PREFIX . 'order_history'                  => 'order_history_id',
            DB_PREFIX . 'order_option'                   => 'order_option_id',
            DB_PREFIX . 'order_product'                  => 'order_product_id',
            DB_PREFIX . 'order_recurring'                => 'order_recurring_id',
            DB_PREFIX . 'order_recurring_transaction'    => 'order_recurring_transaction_id',
            DB_PREFIX . 'order_shipment'                 => 'order_shipment_id',
            DB_PREFIX . 'order_status'                   => 'order_status_id',
            DB_PREFIX . 'order_total'                    => 'order_total_id',
            DB_PREFIX . 'order_voucher'                  => 'order_voucher_id',
            DB_PREFIX . 'product'                        => 'product_id',
            DB_PREFIX . 'product_attribute'              => 'product_attribute_id',
            DB_PREFIX . 'product_description'            => 'product_description_id',
            DB_PREFIX . 'product_discount'               => 'product_discount_id',
            DB_PREFIX . 'product_filter'                 => 'product_filter_id',
            DB_PREFIX . 'product_image'                  => 'product_image_id',
            DB_PREFIX . 'product_option'                 => 'product_option_id',
            DB_PREFIX . 'product_option_value'           => 'product_option_value_id',
            DB_PREFIX . 'product_recurring'              => 'product_recurring_id',
            DB_PREFIX . 'product_related'                => 'product_related_id',
            DB_PREFIX . 'product_reward'                 => 'product_reward_id',
            DB_PREFIX . 'product_special'                => 'product_special_id',
            DB_PREFIX . 'product_to_category'            => 'product_to_category_id',
            DB_PREFIX . 'product_to_download'            => 'product_to_download_id',
            DB_PREFIX . 'product_to_layout'              => 'product_to_layout_id',
            DB_PREFIX . 'product_to_store'               => 'product_to_store_id',
            DB_PREFIX . 'recurring'                      => 'recurring_id',
            DB_PREFIX . 'recurring_description'          => 'recurring_description_id',
            DB_PREFIX . 'return'                         => 'return_id',
            DB_PREFIX . 'return_action'                  => 'return_action_id',
            DB_PREFIX . 'return_history'                 => 'return_history_id',
            DB_PREFIX . 'return_reason'                  => 'return_reason_id',
            DB_PREFIX . 'return_status'                  => 'return_status_id',
            DB_PREFIX . 'review'                         => 'review_id',
            DB_PREFIX . 'seo_url'                        => 'seo_url_id',
            DB_PREFIX . 'session'                        => 'session_id',
            DB_PREFIX . 'setting'                        => 'setting_id',
            DB_PREFIX . 'shipping_courier'               => 'shipping_courier_id',
            DB_PREFIX . 'statistics'                     => 'statistics_id',
            DB_PREFIX . 'stock_status'                   => 'stock_status_id',
            DB_PREFIX . 'store'                          => 'store_id',
            DB_PREFIX . 'tax_class'                      => 'tax_class_id',
            DB_PREFIX . 'tax_rate'                       => 'tax_rate_id',
            DB_PREFIX . 'tax_rate_to_customer_group'     => 'tax_rate_to_customer_group_id',
            DB_PREFIX . 'tax_rule'                       => 'tax_rule_id',
            DB_PREFIX . 'theme'                          => 'theme_id',
            DB_PREFIX . 'translation'                    => 'translation_id',
            DB_PREFIX . 'upload'                         => 'upload_id',
            DB_PREFIX . 'user'                           => 'user_id',
            DB_PREFIX . 'user_group'                     => 'user_group_id',
            DB_PREFIX . 'voucher'                        => 'voucher_id',
            DB_PREFIX . 'voucher_history'                => 'voucher_history_id',
            DB_PREFIX . 'voucher_theme'                  => 'voucher_theme_id',
            DB_PREFIX . 'voucher_theme_description'      => 'voucher_theme_description_id',
            DB_PREFIX . 'weight_class'                   => 'weight_class_id',
            DB_PREFIX . 'weight_class_description'       => 'weight_class_description_id',
            DB_PREFIX . 'zone'                           => 'zone_id',
            DB_PREFIX . 'zone_to_geo_zone'               => 'zone_to_geo_zone_id',
        ]);

        //Store ID
        if (!empty($this->request->server['HTTPS'])) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') =
            '" . $this->db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname
                    ($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') =
            '" . $this->db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname
                    ($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
        }

        if (isset($this->request->get['store_id'])) {
            $this->session->data['code_config_store_id'] = (int) $this->request->get['store_id'];
        } else if ($query->num_rows) {
            $this->session->data['code_config_store_id'] = (int) $query->row['store_id'];
        } else {
            $this->session->data['code_config_store_id'] = 0;
        }
    }

    public function getConfig($id_produto)
    {
        $config = ORM::forTable(DB_PREFIX . 'setting')->where([
            'code' => 'code_api_cache',
            'key'  => base64_encode($id_produto),
        ])->findOne();

        if (empty($config->value)) {
            return null;
        }

        return json_decode(base64_decode($config->value));
    }

    public function getDataStore()
    {
        if (version_compare(VERSION, '2.2.0.0', '>')) {
            $extension = 'extension/';
        } else {
            $extension = '';
        }

        $geo_zones = ORM::forTable(DB_PREFIX . 'geo_zone')->select_many('geo_zone_id', 'name')->find_array();
        array_unshift($geo_zones, ['geo_zone_id' => 0, 'name' => 'Todas as regiões']);

        $curriencies = ORM::forTable(DB_PREFIX . 'currency')->select_many('currency_id', 'title', 'symbol_left', 'symbol_right')->find_array();

        $payments = ORM::forTable(DB_PREFIX . 'extension')->where('type', 'payment')->find_array();
        $payments_array = [];
        foreach ($payments as $p) {
            if (!empty($this->load->language($extension . 'payment/' . $p['code'])['text_title']) && strlen($this->load->language($extension . 'payment/' . $p['code'])['text_title']) <= 60) {
                if (strpos($p['code'], 'code') === 0 || strpos($p['code'], 'codemarket') === 0) {
                    $name = ucwords(str_replace('_', ' ', $p['code']));
                } else {
                    $name = trim($this->load->language($extension . 'payment/' . $p['code'])['text_title']);
                }
            } else {
                $name = ucwords(str_replace('_', ' ', $p['code']));
            }

            foreach ($payments_array as $key => $payment) {
                if ($name == $payment['name']) {
                    $name = ucwords(str_replace('_', ' ', $p['code']));
                }
            }

            $payments_array[] = [
                'code' => $p['code'],
                'name' => $name,
            ];
        }

        $payments = $payments_array;
        array_unshift($payments, ['code' => '0', 'name' => 'Nenhuma das Opções']);

        $shippings = ORM::forTable(DB_PREFIX . 'extension')->where('type', 'shipping')->find_array();

        $shippings_array = [];
        foreach ($shippings as $s) {
            if (!empty($this->load->language($extension . 'shipping/' . $s['code'])['text_title']) && strlen($this->load->language($extension . 'shipping/' . $s['code'])['text_title']) <= 60) {
                if (strpos($s['code'], 'code') === 0 || strpos($s['code'], 'codemarket') === 0) {
                    $name = ucwords(str_replace('_', ' ', $s['code']));
                } else {
                    $name = trim($this->load->language($extension . 'shipping/' . $s['code'])['text_title']);
                }
            } else {
                $name = ucwords(str_replace('_', ' ', $s['code']));
            }

            $shippings_array[] = [
                'code' => $s['code'],
                'name' => $name,
            ];
        }

        $shippings = $shippings_array;
        array_unshift($shippings, ['code' => '0', 'name' => 'Nenhuma das Opções']);

        $stock_statuses = ORM::forTable(DB_PREFIX . 'stock_status')->where('language_id', $this->config->get('config_language_id'))
            ->find_array();
        array_unshift($stock_statuses, ['stock_status_id' => '0', 'name' => 'Nenhuma das Opções']);

        $order_statuses = ORM::forTable(DB_PREFIX . 'order_status')->where('language_id', $this->config->get('config_language_id'))
            ->find_array();
        array_unshift($order_statuses, ['order_status_id' => '0', 'name' => 'Pedido Abandonado (compra não finalizada)']);

        $tax_classes = ORM::forTable(DB_PREFIX . 'tax_class')->select_many('tax_class_id', 'title')->find_array();
        array_unshift($tax_classes, ['tax_class_id' => '0', 'title' => 'Nenhuma das Opções']);

        $customer_groups = ORM::forTable(DB_PREFIX . 'customer_group')->left_outer_join(DB_PREFIX . 'customer_group_description', DB_PREFIX . 'customer_group.customer_group_id = cd.customer_group_id', 'cd')->where('cd.language_id', $this->config->get('config_language_id'))->select_many('cd.customer_group_id', 'cd.name')->find_array();
        array_unshift($customer_groups, ['customer_group_id' => '0', 'name' => 'Todos os Grupos de Clientes']);

        $user_groups = ORM::forTable(DB_PREFIX . 'user_group')->select_many('name', 'user_group_id')->find_array();
        array_unshift($customer_groups, ['user_group_id' => '0', 'name' => 'Todos os Grupos de Usuários']);

        $layouts = ORM::forTable(DB_PREFIX . 'layout')->left_outer_join(DB_PREFIX . 'layout_route',
            DB_PREFIX . 'layout.layout_id = lr.layout_id', 'lr')->where('lr.store_id',
            $this->session->data['code_config_store_id'])->find_array();

        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            if (defined('HTTP_CATALOG')) {
                $this->load->model('customer/custom_field');
                $model_customer_custom_field = $this->registry->get('model_customer_custom_field');
                $custom_fields = $model_customer_custom_field->getCustomFields();
            } else {
                $this->load->model('account/custom_field');
                $model_account_custom_field = $this->registry->get('model_account_custom_field');
                $custom_fields = $model_account_custom_field->getCustomFields();
            }
        } else {
            $custom_fields = [];
        }

        if (!empty($custom_fields)) {
            $custom_fields_temp = [];

            foreach ($custom_fields as $cf) {
                $custom_fields_temp[] = [
                    'custom_field_id' => $cf['custom_field_id'],
                    'name'            => $cf['name'],
                    'sort_orderd'     => $cf['sort_order'],
                ];
            }

            $custom_fields = $custom_fields_temp;
        }
        array_unshift($custom_fields, ['custom_field_id' => 0, 'name' => 'Nenhuma das Opções', 'sort_orderd' => 0]);

        $length_classes = ORM::forTable(DB_PREFIX . 'length_class_description')
            ->where('language_id', $this->config->get('config_language_id'))
            ->select_many('length_class_id', 'title', 'unit')
            ->find_array();

        $weight_classes = ORM::forTable(DB_PREFIX . 'weight_class_description')
            ->where('language_id', $this->config->get('config_language_id'))
            ->select_many('weight_class_id', 'title', 'unit')
            ->find_array();

        $informations = ORM::forTable(DB_PREFIX . 'information_description')
            ->where('language_id', $this->config->get('config_language_id'))
            ->select_many('information_id', 'title')
            ->find_array();
        array_unshift($informations, ['information_id' => 0, 'title' => 'Nenhuma das Opções']);

        $zones = ORM::forTable(DB_PREFIX . 'zone')
            ->where(['country_id' => 30, 'status' => 1])
            ->select_many('zone_id', 'name', 'code')
            ->find_array();
        array_unshift($zones, ['zone_id' => 0, 'name' => 'Todos os Estados', 'code' => 0]);

        $data = compact(
            'zones',
            'geo_zones',
            'curriencies',
            'payments',
            'shippings',
            'stock_statuses',
            'order_statuses',
            'tax_classes',
            'customer_groups',
            'user_groups',
            'layouts',
            'custom_fields',
            'length_classes',
            'weight_classes',
            'informations'
        );

        //Converte a String do Array para utf8, troca � pelo texto original
        array_walk_recursive($data, function (&$item, $key) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = utf8_encode($item);
            }
        });

        return $data;
    }

    public function editSettingStore($type, $code, $key, $value, $store_id = 0)
    {
        if (empty($type) || empty($code) || empty($key) || !isset($value)) {
            return false;
        }

        if (version_compare(VERSION, '3.0.0.0', '>=')) {
            $code = $type . '_' . $code;
            $key = $type . '_' . $key;
        }

        if (empty($store_id)) {
            $store_id = $this->session->data['code_config_store_id'];
        }

        $setting = ORM::for_table(DB_PREFIX . 'setting')
            ->where_equal([
                'code'     => $code,
                'key'      => $key,
                'store_id' => $store_id,
            ])
            ->find_one();

        if (!empty($setting->setting_id)) {
            $setting->value = $value;
            $setting->save();
        } else {
            ORM::for_table(DB_PREFIX . 'setting')->create([
                'store_id'   => $store_id,
                'value'      => $value,
                'code'       => $code,
                'key'        => $key,
                'serialized' => '0',
            ])->save();
        }

        return true;
    }

    public function addExtensionStore($type, $code)
    {
        if (empty($type) || empty($code)) {
            return false;
        }

        $extension = ORM::for_table(DB_PREFIX . 'extension')
            ->where_equal([
                'type' => $type,
                'code' => $code,
            ])
            ->find_one();

        if (!empty($extension->extension_id)) {
            return true;
        }

        ORM::for_table(DB_PREFIX . 'extension')->create([
            'type' => $type,
            'code' => $code,
        ])->save();

        return true;
    }

    public function getServerInfo()
    {
        return [
            'opencart'   => VERSION,
            'api'        => '1.1',
            'date'       => '13/02/2021',
            'php'        => PHP_VERSION,
            'curl'       => curl_version(),
            'extensions' => get_loaded_extensions(),
            'pdo'        => !empty(class_exists('PDO')) ? true : false,
        ];
    }
}
