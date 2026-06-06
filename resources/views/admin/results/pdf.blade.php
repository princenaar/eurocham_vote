<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #64748b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        td.num { text-align: right; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Résultats — {{ $election->name }}</h1>
        <div class="muted">
            EUROCHAM Sénégal · Réf. P01.EUROCHAM.2026 ·
            Généré le {{ $generatedAt->format('d/m/Y H:i') }}
        </div>
    </div>

    <p class="muted">
        {{ $votesCast }} vote(s) exprimé(s).
        @if ($election->mode === \App\Models\Election::MODE_AUTO)
            Mode B : élection automatique de tous les candidats.
        @elseif ($election->mode === \App\Models\Election::MODE_SELECT)
            Mode A : sélection de {{ $election->candidate_threshold }} candidats par votant.
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Candidat</th>
                <th>Voix</th>
                <th>Élu automatiquement</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $i => $candidate)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $candidate->name }}</td>
                    <td class="num">{{ $candidate->selections_count }}</td>
                    <td>{{ $candidate->auto_elected ? 'Oui' : 'Non' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
