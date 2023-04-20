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
            $consTabla = new Tabla;
            $consTabla->getTable();
            $consTabla->bind($value->tabla_destino);
            //dd($consTabla);
            if ($value->group_by == '') {
                $resCons = $consTabla->where('planoRegistro',0)->orderBy($value->orderBy,$value->orderType)->get();

            }else{
                $resCons = $consTabla->where('planoRegistro',0)->groupBy($value->group_by)->orderBy($value->orderBy,$value->orderType)->get();
            }
            //dd($resCons);
            //$totalSuc = array('00210');
            // $totalSuc = array('00210','00211');
            // $totalSuc = array('00210','00211','00212');
            $dataPlan = null;
            $name_us = null;
            $sumR = 0;

                foreach ($resCons as $keya => $valueA) {

                     echo "===> DATA: $sumR  \n";

                    $suma = 0;
                    $array = explode(",", $valueA);
                    //$totalExpl = count($array) - 2;
                    if ($consPlano['display_codigo'] == 0) { $sum = 1; }else{ $sum = 2; }

                    // dd($totalExpl);

                    if (count($array) > 0) {
                        //Log::info($array);
                       // dd($array);
                        foreach ($array as $keyb => $valueB) {
                            //dd($keyb);
                            $dataLost = (count($array) - 2);
                               // dd($dataLost);
                            if ($sum != 1 && $sum < $dataLost) {

				                $valueB = Funciones::caracterEspecial($valueB);
                                $valueB = str_replace('--','/',$valueB);
                                $valueB = Funciones::caracterEspecialSimbolB($valueB);
                                // $valueB = Funciones::caracterEspecialSimbol($valueB);

                                $campoDpl = true;
                                $pos = strpos($valueB, ':');
                                $pos++;
                                $valueB = substr($valueB, $pos);
                                $valueB = Funciones::ReplaceText($valueB);

                                $tipo = explode(",", $consFormato['tipo']);
                                $longitud = explode(",", $consFormato['longitud']);

                                // echo "VALUE FOREACH:".$valueB." | ".$suma." | "."\n";
                                // echo "STATE A: $campoDpl <br>";
                                //dd();
                                if ($valueB == 'NO') { $valueB = ''; }

                                // echo "TOTAL: ".(count($tipo) - 2)." / SUMA: $sum / VAL: $valueB \n";
                                // echo "SUMA: $sum \n";

                                if (count($tipo) == $sum || count($tipo) < $sum || ($sum + 1) == $dataLost) {
                                    // echo "ESPACIADO NULL \n";
                                    // dd();
                                    $separadorPlan = "";
                                }else{
                                    // echo "SEPARADO ; \n";
                                    $separadorPlan = $consPlano['separador'];
                                }

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
                                                $dataResplan = substr($resBusc['codigo'], 0, $planoFuncion->longitud);
                                                $dataPlan .= " ".$consPlano['entre_columna'].str_pad($dataResplan, $planoFuncion->longitud).$consPlano['entre_columna'].$separadorPlan;
                                            }else{ $dataPlan = str_replace(" ", "", $resBusc['codigo']);  $dataPlan .= $resBusc['codigo'].$separadorPlan; }
                                        }else{
                                            $dataPlan .= Funciones::condicionPlano($planoFuncion,$valueB,$name_us,$consPlano);
                                            if ($dataPlan != false) { $campoDpl = false; }
                                        }
                                    }
                                }

                                // echo "STATE B: $campoDpl <br>";
                                // dd();
                                // CAMPOS QUEMADOS
                                if ($campoDpl == true) {
                                    foreach ($consCampoQuemado as $campoQuemado) {
                                        if ($campoQuemado->posicion == $suma) {
                                            $campoDpl = false;
                                            echo "$campoQuemado";
                                            dd();
                                            if ($campoQuemado->tipo == 'texto') {
                                                $dataResplan = substr($campoQuemado->valor, 0, $campoQuemado->longitud);
                                                $dataPlan .= " ".$consPlano['entre_columna'].str_pad($dataResplan, $campoQuemado->longitud).$consPlano['entre_columna'].$separadorPlan;
                                            }else{ $campoQuemado->valor = str_replace(" ", "", $campoQuemado->valor);  $dataPlan .= $campoQuemado->valor.$separadorPlan; }
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
                                            $dataResplan = substr($valueB, 0, $longitudR);
                                            $dataPlan .= "".$consPlano['entre_columna'].str_pad($dataResplan, 0).$consPlano['entre_columna'].$separadorPlan;

                                        }else{
                                             $valueB = str_replace(" ", "", $valueB);
                                             $dataPlan .= $valueB.$separadorPlan;
                                             }

                                    }
                                }


                            } $sum++; $suma++;
                        }
                        // echo "<br>";
                        // echo "PLANO ANT FUNCTION: $dataPlan \n";
                        // echo "\n";
                        // dd($dataPlan);
                        if ($consPlano['salto_linea'] == 1) { $dataPlan .= "\n"; }
                    }   $sumR++;

                }

                if ($dataPlan != null) {
                    $nombreFile = Funciones::NombreArchivo($consPlano);
                    //$rutaFile = "public/plano/".$nombreFile;
                    $rutaFile = $consPlano['ruta'].$nombreFile; $dataPlan = str_replace('\/',"/", $dataPlan);
                    Funciones::crearTXT($dataPlan,$rutaFile,$nombreFile,$consPlano['ftp'],$consPlano['sftp']);
                    // $consTabla->where('planoRegistro',0)->update(['planoRegistro' => 1]);
                }


            //$consTabla->where('planoRegistro',0)->update(['planoRegistro' => 1]);

        }
        $nombreFile = Funciones::NombreArchivo($consPlano);
    }
}
