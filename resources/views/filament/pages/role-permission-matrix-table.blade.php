<div class="overflow-x-auto bg-white rounded-xl border border-gray-200">
    <table class="role-permission-matrix w-full">
        <thead>
            <tr class="bg-gray-50">
                <th class="px-4 py-2 text-left">Permissions</th>
                @foreach($getRoles() as $role)
                    <th class="px-4 py-2 text-center">{{ $role->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($getGroupedPermissions() as $category => $permissions)
                <tr class="border-t border-gray-200 bg-gray-50">
                    <td class="px-4 py-2 text-left font-bold text-gray-900" colspan="{{ count($getRoles()) + 1 }}">
                        {{ $category ? \Illuminate\Support\Str::title($category) : 'Uncategorized' }}
                    </td>
                </tr>
                @foreach($permissions as $permission)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2 text-left pl-8">{{ $permission->name }}</td>
                        @foreach($getRoles() as $role)
                            <td class="px-4 py-2 text-center">
                                <label class="inline-flex items-center justify-center">
                                    <input 
                                        type="checkbox"
                                        class="text-primary-600 transition duration-75 rounded shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 disabled:opacity-70 border-gray-300"
                                        @if($role->hasPermissionTo($permission)) checked @endif
                                        wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                    >
                                </label>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div> 