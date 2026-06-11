<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assembly;
use App\Models\Election;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssemblyController extends Controller
{
    public function index(): View
    {
        return view('admin.assemblies.index', [
            'assemblies' => Assembly::query()
                ->with(['elections' => fn ($query) => $query->orderBy('display_order')->orderBy('id')])
                ->orderByDesc('held_on')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:100', 'unique:assemblies,reference'],
            'held_on' => ['nullable', 'date'],
        ], [
            'name.required' => 'Le nom de l’AG est obligatoire.',
            'reference.required' => 'La référence de l’AG est obligatoire.',
            'reference.unique' => 'Cette référence d’AG existe déjà.',
        ]);

        $assembly = Assembly::create($data);
        AuditLogger::log('assembly.created', "AG créée : {$assembly->name}", ['assembly_id' => $assembly->id]);

        return redirect()->route('admin.assemblies.index')->with('status', 'AG créée.');
    }

    public function storeVote(Request $request, Assembly $assembly): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.Election::TYPE_BOARD.','.Election::TYPE_QUESTIONS],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'name.required' => 'Le nom du vote est obligatoire.',
            'type.in' => 'Type de vote invalide.',
        ]);

        $election = $assembly->elections()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'display_order' => $data['display_order'] ?? ($assembly->elections()->count() + 1),
            'status' => Election::STATUS_DRAFT,
            'current_round' => 1,
        ]);

        AuditLogger::log('election.created', "Vote créé : {$election->name}", [
            'assembly_id' => $assembly->id,
            'election_id' => $election->id,
            'type' => $election->type,
        ]);

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', 'Vote créé.');
    }
}
