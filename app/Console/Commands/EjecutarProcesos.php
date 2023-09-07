<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Modelo\Tabla;
use App\Modelo\Funciones;

class EjecutarProcesos extends Command
{
    protected $signature = 'integracion:ejecutar-proceso';
    protected $description = 'Consulta & verifica existencias de productos en bodegas con y sin WMS';

    public function __construct(){ parent::__construct(); }

    public function handle(){

        echo "=====> [ EJECUTANDO PROCESO MARSH FOOD ] <=====";

        $fechaInicio = Funciones::fechaConsulta("inicio");
        $fechaFin = Funciones::fechaConsulta("fin");
        // dd($fechaFin);

        $consTabla = new Tabla;
        $consTabla->getTable();
        $consTabla->bind("tbl_consulta");
        $dia = '01';

        if ($consTabla->where('codigo','>',0)->where('tabla_destino', 'like', 'tbl_ws_inventario%')->update(['fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin])) {
            echo "FECHAS CONSULTAS ACTUALIZADAS: INICIO[".$fechaInicio."] / FIN[".$fechaFin."] \n";
        }

        if ($consTabla->where('codigo','>',0)->where('tabla_destino', 'like', 'tbl_ws_cliente%')->orWhere('tabla_destino', 'like', 'tbl_ws_detalle_venta%')
           ->update(['fechaInicio' => $fechaFin, 'fechaFin' => $fechaFin])) {
            echo "FECHAS CONSULTAS ACTUALIZADAS: INICIO[".$fechaFin."] / FIN[".$fechaFin."] \n";
        }

        //LAS TABLAS DE "tbl_ws_cliente" DEBEN ESTAR EN CERO EN LA COLUMNA truncate.
        if ($consTabla->whereRaw("DAY(fechaInicio) = ?", [$dia])->where('tabla_destino', 'like', 'tbl_ws_cliente%')->update(['truncate' => 1])) {
            echo "ACTUALIZANDO EL BORRADO DE LAS TABLAS tbl_ws_cliente\n";
        } else {
            $consTabla->where('tabla_destino', 'like', 'tbl_ws_cliente%')->update(['truncate' => 0]);
                echo "NO HACE BORRADO DE LAS TABLAS tbl_ws_cliente \n";
        }

         //LAS TABLAS DE "tbl_ws_detalle_venta" DEBEN ESTAR EN CERO EN LA COLUMNA truncate.
         if ($consTabla->whereRaw("DAY(fechaInicio) = ?", [$dia])->where('tabla_destino', 'like', 'tbl_ws_detalle_venta%')->update(['truncate' => 1])) {
            echo "ACTUALIZANDO EL BORRADO DE LAS TABLAS tbl_ws_detalle_venta \n";
        } else {
            $consTabla->where('tabla_destino', 'like', 'tbl_ws_detalle_venta%')->update(['truncate' => 0]);
                echo "NO HACE BORRADO DE LAS TABLAS tbl_ws_detalle_venta \n";
        }
            // dd('PARAR');
        Artisan::call('integracion:verificar-tipo-documento'); // CONSULTA INVENTARIO EN BODEGAS DE LOS PRODUCTOS DE PEDIDOS APROBADOS

        Artisan::call('integracion:guardar-informacion'); // VERIFICA INVENTARIO MINIMO EN BODEGAS PARA REMISIONAR, MOVER INVENTARIO

        // Artisan::call('integracion:generar-planos'); // VERIFICA INVENTARIO MINIMO EN BODEGAS PARA REMISIONAR, MOVER INVENTARIO

        // Artisan::call('integracion:enviar-planos'); // VERIFICA INVENTARIO MINIMO EN BODEGAS PARA REMISIONAR, MOVER INVENTARIO


    }

}
