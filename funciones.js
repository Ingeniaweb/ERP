/* document javascript*/

function ver(nombre){
    cual=document.getElementById(nombre);
    if(cual.style.display=='none'){
        cual.style.display='block';
    }
    else{
        
        if(cual.dataset.id){
        	if(cual.dataset.id!=''){
        		
                var id='';
                var ele='no';
        		var lugar=location.href;
                var tipo="venta";
                if(lugar.indexOf("/fourn/")>=0){
                    tipo="compra";
                }
                direccion="./dev_op_product.php";
        		 $.ajax({
                        data: { "id" : id, "elegir" : ele, "tipo": tipo },
                        type: "post",
                        url:   direccion, 
                        success:  function (respuesta) { 
                                document.getElementById("idprod").innerHTML=respuesta;
                                //alert(respuesta);
                        }
                     });
                
            }
        }
       // var ruta="https://"+location.hostname+"/indago/product/frame_card_nv.php?&leftmenu=product&action=create&type=0";
        
         //       document.getElementById('fra_new_product').src=ruta;
         document.getElementById('iframe-dentro').innerHTML='<iframe id="fra_new_product" src="../../product/frame_card_nv.php?leftmenu=product&action=create&type=0" style="width: 100%; height: 100%; background: #ffffff;"></iframe>'
        
        cual.style.display='none';
    }
}

//función para activar o desactivar la selección de días en los data para acotar fechas de inicio y fin
function capar_fechas(ini, fin){

    var ffin=$('#'+fin).val();
    var fini=$("#"+ini).val();
    $('#'+ini).attr('max',ffin);
    $('#'+fin).attr('min',fini);

}

