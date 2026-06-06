<div>
    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom du candidat</label>
    <input id="name" name="name" type="text" value="{{ old('name', $candidate?->name) }}" required
           class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-500 focus:ring-slate-500">
</div>
<div>
    <label for="display_order" class="block text-sm font-medium text-slate-700 mb-1">Ordre d’affichage</label>
    <input id="display_order" name="display_order" type="number" min="0"
           value="{{ old('display_order', $candidate?->display_order ?? 0) }}"
           class="w-32 rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-500 focus:ring-slate-500">
</div>
