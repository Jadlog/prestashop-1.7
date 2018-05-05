<?php
require_once(realpath(dirname(__FILE__).'/../../config/config.inc.php'));
require_once(realpath(dirname(__FILE__).'/../../init.php'));
require_once(dirname(__FILE__).'/jadlog.php');


//$dados = Tools::getValue('codCliente');

$value = curl_init();

curl_setopt_array($value, array(
  CURLOPT_URL => "https://viacep.com.br/ws/".Tools::getValue("descep")."/json/",
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

$response = curl_exec($value);
$err = curl_error($value);

curl_close($value);

$dados = json_decode($response);

$volumes = '';
$vol_altura = Tools::getValue('volumealtura');
$vol_comprimento = Tools::getValue('volumecomprimento');
$vol_identificador = Tools::getValue('volumeidentificador');
$vol_largura = Tools::getValue('volumelargura');
$vol_peso = Tools::getValue('volumepeso');
for ($i = 0; $i < (int)Tools::getValue('quantidade_volumes'); $i++) {
  $volumes .= '
    {
      "altura": ' . $vol_altura[$i] . ',
      "comprimento": ' . $vol_comprimento[$i] . ',
      "identificador": null,
      "lacre": null,
      "largura": ' . $vol_largura[$i] . ',
      "peso": ' . $vol_peso[$i] . '
    },';
}
$volumeOut = rtrim($volumes, ",");

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://www.jadlog.com.br/embarcador/api/pedido/incluir",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>
"{
  \"codCliente\": ".Tools::getValue('codCliente').",
  \"conteudo\": \"".Tools::getValue('conteudo')."\",
  \"pedido\": \"".Tools::getValue('pedido')."\",
  \"totPeso\": ".Tools::getValue('totPeso').",
  \"totValor\": ".(int)Tools::getValue('totValor').",
  \"obs\": \"".Tools::getValue('obs')."\",
  \"modalidade\": 40,
  \"contaCorrente\": \"".Tools::getValue('contaCorrente')."\",
  \"centroCusto\": null,
  \"tpColeta\": \"K\",
  \"cdPickupOri\": null,
  \"cdPickupDes\": \"".Tools::getValue('cdPickupDes')."\",
  \"tipoFrete\": 0 ,
  \"cdUnidadeDes\": null,
  \"vlColeta\" : null,
  \"nrContrato\": ".Tools::getValue('nrContrato').",
  \"servico\": ".(int)Tools::getValue('servico').",
  \"shipmentId\": null,
  \"rem\": {
      \"nome\": \"".Tools::getValue('remnome')."\",
      \"cnpjCpf\": \"".Tools::getValue('remcnpjCpf')."\",
      \"ie\": null,
      \"endereco\": \"".Tools::getValue('remendereco')."\",
      \"numero\": \"".Tools::getValue('remnumero')."\",
      \"compl\": \"".Tools::getValue('remcompl')."\",
      \"bairro\": \"".Tools::getValue('rembairro')."\",
      \"cidade\": \"".Tools::getValue('remcidade')."\",
      \"uf\": \"".Tools::getValue('remuf')."\",
      \"cep\": \"".Tools::getValue('remcep')."\",
      \"fone\": \"".Tools::getValue('remfone')."\",
      \"cel\": \"".Tools::getValue('remcel')."\",
      \"email\": \"".Tools::getValue('rememail')."\",
      \"contato\": \"".Tools::getValue('remcontato')."\"
  },
  \"des\": {
      \"nome\": \"".Tools::getValue('desnome')."\",
      \"cnpjCpf\": \"".Tools::getValue('descnpjCpf')."\",
      \"ie\": \"\",
      \"endereco\": \"".Tools::getValue('desendereco')."\",
      \"numero\": \"".Tools::getValue('desnumero')."\",
      \"compl\": null,
      \"bairro\": \"".Tools::getValue('desbairro')."\",
      \"cidade\": \"".Tools::getValue('descidade')."\",
      \"uf\": \"".$dados->uf."\",
      \"cep\": \"".str_replace("-","",Tools::getValue('descep'))."\",
      \"fone\": \"".Tools::getValue('desfone')."\",
      \"cel\": \"".Tools::getValue('descel')."\",
      \"email\": \"".Tools::getValue('desemail')."\",
      \"contato\": \"".Tools::getValue('descontato')."\"
  },
  \"dfe\": [
    {
      \"cfop\": \"".Tools::getValue('dfecfop')."\",
      \"danfeCte\": null,
      \"nrDoc\": \"".Tools::getValue('dfenrDoc')."\",
      \"serie\": null,
      \"tpDocumento\":".Tools::getValue('dfetpDocumento').",
      \"valor\": ".(float)Tools::getValue('dfevalor')."
    }
  ],
  \"volume\": [
" . $volumeOut . "
  ]
}",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer ".Configuration::get('JADLOG_TOKEN'),
    "cache-control: no-cache",
    "content-type: application/json"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

$response = json_decode($response);

if ($err) {
  echo "cURL Error #:" . $err;
  exit;
}
else {
  $update_data = [];
  if(isset($response->erro)) {
    $update_data = array(
      'sent' => 0,
      'server_response' => 'Status: ' . $response->status . ' / Descrição: ' . $response->erro->descricao
    );
  }
  elseif(isset($response->codigo)) {
    $update_data = array(
        'sent' => 1,
        'shipment_id' => $response->shipmentId,
        'server_response' => $response->status
    );
  }
  $id_jadlog_carrier = Db::getInstance()->update(
    "jadlog_pickup",
    $update_data,
    "id = ".Tools::getValue('jadlog_pickup_id')
  );
?>
<html>
<style media="screen">

  body{
    padding: 0;
    margin: 0;
    font-family: sans-serif;
  }
  .mask{
    background-color: rgba(150,150,150,0.7);
    height:100vh;
    width: 100vw;
    margin:0;
    padding-top:150px;
  }

  h1{
    color:rgba(50,50,50,0.8);
  }

  .modal{
    background-color: white;
    width: 300px;
    height: 200px;
    margin: 0 auto;
    box-shadow: 3px 3px 3px rgba(30,30,30,0.7);
    border-radius: 10px;
    text-align: center;
  }

  a{
    text-align: center;
    text-decoration: none;
    padding: 7px 15px;
    border-radius: 5px;
    color:white;
    background-color: rgba(50,160,130,1);
  }

  a:hover{
    text-align: center;
    text-decoration: none;
    padding: 7px 15px;
    border-radius: 5px;
    color:white;
    background-color: rgba(50,160,190,1);3
  }
</style>
<div class="mask">
  <div class="modal">
      <h1><?= $response->status?></h1>
      <?php if(isset($response->erro)){
          echo $response->erro->descricao."<br><br>";
      } ?>
      <a href="<?php echo Tools::getValue('redirect_link');?>">Voltar</a>
  </div>

</div>
</html>
<?php
}
?>