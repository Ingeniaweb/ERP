<?php
/* CONEXION A BD DEBIDO A QUE NO SE CONSIGUE QUE FUNCIONE CON UN INCLUDE */
	/*Datos de conexion a la base de datos*/
	define('DB_HOST', 'www.e-ingenia.es');//DB_HOST:  generalmente suele ser "127.0.0.1"
	define('DB_USER', 'gelagri');//Usuario de tu base de datos
	define('DB_PASS', 'Gelagri2018%');//Contraseña del usuario de la base de datos
	define('DB_NAME', 'gelagri');//Nombre de la base de datos

	$con=@mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	mysqli_set_charset($con,'utf8'); //Evitamos problema de no mostrar las tildes en el PHP
    if(!$con){
       // @die("<h2 style='text-align:center'>Imposible conectarse a la base de datos! </h2>".mysqli_error($con));
         @die("Conexión falló: ".mysqli_connect_errno()." : ". mysqli_connect_error());
    }
    if (@mysqli_connect_errno()) {
        @die("Conexión falló: ".mysqli_connect_errno()." : ". mysqli_connect_error());
    }



if(isset($_REQUEST['proveedor'])){
	$proveedor=$_REQUEST['proveedor'];
	$proveedor = explode("(", $proveedor);
	$proveedor = explode(")", $proveedor[1]);
	$sql="SELECT mode_reglement_supplier, cond_reglement_supplier, fk_shipping_method FROM llx_societe where name_alias like '$proveedor[0]'";
	$result= mysqli_query($con, $sql);
		if (!$result) {
		    print "Error: ".mysqli_error($con)."[ $sql]";
		}
		$fila = mysqli_fetch_assoc($result);
		$_SESSION['cond_pago'] = $fila['cond_reglement_supplier'];
		$_SESSION['forma_pago'] =$fila['mode_reglement_supplier'];
		$_SESSION['envio'] = $fila['fk_shipping_method'];
		$json = array("forma_pago" => $fila['mode_reglement_supplier'], "cond_pago" => $fila['cond_reglement_supplier'], "envio" => $fila['fk_shipping_method']);
		echo json_encode($json);	
}
else{
	$proveedor=$_REQUEST['cliente'];
	$proveedor = explode("(", $proveedor);
	$proveedor = explode(")", $proveedor[1]);	
	$sql="SELECT mode_reglement, cond_reglement FROM llx_societe where name_alias like '$proveedor[0]'";
	$result= mysqli_query($con, $sql);
		if (!$result) {
		    print "Error: ".mysqli_error($con)."[ $sql]";
		}
		$fila = mysqli_fetch_assoc($result);
		$_SESSION['cond_pago'] = $fila['cond_reglement'];
		$_SESSION['forma_pago'] =$fila['mode_reglement'];
		$_SESSION['envio'] = 0;		
		$json = array("forma_pago" => $fila['mode_reglement'], "cond_pago" => $fila['cond_reglement']);
		echo json_encode($json);	
}


?>