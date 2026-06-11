<div>
    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom du candidat</label>
    <input id="name" name="name" type="text" value="{{ old('name', $candidate?->name) }}" required
           class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
</div>
<div>
    <label for="assembly_company_id" class="block text-sm font-medium text-slate-700 mb-1">Structure</label>
    <select id="assembly_company_id" name="assembly_company_id" required
            class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
        <option value="">Sélectionner une structure</option>
        @foreach ($structures as $structure)
            <option value="{{ $structure->id }}" @selected((string) old('assembly_company_id', $candidate?->assembly_company_id) === (string) $structure->id)>
                {{ $structure->name }}
            </option>
        @endforeach
    </select>
</div>
<div>
    <label for="photo" class="block text-sm font-medium text-slate-700 mb-1">Photo</label>
    <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-800 file:px-3 file:py-2 file:text-sm file:text-white">
    @if ($candidate?->photo_path)
        <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
            <img src="{{ $candidate->photoUrl() }}" alt="Photo de {{ $candidate->name }}" class="h-12 w-12 rounded object-cover">
            Photo actuelle. Téléverser une nouvelle image la remplacera.
        </div>
    @endif
</div>
<div>
    <label for="display_order" class="block text-sm font-medium text-slate-700 mb-1">Ordre d’affichage</label>
    <input id="display_order" name="display_order" type="number" min="0"
           value="{{ old('display_order', $candidate?->display_order ?? 0) }}"
           class="w-32 rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
</div>
