<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CandidateController extends Controller
{
    private const PUBLIC_IMAGE_PATH_PREFIX = 'images/';

    public function index(): View
    {
        $election = $this->selectedElection();

        return view('admin.candidates.index', [
            'candidates' => $election->candidates()->with('assemblyCompany')->get(),
            'election' => $election,
        ]);
    }

    public function create(): View
    {
        $election = $this->selectedElection();
        abort_unless($election->isBoardVote() && $election->canEditConfiguration(), 403);

        return view('admin.candidates.create', [
            'election' => $election,
            'structures' => $election->assembly->eligibleCompanies()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $election = $this->selectedElection($request);

        if (! $election->isBoardVote() || ! $election->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $data = $this->validateCandidate($request);
        $data['election_id'] = $election->id;

        $data['photo_path'] = $this->storePhoto($request);
        unset($data['photo']);

        $candidate = Candidate::create($data);
        $election->syncModeFromCandidates();
        AuditLogger::log('candidate.created', "Candidat ajouté : {$candidate->name}", ['id' => $candidate->id]);

        return redirect()->route('admin.candidates.index', ['election' => $election->id])
            ->with('status', "Candidat « {$candidate->name} » ajouté.");
    }

    public function edit(Candidate $candidate): View
    {
        $election = $candidate->election;
        abort_unless($election->canEditConfiguration(), 403);

        return view('admin.candidates.edit', [
            'candidate' => $candidate,
            'election' => $election,
            'structures' => $election->assembly->eligibleCompanies()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $election = $candidate->election;

        if (! $election->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $data = $this->validateCandidate($request, $candidate->election);
        $newPhoto = $this->storePhoto($request);
        unset($data['photo']);

        if ($newPhoto !== null) {
            $this->deletePhoto($candidate);
            $data['photo_path'] = $newPhoto;
        }

        $candidate->update($data);
        AuditLogger::log('candidate.updated', "Candidat modifié : {$candidate->name}", ['id' => $candidate->id]);

        return redirect()->route('admin.candidates.index', ['election' => $election->id])
            ->with('status', "Candidat « {$candidate->name} » modifié.");
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $election = $candidate->election;

        if (! $election->canEditConfiguration()) {
            return $this->configurationLockedRedirect();
        }

        $name = $candidate->name;
        $this->deletePhoto($candidate);
        $candidate->delete();
        $election->syncModeFromCandidates();
        AuditLogger::log('candidate.deleted', "Candidat supprimé : {$name}");

        return redirect()->route('admin.candidates.index', ['election' => $election->id])
            ->with('status', "Candidat « {$name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCandidate(Request $request, ?Election $election = null): array
    {
        $election ??= $this->selectedElection($request);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'assembly_company_id' => [
                'required',
                'integer',
                Rule::exists('assembly_companies', 'id')->where('assembly_id', $election->assembly_id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'name.required' => 'Le nom du candidat est obligatoire.',
            'assembly_company_id.required' => 'La structure du candidat est obligatoire.',
            'assembly_company_id.exists' => 'La structure sélectionnée ne fait pas partie de cette AG.',
            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'display_order.integer' => 'L’ordre d’affichage doit être un nombre.',
        ]);
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('candidate-photos', 'public');
    }

    private function deletePhoto(Candidate $candidate): void
    {
        if ($candidate->photo_path && ! str_starts_with($candidate->photo_path, self::PUBLIC_IMAGE_PATH_PREFIX)) {
            Storage::disk('public')->delete($candidate->photo_path);
        }
    }

    private function configurationLockedRedirect(): RedirectResponse
    {
        return redirect()->route('admin.candidates.index', ['election' => $this->selectedElection()->id])
            ->withErrors(['candidates' => 'La liste des candidats est verrouillée dès l’ouverture du scrutin.']);
    }

    private function selectedElection(?Request $request = null): Election
    {
        $id = $request?->input('election_id') ?? request('election');

        if ($id) {
            return Election::query()->findOrFail($id);
        }

        return Election::current();
    }
}
