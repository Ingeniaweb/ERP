<?php

	/*Datos de conexion a la base de datos*/
	define('DB_HOST', 'localhost');//DB_HOST:  generalmente suele ser "127.0.0.1"
	define('DB_USER', 'indago');//Usuario de tu base de datos
	define('DB_PASS', 'Indago2018%');//Contraseña del usuario de la base de datos
	define('DB_NAME', 'indago');//Nombre de la base de datos

	$con=@mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	mysqli_set_charset($con,'utf8'); //Evitamos problema de no mostrar las tildes en el PHP
    if(!$con){
       // @die("<h2 style='text-align:center'>Imposible conectarse a la base de datos! </h2>".mysqli_error($con));
         @die("Conexión falló: ".mysqli_connect_errno()." : ". mysqli_connect_error());
    }
    if (@mysqli_connect_errno()) {
        @die("Conexión falló: ".mysqli_connect_errno()." : ". mysqli_connect_error());
    }
?>