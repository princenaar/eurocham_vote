<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CompaniesImport;
use App\Models\Assembly;
use App\Models\Company;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller
{
    public function index(): View
    {
        $assembly = $this->selectedAssembly();

        return view('admin.companies.index', [
            'assembly' => $assembly,
            'assemblies' => Assembly::query()->orderByDesc('held_on')->orderByDesc('id')->get(),
            'companies' => $assembly->companies()->orderBy('name')->paginate(50),
            'total' => $assembly->companies()->count(),
        ]);
    }

    public function showImport(): View
    {
        $assembly = $this->selectedAssembly();
        abort_unless($assembly->canEditCompanies(), 403);

        return view('admin.companies.import', ['assembly' => $assembly]);
    }

    public function import(Request $request): RedirectResponse
    {
        $assembly = $this->selectedAssembly($request);

        if (! $assembly->canEditCompanies()) {
            return redirect()->route('admin.companies.index', ['assembly' => $assembly->id])
                ->withErrors(['file' => 'La liste des membres est verrouillée dès l’ouverture du scrutin.']);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
        ]);

        $import = new CompaniesImport($assembly);
        Excel::import($import, $request->file('file'));

        AuditLogger::log('companies.imported', "Import de la liste des membres", [
            'imported' => $import->imported,
            'errors' => count($import->errors),
        ]);

        $message = "{$import->imported} entreprise(s) importée(s).";
        if ($import->errors !== []) {
            return redirect()->route('admin.companies.index', ['assembly' => $assembly->id])
                ->with('status', $message)
                ->with('import_errors', $import->errors);
        }

        return redirect()->route('admin.companies.index', ['assembly' => $assembly->id])->with('status', $message);
    }

    private function selectedAssembly(?Request $request = null): Assembly
    {
        $id = $request?->input('assembly_id') ?? request('assembly');

        if ($id) {
            return Assembly::query()->findOrFail($id);
        }

        return Assembly::current();
    }
}
