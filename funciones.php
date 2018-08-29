
<?php


//función que devuelve el primer día del mes en curso en formato yyyy-mm-dd
function primer_dia(){
    $primerdia=date('Y-m')."-01";
    return $primerdia;
}
//función que devuelve el último día del mes en curso
function ultimo_dia(){

    $mon = date('m');
    $ye = date('Y');
    $da = date("d", mktime(0,0,0, $mon+1, 0, $ye));
            
    $diaultimo=date('Y-m').'-'.$da;
    return $diaultimo;

}

?>