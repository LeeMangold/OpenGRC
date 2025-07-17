<div class="fi-sidebar-bottom-links flex flex-col gap-2 p-4">
    <hr class="border-t-1 my-2">
    @canany(['Manage Users', 'View Audit Log', 'Manage Permissions', 'Configure Authentication'])
    <a href="/admin/settings" class="flex items-center gap-2 text-sm text-primary-600 hover:underline">
        <x-heroicon-o-cog class="w-5 h-5" />
        Settings
    </a>
    @endcanany
    <a href="https://docs.opengrc.com" target="_blank" class="flex items-center gap-2 text-sm text-primary-600 hover:underline">
        <x-heroicon-o-question-mark-circle class="w-5 h-5" />
        Help
    </a>
</div> 