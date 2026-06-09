<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(): View
    {
        $election = Election::current();

        return view('admin.candidates.index', [
            'candidates' => Candidate::query()->orderBy('display_order')->orderBy('name')->get(),
            'election' => $election,
        ]);
    }

    public function create(): View
    {
        abort_unless(Election::current()->canEditConfiguration(), 403);

        return view('admin.candidates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Election::current()->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $data = $this->validateCandidate($request);

        $candidate = Candidate::create($data);
        Election::current()->syncModeFromCandidates();
        AuditLogger::log('candidate.created', "Candidat ajouté : {$candidate->name}", ['id' => $candidate->id]);

        return redirect()->route('admin.candidates.index')
            ->with('status', "Candidat « {$candidate->name} » ajouté.");
    }

    public function edit(Candidate $candidate): View
    {
        abort_unless(Election::current()->canEditConfiguration(), 403);

        return view('admin.candidates.edit', ['candidate' => $candidate]);
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        if (! Election::current()->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $candidate->update($this->validateCandidate($request));
        AuditLogger::log('candidate.updated', "Candidat modifié : {$candidate->name}", ['id' => $candidate->id]);

        return redirect()->route('admin.candidates.index')
            ->with('status', "Candidat « {$candidate->name} » modifié.");
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        if (! Election::current()->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $name = $candidate->name;
        $candidate->delete();
        Election::current()->syncModeFromCandidates();
        AuditLogger::log('candidate.deleted', "Candidat supprimé : {$name}");

        return redirect()->route('admin.candidates.index')
            ->with('status', "Candidat « {$name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCandidate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'name.required' => 'Le nom du candidat est obligatoire.',
            'display_order.integer' => 'L’ordre d’affichage doit être un nombre.',
        ]);
    }

    private function configurationLockedRedirect(): RedirectResponse
    {
        return redirect()->route('admin.candidates.index')
            ->withErrors(['candidates' => 'La liste des candidats est verrouillée dès l’ouverture du scrutin.']);
    }
}
