<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag; // Asume que tienes estos modelos
use App\Models\Unidad; // Asume que tienes estos modelos
use Illuminate\Support\Facades\Auth;

class ApiLookupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Solo usuarios autenticados pueden usar la API
        // Opcional: middleware('permission:use api lookups')
    }

    /**
     * Busca tags.
     * @param Request $request Contiene 'search' query.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTags(Request $request)
    {
        $search = $request->input('search');
        $tags = Tag::query()
                    ->when($search, function ($query, $search) {
                        $query->where('tag', 'like', '%' . $search . '%');
                    })
                    ->limit(10) // Limitar resultados para no sobrecargar
                    ->get(['id', 'tag']); // Solo ID y nombre

        return response()->json($tags);
    }

    /**
     * Busca unidades.
     * @param Request $request Contiene 'search' query.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnidades(Request $request)
    {
        $search = $request->input('search');
        $unidades = Unidad::query()
                            ->when($search, function ($query, $search) {
                                $query->where('name', 'like', '%' . $search . '%');
                            })
                            ->limit(10)
                            ->get(['id', 'name']);

        return response()->json($unidades);
    }

    // ... Puedes añadir métodos similares para sistemas, servicios, etc. ...
}