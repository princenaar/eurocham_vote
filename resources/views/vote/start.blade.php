@extends('layouts.app')

@section('title', 'Accès au vote — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-lg mx-auto py-8">
        <h2 class="font-serif text-3xl font-semibold text-brand-800">Accès au vote</h2>
        <p class="mt-2 text-slate-600">
            Identifiez votre entreprise membre pour accéder au bulletin. Chaque entreprise
            dispose d’<strong>une seule voix</strong>.
        </p>

        <form method="POST" action="{{ route('vote.identify') }}" class="mt-6 space-y-5" data-testid="vote-identify-form"
              x-data="companyPicker(@js(old('assembly_company_id', old('company_id'))))">
            @csrf

            {{-- Searchable company combobox. The server re-validates by id regardless of UI state. --}}
            <div x-id="['company-list']">
                <label class="block text-sm font-medium text-slate-700">Entreprise membre</label>
                <div class="relative mt-1">
                    <input type="hidden" name="assembly_company_id" x-model="selectedId">
                    <input
                        type="text"
                        x-model="query"
                        @focus="open = true"
                        @input="open = true; selectedId = ''"
                        @click.away="open = false"
                        placeholder="Tapez le nom de votre entreprise…"
                        autocomplete="off"
                        data-testid="company-search"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        :aria-controls="$id('company-list')"
                    >
                    <ul
                        x-show="open && filtered().length"
                        x-cloak
                        :id="$id('company-list')"
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-slate-200 bg-white py-1 text-sm shadow-lg"
                    >
                        <template x-for="company in filtered()" :key="company.id">
                            <li
                                @click="select(company)"
                                data-testid="company-option"
                                class="cursor-pointer px-3 py-2 hover:bg-brand-50"
                                x-text="company.name"
                            ></li>
                        </template>
                    </ul>
                    <p x-show="open && query.length && !filtered().length" x-cloak
                       class="absolute z-10 mt-1 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 shadow-lg">
                        Aucune entreprise correspondante. Contactez le secrétariat.
                    </p>
                </div>
                @error('company_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700">Nom</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required data-testid="representative-last-name"
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                    @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700">Prénom</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required data-testid="representative-first-name"
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                    @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="flex items-start gap-3 rounded-md border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700">
                    <input name="is_proxy" type="checkbox" value="1" @checked(old('is_proxy')) data-testid="proxy-checkbox"
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                    <span>
                        <span class="font-medium text-slate-800">Je vote par procuration</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            Cochez cette case si vous exprimez la voix de l’entreprise sélectionnée en tant que mandataire.
                        </span>
                    </span>
                </label>
                @error('is_proxy')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" data-testid="identify-submit"
                    class="w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                Accéder au bulletin
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Entreprise introuvable ou non à jour ? Contactez le secrétariat EUROCHAM.
        </p>
    </div>

    <script>
        function companyPicker(oldId) {
            return {
                companies: @js($companies->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                query: '',
                selectedId: oldId || '',
                open: false,
                init() {
                    if (this.selectedId) {
                        const match = this.companies.find(c => String(c.id) === String(this.selectedId));
                        if (match) this.query = match.name;
                    }
                },
                filtered() {
                    const q = this.query.trim().toLowerCase();
                    if (!q) return this.companies;
                    return this.companies.filter(c => c.name.toLowerCase().includes(q));
                },
                select(company) {
                    this.selectedId = company.id;
                    this.query = company.name;
                    this.open = false;
                },
            };
        }
    </script>
@endsection
