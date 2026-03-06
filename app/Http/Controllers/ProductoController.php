<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productos = Producto::all()->map(function ($producto) {
            $producto->imagen = asset('storage/' . $producto->imagen);
            return $producto;
        });

        return response()->json($productos);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validación de datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'precio_referencial' => 'nullable|numeric|min:0',
            'categoria_id' => 'nullable|integer|exists:categorias,id',
            'imagen' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120', // max 5MB
        ]);

        $imagenPath = null;

        // Manejo de imagen
        if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
            $file = $request->file('imagen');

            // Nombre seguro y único
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

            // Carpeta dentro del disco public
            $folder = 'productos';

            // Guardar archivo en storage/app/public/productos
            $path = $file->storeAs($folder, $filename, 'public');

            $imagenPath = $path;
        }

        // Crear producto
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => 'activo',
            'stock' => $request->stock,
            'precio_referencial' => $request->precio_referencial,
            'categoria_id' => $request->categoria_id,
            'imagenes' => $imagenPath, // columna en DB
        ]);

        return response()->json([
            'success' => true,
            'data' => $producto
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function show(Producto $producto)
    {
        return Producto::findOrFail($id);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Producto $producto)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->all());
        return $producto;

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Producto $producto)
    {
        //
    }
}
