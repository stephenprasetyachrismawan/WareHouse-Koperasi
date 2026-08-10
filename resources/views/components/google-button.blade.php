<div class="relative">
    <div class="absolute inset-0 flex items-center">
        <span class="w-full border-t border-zinc-200 dark:border-zinc-800"></span>
    </div>
    <div class="relative flex justify-center text-sm">
        <span class="px-2 bg-white text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
            {{ __('or continue with') }}
        </span>
    </div>
</div>

<flux:button variant="subtle" class="w-full justify-center" :href="route('auth.google.redirect')" data-test="google-sign-in-button">
    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M21.6 12.227C21.6 11.519 21.536 10.838 21.418 10.182H12v3.949h5.382c-.232 1.25-.938 2.31-2 3.014v2.506h3.24c1.895-1.745 2.978-4.318 2.978-7.424Z" fill="#4285F4"/>
        <path d="M12 22c2.7 0 4.964-.895 6.622-2.424l-3.24-2.506c-.898.602-2.047.957-3.382.957-2.6 0-4.802-1.757-5.588-4.118H3.078v2.588A9.996 9.996 0 0 0 12 22Z" fill="#34A853"/>
        <path d="M6.412 13.909a5.99 5.99 0 0 1 0-3.818V7.503H3.078a10.002 10.002 0 0 0 0 8.994l3.334-2.588Z" fill="#FBBC05"/>
        <path d="M12 5.973c1.468 0 2.787.504 3.824 1.494l2.87-2.87A9.972 9.972 0 0 0 12 2 9.996 9.996 0 0 0 3.078 7.503l3.334 2.588C7.198 7.73 9.4 5.973 12 5.973Z" fill="#EA4335"/>
    </svg>
    {{ __('Continue with Google') }}
</flux:button>
