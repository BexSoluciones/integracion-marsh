<?php

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Modelo\Tabla;
use App\Modelo\LogTable;

class Funciones extends Model {

    public static function TrimVal($val){ return trim($val); }

    public static function TrimArray($array){
        $expl = explode(',', $array); $dataRay = array();
        foreach ($expl as $key => $value) {
            $val = str_replace([' ','"'], '', $value); $resVal = trim($val);
            array_push($dataRay, $resVal);
        }
        return $dataRay;
    }

    public static function getTableColumns($table){
        return Schema::getColumnListing($table);
    }

    public static function deglosarNombre($nombre,$position){
        $name = str_replace("  ", " ", $nombre);
        $nameEXPLODE = explode(" ", $name);

        if (count($nameEXPLODE) == 1) {
            if ($position == 1) { return trim($nameEXPLODE[0]); }else{ return ""; }
        }else if (count($nameEXPLODE) == 2) {
            if ($position == 1) { return trim($nameEXPLODE[0]); }else{ return trim($nameEXPLODE[1]); }
        }else if (count($nameEXPLODE) == 3) {
            if ($position == 1) { return trim($nameEXPLODE[0]); }else{ return trim($nameEXPLODE[1])." ".trim($nameEXPLODE[2]); }
        }else if (count($nameEXPLODE) == 4) {
            if ($position == 1) { return trim($nameEXPLODE[0])." ".trim($nameEXPLODE[1]); }else{ return trim($nameEXPLODE[2])." ".trim($nameEXPLODE[3]); }
        }

    }

    public static function convertirObjetosArrays($objetos){
        $arrayValues = [];  $acumValues = 0;
        foreach ($objetos as $key => $objeto) {
            $arrayValuesRow = [];
            foreach ($objeto as $keyb => $valores) {
                $value = ltrim($valores); $value = rtrim($valores);
                if ($value != '') {
                    $arrayValuesRow[(String) $keyb] = (String) "'".htmlspecialchars($value)."'";
                }else{
                    $arrayValuesRow[(String) $keyb] = (String) "NO";
                }
            }
            $arrayValues[$acumValues] = (array) $arrayValuesRow;
            $acumValues++;
        }
        return $arrayValues;
    }

    public static function convertirObjetosArraysWS($objetos,$tabla){
        $listaColumnas = self::getTableColumns($tabla); $data = [];
        foreach ($listaColumnas as $keya => $column) {
            if ($column != 'codigo' && $column != 'created_at' && $column != 'updated_at') {
                $sum = 1;
                foreach ($objetos as $keyb => $objeto) {
                    $srhColumn = false;
                    foreach ($objeto as $keyc => $valores) {
                        if ($column == $keyc) {
                            $srhColumn = true; $value = ltrim($valores); $value = rtrim($valores);
                            if ($value != '') {
                                $data[$sum][$column] = htmlspecialchars($value);
                            }else{
                                $data[$sum][$column] = "NO";
                            }
                        }
                    }
                    if ($srhColumn == false) { $data[$sum][$column] = "NO"; }
                    $sum++;
                }
            }
        }
        return $data;
    }

    public static function NombreArchivo($consPlano){

        $name = NULL;

        if ($consPlano['seccion_a'] != '') {
            $name .= self::RutaDate($consPlano['seccion_a']);
        }else if($consPlano['seccion_campo_a'] != ''){
            $consPlan = $consPlano['seccion_campo_a'];
            $consTabla = new Tabla; $consTabla->getTable(); $consTabla->bind($consPlan[0]); $resCons = $consTabla->select($consPlan[1])->first();
            $name .= $consTabla[$consPlan[1]];
        }else{ $name .= $consPlano['seccion_default']; }

        if ($consPlano['seccion_b'] != '') {
            $name .= self::RutaDate($consPlano['seccion_b']);
        }else if($consPlano['seccion_campo_b'] != ''){
            $consPlan = $consPlano['seccion_campo_b'];
            $consTabla = new Tabla; $consTabla->getTable(); $consTabla->bind($consPlan[0]); $resCons = $consTabla->select($consPlan[1])->first();
            $name .= $consTabla[$consPlan[1]];
        }else{ $name .= $consPlano['seccion_default']; }

        if ($consPlano['seccion_c'] != '') {
            $name .= self::RutaDate($consPlano['seccion_c']);
        }else if($consPlano['seccion_campo_c'] != ''){
            $consPlan = $consPlano['seccion_campo_c'];
            $consTabla = new Tabla; $consTabla->getTable(); $consTabla->bind($consPlan[0]); $resCons = $consTabla->select($consPlan[1])->first();
            $name .= $consTabla[$consPlan[1]];
        }else{ $name .= $consPlano['seccion_default']; }

        if ($consPlano['seccion_d'] != '') {
            $name .= self::RutaDate($consPlano['seccion_d']);
        }else if($consPlano['seccion_campo_d'] != ''){
            $consPlan = $consPlano['seccion_campo_d'];
            $consTabla = new Tabla; $consTabla->getTable(); $consTabla->bind($consPlan[0]); $resCons = $consTabla->select($consPlan[1])->first();
            $name .= $consTabla[$consPlan[1]];
        }else{ $name .= $consPlano['seccion_default']; }

        $name .= $consPlano['extension'];

        return $name;
    }

    public static function ReplaceText($texto){
        $texto = str_replace(['"',"'",'{','}','[',']','(',')','null','NULL'], "", $texto);
        $texto = trim($texto);
        return $texto;
    }

    public static function RutaDate($campo){

        $camp = $campo;

        if ($campo == 'DD') {
            $camp = date('d');
        }else if ($campo == 'MM') {
            $camp = date('m');
        }else if ($campo == '--AA') {
            $camp = substr(date('Y'), -2);
        }else if ($campo == 'AA') {
            $camp = date('Y');
        }else if ($campo == 'SS') {
            $camp = date('Y-m-d');
            $camp = self::weekOfMonth($camp);
            if ($camp > 5) {
                $camp = 5;
            }
        }

        if (strlen($camp) < 2) { $camp = "0".$camp; }

        return $camp;
    }

    public static function fechaConsulta($type){
        if ($type == "inicio") {
            return date("Y")."-".date("m")."-01";
        }else{
            return date("Y-m-d");
        }
    }

    public static function weekOfMonth($date) {
        //Get the first day of the month.
        $date = strtotime($date);
        $firstOfMonth = strtotime(date("Y-m-01", $date));
        //Apply above formula.
        return self::weekOfYear($date) - self::weekOfYear($firstOfMonth) + 1;
    }

    public static function weekOfYear($date) {
        $weekOfYear = intval(date("W", $date));
        if (date('n', $date) == "1" && $weekOfYear > 51) {
            // It's the last week of the previos year.
            return 0;
        }
        else if (date('n', $date) == "12" && $weekOfYear == 1) {
            // It's the first week of the next year.
            return 53;
        }
        else {
            // It's a "normal" week.
            return $weekOfYear;
       }
    }

    public static function crearTXT($plano,$ruta,$nombreFile,$ftp,$sftp){

        if (!empty($plano)) {

            if (file_exists($ruta)){
                $archivo = fopen($ruta, "a+"); fwrite($archivo, $plano); fclose($archivo);
            }else{
                $archivo = fopen($ruta, "w+"); fwrite($archivo, $plano); fclose($archivo);
            }

            if ($ftp === 1) {
                $exist = Storage::disk('ftp')->exists($nombreFile);
                if (!empty($exist)) {
                    $dataPlan = Storage::disk('ftp')->get($nombreFile); $dataPlan .= $plano;
                    Storage::disk('ftp')->put($nombreFile, $dataPlan);
                }else{
                    Storage::disk('ftp')->put($nombreFile, $plano);
                }
            }

            if ($sftp === 1) {
                $existB = Storage::disk('sftp')->exists($nombreFile);
                if (!empty($existsB)) {
                    $dataPlanB = Storage::disk('sftp')->get($nombreFile); $dataPlanB .= $plano;
                    Storage::disk('sftp')->put($nombreFile, $dataPlanB);
                }else{
                    Storage::disk('sftp')->put($nombreFile, $plano);
                }
            }
        }

    }

    public static function nombreDia($fecha) {
        $dias = array('Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado');
        $fecha = $dias[date('N', strtotime('2022-02-05'))];
        return $fecha;
    }

    public static function caracterEspecialSimbolB($val){
        $char = str_replace(['ñ','Ñ',"'",'?','*','¡','¿','[',']','{','}','^','¬','|','°','!','"','$','%','&','(',')','=',',','_','>','<','@'], ['n','N',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' '], $val);
        return $char;
    }

    public static function diaSemana($dia) {
        if ($dia == 'Lunes') {
            return "1";
        }else if ($dia == 'Martes') {
            return "2";
        }else if ($dia == 'Miercoles') {
            return "3";
        }else if ($dia == 'Jueves') {
            return "4";
        }else if ($dia == 'Viernes') {
            return "5";
        }else if ($dia == 'Sabado') {
            return "6";
        }else if ($dia == 'Domingo') {
            return "7";
        }
    }

    public static function diaVisita($dia) {
        if ($dia == 'Lunes') {
            return "1000000";
        }else if ($dia == 'Martes') {
            return "0100000";
        }else if ($dia == 'Miercoles') {
            return "0010000";
        }else if ($dia == 'Jueves') {
            return "0001000";
        }else if ($dia == 'Viernes') {
            return "0000100";
        }else if ($dia == 'Sabado') {
            return "0000010";
        }else if ($dia == 'Domingo') {
            return "0000001";
        }
    }

    // CREA UNA ESTRUCTURA XML CON LOS DATOS DE CONEXION Y CONSULTA DE LA BD PARA REALIZAR CIERTA CONSULTA
    public static function consultaStructuraXML($empresa,$cia,$proveedor,$usuario,$clave,$sentencia,$idConsulta,$printError,$cacheWSDL){
        $parm['printTipoError'] = $printError;
        $parm['cache_wsdl'] = $cacheWSDL;
        $parm['pvstrxmlParametros'] = "<Consulta>
                                            <NombreConexion>" . $empresa . "</NombreConexion>
                                            <IdCia>" . $cia . "</IdCia>
                                            <IdProveedor>" . $proveedor . "</IdProveedor>
                                            <IdConsulta>" . $idConsulta . "</IdConsulta>
                                            <Usuario>" . $usuario . "</Usuario>
                                            <Clave>" . $clave . "</Clave>
                                            <Parametros>
                                                <Sql>".$sentencia."</Sql>
                                            </Parametros>
                                        </Consulta>";
        return $parm;
    }

    // EJECUTA CONEXION SOAP CON LA URL DE CONEXION CONSULTADA Y LA ESTRUCTURA XML CREADA ANTERIORMENTE
    public static function SOAP($url, $parametro, $table){
        $terminar = 1;
        do{
            try {
                $client = new \SoapClient($url, $parametro);
                $result = $client->EjecutarConsultaXML($parametro)->EjecutarConsultaXMLResult->any;
                $any = simplexml_load_string($result);
                if (@is_object($any->NewDataSet->Resultado))
                {
                    return Funciones::convertirObjetosArrays($any->NewDataSet->Resultado);
                }
                else
                {
                    $terminar = 0;
                }

                if (@$any->NewDataSet->Table) {
                    foreach ($any->NewDataSet->Table as $key => $value) {
                        echo ("\n");
                        echo ("\n Error Linea:\t " . $value->F_NRO_LINEA);
                        echo ("\n Error Value:\t " . $value->F_VALOR);
                        echo ("\n Error Desc:\t " . $value->F_DETALLE);
                    }
                }
            }catch (\Exception $e){

                $error = self::errorSOAP($e->getMessage());
                if ($error == true)
                {
                    $reg = new LogTable; $reg->descripcion =  '´'.$table.'´ => '.$e->getMessage();
                    if ($reg->save())
                    {
                        $terminar = 0;
                    }
                    else
                    {
                        echo '´'.$table.'´ => Excepción capturada: ', $e->getMessage(), "\n";
                    }
                }

            }
        }while($terminar != 0);
    }

    // EJECUTA CONEXION SOAP CON LA URL DE CONEXION CONSULTADA Y LA ESTRUCTURA XML CREADA ANTERIORMENTE
    public static function SOAP_SAVE($url, $parametro, $table){
        $terminar = 1;
        do{
            try {
                $client = new \SoapClient($url, $parametro);
                $result = $client->EjecutarConsultaXML($parametro)->EjecutarConsultaXMLResult->any;
                $any = simplexml_load_string($result);
                if (@is_object($any->NewDataSet->Resultado))
                {
                    return Funciones::convertirObjetosArraysWS($any->NewDataSet->Resultado,$table);
                }
                else
                {
                    $terminar = 0;
                }

                if (@$any->NewDataSet->Table)
                {
                    foreach ($any->NewDataSet->Table as $key => $value) {
                        echo ("\n");
                        echo ("\n Error Linea:\t " . $value->F_NRO_LINEA);
                        echo ("\n Error Value:\t " . $value->F_VALOR);
                        echo ("\n Error Desc:\t " . $value->F_DETALLE);
                    }
                }
            }catch (\Exception $e){
                $error = self::errorSOAP($e->getMessage());
                if ($error == true) {
                    $reg = new LogTable; $reg->descripcion =  '´'.$table.'´ => '.$e->getMessage();
                    if ($reg->save()) { $terminar = 0; }else{ echo '´'.$table.'´ => Excepción capturada: ', $e->getMessage(), "\n"; }
                }

            }
        }while($terminar != 0);
    }

    public static function errorSOAP($error){
        if ($error == 'Server was unable to process request. ---> Error al conectarse a la base de datos.Timeout expired.  The timeout period elapsed prior to obtaining a connection from the pool.  This may have occurred because all pooled connections were in use and max pool size was reached.' || strpos($error, "SOAP-ERROR: Parsing WSDL: Couldn't load from") != false || $error == 'Error Fetching http headers' || $error == 'Server was unable to process request. ---> El parámetro Sql es obligatorio y no existe en la lista de parámetros.') {
            return false;
        }else{ return true; }
    }

    public static function ParametroSentencia($consulta,$conexion,$artisan,$busquedAlterna,$consecutivoDat){
        $criterio = explode(',', $consulta->criterio);
        if ($artisan == true)
        {
            $top = $consulta->top_tabla;
        }
         else
        {
            $top = $consulta->top;
        }

        if ($busquedAlterna == true)
        {
            $sentencia = str_replace('@Cia', $conexion->cia, $consulta->sentencia_alterna);
        }
        else
        {
            $sentencia = str_replace('@Cia', $consulta->cia, $consulta->sentencia);
        }

        if ($consecutivoDat != null)
        {
            $sentencia = str_replace('@tipoDoc', $consecutivoDat->tipo_documento, $sentencia);
            $sentencia = str_replace('@conseDoc', $consecutivoDat->consecutivo, $sentencia);
            $sentencia = str_replace('@conseItem', $consecutivoDat->consecutivo_b, $sentencia);
        }

        $sentencia = str_replace('@fechaInicio', $consulta->fechaInicio, $sentencia);
        $sentencia = str_replace('@fechaFin', $consulta->fechaFin, $sentencia);

        $sentencia = str_replace('@top', $top, $sentencia);
        $sentencia = str_replace('@desdeItems', $consulta->desde_items, $sentencia);
        $sentencia = str_replace('@idPlan', $consulta->id_plan, $sentencia);
        $sentencia = str_replace('@idCriterio', $consulta->criterio, $sentencia);
        $sentencia = str_replace('@idCriterioAlterno', $consulta->criterio_alterno, $sentencia);//nuevo
        $sentencia = str_replace('@buscar_campo', $consulta->buscar_campo, $sentencia);
        $sentencia = str_replace('@sentencia_alterna', $consulta->sentencia_alterna, $sentencia);
        $sentencia = str_replace('@idEstadoActivo', 1, $sentencia);
        $sentencia = str_replace('@idEstado', 1, $sentencia);
        $sentencia = str_replace('@idValTercero', 1, $sentencia);
        $sentencia = str_replace('@idClaseImpuesto', 1, $sentencia);
        return $sentencia;
    }

    public static function caracterEspecial($val){
        $char = str_replace(['\u00f1','\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1','&amp;'], ['ñ','á','é','í','ó','ú','Á','É','Í','Ó','Ú','Ñ','&'], $val);
        return $char;
    }

    public static function caracterEspecialSimbol($val){
        $char = str_replace(['ñ','Ñ',"'",'?','/','*','¡','¿','[',']','{','}','^','¬','|','°','!','"','$','%','&','(',')','=',',','_','>','<','@'], ['n','N',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' '], $val);
        return $char;
    }

    public static function condicionPlano($planoFuncion,$valueB,$name_us,$consPlano){

        $nombreDia = self::nombreDia($valueB);
        $diaSemana = self::diaSemana($nombreDia);
        $diaName = self::diaVisita($nombreDia);

        if($planoFuncion->tipo == 'fecha_a'){
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($diaSemana, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $diaSemana.$consPlano['separador']; }
        }elseif($planoFuncion->tipo == 'fecha_b'){
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($diaName, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $diaName.$consPlano['separador']; }
        }elseif($planoFuncion->tipo == 'fecha_c'){
            $fech = str_replace('T', ' ', $valueB); $fech = substr($fech, 0, 19);
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($fech, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $fech.$consPlano['separador']; }
        }elseif($planoFuncion->tipo == 'agregar_cero'){
            $valret = "0".$valueB;
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($valret, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $valret.$consPlano['separador']; }
        }elseif($planoFuncion->tipo == 'remove'){
            $valret =  str_replace($planoFuncion->nombre, '', $valueB);
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($valret, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $valret.$consPlano['separador']; }

        }elseif($planoFuncion->tipo == 'ultimos_dos'){
            $valremov =  substr($valueB, -2);
            if ($valremov == '00' || $valremov == '0') { $valremov = '01';  }
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($valremov, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $valremov.$consPlano['separador']; }

        }elseif($planoFuncion->tipo == 'exploy_name_a'){

            $nameExploy = self::deglosarNombre($valueB,1);
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($nameExploy, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $nameExploy.$consPlano['separador']; }

        }elseif($planoFuncion->tipo == 'exploy_name_b'){

            $nameExploy = self::deglosarNombre($valueB,2);
            if ($planoFuncion->tipo_campo == 'texto') {
                return " ".$consPlano['entre_columna'].str_pad($nameExploy, $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
            }else{ return $nameExploy.$consPlano['separador']; }
        }else{
            return false;
        }

    }

}
