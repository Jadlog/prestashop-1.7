<?php

if (!defined('_PS_VERSION_'))
{
    exit;
}

class Jadlog extends CarrierModule
{
    public $carriers_list = array(
        'JADLOG_REPO' => 'Jadlog Pickup',
    );

    public function __construct()
    {
        $this->name = 'jadlog';
        $this->tab = 'shipping_module';
        $this->controllers = array('Back');
        $this->version = '1.0.3';
        $this->author = 'Jadlog';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->multishop_context = Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP;
        $this->multishop_context_group = Shop::CONTEXT_GROUP;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Jadlog');
        $this->description = $this->l('Modulo com o objetivo de facilitar o envio de encomendas pelo sistema de coleta da Jadlog.');
        $this->confirmUninstall = $this->l('Tem certeza que quer desinstalar?');

        if (!Configuration::get('JADLOG_NAME'))
            $this->warning = $this->l('No name provided');


        if (Configuration::get('JADLOG_NAME') == 0) {
            $this->warning = $this->l('Por favor configure o plugin de cotação e Coleta da Jadlog.');
        }
        if (!extension_loaded('soap')) {
            $this->warning = $this->l('Atencão!!! A extensão do PHP Soap é necessária.');
        }
    }

    public function install()
    {
        if ( !parent::install()
            || !$this->registerHooks()
            || !$this->installModuleTab('AdminJadlog', 'JadLog',\Tab::getIdFromClassName('AdminParentOrders'))
            || !Configuration::updateValue('JADLOG_ID','')
            || !Configuration::updateValue('JADLOG_MY_PUDO', 'http://mypudo.pickup-services.com/mypudo/mypudo.asmx')
            || !Configuration::updateValue('JADLOG_KEY_PUDO','')
            || !Configuration::updateValue('JADLOG_NAME','')
            || !Configuration::updateValue('JADLOG_LOGIN','')
            || !Configuration::updateValue('JADLOG_SENHA','')
            || !Configuration::updateValue('JADLOG_TOKEN','')
            || !Configuration::updateValue('JADLOG_CONTACORRENTE','')
            || !Configuration::updateValue('JADLOG_CONTRATO','')
            || !Configuration::updateValue('JADLOG_CPF_CNPJ','')
            || !Configuration::updateValue('JADLOG_ENDERECO','')
            || !Configuration::updateValue('JADLOG_REM','')
            || !Configuration::updateValue('JADLOG_NUMERO','')
            || !Configuration::updateValue('JADLOG_BAIRRO','')
            || !Configuration::updateValue('JADLOG_CIDADE','')
            || !Configuration::updateValue('JADLOG_UF','')
            || !Configuration::updateValue('JADLOG_CEP','')
            || !Configuration::updateValue('JADLOG_EMAIL','')
            || !$this->installCarriers()
        ){
            return FALSE;
        }
        $this->createDB();

        return TRUE;
    }

    private function createDB ()
    {
        $sql =  "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "jadlog_pickup`(
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `id_cart` int(11) NOT NULL,
          `id_customer` int(11) NOT NULL,
          `sent` int(1) NOT NULL,
          `cpf` varchar(100) NOT NULL,
          `relay_id` varchar(100) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;" ;
        $query = Db::getInstance()->Execute($sql);


        //new fields
        $new_fields = [
            'shipment_id' => [
                'created' => false,
                'type' => 'varchar(100)'
            ],
            'server_response' => [
                'created' => false,
                'type' => 'text'
            ],
            'pudo_nome' => [
                'created' => false,
                'type' => 'varchar(128)'
            ],
            'pudo_endereco' => [
                'created' => false,
                'type' => 'varchar(128)'
            ],
            'pudo_cep' => [
                'created' => false,
                'type' => 'varchar(9)'
            ],
            'pudo_cidade' => [
                'created' => false,
                'type' => 'varchar(100)'
            ]
        ];
        $sql = "show fields from `" . _DB_PREFIX_ . "jadlog_pickup`";
        $columns = Db::getInstance()->executeS($sql, true, false); //sql, array?, cache?
        foreach($columns as $column) {
            if(isset($new_fields[$column['Field']])) {
                $new_fields[$column['Field']]['created'] = true;
            }
        }
        foreach($new_fields as $field_name => $new_field) {
            if(!$new_field['created']) {
                $sql =  "alter table `" . _DB_PREFIX_ . "jadlog_pickup`
                  add column `". $field_name . "` " . $new_field['type'] . " NULL;";
                $query = Db::getInstance()->Execute($sql);
            }
        }

        //indexes
        $new_indexes = [
            _DB_PREFIX_ . 'jadlog_pickup_cart' => [
                'created' => false,
                'columns' => '(`id_cart`)'
            ],
            _DB_PREFIX_ . 'jadlog_pickup_shipid' => [
                'created' => false,
                'columns' => '(`shipment_id`)'
            ]
        ];
        $sql = "show index from `" . _DB_PREFIX_ . "jadlog_pickup`";
        $indexes = Db::getInstance()->executeS($sql, true, false); //sql, array?, cache?
        foreach($indexes as $index) {
            if(isset($new_indexes[$index['Key_name']])) {
                $new_indexes[$index['Key_name']]['created'] = true;
            }
        }
        foreach($new_indexes as $index_name => $new_index) {
            if(!$new_index['created']) {
                $sql =  "alter table `" . _DB_PREFIX_ . "jadlog_pickup`
                  add index `". $index_name . "` " . $new_index['columns'] . ";";
                $query = Db::getInstance()->Execute($sql);
            }
        }
    }

    private function registerHooks()
    {
        if (
            !$this->registerHook('actionFrontControllerSetMedia')||
            !$this->registerHook('displayHeader')||
            !$this->registerHook('displayBackOfficeHeader')||
            !$this->registerHook('displayAfterCarrier')||
            !$this->registerHook('actionCarrierUpdate')||
            !$this->registerHook('displayAdminNavBarBeforeEnd')||
            !$this->registerHook('actionValidateOrder')
        ) {
            return false;
        }
        return true;
    }

    private function unregisterHooks()
    {
        if (
            !$this->unregisterHook('actionFrontControllerSetMedia')||
            !$this->unregisterHook('displayHeader')||
            !$this->unregisterHook('displayBackOfficeHeader')||
            !$this->unregisterHook('displayAfterCarrier')||
            !$this->unregisterHook('actionCarrierUpdate')||
            !$this->unregisterHook('displayAdminNavBarBeforeEnd')||
            !$this->unregisterHook('actionValidateOrder')
        ) {
            return false;
        }
        return true;
    }

    public function hookDisplayAdminNavBarBeforeEnd()
    {
        return "jadbeforend";
    }

    public static function calculaPesoValorCarrinho($products) {
        $weight = 0;
        $value = 0;
        foreach($products as $product){
            $weight = $weight + Jadlog::pesoCubado($product)*$product['cart_quantity'];
            $value = $value + $product['price']*$product['cart_quantity'];
        }
        if($weight < 1) {
            $weight = 1;
        }
        return array('weight' => $weight, 'value' => $value);
    }

    public static function pesoCubado($product) {
        $weight = $product['weight'];
        $cubic_weight = $product['height'] * $product['depth'] * $product['width'] / 6000;
        if ($cubic_weight < $weight) {
            $cubic_weight = $weight;
        }
        return $cubic_weight;
    }

    //hookDisplayCarrierExtraContent
    //hookDisplayAfterCarrier
    public function hookDisplayAfterCarrier($params)
    {
        if ($params['cart']->id_address_delivery) {
            $address=new Address((int) $params['cart']->id_address_delivery);
            $cart = new Cart((int) $params['cart']->id);
            $calculoCarrinho = Jadlog::calculaPesoValorCarrinho($cart->getProducts());
            $weight = $calculoCarrinho['weight'];
            $value = $calculoCarrinho['value'];
            $address_details = $address->getFields();
            $id_shipping_jad = Configuration::get("JADLOG_REPO");
            $jadlog_points =  $this->getPoints($address_details,$value,$weight);

            $this->context->smarty->assign(array(
                    'ajax_link' =>  $this->context->link->getModuleLink('jadlog','JadlogFront'),
                    'id_shipping_jad' => $id_shipping_jad,
                    'pontos_pudo' => (!isset($jadlog_points['error']) ? $jadlog_points : $jadlog_points))
            );
            return $this->display(__FILE__,'views/templates/front/map.tpl');
        }
    }

    public function getPoints($input,$valeu,$weight)
    {
        $jadlog_points = array();
        $serviceurl = Configuration::get('JADLOG_MY_PUDO')."?WSDL";
        $date = date('d/m/y');

        $this->address  = self::stripAccents($input['address1']);
        $this->zipcode  = $input['postcode'];
        $this->city     = self::stripAccents($input['city']);
        if (empty($this->zipcode)) {
            $dpdfrance_relais_points['error'] = $this->l('Faltando código postal, favor modificar');
            return $dpdfrance_relais_points;
        }

        // MyPudo call parameters
        $variables = array(
            'carrier'=>'JAD',
            'key'=> Configuration::get('JADLOG_KEY_PUDO'),
            'address'=> $this->address,
            'zipCode'=> $this->zipcode,
            'city'=> $this->city,
            'countrycode'=>'BRA',
            'requestID'=>'12',
            'request_id'=>'12',
            'date_from'=>'',
            'max_pudo_number'=> '100',
            'max_distance_search'=>'',
            'weight'=>'',
            'category'=>'',
            'holiday_tolerant'=>''
        );

        try
        {
            ini_set('default_socket_timeout', 15);
            $soappudo = new SoapClient($serviceurl);
            $GetPudoList = $soappudo->getPudoList($variables)->GetPudoListResult->any;
            $curl = curl_init();


            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            // Get the webservice XML response and parse its values

            $xml = new SimpleXMLElement($GetPudoList);
            if (Tools::strlen($xml->ERROR) > 0) {
                $jadlog_points['error'] = $this->l('Serviço indisponível no momento.');

            } else {
                $relais_items = $xml->PUDO_ITEMS;
                //prepare coockie
                $i = 0;
                foreach ($relais_items->PUDO_ITEM as $item) {
                    $point = array();
                    $item = (array)$item;

                    $point['relay_id']       = $item['PUDO_ID'];
                    $point['shop_name']      = self::stripAccents($item['NAME']);
                    $point['address1']       = self::stripAccents($item['ADDRESS1']);
                    $point['streetnum']      = self::stripAccents($item['STREETNUM']);
                    if ($item['ADDRESS2'] != '') {
                        $point['address2']   = self::stripAccents($item['ADDRESS2']);
                    }

                    $point['postal_code']    = $item['ZIPCODE'];
                    $point['city']           = self::stripAccents($item['CITY']);
                    $point['id_country']     = $input['id_country'];

                    $point['distance']       = number_format($item['DISTANCE'] / 1000, 2);
                    $point['coord_lat']      = (float)strtr($item['LATITUDE'], ',', '.');
                    $point['coord_long']     = (float)strtr($item['LONGITUDE'], ',', '.');
                    $point['price']          = $this->getShipPrice($valeu,str_replace("-","",$item['ZIPCODE']),$weight);

                    $days = array(1=>'Segunda', 2=>'Terca', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta', 6=>'Sabado', 7=>'Domingo');
                    if (count($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM) > 0) {
                        foreach ($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM as $oh_item) {
                            $oh_item = (array)$oh_item;
                            $point[$days[$oh_item['DAY_ID']]][] = $oh_item['START_TM'].' - '.$oh_item['END_TM'];
                        }
                    }
                    if (count($item['HOLIDAY_ITEMS']->HOLIDAY_ITEM) > 0) {
                        $x = 0;
                    }
                    foreach ($item['HOLIDAY_ITEMS']->HOLIDAY_ITEM as $holiday_item) {
                        $holiday_item = (array)$holiday_item;
                        $point['closing_period'][$x] = $holiday_item['START_DTM'].' - '.$holiday_item['END_DTM'];
                        ++$x;
                    }

                    array_push($jadlog_points, $point);
                    if (++$i == 5) {
                        break;
                    }
                }
            }

        }catch (Exception $e)
        {
            return (string) $e;
        }
        return $jadlog_points;
    }

    public function getShipPrice($vldec,$cepdest,$peso){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://www.jadlog.com.br/JadlogEdiWs/services/ValorFreteBean?method=valorar&vModalidade=40&Password=".Configuration::get("JADLOG_SENHA")."&vSeguro=N&vVlDec=".$vldec."&vVlColeta=&vCepOrig=".Configuration::get("JADLOG_CEP")."&vCepDest=".$cepdest."&vPeso=".$peso."&vFrap=N&vEntrega=D&vCnpj=".Configuration::get("JADLOG_CPF_CNPJ"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $resposta = $this->convertXmlToJson($response,"Jadlog_Valor_Frete");
            $resposta = json_decode($resposta);
            return (float)strtr($resposta->response->code, ',', '.');
        }
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configurações'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Link PUDO'),
                    'name' => 'JADLOG_MY_PUDO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Chave de acesso PUDO'),
                    'name' => 'JADLOG_KEY_PUDO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Nome'),
                    'name' => 'JADLOG_NAME',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Id Jadlog'),
                    'name' => 'JADLOG_ID',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('E-mail Jadlog'),
                    'name' => 'JADLOG_EMAIL',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Login Jadlog'),
                    'name' => 'JADLOG_LOGIN',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Senha JadLog'),
                    'name' => 'JADLOG_SENHA',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Token Embarcador JadLog'),
                    'name' => 'JADLOG_TOKEN',
                    'size' => 64,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Conta Corrente'),
                    'name' => 'JADLOG_CONTACORRENTE',
                    'size' => 64,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Código de Contrato JadLog'),
                    'name' => 'JADLOG_CONTRATO',
                    'size' => 64,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('CPF ou CNPJ'),
                    'name' => 'JADLOG_CPF_CNPJ',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Nome Remetente'),
                    'name' => 'JADLOG_REM',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Endereço'),
                    'name' => 'JADLOG_ENDERECO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Numero'),
                    'name' => 'JADLOG_NUMERO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Bairro'),
                    'name' => 'JADLOG_BAIRRO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Cidade'),
                    'name' => 'JADLOG_CIDADE',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('UF'),
                    'name' => 'JADLOG_UF',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('CEP'),
                    'name' => 'JADLOG_CEP',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'hidden',
                    'label' => $this->l('Carrier ID'),
                    'name' => 'CARRIER_ID',
                    'size' => 20,
                    'required' => false,
                    'is_invisible' => true
                )
            ),

            'submit' => array(
                'title' => $this->l('Salvar'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['JADLOG_ID'] = Configuration::get('JADLOG_ID');
        $helper->fields_value['JADLOG_NAME'] = Configuration::get('JADLOG_NAME');
        $helper->fields_value['JADLOG_LOGIN'] = Configuration::get('JADLOG_LOGIN');
        $helper->fields_value['JADLOG_TOKEN'] = Configuration::get('JADLOG_TOKEN');
        $helper->fields_value['JADLOG_CONTACORRENTE'] = Configuration::get('JADLOG_CONTACORRENTE');
        $helper->fields_value['JADLOG_CONTRATO'] = Configuration::get('JADLOG_CONTRATO');
        $helper->fields_value['JADLOG_KEY_PUDO'] = Configuration::get('JADLOG_KEY_PUDO');
        $helper->fields_value['JADLOG_MY_PUDO'] = Configuration::get('JADLOG_MY_PUDO');
        $helper->fields_value['JADLOG_SENHA'] = Configuration::get('JADLOG_SENHA');
        $helper->fields_value['JADLOG_TOKEN'] = Configuration::get('JADLOG_TOKEN');
        $helper->fields_value['JADLOG_CPF_CNPJ'] = Configuration::get('JADLOG_CPF_CNPJ');
        $helper->fields_value['JADLOG_ENDERECO'] = Configuration::get('JADLOG_ENDERECO');
        $helper->fields_value['JADLOG_REM'] = Configuration::get('JADLOG_REM');
        $helper->fields_value['JADLOG_NUMERO'] = Configuration::get('JADLOG_NUMERO');
        $helper->fields_value['JADLOG_BAIRRO'] = Configuration::get('JADLOG_BAIRRO');
        $helper->fields_value['JADLOG_CIDADE'] = Configuration::get('JADLOG_CIDADE');
        $helper->fields_value['JADLOG_UF'] = Configuration::get('JADLOG_UF');
        $helper->fields_value['JADLOG_CEP'] = Configuration::get('JADLOG_CEP');
        $helper->fields_value['JADLOG_EMAIL'] = Configuration::get('JADLOG_EMAIL');
        $helper->fields_value['CARRIER_ID'] = Configuration::get('JADLOG_REPO');

        return $helper->generateForm($fields_form);
    }

    public function getContent()
    {
        $output = '<h1>'. $this->displayName .'</h1>';
        if(Tools::isSubmit('submit'.$this->name))
        {
            $my_module_name = strval(Tools::getValue('JADLOG_NAME'));
            if( !$my_module_name || empty($my_module_name) || !Validate::isGenericName($my_module_name))
                $output .=$this->displayError =($this->l('Configuração Inválida'));
            else
            {
                Configuration::updateValue('JADLOG_NAME',$my_module_name);
                $output .= $this->displayConfirmation('Configurações Salvas');
            }

            if(!Validate::IsEmail(Tools::getValue('JADLOG_NAME'))){
                Configuration::updateValue('JADLOG_EMAIL',Tools::getValue('JADLOG_EMAIL'));
            }
            Configuration::updateValue('JADLOG_MY_PUDO',Tools::getValue('JADLOG_MY_PUDO'));
            Configuration::updateValue('JADLOG_KEY_PUDO',Tools::getValue('JADLOG_KEY_PUDO'));
            Configuration::updateValue('JADLOG_ID',Tools::getValue('JADLOG_ID'));
            Configuration::updateValue('JADLOG_LOGIN',Tools::getValue('JADLOG_LOGIN'));
            Configuration::updateValue('JADLOG_SENHA',Tools::getValue('JADLOG_SENHA'));
            Configuration::updateValue('JADLOG_TOKEN',Tools::getValue('JADLOG_TOKEN'));
            Configuration::updateValue('JADLOG_CONTACORRENTE',Tools::getValue('JADLOG_CONTACORRENTE'));
            Configuration::updateValue('JADLOG_CONTRATO',Tools::getValue('JADLOG_CONTRATO'));
            Configuration::updateValue('JADLOG_CPF_CNPJ',Tools::getValue('JADLOG_CPF_CNPJ'));
            Configuration::updateValue('JADLOG_ENDERECO',Tools::getValue('JADLOG_ENDERECO'));
            Configuration::updateValue('JADLOG_REM',Tools::getValue('JADLOG_REM'));
            Configuration::updateValue('JADLOG_NUMERO',Tools::getValue('JADLOG_NUMERO'));
            Configuration::updateValue('JADLOG_BAIRRO',Tools::getValue('JADLOG_BAIRRO'));
            Configuration::updateValue('JADLOG_CIDADE',Tools::getValue('JADLOG_CIDADE'));
            Configuration::updateValue('JADLOG_UF',Tools::getValue('JADLOG_UF'));
            Configuration::updateValue('JADLOG_CEP',Tools::getValue('JADLOG_CEP'));
        }
        return $output.$this->displayForm();
    }

    public function uninstall()
    {
        if ( !parent::uninstall()
            || !Configuration::deleteByName('JADLOG_ID')
            || !Configuration::deleteByName('JADLOG_NAME')
            || !Configuration::deleteByName('JADLOG_MY_PUDO')
            || !Configuration::deleteByName('JADLOG_KEY_PUDO')
            || !Configuration::deleteByName('JADLOG_LOGIN')
            || !Configuration::deleteByName('JADLOG_SENHA')
            || !Configuration::deleteByName('JADLOG_TOKEN')
            || !Configuration::deleteByName('JADLOG_CONTACORRENTE')
            || !Configuration::deleteByName('JADLOG_CONTRATO')
            || !Configuration::deleteByName('JADLOG_CPF_CNPJ')
            || !Configuration::deleteByName('JADLOG_ENDERECO')
            || !Configuration::deleteByName('JADLOG_REM')
            || !Configuration::deleteByName('JADLOG_NUMERO')
            || !Configuration::deleteByName('JADLOG_BAIRRO')
            || !Configuration::deleteByName('JADLOG_CIDADE')
            || !Configuration::deleteByName('JADLOG_UF')
            || !Configuration::deleteByName('JADLOG_CEP')
            || !Configuration::deleteByName('JADLOG_EMAIL')
            || !$this->unregisterHooks()
            || !$this->deleteCarriers()
            || !$this->uninstallModuleTab('AdminJadlog')) {
            return false;
        }
        return true;
    }

    public static function stripAccents($str)
    {
        $str = preg_replace('/[\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u', 'A', $str);
        $str = preg_replace('/[\x{0105}\x{0104}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u', 'a', $str);
        $str = preg_replace('/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u', 'C', $str);
        $str = preg_replace('/[\x{00E7}\x{0107}\x{0109}\x{010B}\x{010D}}]/u', 'c', $str);
        $str = preg_replace('/[\x{010E}\x{0110}]/u', 'D', $str);
        $str = preg_replace('/[\x{010F}\x{0111}]/u', 'd', $str);
        $str = preg_replace('/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u', 'E', $str);
        $str = preg_replace('/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u', 'e', $str);
        $str = preg_replace('/[\x{00CC}\x{00CD}\x{00CE}\x{00CF}\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u', 'I', $str);
        $str = preg_replace('/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u', 'i', $str);
        $str = preg_replace('/[\x{0142}\x{0141}\x{013E}\x{013A}]/u', 'l', $str);
        $str = preg_replace('/[\x{00F1}\x{0148}]/u', 'n', $str);
        $str = preg_replace('/[\x{00D2}\x{00D3}\x{00D4}\x{00D5}\x{00D6}\x{00D8}]/u', 'O', $str);
        $str = preg_replace('/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}]/u', 'o', $str);
        $str = preg_replace('/[\x{0159}\x{0155}]/u', 'r', $str);
        $str = preg_replace('/[\x{015B}\x{015A}\x{0161}]/u', 's', $str);
        $str = preg_replace('/[\x{00DF}]/u', 'ss', $str);
        $str = preg_replace('/[\x{0165}]/u', 't', $str);
        $str = preg_replace('/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{016E}\x{0170}\x{0172}]/u', 'U', $str);
        $str = preg_replace('/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}\x{0171}\x{0173}]/u', 'u', $str);
        $str = preg_replace('/[\x{00FD}\x{00FF}]/u', 'y', $str);
        $str = preg_replace('/[\x{017C}\x{017A}\x{017B}\x{0179}\x{017E}]/u', 'z', $str);
        $str = preg_replace('/[\x{00C6}]/u', 'AE', $str);
        $str = preg_replace('/[\x{00E6}]/u', 'ae', $str);
        $str = preg_replace('/[\x{0152}]/u', 'OE', $str);
        $str = preg_replace('/[\x{0153}]/u', 'oe', $str);
        $str = preg_replace('/[\x{0022}\x{0025}\x{0026}\x{0027}\x{00A1}\x{00A2}\x{00A3}\x{00A4}\x{00A5}\x{00A6}\x{00A7}\x{00A8}\x{00AA}\x{00AB}\x{00AC}\x{00AD}\x{00AE}\x{00AF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B4}\x{00B5}\x{00B6}\x{00B7}\x{00B8}\x{00BA}\x{00BB}\x{00BC}\x{00BD}\x{00BE}\x{00BF}]/u', ' ', $str);
        $str = Tools::strtoupper($str);
        return $str;
    }

    public function installCarriers()
    {
        foreach ($this->carriers_list as $carrier_key => $carrier_name)
        {
            $carrier = new Carrier();
            $carrier->name = $carrier_name;
            $carrier->id_tax_rules_group = 0;
            $carrier->active = 1;
            $carrier->deleted = 0;
            foreach (Language::getLanguages(true) as $language)
                $carrier->delay[(int)$language['id_lang']] = 'Prazo '.$carrier_name;
            $carrier->shipping_handling = false;
            $carrier->range_behavior = 0;
            $carrier->is_module = true;
            $carrier->shipping_external = true;
            $carrier->external_module_name = $this->name;
            $carrier->need_range = true;
            if (!$carrier->add())
                return false;

            $groups = Group::getGroups(true);
            foreach ($groups as $group)
                Db::getInstance()->insert('carrier_group',array('id_carrier' => (int)$carrier->id, 'id_group' => (int)$group['id_group']));

            // Create price range
            $rangePrice = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '10000';
            $rangePrice->add();
            // Create weight range
            $rangeWeight = new RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '10000';
            $rangeWeight->add();

            // Associate carrier to all zones
            $zones = Zone::getZones(true);
            foreach ($zones as $zone)
            {
                Db::getInstance()->insert('carrier_zone',array('id_carrier' => (int)$carrier->id, 'id_zone' => (int)$zone['id_zone']));
                Db::getInstance()->insert('delivery',array('id_carrier' => (int)$carrier->id, 'id_range_price' => (int)$rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int)$zone['id_zone'], 'price' => '0'));
                Db::getInstance()->insert('delivery',array('id_carrier' => (int)$carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int)$rangeWeight->id, 'id_zone' => (int)$zone['id_zone'], 'price' => '0'));
            }

            Configuration::updateValue($carrier_key, $carrier->id);
        }
        return true;
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        $precos = array(0);
        if ($params->id_address_delivery) {
            $address=new Address((int) $params->id_address_delivery);
            $cart = new Cart((int) $params->id);
            $calculoCarrinho = Jadlog::calculaPesoValorCarrinho($cart->getProducts());
            $weight = $calculoCarrinho['weight'];
            $value = $calculoCarrinho['value'];
            $address_details = $address->getFields();
            $jadlog_points =  $this->getPoints($address_details,$value,$weight);
            if(is_array($jadlog_points)){
                foreach ($jadlog_points as $point){
                    array_push($precos,$point['price']);
                }
            }
            return (float) max($precos);
        }
    }

    public function getOrderShippingCostExternal($params)
    {
        //return $this->getOrderShippingCost($params, 0);
        return 0;
    }

    /*
    **
    ** Hook update carrier
    ** http://doc.prestashop.com/display/PS14/Carrier+modules+-+functions%2C+creation+and+configuration
    **
    */
    public function hookActionCarrierUpdate($params)
    {
        // Update the id for carriers
        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
            $id = (int)Configuration::get($carrier_key);
            if ((int)($params['id_carrier']) == $id) {
                Configuration::updateValue($carrier_key, (int)($params['carrier']->id));
            }
        }
        return TRUE;
    }

    protected function deleteCarriers()
    {
        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
            $id = (int)Configuration::get($carrier_key);
            $carrier = new Carrier($id);
            $carrier->delete();
        }
        return TRUE;
    }

    private function convertXmlToJson($xml, $type)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $xml = simplexml_load_string($vals[3]['value']);

        $xml = (array) $xml->{$type};

        $response = ["status" => false, "message" => "Falha ao coletar dados"];

        if (isset($xml["ND"]))
            $response = [
                "response" => $xml["ND"],
                "status" => true,
            ];

        if (isset($xml["Retorno"]) && $xml["Retorno"] < 0)
            $response= [
                "status" => false,
                "error" => [
                    "code" => (isset($xml["Retorno"]) ? $xml["Retorno"] : 9999),
                    "message" => (isset($xml["Mensagem"]) ? $xml["Mensagem"] : "Não retornou dados."),
                ]
            ];

        if (isset($xml["Retorno"]) && $xml["Retorno"] > 0)
            $response= [
                "status" => true,
                "response" => [
                    "code" => $xml["Retorno"],
                    "message" => $xml["Mensagem"],
                ]

            ];

        return json_encode($response);
    }


    private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
    {
        $tab = new Tab();

        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = $tab_name;
        }
        $tab->class_name = $tab_class;
        $tab->module = $this->name;
        $tab->id_parent = $id_tab_parent;

        if (!$tab->save()) {
            return false;
        }
        return true;
    }


    private function uninstallModuleTab($tab_class)
    {
        $id_tab = Tab::getIdFromClassName($tab_class);
        if ($id_tab != 0) {
            $tab = new Tab($id_tab);
            $tab->delete();

            return true;
        }
        return false;
    }

    //Função auxiliar para retornar o valor do frete da Jadlog
    //desativada
    public function shippingValue($shippingMethod, $insuranceValue, $senderPostalCode, $recipientPostalCode, $weight, $height, $width, $length, $cnpj = null, $insuranceType = "N", $collectValue = 0)
    {
        $query = [
            'method' => 'valorar',
            'vModalidade' => $shippingMethod, //9,
            'Password' => Configuration::get('JADLOG_SENHA'),
            'vSeguro' => $insuranceType, //Tipo do Seguro ―N  normal ―A  apólice própria
            'vVlDec' => $insuranceValue < 13 ? 13 : $insuranceValue,
            'vVlColeta' => $collectValue,
            'vCepOrig' => $senderPostalCode,
            'vCepDest' => $recipientPostalCode,
            'vPeso' => $this->weightValue($weight, $height, $width, $length, $shippingMethod),
            'vFrap' => 'N',
            'vEntrega' => 'D',
            'vCnpj' => isset($cnpj) && $cnpj ? $cnpj : config('jadlog.CNPJ'),
        ];
        return $this->requestMethod($query, "ValorFreteBean");
    }

    private function requestMethod($query, $method)
    {
        $client = new GuzzleHttp\Client();

        $res = $client->request('GET', $this->url.$method, [
            'query' => $query
        ]);

        switch ($method) {
            case "NotfisBean":
                if($query["method"] == "inserir")
                    $type = "Jadlog_Pedido_eletronico_Inserir";
                else
                    $type = "Jadlog_Pedido_eletronico_Cancelar";
                break;
            case "TrackingBean":
                $type = "Jadlog_Tracking_Consultar";
                break;
            case "ValorFreteBean":
                $type = "Jadlog_Valor_Frete";
                break;
        }

        return $this->convertXmlToJson($res->getBody()->getContents(), $type);

    }

    // desativada
    private function weightValue($weight, $height, $width, $length, $shippingMethod)
    {
        $weight = str_replace(",",".",$weight);
        $shippingMethod = $this->shippingMethods($shippingMethod);
        $cubing_value = $shippingMethod['cubing_value']; //6000 - aereo
        $volume = ($height * $width * $length)/$cubing_value;
        return number_format(round(($volume > $weight ? $volume : $weight ),2),2,",","");

    }

}
