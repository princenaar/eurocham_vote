<?php

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function candidateStructure(Election $election, string $name = 'Structure Candidate'): AssemblyCompany
{
    $company = Company::create([
        'name' => $name,
        'normalized_name' => Company::normalizeName($name),
        'survey_2025' => true,
        'dues_2025' => true,
    ]);

    return $company->assemblyCompanies()
        ->where('assembly_id', $election->assembly_id)
        ->first();
}

it('creates a candidate with an AG structure and optional photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $election = Election::current();
    $structure = candidateStructure($election);

    $this->actingAs($admin)->post(route('admin.candidates.store'), [
        'election_id' => $election->id,
        'name' => 'Awa Diop',
        'assembly_company_id' => $structure->id,
        'photo' => UploadedFile::fake()->image('awa.jpg', 160, 160),
    ])->assertRedirect(route('admin.candidates.index', ['election' => $election->id]));

    $candidate = Candidate::where('name', 'Awa Diop')->first();
    expect($candidate)->not->toBeNull();
    expect($candidate->assembly_company_id)->toBe($structure->id);
    expect($candidate->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($candidate->photo_path);
});

it('rejects a candidate structure from another AG', function () {
    $admin = User::factory()->create();
    $election = Election::current();
    $otherAssembly = Assembly::create([
        'name' => 'Autre AG',
        'reference' => 'AUTRE.AG',
        'held_on' => now()->toDateString(),
    ]);
    $otherCompany = Company::create([
        'name' => 'Structure hors AG',
        'normalized_name' => Company::normalizeName('Structure hors AG'),
        'survey_2025' => true,
        'dues_2025' => true,
    ]);
    $otherStructure = AssemblyCompany::create([
        'assembly_id' => $otherAssembly->id,
        'company_id' => $otherCompany->id,
        'name' => $otherCompany->name,
        'normalized_name' => $otherCompany->normalized_name,
        'survey_2025' => true,
        'dues_2025' => true,
        'new_member_2026' => false,
        'eligible' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.candidates.store'), [
        'election_id' => $election->id,
        'name' => 'Candidat invalide',
        'assembly_company_id' => $otherStructure->id,
    ])->assertSessionHasErrors('assembly_company_id');

    expect(Candidate::where('name', 'Candidat invalide')->exists())->toBeFalse();
});

it('replaces and deletes candidate photos from public storage', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $election = Election::current();
    $structure = candidateStructure($election);
    $candidate = Candidate::create([
        'election_id' => $election->id,
        'assembly_company_id' => $structure->id,
        'name' => 'Photo Remplacée',
        'photo_path' => UploadedFile::fake()->image('old.jpg')->store('candidate-photos', 'public'),
    ]);
    $oldPath = $candidate->photo_path;

    $this->actingAs($admin)->put(route('admin.candidates.update', $candidate), [
        'name' => 'Photo Remplacée',
        'assembly_company_id' => $structure->id,
        'photo' => UploadedFile::fake()->image('new.png', 160, 160),
    ])->assertRedirect(route('admin.candidates.index', ['election' => $election->id]));

    $candidate->refresh();
    expect($candidate->photo_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($candidate->photo_path);

    $newPath = $candidate->photo_path;
    $this->actingAs($admin)->delete(route('admin.candidates.destroy', $candidate))->assertRedirect();
    Storage::disk('public')->assertMissing($newPath);
});

it('shows candidate structures and default images in ballot review and results', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 1,
        'candidate_min_choices' => 1,
        'candidate_max_choices' => 1,
        'status' => Election::STATUS_DRAFT,
        'window_open' => false,
        'qr_active' => false,
        'active_slot' => null,
    ]);

    $structureA = candidateStructure($election, 'Structure Alpha');
    $structureB = candidateStructure($election, 'Structure Bravo');

    $alpha = Candidate::create([
        'election_id' => $election->id,
        'assembly_company_id' => $structureA->id,
        'name' => 'Alpha',
        'display_order' => 1,
    ]);
    expect($alpha->displayPhotoUrl())->toContain('images/candidate-default.svg');
    expect($alpha->displayPhotoPathForPdf())->toBe(public_path('images/candidate-default.svg'));

    Candidate::create([
        'election_id' => $election->id,
        'assembly_company_id' => $structureB->id,
        'name' => 'Bravo',
        'display_order' => 2,
    ]);
    $election->syncModeFromCandidates();

    $admin = User::factory()->create();
    $this->actingAs($admin)->get(route('admin.candidates.edit', $alpha))
        ->assertOk()
        ->assertDontSee('Photo actuelle');

    $election->update([
        'status' => Election::STATUS_OPEN,
        'window_open' => true,
        'qr_active' => true,
        'active_slot' => Election::ACTIVE_SLOT_GLOBAL,
    ]);

    $voter = Company::create([
        'name' => 'Entreprise Votante',
        'normalized_name' => Company::normalizeName('Entreprise Votante'),
        'survey_2025' => true,
        'dues_2025' => true,
    ]);

    $this->actingAs($admin)->get(route('admin.candidates.index', ['election' => $election->id]))
        ->assertOk()
        ->assertSee('candidate-default.svg')
        ->assertSee('Structure Alpha');

    $this->post(route('vote.identify'), [
        'company_id' => $voter->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertRedirect(route('vote.ballot'));

    $this->get(route('vote.ballot'))
        ->assertOk()
        ->assertSee('candidate-default.svg')
        ->assertSee('Structure Alpha')
        ->assertSee('Structure Bravo');

    $this->post(route('vote.review'), ['candidates' => [$alpha->id]])
        ->assertOk()
        ->assertSee('candidate-default.svg')
        ->assertSee('Structure Alpha');

    $this->post(route('vote.submit'), ['candidates' => [$alpha->id]])->assertRedirect();

    $election->update([
        'status' => Election::STATUS_CLOSED,
        'window_open' => false,
        'active_slot' => null,
        'closed_at' => now(),
    ]);

    $this->get(route('results.public', ['election' => $election->id]))
        ->assertOk()
        ->assertSee('candidate-default.svg')
        ->assertSee('Structure Alpha');

    $this->actingAs($admin)->get(route('admin.results.index', ['election' => $election->id]))
        ->assertOk()
        ->assertSee('candidate-default.svg')
        ->assertSee('Structure Alpha');

    $this->actingAs($admin)->get(route('admin.results.excel', ['election' => $election->id]))->assertOk();
    $this->actingAs($admin)->get(route('admin.results.pdf', ['election' => $election->id]))->assertOk();
});
