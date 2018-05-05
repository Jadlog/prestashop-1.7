
<div id="jadlog_block_home" class="block">
  <style media="screen">
    tr td a.beautybutton{
      color: white;
      background-color: rgba(0,200,250,.8);
      padding: 6px;
      margin: 3px !important;
      border-radius: 4px;
      display: inline-block;
      margin: 7px;
    }
    .beautygreen{
      color: rgba(0,130,20,1);
    }

    tr td a.beautybutton:hover{
      color: white;
      background-color: rgba(0,200,120,1);
      padding: 6px;
      text-decoration: none;
      border-radius: 4px;
      margin: 7px;
    }
    td{
      padding: 4px;
    }
  </style>

    <div class="block-content">
    <table class="table ">
        <thead>
            <tr>
                <th>{l s='Id' mod='jadlog'}</th>
                <th>{l s='Data de compra' mod='jadlog'}</th>
                <th>{l s='Recebedor' mod='jadlog'}</th>
                <th>{l s='Serviço' mod='jadlog'}</th>
                <th>{l s='Pudo Destino' mod='jadlog'}</th>
                <th>{l s='Endereço' mod='jadlog'}</th>
                <th>{l s='CEP' mod='jadlog'}</th>
                <th>{l s='Cidade' mod='jadlog'}</th>
                <th>{l s='ShipmentId' mod='jadlog'}</th>
                <th>{l s='Server response' mod='jadlog'}</th>
                <th>{l s='Enviar' mod='jadlog'}</th>
            </tr>
        </thead>
        <tbody>
          {foreach from=$orders item=order key=k}
            <tr>
              <td>{$order.id_order|escape:'htmlall':'UTF-8'}</td>
              <td>{$order.date_add|date_format}</td>
              <td>{$order.firstname} {$order.lastname}</td>
              <td>{$order.name} / {$order.shipping_method}</td>
              <td>{$order.relay_id}</td>
              <td>{$order.pudo_endereco}</td>
              <td>{$order.pudo_cep}</td>
              <td>{$order.pudo_cidade}</td>
              <td>{$order.shipment_id}</td>
              <td>{$order.server_response}</td>
              <td>
                {if $order.sent eq 0}
                <form class="" action="../modules/jadlog/ajax.php" method="post">
                  <input type="hidden" name="redirect_link" value={$redirect_link}>
                  <input type="hidden" name="codCliente" value={$remetente.codCliente} >
                  <input type="hidden" name="conteudo" value="Mercadorias">
                  <input type="hidden" name="pedido" value="{$order.id_order}">
                  <input type="hidden" name="totPeso" value="{$order.pesoTotal}">
                  <input type="hidden" name="totValor" value="{$order.valorTotal}">
                  <input type="hidden" name="obs" value="">
                  <input type="hidden" name="modalidade" value="40">
                  <input type="hidden" name="contaCorrente" value="{$remetente.contaCorrente}">
                  <input type="hidden" name="centroCusto" value="">
                  <input type="hidden" name="tpColeta" value="K">
                  <input type="hidden" name="cdPickupOri" value="">
                  <input type="hidden" name="cdPickupDes" value="{$order.relay_id}">
                  <input type="hidden" name="tipoFrete" value="0">
                  <input type="hidden" name="cdUnidadeDes" value="">
                  <input type="hidden" name="vlColeta" value="">
                  <input type="hidden" name="nrContrato" value="{$remetente.nrContrato}">
                  <input type="hidden" name="servico" value="">
                  <input type="hidden" name="shipmentId" value="">
                  <input type="hidden" name="remnome" value="{$remetente.nome}">
                  <input type="hidden" name="remcnpjCpf" value="{$remetente.cnpjCpf}">
                  <input type="hidden" name="remie" value="{$remetente.rem}">
                  <input type="hidden" name="remendereco" value="{$remetente.endereco}">
                  <input type="hidden" name="remnumero" value="{$remetente.numero}">
                  <input type="hidden" name="remcompl" value="">
                  <input type="hidden" name="rembairro" value="{$remetente.bairro}">
                  <input type="hidden" name="remcidade" value="{$remetente.cidade}">
                  <input type="hidden" name="remuf" value="{$remetente.uf}">
                  <input type="hidden" name="remcep" value="{$remetente.cep}">
                  <input type="hidden" name="remfone" value="">
                  <input type="hidden" name="remcel" value="">
                  <input type="hidden" name="rememail" value="{$remetente.email}">
                  <input type="hidden" name="remcontato" value="">
                  <input type="hidden" name="desnome" value="{$order.firstname} {$order.lastname}">
                  <input type="hidden" name="descnpjCpf" value="{$order.cpf}">
                  <input type="hidden" name="desie" value="">
                  <input type="hidden" name="desendereco" value="{$order.address1}">
                  <input type="hidden" name="desnumero" value="">
                  <input type="hidden" name="descompl" value="">
                  <input type="hidden" name="desbairro" value="{$order.address2}">
                  <input type="hidden" name="descidade" value="{$order.city}">
                  <input type="hidden" name="desuf" value="">
                  <input type="hidden" name="descep" value="{$order.postcode}">
                  <input type="hidden" name="desfone" value="">
                  <input type="hidden" name="descel" value="">
                  <input type="hidden" name="desemail" value="{$order.email}">
                  <input type="hidden" name="descontato" value="">
                  <input type="hidden" name="dfecfop" value="6909">
                  <input type="hidden" name="dfedanfeCte" value="">
                  <input type="hidden" name="dfenrDoc" value="DECLARACAO">
                  <input type="hidden" name="dfeserie" value="">
                  <input type="hidden" name="dfetpDocumento" value="2">
                  {assign var=i value=0}
                  {foreach from=$order.products item=product key=k}
                    {for $n=1 to $product.cart_quantity}
                      <input type="hidden" name="volumealtura[{$i}]" value='{$product.height}'>
                      <input type="hidden" name="volumecomprimento[{$i}]" value="{$product.depth}">
                      <input type="hidden" name="volumeidentificador[{$i}]" value="{$product.reference}">
                      <input type="hidden" name="volumelargura[{$i}]" value="{$product.width}">
                      <input type="hidden" name="volumepeso[{$i}]" value="{$product.weight}">
                      {assign var=i value=$i+1}
                    {/for}
                  {/foreach}
                  <input type="hidden" name="quantidade_volumes" value="{$i}">
                  <input type="hidden" name="jadlog_pickup_id" value="{$order.id}">
                  <button type="submit" class="beautybutton" id="">Enviar</a>
                </form>
                {else}
                <span class="beautygreen">Enviado</span></td>
                {/if}
            </tr>
            {/foreach}
        </tbody>
    </table>
    </div>
</div>
