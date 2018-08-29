<?php
include_once "config.php";

$id=$_REQUEST['id'];
$elegir=$_REQUEST['elegir'];
$tipo=$_REQUEST['tipo'];
if($elegir=='no'){
	$id='';
}
$condicion='';
if($tipo=='compra'){
	$condicion=" WHERE tosell=1 ";
}else if($tipo=='venta'){
	$condicion="WHERE tobuy=1 ";
}

$consulta="SELECT * FROM  llx_product $condicion ORDER BY label";
$result=mysqli_query($con,$consulta);

$devuelve="";
$sel="";
if($id==''){
	$sel=" selected";
}
$devuelve.="<option value='' $sel></option>";

while($v=mysqli_fetch_array($result)){
	$sel='';
	if($v['rowid']==$id) {
		$sel=' selected';
	}
	$devuelve.="<option value='".$v['rowid']."' data-price='".round($v['price'],2)."' $sel>".$v['ref']." - ".$v['label']." - ".number_format($v['price'],2,',','.')."</option>";
}

echo $devuelve;





?>
