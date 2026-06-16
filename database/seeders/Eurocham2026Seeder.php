<?php

namespace Database\Seeders;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\ElectionQuestion;
use Illuminate\Database\Seeder;

class Eurocham2026Seeder extends Seeder
{
    private const ASSEMBLY_REFERENCE = 'P01.EUROCHAM.2026';

    private const OFFICIAL_CANDIDATE_PHOTO_PREFIX = 'images/candidate-photos/eurocham-2026/';

    private const LEGACY_GENERATED_PHOTO_PREFIX = 'candidate-photos/eurocham-2026/';

    private const COMPANY_ROWS = <<<'CSV'
ALDELIA;0;0;1
ALGO PARTNERS;0;0;1
ELEPHANT VERT;0;0;1
FRUTTI SASU;0;0;1
INNATES SOLUTION;0;0;1
LOW PRICE;0;0;1
OBT LOGISTICS;0;0;1
SEKOYA;0;0;1
SOCIETE DES EAUX MINERALES;0;0;1
TFE SENEGAL;0;0;1
ZEBRACONCEPT;0;0;1
2S CONSULTING;1;1;0
AFRICA MOVE;1;1;0
AFRICA WORKS;1;1;0
AGL GROUP;1;1;0
AGRIVISION SENEGAL;1;1;0
AIR FRANCE;1;1;0
ALBISIA;1;1;0
ALIZES;1;1;0
AMBIANCE;1;1;0
ANCO;1;1;0
APAVE;1;1;0
ARCHER TRANSIT ex ARCHER LOGISTICS;1;1;0
ARMOR FC;1;1;0
ATOS;1;1;0
AUCHAN;1;1;0
AXA;1;1;0
AXESS - ATALIAN;1;1;0
BAOBAB SENEGAL;1;1;0
BELLA ROCCA;1;1;0
BERNABE;1;1;0
BIA DAKAR SARL;1;1;0
BUREAU VERITAS Senegal SAU;1;1;0
Business France Sénégal;1;1;0
CABINET MERLIN;1;1;0
CANAL+;1;1;0
CAOUTCHOUC & PLASTIQUES;1;1;0
CAPI;1;1;0
CCCS FINEXCO;1;1;0
CCIS;1;1;0
CEEMO;1;1;0
CFAO INFRASTRUCTURE;1;1;0
CFAO MOBILITY;1;1;0
CIF SENEGAL- Compagnie Industrielle des fibres;1;1;0
CMA CGM;1;1;0
CONCERTO EX INSIGN;1;1;0
COTOA;1;1;0
CSS - Compagnie Sucrière Sénégalaise;1;1;0
CSTM;1;1;0
DAKAR CATERING;1;1;0
DAKARNAVE;1;1;0
DECATHLON SENEGAL SARL;1;1;0
EGB R.BADARACCHI;1;1;0
Eiffage Operations Services;1;1;0
EIFFAGE SENEGAL;1;1;0
FORMARECRUT SARL;1;1;0
GDS - Grands Domaines du Sénégal;1;1;0
GECAMINES;1;1;0
GENI & KEBE;1;1;0
GEORIS GROUP;1;1;0
GEOTEC AFRIQUE;1;1;0
GGA SENEGAL;1;1;0
GROUPE TOP INTER;1;1;0
HAVAS AFRICA SENEGAL;1;1;0
HEPPNER;1;1;0
HOTEL ADRESSE;1;1;0
IAAFRIQUE;1;1;0
IBS;1;1;0
ICE;1;1;0
INGEROP AFRIQUE INGENIERIE;1;1;0
IPSOS ex OMEDIA;1;1;0
JADE;1;1;0
JARDIN DU SAHEL;1;1;0
KAP EXPERTISE;1;1;0
KSOUMINE;1;1;0
LA CONCIERGERIE DAKAR;1;1;0
LAGARDERE TRAVEL RETAIL SENEGAL;1;1;0
LASA - LA SENEGALAISE DE L'AUTOMOBILE;1;1;0
LASSARAT;1;1;0
LCS - Les Câbleries du Sénégal;1;1;0
LE CAFE KERMEL;1;1;0
LE FROID;1;1;0
LSE - Les Spécialistes de l'Energie;1;1;0
LUB & SUPPLY sarl;1;1;0
MANSA LEGAL TAX;1;1;0
MATIERE SENEGAL SAS;1;1;0
MC3 SENEGAL;1;1;0
MIA INDUSTRIES;1;1;0
MTOA;1;1;0
NESTLE SENEGAL;1;1;0
NETLOGIK;1;1;0
NEW LIFE CONTAINERS;1;1;0
NKAC;1;1;0
NOKIA WCA SENEGAL;1;1;0
NOUVELLES FRONTIERES SENEGAL & WEST AFRICA;1;1;0
NOVAGO SENEGAL;1;1;0
OBBO SA;1;1;0
OINIS;1;1;0
OLEA ASSURANCE;1;1;0
OO2 SENEGAL;1;1;0
ORABANK SENEGAL;1;1;0
ORSEN ex FRIEDLANDER SENEGAL;1;1;0
OXYGEN AFRICA;1;1;0
POULTRADE;1;1;0
PREMIUM SENEGAL;1;1;0
PULLMAN;1;1;0
REGIE IMMOBILIERE MUGNIER;1;1;0
RMO;1;1;0
SANLAM ALLIANZ;1;1;0
SCL - Société de Cultures Légumières;1;1;0
SCP HOUDA & ASSOCIES;1;1;0
SENAC PEINTURES;1;1;0
SENEGAL MINES SA;1;1;0
SENEMECA;1;1;0
SENOUTIL SAS;1;1;0
Service Economique et Commercial Belge;1;1;0
SETER SAS;1;1;0
SGS Senegal SA;1;1;0
SGSN - Société Générale du Sénégal;1;1;0
SIDEM;1;1;0
SILIKA GRANULATS;1;1;0
SMT;1;1;0
SND CASALA - AGS;1;1;0
SNSSS;1;1;0
SOCAS;1;1;0
SOCOCIM INDUSTRIES;1;1;0
SODACOM - Société Dakaroise de Construction Métallique;1;1;0
SODIPHARM;1;1;0
SOGAFRIC;1;1;0
SONATEL-ORANGE;1;1;0
SORIA SARL;1;1;0
SOYERE CONSULTING;1;1;0
SSPT TOLSA;1;1;0
SUNU BICIS;1;1;0
TECTRA;1;1;0
TELOGIK SARL;1;1;0
TOPWORK;1;1;0
TROPICASEM;1;1;0
TSG;1;1;0
TYSILIO;1;1;0
VERTI AFRICA;1;1;0
VINCI ENERGIES;1;1;0
WILLIS;1;1;0
WISE GROUP;1;1;0
AIR LIQUIDE - SEGOA Société Sénégalaise d'Oxygène et d'Acétylène;0;1;0
ASCOMA;0;1;0
BAOBAB PLUS SENEGAL SASU;0;1;0
BAUER;0;1;0
BRUSSELAIRLINES;0;1;0
CAETANO FORMULA SENEGAL;0;0;0
CEFI - Consulting Expertises et Formation Incendies;0;1;0
CHARGEL;0;1;0
CIMENTS DU SAHEL;0;1;0
CODEX;0;1;0
CVL;0;0;0
DAKAR TERMINAL;0;1;0
Eiffage Énergie Transport & Distribution Sénégal;0;0;0
EMATYS;0;1;0
EPC MINEEX SENEGAL SA;0;1;0
FAMY SENEGAL;0;1;0
FLUICONNECTO SENEGAL;0;0;0
GB FOODS;0;1;0
GERMANI WEST AFRICA;0;1;0
GRIMALDI SENEGAL;1;1;0
GRIPS;0;0;0
GROUPE CF-ID HR PARTNER;1;1;0
GROUPE ISM;1;1;0
IBERIA;0;1;0
ITO;0;1;0
IWOL;0;1;0
JOKE-COOL;0;1;0
KAJOU;0;1;0
KOF-EXPERTS;0;0;0
LABOREX;0;1;0
LOCSET;1;0;0
NGE;1;1;0
NOVOTEL SENEGAL;0;1;0
NUMERIKA;0;1;0
ORYX ENERGIES;0;0;0
PLUS SAFE;0;1;0
PUMA - ENERGY;0;0;0
PYXIS SUPPORT SARL;0;1;0
SADE SENEGAL SA;0;1;0
SAEZ CONSULTING;0;1;0
Safety Expertise Sénégal;0;1;0
SATLX IT SERVICES DAKAR;1;1;0
SEA INVEST SENEGAL AGENCY;1;1;0
SENEGAL FLEURS;1;1;0
SENEGAL TOURS;1;1;0
SERVTEC Sénégal;1;1;0
SH PROPERTIES SENEGAL;1;1;0
SOA-SORAM OUEST AFRICA;1;1;0
SOBOA - Société des Brasseries de l'Ouest Africain;1;1;0
SOCIUM JOB;0;1;0
SOLEVO SENEGAL;0;1;0
SURICATE SOLUTIONS SN;1;1;0
Teranga Security Operations;0;1;0
TERROU BI;0;0;0
TOLERIE REMORQUES COMBEDIMANCHE;0;1;0
TOM - Terminal des Opérations Maritimes SA;0;1;0
TOTAL E&P SENEGAL;0;1;0
TOTAL SENEGAL;1;1;0
USP GROUP;0;1;0
VIGASSISTANCE SA;0;1;0
YAS ex SENTEL GSM - FREE;0;0;0
CSV;

    private const BOARD_CANDIDATES = <<<'CSV'
François Cherpion;SSPT TOLSA
Nicolas Soyere;SOYERE CONSULTING
Alain Nöel;NOUVELLES FRONTIERES SENEGAL & WEST AFRICA
Jérémie Petit;OXYGEN AFRICA
Fatoumata Ly;BIA DAKAR SARL
Abdou Lo;NOKIA WCA SENEGAL
Pascal Louchelart;CFAO MOBILITY
Matthieu Coulon;CSTM
Alain Masson;AMBIANCE
Frédéric Beaune;LSE - Les Spécialistes de l'Energie
Florian Rapetti;NGE
Olivier Bremond;PREMIUM SENEGAL
Dame Sene;EIFFAGE SENEGAL
Cathy Suarez;REGIE IMMOBILIERE MUGNIER
Ibrahima Ndao;SENOUTIL SAS
Eric Binson;SOCAS
Philippe Lenormand;ARCHER TRANSIT ex ARCHER LOGISTICS
Julien Malle;CONCERTO EX INSIGN
Jeanne Malouf;FORMARECRUT SARL
Fabiola Kitoukona;KSOUMINE
Georges Amar;BELLA ROCCA
CSV;

    private const BOARD_CANDIDATE_PHOTOS = [
        'François Cherpion' => 'images/candidate-photos/eurocham-2026/francois-cherpion.png',
        'Nicolas Soyere' => 'images/candidate-photos/eurocham-2026/nicolas-soyere.jpg',
        'Alain Nöel' => 'images/candidate-photos/eurocham-2026/alain-noel.jpg',
        'Jérémie Petit' => 'images/candidate-photos/eurocham-2026/jeremie-petit.jpeg',
        'Fatoumata Ly' => 'images/candidate-photos/eurocham-2026/fatoumata-ly.jpg',
        'Abdou Lo' => 'images/candidate-photos/eurocham-2026/abdou-lo.jpg',
        'Pascal Louchelart' => 'images/candidate-photos/eurocham-2026/pascal-louchelart.jpg',
        'Matthieu Coulon' => 'images/candidate-photos/eurocham-2026/matthieu-coulon.jpeg',
        'Alain Masson' => 'images/candidate-photos/eurocham-2026/alain-masson.jpeg',
        'Frédéric Beaune' => 'images/candidate-photos/eurocham-2026/frederic-beaune.jpg',
        'Florian Rapetti' => 'images/candidate-photos/eurocham-2026/florian-rapetti.png',
        'Olivier Bremond' => 'images/candidate-photos/eurocham-2026/olivier-bremond.jpg',
        'Dame Sene' => 'images/candidate-photos/eurocham-2026/dame-sene.jpg',
        'Cathy Suarez' => 'images/candidate-photos/eurocham-2026/cathy-suarez.jpeg',
        'Ibrahima Ndao' => 'images/candidate-photos/eurocham-2026/ibrahima-ndao.jpeg',
        'Eric Binson' => 'images/candidate-photos/eurocham-2026/eric-binson.jpg',
        'Philippe Lenormand' => 'images/candidate-photos/eurocham-2026/philippe-lenormand.jpg',
        'Julien Malle' => 'images/candidate-photos/eurocham-2026/julien-malle.png',
        'Jeanne Malouf' => 'images/candidate-photos/eurocham-2026/jeanne-malouf.jpg',
        'Fabiola Kitoukona' => 'images/candidate-photos/eurocham-2026/fabiola-kitoukona.png',
        'Georges Amar' => 'images/candidate-photos/eurocham-2026/georges-amar.jpeg',
    ];

    public function run(): void
    {
        $assembly = Assembly::query()->updateOrCreate(
            ['reference' => self::ASSEMBLY_REFERENCE],
            [
                'name' => 'Assemblée Générale Mixte EUROCHAM 2026',
                'held_on' => '2026-06-18',
            ],
        );

        $this->seedCompanies($assembly);
        $this->seedQuestionVotes($assembly);
        $this->seedBoardVote($assembly);
    }

    private function seedCompanies(Assembly $assembly): void
    {
        foreach ($this->parseCsv(self::COMPANY_ROWS) as [$name, $survey, $dues, $newMember]) {
            $company = Company::query()->updateOrCreate(
                ['normalized_name' => Company::normalizeName($name)],
                [
                    'name' => $name,
                    'survey_2025' => (bool) $survey,
                    'dues_2025' => (bool) $dues,
                    'new_member_2026' => (bool) $newMember,
                ],
            );

            $this->upsertAssemblyCompany($assembly, $company);
        }
    }

    private function seedQuestionVotes(Assembly $assembly): void
    {
        foreach ($this->questionVotes() as $vote) {
            $election = $assembly->elections()->updateOrCreate(
                ['type' => Election::TYPE_QUESTIONS, 'name' => $vote['name']],
                [
                    'display_order' => $vote['display_order'],
                    'status' => Election::STATUS_READY,
                    'current_round' => 1,
                    'window_open' => false,
                    'qr_active' => false,
                    'active_slot' => null,
                    'opened_at' => null,
                    'closed_at' => null,
                ],
            );

            foreach ($vote['questions'] as $index => $question) {
                ElectionQuestion::query()->updateOrCreate(
                    ['election_id' => $election->id, 'title' => $question['title']],
                    [
                        'description' => $question['description'],
                        'display_order' => $index + 1,
                    ],
                );
            }
        }
    }

    private function seedBoardVote(Assembly $assembly): void
    {
        $election = $assembly->elections()->updateOrCreate(
            ['type' => Election::TYPE_BOARD, 'name' => 'Vote 3 — Renouvellement du Conseil d’Administration'],
            [
                'display_order' => 3,
                'status' => Election::STATUS_DRAFT,
                'candidate_threshold' => 20,
                'candidate_min_choices' => 5,
                'candidate_max_choices' => 20,
                'current_round' => 1,
                'runoff_candidate_ids' => null,
                'runoff_seats' => null,
                'window_open' => false,
                'qr_active' => false,
                'active_slot' => null,
                'opened_at' => null,
                'closed_at' => null,
            ],
        );

        foreach ($this->parseCsv(self::BOARD_CANDIDATES) as $index => [$name, $structureName]) {
            $structure = $this->ensureCandidateStructure($assembly, $structureName);
            $photoPath = $this->candidatePhotoPath($name);

            $candidate = Candidate::query()
                ->where('election_id', $election->id)
                ->whereIn('name', $this->candidateNameLookup($name))
                ->first() ?? new Candidate(['election_id' => $election->id]);

            $candidate->name = $name;
            $candidate->assembly_company_id = $structure->id;
            $candidate->display_order = $index + 1;

            if (! $candidate->photo_path || $this->isSeederManagedPhotoPath($candidate->photo_path)) {
                $candidate->photo_path = $photoPath;
            }

            $candidate->auto_elected = false;
            $candidate->save();
        }

        $election->syncModeFromCandidates();
    }

    private function upsertAssemblyCompany(Assembly $assembly, Company $company): AssemblyCompany
    {
        return AssemblyCompany::query()->updateOrCreate(
            ['assembly_id' => $assembly->id, 'company_id' => $company->id],
            [
                'name' => $company->name,
                'normalized_name' => $company->normalized_name,
                'survey_2025' => $company->survey_2025,
                'dues_2025' => $company->dues_2025,
                'new_member_2026' => $company->new_member_2026,
                'eligible' => $company->isEligible(),
            ],
        );
    }

    private function ensureCandidateStructure(Assembly $assembly, string $name): AssemblyCompany
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
                'survey_2025' => $company->survey_2025,
                'dues_2025' => $company->dues_2025,
                'new_member_2026' => $company->new_member_2026,
                'eligible' => $company->isEligible(),
            ],
        );
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    private function parseCsv(string $csv): array
    {
        return collect(preg_split('/\R/u', trim($csv)))
            ->filter()
            ->map(fn (string $line) => str_getcsv($line, ';'))
            ->map(fn (array $row) => array_map(fn (string $value) => $value === '1' ? 1 : ($value === '0' ? 0 : $value), $row))
            ->values()
            ->all();
    }

    private function candidatePhotoPath(string $name): ?string
    {
        return self::BOARD_CANDIDATE_PHOTOS[$name] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function candidateNameLookup(string $name): array
    {
        return match ($name) {
            'Philippe Lenormand' => [$name, 'Phillipe Lenormand'],
            default => [$name],
        };
    }

    private function isSeederManagedPhotoPath(string $path): bool
    {
        return str_starts_with($path, self::OFFICIAL_CANDIDATE_PHOTO_PREFIX)
            || str_starts_with($path, self::LEGACY_GENERATED_PHOTO_PREFIX);
    }

    /**
     * @return array<int, array{name: string, display_order: int, questions: array<int, array{title: string, description: string}>}>
     */
    private function questionVotes(): array
    {
        return [
            [
                'name' => 'Vote 1 — Assemblée générale à titre extraordinaire',
                'display_order' => 1,
                'questions' => [
                    [
                        'title' => 'Première résolution — Adoption des modifications des statuts',
                        'description' => 'L’Assemblée Générale Extraordinaire approuve l’ensemble des modifications statutaires telles qu’elles lui ont été présentées.',
                    ],
                    [
                        'title' => 'Deuxième résolution — Pouvoirs pour formalités',
                        'description' => 'L’Assemblée Générale confère tous pouvoirs pour accomplir les formalités administratives, légales ou réglementaires nécessaires.',
                    ],
                ],
            ],
            [
                'name' => 'Vote 2 — Assemblée générale à titre ordinaire',
                'display_order' => 2,
                'questions' => [
                    [
                        'title' => 'Troisième résolution — Approbation du rapport moral',
                        'description' => 'L’Assemblée Générale Ordinaire approuve le rapport moral présenté pour l’exercice 2025.',
                    ],
                    [
                        'title' => 'Quatrième résolution — Approbation du rapport financier et des états financiers',
                        'description' => 'L’Assemblée Générale Ordinaire approuve les comptes et états financiers de l’exercice clos le 31 décembre 2025.',
                    ],
                    [
                        'title' => 'Cinquième résolution — Quitus au Conseil d’Administration',
                        'description' => 'L’Assemblée Générale donne quitus entier, définitif et sans réserve aux membres du Conseil d’administration.',
                    ],
                    [
                        'title' => 'Sixième résolution — Adoption du budget prévisionnel 2026',
                        'description' => 'L’Assemblée Générale Ordinaire approuve le budget prévisionnel 2026 soumis par le Conseil d’administration.',
                    ],
                    [
                        'title' => 'Septième résolution — Nomination du Commissaire aux comptes',
                        'description' => 'L’Assemblée Générale Ordinaire décide de nommer le cabinet NKAC Expertises en qualité de Commissaire aux comptes pour trois années.',
                    ],
                ],
            ],
            [
                'name' => 'Vote 4 — Pouvoirs pour l’exécution des délibérations',
                'display_order' => 4,
                'questions' => [
                    [
                        'title' => 'Neuvième résolution — Pouvoirs pour l’exécution des délibérations',
                        'description' => 'L’Assemblée Générale confère tous pouvoirs au Président, ou à toute personne mandatée par lui, pour exécuter les présentes résolutions.',
                    ],
                ],
            ],
        ];
    }
}
