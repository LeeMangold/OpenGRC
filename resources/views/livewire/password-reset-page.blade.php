<section class="flex flex-col gap-y-8 py-8 w-full">
    <div class="flex justify-center p-6">
        <img src="{{ asset('/img/logo-128-128.png') }}" alt="OpenGRC Logo">
    </div>
    <div class="flex justify-center p-6">
        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
            Reset Your Password
        </h1>
    </div>
    <div class="fi-page flex justify-center">
        <div class="w-full max-w-2xl p-6 bg-white rounded-lg shadow-md">
            <p class="text font-semibold mb-12">
                You must change your password before proceeding.
            </p>
            <form wire:submit="create">
                {{ $this->form }}
                <x-filament::button type="submit" class="w-full bg-grcblue-700 hover:bg-grcblue-400 mt-10">
                    Change Password
                </x-filament::button>
            </form>
        </div>
    </div>
</section>