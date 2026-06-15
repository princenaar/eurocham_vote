<?php

namespace Database\Seeders;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EurochamE2ESeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@eurocham.sn'],
            [
                'name' => 'Administrateur EUROCHAM',
                'password' => Hash::make('eurocham2026'),
            ],
        );

        $this->call(Eurocham2026Seeder::class);

        $assembly = Assembly::query()->where('reference', 'P01.EUROCHAM.2026')->firstOrFail();

        $assembly->elections()->update([
            'qr_active' => true,
            'window_open' => false,
            'active_slot' => null,
            'opened_at' => null,
            'closed_at' => null,
        ]);

        $this->seedModeBBoardVote($assembly);
    }

    private function seedModeBBoardVote(Assembly $assembly): void
    {
        $election = $assembly->elections()->updateOrCreate(
            ['type' => Election::TYPE_BOARD, 'name' => 'E2E — Conseil d’Administration Mode B'],
            [
                'display_order' => 50,
                'status' => Election::STATUS_DRAFT,
                'candidate_threshold' => 20,
                'candidate_min_choices' => 5,
                'candidate_max_choices' => 20,
                'current_round' => 1,
                'runoff_candidate_ids' => null,
                'runoff_seats' => null,
                'window_open' => false,
                'qr_active' => true,
                'active_slot' => null,
                'opened_at' => null,
                'closed_at' => null,
            ],
        );

        foreach ($this->modeBCandidates() as $index => [$name, $structureName]) {
            $structure = $this->ensureStructure($assembly, $structureName);
            $photoPath = 'candidate-photos/eurocham-e2e/'.Str::slug($name).'.svg';

            if (! Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->put($photoPath, $this->avatarSvg($name, $index));
            }

            Candidate::query()->updateOrCreate(
                ['election_id' => $election->id, 'name' => $name],
                [
                    'assembly_company_id' => $structure->id,
                    'display_order' => $index + 1,
                    'photo_path' => $photoPath,
                    'auto_elected' => true,
                ],
            );
        }

        $election->syncModeFromCandidates();
    }

    private function ensureStructure(Assembly $assembly, string $name): AssemblyCompany
    {
        $company = Company::query()->firstOrCreate(
            ['normalized_name' => Company::normalizeName($name)],
            [
                'name' => $name,
                'survey_2025' => true,
                'dues_2025' => true,
                'new_member_2026' => false,
            ],
        );

        return AssemblyCompany::query()->firstOrCreate(
            ['assembly_id' => $assembly->id, 'company_id' => $company->id],
            [
                'name' => $company->name,
                'normalized_name' => $company->normalized_name,
                'survey_2025' => true,
                'dues_2025' => true,
                'new_member_2026' => false,
                'eligible' => true,
            ],
        );
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function modeBCandidates(): array
    {
        return [
            ['Awa Mode B', 'Structure Mode B Alpha'],
            ['Moussa Mode B', 'Structure Mode B Bravo'],
            ['Fatou Mode B', 'Structure Mode B Charlie'],
            ['Jean Mode B', 'Structure Mode B Delta'],
            ['Marie Mode B', 'Structure Mode B Echo'],
        ];
    }

    private function avatarSvg(string $name, int $index): string
    {
        $palette = [
            ['#155e75', '#cffafe'],
            ['#166534', '#dcfce7'],
            ['#854d0e', '#fef3c7'],
            ['#991b1b', '#fee2e2'],
            ['#3730a3', '#e0e7ff'],
        ];
        [$background, $foreground] = $palette[$index % count($palette)];
        $initials = htmlspecialchars($this->initials($name), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240" role="img" aria-label="Avatar {$initials}">
  <rect width="240" height="240" rx="32" fill="{$background}"/>
  <circle cx="120" cy="88" r="42" fill="{$foreground}" opacity="0.9"/>
  <path d="M48 204c10-44 41-68 72-68s62 24 72 68" fill="{$foreground}" opacity="0.9"/>
  <text x="120" y="132" text-anchor="middle" font-family="Arial, sans-serif" font-size="54" font-weight="700" fill="{$background}">{$initials}</text>
</svg>
SVG;
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
