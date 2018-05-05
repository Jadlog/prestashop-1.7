<?php

/**
 *
 */
class AdminJadlogController extends ModuleAdminController
{

    public function __construct()
    {
        $this->name = 'Jadlog';
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'A sua encomenda na 1a Classe';

        parent::__construct();

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function display() {

        parent::display();

    }

    public function renderView() {

      $current_shop = (int)Tools::substr(Context::getContext()->cookie->shopContext, 2);
      $fieldlist = array('O.`id_order`', 'O.`id_cart`','O.`date_add`', 'AD.`lastname`', 'AD.`firstname`', 'AD.`postcode`', 'AD.`city`','AD.`address1`','AD.`address2`', 'CL.`iso_code`', 'C.`email`', 'CA.`name`','CA.`shipping_method`','JP.`relay_id`','JP.`cpf`','JP.`id`','JP.`id_customer`','JP.`sent`','JP.`id_cart`','JP.`pudo_endereco`','JP.`pudo_cep`','JP.`pudo_cidade`','JP.`shipment_id`','JP.`server_response`');
      $orders = self::getAllOrders($current_shop, $fieldlist);

      $remetente['codCliente'] =  Configuration::get('JADLOG_ID');
      $remetente['pudo_link'] =  Configuration::get('JADLOG_MY_PUDO');
      $remetente['key_pudo'] =  Configuration::get('JADLOG_KEY_PUDO');
      $remetente['nome'] =  Configuration::get('JADLOG_NAME');
      $remetente['login'] =  Configuration::get('JADLOG_LOGIN');
      $remetente['senha'] =  Configuration::get('JADLOG_SENHA');
      $remetente['cnpjCpf'] =  Configuration::get('JADLOG_CPF_CNPJ');
      $remetente['endereco'] =  Configuration::get('JADLOG_ENDERECO');
      $remetente['token'] =  Configuration::get('JADLOG_TOKEN');
      $remetente['contaCorrente'] =  Configuration::get('JADLOG_CONTACORRENTE');
      $remetente['nrContrato'] =  Configuration::get('JADLOG_CONTRATO');
      $remetente['rem'] =  Configuration::get('JADLOG_REM');
      $remetente['numero'] =  Configuration::get('JADLOG_NUMERO');
      $remetente['bairro'] =  Configuration::get('JADLOG_BAIRRO');
      $remetente['cidade'] =  Configuration::get('JADLOG_CIDADE');
      $remetente['uf'] =  Configuration::get('JADLOG_UF');
      $remetente['cep'] =  Configuration::get('JADLOG_CEP');
      $remetente['email'] =  Configuration::get('JADLOG_EMAIL');
        if(true){
            $this->context->smarty->assign(array(
              'remetente' => $remetente,
              'orders' => $orders,
              'redirect_link' => $this->context->link->getAdminLink('AdminJadlog')
            ));
            $return = $this->context->smarty->fetch(_PS_MODULE_DIR_. 'jadlog/views/templates/admin/sells.tpl');
            return $return;
        }

    }


    public static function getAllOrders($id_shop,$fieldlist)
  {
      if ($id_shop==0) {
          $id_shop='LIKE "%"';
      } else {
          $id_shop='= '.(int) $id_shop;
      }
      $sql='  SELECT  '.implode(', ', $fieldlist).'
            FROM '._DB_PREFIX_.'orders AS O,
                    '._DB_PREFIX_.'carrier AS CA,
                    '._DB_PREFIX_.'customer AS C,
                    '._DB_PREFIX_.'address AS AD,
                    '._DB_PREFIX_.'country AS CL,
                    '._DB_PREFIX_.'jadlog_pickup AS JP
            WHERE   O.id_address_delivery=AD.id_address AND
                    C.id_customer=O.id_customer AND
                    CL.id_country=AD.id_country AND
                    CA.id_carrier=O.id_carrier AND
                    JP.id_cart = O.id_cart AND
                    O.id_carrier = ' . pSQL(Configuration::get('JADLOG_REPO')) . '
            ORDER BY id_order DESC';
      $result=Db::getInstance()->ExecuteS($sql);
      $orders=array();
      if (!empty($result)) {
          foreach ($result as $order) {
            $sql2 = ' SELECT OC.`id_product` ,PD.`reference`, PD.`price`, PD.`width`, PD.`height`,PD.`depth`,PD.`weight`,OC.`quantity` as cart_quantity
            FROM '._DB_PREFIX_.'product AS PD,
                 '._DB_PREFIX_.'cart_product AS OC
            WHERE OC.id_product=PD.id_product AND
            OC.id_cart = '.$order['id_cart'].';';
            $result_objects=Db::getInstance()->ExecuteS($sql2);
            foreach($result_objects as $object){
              $order['products'][] = $object;
            }
            $calculoCarrinho = Jadlog::calculaPesoValorCarrinho($order['products']);
            $order['pesoTotal'] = $calculoCarrinho['weight'];
            $order['valorTotal'] = $calculoCarrinho['value'];
            $orders[]=$order;
          }
      }
      return $orders;
  }

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Pedidos');
        $this->toolbar_title[] = $this->l('Gerenciamento de Encomendas - JADLOG');
    }
}
