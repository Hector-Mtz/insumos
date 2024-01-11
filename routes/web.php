<?php

use App\Http\Controllers\CategoriasProductoController;
use App\Http\Controllers\DtController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SalidasController;
use App\Http\Controllers\StageController;
use App\Models\categoria;
use App\Models\categorias_producto;
use App\Models\corte_diario_historico;
use App\Models\entrada;
use App\Models\producto;
use App\Models\salidas;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () 
{
    //Rutas donde se requiere el login
    Route::get('/dashboard', function (Request $request) 
    {
        $categorias_productos = categoria::all();

        //diario
        $ultimo_corte = corte_diario_historico::select('corte_diario_historicos.*')
        ->where('corte_diario_historicos.activo','=',1)
        ->orderBy('corte_diario_historicos.id', 'DESC')
        ->first();

        //mensual
        date_default_timezone_set('America/Mexico_City');
        $fechaactual = getdate();
        $añoActual = $fechaactual['year'];
        $mesActual = $fechaactual['mon'];
        $diaActual= $fechaactual['mday'];
        if($mesActual < 10)
        {
          $mesActual = '0'.$mesActual;
        }

        if($diaActual < 10)
        {
          $diaActual = '0'.$diaActual;
        } 
        $newFechaActual = $añoActual.'-'.$mesActual;
        $ultimo_corte_mensual = corte_diario_historico::select('corte_diario_historicos.*')
        ->where('corte_diario_historicos.fecha','LIKE','%'.$newFechaActual.'%')
        ->orderBy('corte_diario_historicos.id', 'ASC')
        ->first();

       // return $ultimo_corte['fecha'];
        $productos = categorias_producto::select(
        'categorias_productos.*', 
        'productos.nombre as producto_nombre',
        'productos.codigo as codigo',
        )
        ->with([
            'entradas' =>  function ($query) use ($ultimo_corte) 
            {
                if($ultimo_corte !== null)
                {
                    $query->select(
                        'entradas.*'
                      )
                      ->where('entradas.fecha','>=',$ultimo_corte['fecha']);
                      //->whereTime('entradas.fecha','>',$ultimo_corte['fecha']);
                }
                else
                {
                    $query->select(
                        'entradas.*'
                    );
                }
            },
            'salidas' =>  function ($query) use ($ultimo_corte) 
            {
                if($ultimo_corte !== null)
                {
                    $query->select(
                        'salidas.*'
                        )
                        ->where('salidas.created_at','>',$ultimo_corte['fecha']);
                }
                else
                {
                    $query->select(
                        'salidas.*'
                    );
                }

            },
            'corte_diario' =>  function ($query) use ($ultimo_corte) 
            {
                if($ultimo_corte !== null)
                {
                    $query->select(
                        'corte_diario_historicos.*'
                        )
                        ->where('corte_diario_historicos.activo','=',1)
                        ->where('corte_diario_historicos.fecha','>=',$ultimo_corte['fecha']);
                }
                else
                {
                    $query->select(
                        'corte_diario_historicos.*'
                        )
                        ->where('corte_diario_historicos.activo','=',1);
                }
            },

        ])
        ->join('productos', 'categorias_productos.producto_id', 'productos.id');

        //Acumulado
        if($request->has("inventario_actual"))
        {
           if($request["inventario_actual"] == 2) //inventario acumulado
           {
            //return $newFechaActual;
            $productos = categorias_producto::select(
                'categorias_productos.*', 
                'productos.nombre as producto_nombre',
                )
                ->with([
                    'entradas' =>  function ($query) use ($ultimo_corte_mensual) 
                    {
                      $query->select(
                          'entradas.*'
                        )
                        ->where('entradas.fecha','>',$ultimo_corte_mensual['fecha']);
                    },
                    'salidas' =>  function ($query) use ($ultimo_corte_mensual) 
                    {
                        $query->select(
                            'salidas.*'
                            )
                            ->where('salidas.created_at','>',$ultimo_corte_mensual['fecha']);
                    },
                    'corte_diario' =>  function ($query) use ($ultimo_corte_mensual)  //ahora es mensual
                    {
                        $query->select(
                            'corte_diario_historicos.*'
                            )
                            ->where('corte_diario_historicos.fecha','like','%'.$ultimo_corte_mensual['fecha'].'%')
                            ->first();
                    },
        
                ])
                ->join('productos', 'categorias_productos.producto_id', 'productos.id');
           }
        }
        
        if ($request->has("categoria")) 
        {
          $productos->where('categorias_productos.categoria_id','=',$request['categoria']);
        }
        else
        {
            $productos->where('categorias_productos.categoria_id','=',1);
        }        
        
        return Inertia::render('Dashboard',
        [
            'categorias' => $categorias_productos,
            'productos' => fn() => $productos->paginate(10)
        ]);
    })->name('dashboard');

    //ruta para guardado de nuevos productos
    Route::post('/newProducto',[CategoriasProductoController::class, 'store'])->name('saveNewProduct');
    //ruta de guardado de entradas
    Route::post('/newEntrada', [EntradaController::class, 'store'])->name('saveEntrada');
    //Ruta para la importacion de bolo
    Route::post('/importBolo', [DtController::class, 'store'])->name('importBolo');
    //Ruta para obtener dts 
    Route::get('/getDts',[DtController::class, 'index'] )->name('getDts');
    //Ruta para obtener los stages
    Route::get('/getStages',[StageController::class, 'index'])->name('getStages');
    //Ruta para cambiar el status del stage
    Route::post('/cambioStage', [StageController::class, 'cambioStage'])->name('cambioStage');
    //Ruta para crear stage 
    Route::post('/createStage',[StageController::class,'store'])->name('createStage');
    //Ruta para recuperar datos de dt  para una nueva salida
    Route::get('/consultaInformacion',[DtController::class,'consultaInformacion'])->name('consultaInformacion');
    //Ruta para guardar nueva salida
    Route::post('/saveSalida', [SalidasController::class, 'store'])->name('saveSalida');
    //Ruta para obtener los movimientos por producto (entradas)
    Route::get('/getEntradasByProducto', [ProductoController::class, 'index'])->name('getEntradasByProducto');
    //Descargar pdf de productos
    Route::get('/pdfCodes',[ProductoController::class,'pdfCodes'])->name('pdfCodes');
});