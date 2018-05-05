{if isset($pontos_pudo[0]['relay_id']) }
<link rel="stylesheet" href="https://openlayers.org/en/v4.5.0/css/ol.css" type="text/css">
<style>
.map {
 height: 180px;
 width: 970px;
}

.horarios{
 font-size: 14px;
 display: none;
 z-index: 100;
 background-color:white;
}

.horarios tr{
 background-color: rgba(40,80,250,0.1) ;
}

.map{
 display: inline-block;
 position: absolute;
}

#cpf{
 margin-top:10px;
}

tr.active{
 font-weight: bold;
 color:rgba(80,180,80,.9);
}
.jadlog_select_button{
 padding: 8px 18px;
 border-radius: 6px;
 margin:10px 20px 0;
 font-size: 14px;
 background-color: #0EDC2E;
 color:white;
 right: 20px;
 float: right;
}

.jadlog_select_button:hover{
 background-color: #0ADCAE;
 color:white !important;
}

.maske{
 position: fixed;
 width: 100vw;
 height: 100vh;
 z-index: 1;
 visibility: hidden;
 background-color: rgba(0,0,10,0.3);
 /* position: sticky; */
 left: 0;
 top: 0;
}

.modales{
 position: fixed;
 width: 1000px;
 max-width: 80%;
 max-height:92vh;
 padding: 15px 10px 25px 15px;
 border-radius: 5px;
 z-index: 2;
 visibility: hidden;
 /* position: sticky; */
 left: 13%;
 background: rgba(255,255,255,1);
 top: 1%;
 overflow: auto;
}

table{
 font-size: 14px;
 text-align: center;
 max-width: 100%;
}

</style>
<!-- The line below is only needed for old environments like Internet Explorer and Android 4.x -->
<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList,URL"></script>
<script src="https://openlayers.org/en/v4.4.2/build/ol.js"></script>
<script src="https://code.jquery.com/jquery-2.2.3.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script type="text/javascript">

</script>
<style>
.marker{
 width: 12px;
 height: 12px;
 border: 1px solid orange;
 border-radius: 10px;
 background-color: orange;
 opacity: 0.9;
}
.vienna {
 text-decoration: none;
 color: blue;
 font-size: 10pt;
 font-weight: bold;
 text-shadow: rgba(255,255,255,0.8) 0.1em 0.1em 0.2em;
}



.block .table{
 margin-top: 180px;
 max-width:96%;
}

#close{
 font-size: 30px;
 position: absolute;
 right: 15px;
 top: 15px;
 color: rgba(150,150,150,1);

}

.modales .footer{
 color: #AAAAAA;
 font-size: 12px;
 text-align: center;
 padding: 10px;
}
</style>


<div class="maske">

</div>
<div class="modales">
 <h3>Selecione o seu Ponto de Coleta e Digite seu CPF</h3>
 <span><a href="javascript;" id="close"> &#x2715 </a></span>
 <div id="map" class="map"></div>
 <div style="display: none;">
   <!-- Clickable label for Vienna -->
   {foreach from=$pontos_pudo item=ponto}
   <a class="vienna pontoponto" id="{$ponto['relay_id']}" target=";" href="javascript;">{$ponto['shop_name']}</a>
   <div id="marker{$ponto['relay_id']}" title="marker" class="marker pontoponto"></div>
   {/foreach}
   <!-- Popup -->
   <div id="popup" title="Map"></div>
 </div>
 <div id="jadlog_block_home" class="block">
   <div class="block-content">
     <table class="table ">
       <thead>
         <tr>
           <th>{l s='' mod='jadlog'}</th>
           <th>{l s='Nome' mod='jadlog'}</th>
           <th>{l s='CEP' mod='jadlog'}</th>
           <th>{l s='Endereço' mod='jadlog'}</th>
           <th>{l s='Horários de Funcionamento' mod='jadlog'}</th>
         </tr>
       </thead>
       <tbody>
         {foreach from=$pontos_pudo item=ponto key=k}
         <tr class="{$ponto['relay_id']}  {if $k eq 0} active {/if}"><label for="{$ponto['relay_id']}">
           <td><input type="radio" class="radio" name="radio" value="{$ponto['relay_id']}" id="mk{$ponto['relay_id']}" {if $k eq 0} checked {/if}></td>
           <td>{$ponto['shop_name']}</td>
           <td>{$ponto['postal_code']}</td>
           <td>{$ponto['address1']} {if isset($ponto['streetnum'])}, {$ponto['streetnum']} {/if} {if isset($ponto['address2'])} - {$ponto['address2']} {/if} / {$ponto['city']} </td>


           <td>
             <a href="javascript;" class="closehorario horarios">fechar</a>
             <a href="" class="verhorario"  data-do="{$ponto['relay_id']}">Ver horários</a>
           </td>
         </label>
       </tr>

       <tr class="horarios">
         <td colspan="6">
           <table class="horarios">
             <tr><td>Seg</td>
               <td>Ter</td>
               <td>Qua</td>
               <td>Qui</td>
               <td>Sex</td>
               <td>Sab</td>
               <td>Dom</td>
               <td><a href="javascript;" class="closehorario">&#x2715</a></td>
             </tr>
             <tr>

               {if isset($ponto['Segunda'])}
               <td>{foreach from=$ponto['Segunda'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}


               {if isset($ponto['Terca'])}
               <td>{foreach from=$ponto['Terca'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}

               {if isset($ponto['Quarta'])}
               <td>{foreach from=$ponto['Quarta'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}
               {if isset($ponto['Quinta'])}
               <td>{foreach from=$ponto['Quinta'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}
               {if isset($ponto['Sexta'])}
               <td>{foreach from=$ponto['Sexta'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}
               {if isset($ponto['Sabado'])}
               <td>{foreach from=$ponto['Sabado'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}
               {if isset($ponto['Domingo'])}
               <td>{foreach from=$ponto['Domingo'] item=dia}
                 {$dia} <br>
                 {/foreach}
               </td>
               {/if}
             </tr>
           </tr>
         </table>
       </td>
     </tr>
     {/foreach}
   </tbody>
 </table>
 <hr>
 <label><strong>Digite seu CPF:</strong> (sem pontos e traços)</label>
 <input type="text" id="cpf">
 <a href="javascript;" class="jadlog_select_button">Selecionar</a>
</div>
<script>

</script>
</div>
</div>

<script type="text/javascript">
  //Só mostra se for a nossa id
  id_carrier_relay_point = {$id_shipping_jad}
  $(document).ready(function(){
    var layer = new ol.layer.Tile({
      source: new ol.source.OSM()
    });

    long ={$pontos_pudo[0]['coord_long']};
    lat ={$pontos_pudo[0]['coord_lat']};
    $('#map').html('');
    let map = new ol.Map({
      layers: [layer],
      target: 'map',
      view: new ol.View({
        center: ol.proj.transform([lat, long], 'EPSG:3857', 'EPSG:4326'),
        zoom: 11
      })
    });


    map.getView().setCenter(ol.proj.transform([long, lat], 'EPSG:4326', 'EPSG:3857'));

    var marker = [];
    {foreach from=$pontos_pudo item=ponto}
    var pos = ol.proj.fromLonLat([{$ponto['coord_long']},{$ponto['coord_lat']}]);
    // Vienna marker
    marker['{$ponto['relay_id']}'] = new ol.Overlay({
      position: pos,
      positioning: 'center-center',
      element: document.getElementById('marker{$ponto["relay_id"]}'),
      stopEvent: false
    });
    map.addOverlay(marker['{$ponto['relay_id']}']);
    // Vienna label
    var {$ponto['relay_id']} = new ol.Overlay({
      position: pos,
      element: document.getElementById('{$ponto["relay_id"]}')
    });
    map.addOverlay({$ponto['relay_id']});
    {/foreach}

    $.wait = function(ms) {
      var defer = $.Deferred();
      setTimeout(function() { defer.resolve(); }, ms);
      return defer;
    };

    $('.step-title').click(function (e){
      e.preventDefault();
      setTimeout(function () {
        map.updateSize();
      },2000);
    });

    $('.maske').click(function(){
      $('.maske').css('visibility','hidden');
      $('.modales').css('visibility','hidden');
    });

    $(':radio').each(function() {
      // Check if the Relay Point carrier is selected
      if (!$(this).val().indexOf(id_carrier_relay_point) && $(this).prop('checked')){
        $('.maske').css('visibility','visible');
        $('.modales').css('visibility','visible');
        {literal}

        {/literal}
      }
    });

    $('#checkout-delivery-step').click(function() {
      setTimeout(map.updateSize(),2000);
    });

    $(':radio').change(function() {
      // Check if the Relay Point carrier is selected
      if (!$(this).val().indexOf(id_carrier_relay_point) && $(this).prop('checked')){
        $('.maske').css('visibility','visible');
        $('.modales').css('visibility','visible');
        map.updateSize();
      }
    });
    $('.pontoponto').click(function(e){
      map.zoom = 5;
      e.preventDefault();
      $('.radio').prop("checked",false);
      valor = $(this).attr('id');
      $('tr').removeClass('active');
      $('.'+valor).addClass('active');
      elemento = $("#mk"+valor);
      elemento.prop('checked',true);
      //Manipulação do mapa
      map.getView().setZoom(15);
      map.getView().setCenter(marker[$(this).attr('id')].getPosition());

    });
    $('.radio').click(function () {
      $('tr').removeClass('active');
      $('.'+$(this).val()).addClass('active');
      map.getView().setZoom(15);
      map.getView().setCenter(marker[$(this).attr('value')].getPosition());
    });

    $('.verhorario').click(function(e) {
      e.preventDefault();
      $('.verhorario').show();
      $(this).hide();
      $('.horarios').hide();
      $(this).parent().find('.horarios').show();
      $(this).parent().parent().next().find('.horarios').show();
      $(this).parent().parent().next().show();
    });

    $('.closehorario').click(function(e) {
      e.preventDefault();
      $('.verhorario').show();
      $('.horarios').hide();
    });

    $('#close').click(function (e) {
      e.preventDefault();
      $('.maske').css('visibility','hidden');
      $('.modales').css('visibility','hidden');
    });

    var array_pudo = [];
    //Seleciona o ponto caso o link seja clicado

    {foreach from=$pontos_pudo item=ponto}
    array_pudo['{$ponto['relay_id']}'] = {
      price: "{$ponto['price']}",
      nome: "{$ponto['shop_name']}",
      endereco: "{$ponto['address1']} {if isset($ponto['streetnum'])}, {$ponto['streetnum']}{/if} {if isset($ponto['address2'])} - {$ponto['address2']}{/if}",
      cep: "{$ponto['postal_code']}",
      cidade: "{$ponto['city']} ",
    }
    {/foreach}

    //Envia o ponto escolhido pro backend
    $('.jadlog_select_button').click(function(e){
      e.preventDefault();
      radio = $(':radio[name=radio]:checked').val();
      ajax_link = "{$ajax_link}";
      cpf = $('#cpf').val();
      if(cpf.length > 10 && cpf.length < 12 ){
        $.ajax({
          type: "GET",
          url: ajax_link +
            "?relay_id=" + radio +
            "&cpf=" + encodeURIComponent(cpf) +
            "&price_shipping=" + encodeURIComponent(array_pudo[radio].price) +
            "&pudo_nome=" + encodeURIComponent(array_pudo[radio].nome) +
            "&pudo_endereco=" +encodeURIComponent( array_pudo[radio].endereco) +
            "&pudo_cep=" + encodeURIComponent(array_pudo[radio].cep) +
            "&pudo_cidade=" + encodeURIComponent(array_pudo[radio].cidade),
          context: document.body,
          dataType: "json"
        }).then(function(result){
          $("#delivery_option_"+id_carrier_relay_point).closest('div.row').find("span.carrier-delay")[0].innerText = result.message;
          $('.maske').css('visibility','hidden');
          $('.modales').css('visibility','hidden');
        });

      } else {
        alert('Informe o CPF corretamente (somente dígitos, sem pontuação).');
      }


    });
  });
</script>
{else}
<style>
.map {
  height: 180px;
  width: 770px;
}

#cpf{
  margin-top:10px;
}

.jadlog_select_button{
  padding: 8px 18px;
  border-radius: 6px;
  margin:10px 20px 0;
  font-size: 14px;
  background-color: #0EDC2E;
  color:white;
  right: 20px;
  float: right;
}

.jadlog_select_button:hover{
  background-color: #0ADCAE;
  color:white !important;
}

.maske{
  position: fixed;
  width: 100vw;
  height: 100vh;
  z-index: 1;
  visibility: hidden;
  background-color: rgba(0,0,10,0.3);
  /* position: sticky; */
  left: 0;
  top: 0;
}

.modales{
  position: fixed;
  width: 800px;
  max-width: 80%;
  padding: 15px 10px 25px 15px;
  border-radius: 5px;
  z-index: 2;
  visibility: hidden;
  /* position: sticky; */
  left: 22%;
  background: rgba(255,255,255,1);
  top: 1%;
  overflow: scroll ;
}

.modales h1{
  color: rgba(220,50,50,1);
  text-align: center;
}

.modales p {
  color: rgba(255,100,100,1);
  text-align: center;
}
</style>
<script src="https://code.jquery.com/jquery-2.2.3.min.js"></script>
<script>
  id_carrier_relay_point = {$id_shipping_jad}
  $(document).ready(function() {
    $('.maske').click(function () {
      $('.maske').css('visibility','hidden');
      $('.modales').css('visibility','hidden');
    });

    $(':radio').each(function () {
                // Check if the Relay Point carrier is selected
                if (!$(this).val().indexOf(id_carrier_relay_point) && $(this).prop('checked')) {
                  $('.maske').css('visibility','visible');
                  $('.modales').css('visibility','visible');
                }
              });

    $(':radio').click(function () {
                // Check if the Relay Point carrier is selected
                if (!$(this).val().indexOf(id_carrier_relay_point) && $(this).prop('checked')) {
                  $('.maske').css('visibility','visible');
                  $('.modales').css('visibility','visible');
                }
              });
  });

</script>

<div class="maske">

  <div class="modales">
    <h1>Não existem pontos de coleta perto de você.</h1>
    <p>Nao existem pontos de coleta perto de você, assim não podemos especificar um local viável onde você possa buscar sua encomenda, favor escolha outro metodo de envio.</p>
  </div>

</div>
{/if}
