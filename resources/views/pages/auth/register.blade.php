<x-layouts::auth :title="__('Register SaaS Company')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Register Your Company')" :description="__('Enter your user details and company info below to create your SaaS tenant account.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-google-button />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- User Information Section -->
            <div class="space-y-4">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b pb-1 border-zinc-200 dark:border-zinc-800">
                    {{ __('Administrator Account') }}
                </div>

                <!-- Name -->
                <flux:input
                    name="name"
                    :label="__('Full Name')"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Full name')"
                />

                <!-- Email Address -->
                <flux:input
                    name="email"
                    :label="__('Email Address')"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="admin@company.com"
                />

                <!-- Password -->
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />

                <!-- Confirm Password -->
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
            </div>

            <!-- Company / SaaS Tenant Section -->
            <div class="space-y-4">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b pb-1 border-zinc-200 dark:border-zinc-800">
                    {{ __('Company Information') }}
                </div>

                <!-- Company Name -->
                <flux:input
                    name="company_name"
                    :label="__('Company / Koperasi Name')"
                    :value="old('company_name')"
                    type="text"
                    required
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
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Register Company & Account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
