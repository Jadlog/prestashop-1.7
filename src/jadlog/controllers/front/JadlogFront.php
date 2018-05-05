<?php
class JadlogJadlogFrontModuleFrontController extends ModuleFrontController {
  public function initContent() {
    parent::initContent();

    $id_cart = (int)$this->context->cookie->id_cart;
    $id_customer = (int)$this->context->cookie->id_customer;
    $relay_id =$_GET['relay_id'];
    $cpf = $_GET['cpf'];
    $price_shipping = (float) $_GET['price_shipping'];
    $pudo_nome = $_GET['pudo_nome'];
    $pudo_endereco = $_GET['pudo_endereco'];
    $pudo_cep = $_GET['pudo_cep'];
    $pudo_cidade = $_GET['pudo_cidade'];
    $relaypoint = self::getRelayPointByCartId($id_cart);
    $id_jadlog_carrier = Db::getInstance()->update(
      'orders',
      array(
        'total_shipping' => str_replace("R$","",$price_shipping)
      ),
      'id_cart = ' . $id_cart,
      'AND id_customer = ' . $id_customer
    );
    // Return result
    if($relaypoint['relay_id'] == Null &&  $relaypoint['id'] < 1 && $relaypoint['id_cart'] < 1){
      $id_jadlog_carrier = Db::getInstance()->insert(
        'jadlog_pickup',
        array(
          'id_cart' => $id_cart,
          'id_customer' => $id_customer,
          'relay_id' => $relay_id,
          'cpf' => $cpf,
          'pudo_nome' => $pudo_nome,
          'pudo_endereco' => $pudo_endereco,
          'pudo_cep' => $pudo_cep,
          'pudo_cidade' => $pudo_cidade
        )
      );
    }
    else {
      $id_jadlog_carrier = Db::getInstance()->update(
        'jadlog_pickup',
        array(
          'id_cart' => $id_cart,
          'id_customer' => $id_customer,
          'relay_id' => $relay_id,
          'cpf' => $cpf,
          'pudo_nome' => $pudo_nome,
          'pudo_endereco' => $pudo_endereco,
          'pudo_cep' => $pudo_cep,
          'pudo_cidade' => $pudo_cidade
        ),
        'id_cart = '.$id_cart
      );
    }
    // Return result
    $response = array(
      "Success" => true,
      "message" => "Local retirada " . $pudo_nome . " (" . $relay_id . ") endereço " . $pudo_endereco . " " . $pudo_cep
    );
    echo json_encode($response);
    exit;
  }

  public static function getRelayPointByCartId($id_cart) {
    $id_jadlog_carrier = Db::getInstance()->getRow('SELECT `id`, `id_customer`,`relay_id`, `id_cart` `cpf`FROM `'._DB_PREFIX_.'jadlog_pickup` WHERE `id_cart` = '.(int)$id_cart);
    return $id_jadlog_carrier;
  }
}
