<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Edit Pengguna Internal') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Perbarui informasi dan peran pengguna internal perusahaan.') }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 flex flex-col">
        <!-- Name -->
        <div>
            <flux:input
                wire:model="name"
                :label="__('Nama Lengkap')"
                type="text"
                required
                autofocus
            />
            <flux:error name="name" />
        </div>

        <!-- Email -->
        <div>
            <flux:input
                wire:model="email"
                :label="__('Alamat Email')"
                type="email"
                required
            />
            <flux:error name="email" />
        </div>

        <!-- Role -->
        <div>
            <flux:select wire:model="role" :label="__('Peran / Role')">
                @foreach ($roles as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="role" />
        </div>

        <div class="flex items-center gap-4 justify-end">
            <flux:button href="{{ route('company.users.index') }}" variant="subtle" wire:navigate>
                {{ __('Batal') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ __('Simpan Perubahan') }}
            </flux:button>
        </div>
    </form>
</div>
