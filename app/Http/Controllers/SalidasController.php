<?php

namespace App\Http\Controllers;

use App\Models\categorias_producto;
use App\Models\dt;
use App\Models\producto;
use App\Models\salidas;
use Illuminate\Http\Request;

class SalidasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //Primero buscamos el dt en dado caso de meterlo incompletop
        $dt = dt::select('dts.*')
        ->where('dts.referencia','LIKE','%'.$request['dt'].'%')
        ->first();

        if($dt !== null) //si encontramos el dt pasamos a crear
        {
            //Buscamos el producto
            $producto =  producto::select('productos.*')
            ->where('productos.codigo','=',$request['codigo'])
            ->first();
            //
            if($producto !== null)
            {
                $categoria_producto = categorias_producto::select('categorias_productos.*')
                ->where('categorias_productos.categoria_id','=',$request['categoria'])
                ->where('categorias_productos.producto_id','=',$producto['id'])
                ->first();

                salidas::create([
                    'categorias_producto_id' => $categoria_producto['id'],
                    'dt_id' => $dt['id'],
                    'cantidad' => $request['cantidad']
                ]);
            }
        }
        else//sino no se crea asociado a ningun dt
        {
           //Buscamos el producto
           $producto =  producto::select('productos.*')
           ->where('productos.codigo','=',$request['codigo'])
           ->first();
           //
           if($producto !== null)
           {
               $categoria_producto = categorias_producto::select('categorias_productos.*')
               ->where('categorias_productos.categoria_id','=',$request['categoria'])
               ->where('categorias_productos.producto_id','=',$producto['id'])
               ->first();
         
               salidas::create([
                   'categorias_producto_id' => $categoria_producto['id'],
                   'cantidad' => $request['cantidad']
               ]);
           }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(salidas $salidas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(salidas $salidas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, salidas $salidas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(salidas $salidas)
    {
        //
    }
}