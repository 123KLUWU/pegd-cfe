<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSupervisorRequest;
use App\Http\Requests\UpdateSupervisorRequest;
use App\Models\Supervisor;
use Illuminate\Http\RedirectResponse;

class SupervisorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $supervisores = Supervisor::query()
            ->when($q, fn($qry) =>
                $qry->where(function($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                })
            )
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // Si quieres mostrar eliminados:
        $incluyeEliminados = $request->boolean('with_trashed', false);
        if ($incluyeEliminados) {
            $supervisores = Supervisor::withTrashed()
                ->when($q, fn($qry) =>
                    $qry->where(function($w) use ($q) {
                        $w->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    })
                )
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString();
        }

        return view('admin.supervisores.index', compact('supervisores', 'q', 'incluyeEliminados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.supervisores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupervisorRequest $request)
    {
        Supervisor::create($request->validated());

        return redirect()
            ->route('admin.supervisores.index')
            ->with('status', 'Supervisor creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supervisor $supervisor)
    {
        return view('admin.supervisores.show', compact('supervisor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supervisor $supervisor)
    {
        return view('admin.supervisores.edit', compact('supervisor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupervisorRequest $request, Supervisor $supervisor)
    {
        $supervisor->update($request->validated());

        return redirect()
            ->route('admin.supervisores.index')
            ->with('status', 'Supervisor actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supervisor $supervisor)
    {
        $supervisor->delete(); // Soft delete
        return redirect()
            ->route('admin.supervisores.index')
            ->with('status', 'Supervisor enviado a papelera.');
    }
    
    // ---- Extras SoftDeletes ----
    public function restore($id): RedirectResponse
    {
        $supervisor = Supervisor::withTrashed()->findOrFail($id);
        $supervisor->restore();

        return redirect()
            ->route('admin.supervisores.index', ['with_trashed' => 1])
            ->with('status', 'Supervisor restaurado.');
    }

    public function forceDelete($id): RedirectResponse
    {
        $supervisor = Supervisor::withTrashed()->findOrFail($id);
        $supervisor->forceDelete();

        return redirect()
            ->route('admin.supervisores.index', ['with_trashed' => 1])
            ->with('status', 'Supervisor eliminado permanentemente.');
    }
}
