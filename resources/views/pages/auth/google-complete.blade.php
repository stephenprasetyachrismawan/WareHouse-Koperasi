<x-layouts::auth :title="__('Complete Your Setup')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Set Up Your Company')" :description="__('We\'ve verified your Google account. Create your company to get started.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('auth.google.complete.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Company Name -->
            <flux:input
                name="company_name"
                :label="__('Company / Koperasi Name')"
                :value="old('company_name')"
                type="text"
                required
                autofocus
                :placeholder="__('e.g. Koperasi Mandiri Sejahtera')"
            />

            <!-- Company Code -->
            <flux:input
                name="company_code"
                :label="__('Company Code (Optional)')"
                :value="old('company_code')"
                type="text"
                :placeholder="__('e.g. KOP-MANDIRI')"
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="google-complete-button">
                    {{ __('Create Company & Continue') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
