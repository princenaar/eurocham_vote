<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CompaniesImport;
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
        return view('admin.companies.index', [
            'companies' => Company::query()->orderBy('name')->paginate(50),
            'total' => Company::query()->count(),
        ]);
    }

    public function showImport(): View
    {
        return view('admin.companies.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
        ]);

        $import = new CompaniesImport();
        Excel::import($import, $request->file('file'));

        AuditLogger::log('companies.imported', "Import de la liste des membres", [
            'imported' => $import->imported,
            'errors' => count($import->errors),
        ]);

        $message = "{$import->imported} entreprise(s) importée(s).";
        if ($import->errors !== []) {
            return redirect()->route('admin.companies.index')
                ->with('status', $message)
                ->with('import_errors', $import->errors);
        }

        return redirect()->route('admin.companies.index')->with('status', $message);
    }
}
