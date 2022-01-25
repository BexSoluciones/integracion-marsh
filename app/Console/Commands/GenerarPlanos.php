<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Modelo\Consulta;
use App\Modelo\Plano;
use App\Modelo\Funciones;
use App\Modelo\Tabla;
use App\Modelo\Formato;
use App\Modelo\PlanoFuncion;
use App\Modelo\CampoQuemado;

class GenerarPlanos extends Command
{
    protected $signature = 'integracion:generar-planos';
    protected $description = 'Generar planos de tablas registradas';

    public function __construct(){
        parent::__construct();
    }

    public function handle(){
        $listaConsulta = Consulta::where('estado',1)->get();
        foreach ($listaConsulta as $value) {
            
            $consPlano = Plano::where('codigo',$value->id_plano)->first();
            $consPlanoFuncion = PlanoFuncion::where('id_consulta',$value->codigo)->get();
            $consCampoQuemado = CampoQuemado::where('id_consulta',$value->codigo)->get();
            $consFormato = Formato::where('id_consulta',$value->codigo)->first();
            $consTabla = new Tabla; $consTabla->getTable(); $consTabla->bind($value->tabla_destino); 
            $resCons = $consTabla->where('planoRegistro',0)->orderBy($value->orderBy,$value->orderType)->get();

            $dataPlan = null; $name_us = null;
            foreach ($resCons as $keya => $valueA) {                
                $suma = 0; $array = explode(",", $valueA); 
                if ($consPlano['display_codigo'] == 0) { $sum = 1; }else{ $sum = 2; }     

                if (count($array) > 0) {
                    foreach ($array as $keyb => $valueB) {                  
                        if ($sum != 1) {

                            $valueB = Funciones::caracterEspecial($valueB);
                            $campoDpl = true; $pos = strpos($valueB, ':'); $pos++;
                            $valueB = substr($valueB, $pos); $valueB = Funciones::ReplaceText($valueB);
                
                            $tipo = explode(",", $consFormato['tipo']); $longitud = explode(",", $consFormato['longitud']); 
                            
                            echo "<br>VALUE FOREACH:".$valueB." | ".$suma." | <br>";
                            echo "STATE A: $campoDpl <br>";

                            if ($valueB == 'NO') { $valueB = ''; }

                            // CAMPOS CON FUNCIONES ESPECIFICAS
                            foreach ($consPlanoFuncion as $planoFuncion) {
                                if ($planoFuncion->posicion == $suma) {                                
                                    if($planoFuncion->tipo == 'name_us'){
                                        $name_us = $valueB;
                                    }elseif($planoFuncion->tipo == 'buscar_codigo'){ 
                                        $campoDpl = false; 
                                        $tablaBuscar = Consulta::where('codigo',$planoFuncion->consulta)->first();
                                        $buscarTabla = new Tabla; $buscarTabla->getTable(); $buscarTabla->bind($tablaBuscar['tabla_destino']); $resBusc = $buscarTabla->where($planoFuncion->nombre,$valueB)->first();     
                                        if ($planoFuncion->tipo == 'texto') {
                                            $dataPlan .= " ".$consPlano['entre_columna'].str_pad($resBusc['codigo'], $planoFuncion->longitud).$consPlano['entre_columna'].$consPlano['separador'];
                                        }else{ $dataPlan .= $resBusc['codigo'].$consPlano['separador']; }
                                    }else{
                                        $dataPlan .= Funciones::condicionPlano($planoFuncion,$valueB,$name_us,$consPlano);
                                        if ($dataPlan != false) { $campoDpl = false; }
                                    }
                                }
                            }

                            echo "STATE B: $campoDpl <br>";

                            // CAMPOS QUEMADOS
                            if ($campoDpl == true) {
                                foreach ($consCampoQuemado as $campoQuemado) {
                                    if ($campoQuemado->posicion == $suma) {
                                        $campoDpl = false; echo "$campoQuemado";
                                        if ($campoQuemado->tipo == 'texto') {
                                            $dataPlan .= " ".$consPlano['entre_columna'].str_pad($campoQuemado->valor, $campoQuemado->longitud).$consPlano['entre_columna'].$consPlano['separador'];
                                        }else{ $dataPlan .= $campoQuemado->valor.$consPlano['separador']; }
                                    }
                                }
                            }

                            echo "STATE C: $campoDpl <br>";

                            if ($campoDpl == true) {
                                // CAMPOS CONSULTA TABLA
                                if ($suma >= count($tipo)) {
                                    // echo "ALERTA: => LA CANTIDAD DE CAMPOS EN LA POSICION `$sum` DE LA TABLA `tbl_formato` SOBREPASA, NO CONCUERDA CON LA CANTIDAD DE CAMPOS QUE CONTIENE LA TABLA: `$value->tabla_destino` ES `".count($tipo)."` <br>";
                                }else{
                                    $tipoR = Funciones::ReplaceText($tipo[$suma]);  
                                    $longitudR = Funciones::ReplaceText($longitud[$suma]);
                                    
                                    if ($tipoR == 'texto') {
                                        $dataPlan .= " ".$consPlano['entre_columna'].str_pad($valueB, $longitudR).$consPlano['entre_columna'].$consPlano['separador'];
                                    }else{ $dataPlan .= $valueB.$consPlano['separador']; }

                                }   
                            }
                                            
                            
                        } $sum++; $suma++;
                    }
                    echo "<br>";
                    echo "PLANO ANT FUNCTION: $dataPlan <br>";
                    if ($consPlano['salto_linea'] == 1) { $dataPlan .= "\n"; }
                }   

            }

            if ($dataPlan != null) {
                $nombreFile = Funciones::NombreArchivo($consPlano); 
                //$rutaFile = "public/plano/".$nombreFile; 
                $rutaFile = $consPlano['ruta'].$nombreFile;
                Funciones::crearTXT($dataPlan,$rutaFile,$nombreFile,$consPlano['ftp'],$consPlano['sftp']);
                $consTabla->where('planoRegistro',0)->update(['planoRegistro' => 1]);        
            }        

        }
    }
}