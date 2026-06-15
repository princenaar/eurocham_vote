<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; }
        h1 { font-size: 18px; margin: 0; color: #16386f; }
        .muted { color: #64748b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #eef3fa; color: #16386f; }
        td.num { text-align: right; }
        .header { border-bottom: 3px solid #16386f; padding-bottom: 8px; margin-bottom: 8px; }
        .header td { border: none; padding: 0; vertical-align: middle; }
        .tricolor { height: 4px; margin-bottom: 10px; }
        .tricolor td { padding: 0; border: none; }
    </style>
</head>
<body>
    <table class="tricolor">
        <tr>
            <td style="background:#00853f; width:33.33%;">&nbsp;</td>
            <td style="background:#f2c500; width:33.33%;">&nbsp;</td>
            <td style="background:#e2231a; width:33.34%;">&nbsp;</td>
        </tr>
    </table>
    <div class="header">
        <table>
            <tr>
                <td style="width:120px;">
                    <img src="{{ public_path('images/logo-eurocham.png') }}" alt="EUROCHAM" style="height:48px;">
                </td>
                <td>
                    <h1>Résultats — {{ $election->name }}</h1>
                    <div class="muted">
                        EUROCHAM Sénégal · Version 1 ·
                        Généré le {{ $generatedAt->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <p class="muted">
        {{ $votesCast }} vote(s) exprimé(s).
        @if ($election->isQuestionsVote())
            Vote par questions Oui / Non / Abstention. Les pourcentages Oui/Non sont calculés sur les suffrages exprimés.
        @elseif ($election->mode === \App\Models\Election::MODE_AUTO)
            Mode B : élection automatique de tous les candidats.
        @elseif ($election->mode === \App\Models\Election::MODE_SELECT)
            Mode A : sélection de {{ $election->candidate_min_choices }} à {{ $election->candidate_max_choices }} candidats par votant.
        @endif
    </p>

    @if ($election->isBoardVote() && $hasUnresolvedTie && $pendingTie)
        <p class="muted" style="color:#b45309;">
            Égalité pour {{ $pendingTie['seats'] }} siège(s) entre :
            {{ $pendingTie['tied']->pluck('name')->implode(', ') }} — départage requis.
        </p>
    @endif

    @if ($election->isQuestionsVote())
        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Oui</th>
                    <th>Non</th>
                    <th>Abstention</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($questionResults as $row)
                    <tr>
                        <td>{{ $row['question']->title }}</td>
                        <td class="num">{{ $row['yes'] }} ({{ $row['yes_percent'] }}%)</td>
                        <td class="num">{{ $row['no'] }} ({{ $row['no_percent'] }}%)</td>
                        <td class="num">{{ $row['abstain'] }}</td>
                        <td>{{ $row['result'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Photo</th>
                    <th>Candidat</th>
                    <th>Structure</th>
                    <th>Voix</th>
                    <th>Élu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ranking as $row)
                    <tr>
                        <td>{{ $row['rank'] }}</td>
                        <td>
                            <img src="{{ $row['candidate']->displayPhotoPathForPdf() }}" alt="Photo" style="height:32px; width:32px; object-fit:cover;">
                        </td>
                        <td>{{ $row['candidate']->name }}</td>
                        <td>{{ $row['candidate']->assemblyCompany?->name }}</td>
                        <td class="num">{{ $row['votes'] }}</td>
                        <td>{{ in_array($row['candidate']->id, $electedIds, true) ? 'Oui' : 'Non' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($runoffRounds->isNotEmpty())
        @foreach ($runoffRounds as $runoffRound)
            <h1 style="font-size:14px; margin-top:20px; color:#16386f;">Vote de départage (tour {{ $runoffRound['round'] }})</h1>
            <p class="muted">{{ $runoffRound['votes_cast'] }} vote(s) · {{ $runoffRound['seats'] }} siège(s).</p>
            <table>
                <thead>
                    <tr><th>Rang</th><th>Candidat</th><th>Structure</th><th>Voix</th></tr>
                </thead>
                <tbody>
                    @foreach ($runoffRound['ranking'] as $row)
                        <tr>
                            <td>{{ $row['rank'] }}</td>
                            <td>{{ $row['candidate']->name }}</td>
                            <td>{{ $row['candidate']->assemblyCompany?->name }}</td>
                            <td class="num">{{ $row['votes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</body>
</html>
