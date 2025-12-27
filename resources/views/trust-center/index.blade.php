<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $companyName ? $companyName . ' ' : '' }}{{ $trustCenterName }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS via CDN for standalone page -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b" x-data="{ showHeaderModal: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @php
                        $customLogo = setting('report.logo');
                        $logoUrl = $customLogo ? asset('storage/' . $customLogo) : asset('img/logo-128-128.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="{{ $companyName ?: $trustCenterName }}" class="h-12 w-auto">
                    <div>
                        @if($companyName)
                            <h1 class="text-2xl font-bold text-gray-900">{{ $companyName }}</h1>
                            <p class="text-lg text-gray-600">{{ $trustCenterName }}</p>
                        @else
                            <h1 class="text-2xl font-bold text-gray-900">{{ $trustCenterName }}</h1>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @if($protectedDocuments->count() > 0)
                        <a href="#protected-documents" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Request Access
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <p class="text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Overview Section -->
        @if(isset($contentBlocks['overview']) && $contentBlocks['overview']->is_enabled)
            <section class="mb-12">
                <div class="bg-white rounded-xl shadow-sm border p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $contentBlocks['overview']->title }}</h2>
                    <div class="prose prose-blue max-w-none">
                        {!! $contentBlocks['overview']->content !!}
                    </div>
                </div>
            </section>
        @endif

        <!-- Certifications Section -->
        @if($certifications->count() > 0)
            <section class="mb-12">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Certifications & Compliance</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($certifications as $certification)
                        <div class="bg-white rounded-lg shadow-sm border p-4 text-center hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-900 text-sm">{{ $certification->name }}</h3>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Documents Section -->
        <section class="mb-12" x-data="{ showRequestModal: false, selectedDocuments: [] }">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Security Documentation</h2>

            <!-- Public Documents -->
            @if($publicDocuments->count() > 0)
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        Public Documents
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($publicDocuments as $document)
                            <div class="bg-white rounded-lg shadow-sm border p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ $document->name }}</h4>
                                        @if($document->description)
                                            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($document->description, 100) }}</p>
                                        @endif
                                        @if($document->certifications->count() > 0)
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach($document->certifications as $cert)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $cert->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('trust-center.document.download', $document) }}" class="ml-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Protected Documents -->
            @if($protectedDocuments->count() > 0)
                <div id="protected-documents">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-700 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-amber-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Protected Documents
                            <span class="ml-2 text-sm text-gray-500 font-normal">(Requires access request)</span>
                        </h3>
                        <button
                            type="button"
                            @click="selectedDocuments = [{{ $protectedDocuments->pluck('id')->implode(', ') }}]; showRequestModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Request Access to All
                        </button>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($protectedDocuments as $document)
                            <div class="bg-white rounded-lg shadow-sm border p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="font-medium text-gray-900">{{ $document->name }}</h4>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-2 text-amber-500">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                        </div>
                                        @if($document->description)
                                            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($document->description, 100) }}</p>
                                        @endif
                                        @if($document->certifications->count() > 0)
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach($document->certifications as $cert)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $cert->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        @click="if (!selectedDocuments.includes({{ $document->id }})) { selectedDocuments.push({{ $document->id }}) }; showRequestModal = true"
                                        class="ml-4 inline-flex items-center px-3 py-2 border border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Request Access
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Request Access Modal -->
                <div x-show="showRequestModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showRequestModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRequestModal = false"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div x-show="showRequestModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <form action="{{ route('trust-center.request-access') }}" method="POST">
                                @csrf
                                {{-- Honeypot field to catch bots - must remain empty --}}
                                <div class="absolute -left-[9999px]" aria-hidden="true">
                                    <label for="website_url">Leave this field empty</label>
                                    <input type="text" name="website_url" id="website_url" tabindex="-1" autocomplete="off">
                                </div>
                                <div>
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Request Document Access
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Please provide your information to request access to protected documents.
                                    </p>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div>
                                        <label for="requester_name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                                        <input type="text" name="requester_name" id="requester_name" required value="{{ old('requester_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('requester_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="requester_email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                                        <input type="email" name="requester_email" id="requester_email" required value="{{ old('requester_email') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('requester_email')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="requester_company" class="block text-sm font-medium text-gray-700">Company *</label>
                                        <input type="text" name="requester_company" id="requester_company" required value="{{ old('requester_company') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('requester_company')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Access</label>
                                        <textarea name="reason" id="reason" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('reason') }}</textarea>
                                        @error('reason')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Documents</label>
                                        <div class="space-y-2 max-h-40 overflow-y-auto border rounded-md p-3">
                                            @foreach($protectedDocuments as $document)
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="document_ids[]" value="{{ $document->id }}"
                                                        :checked="selectedDocuments.includes({{ $document->id }})"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    <span class="ml-2 text-sm text-gray-700">{{ $document->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('document_ids')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @if($ndaRequired && $ndaText)
                                        <div class="bg-gray-50 rounded-md p-4">
                                            <h4 class="text-sm font-medium text-gray-900 mb-2">Non-Disclosure Agreement</h4>
                                            <div class="text-sm text-gray-600 max-h-32 overflow-y-auto prose prose-sm">
                                                {!! $ndaText !!}
                                            </div>
                                            <label class="flex items-center mt-3">
                                                <input type="checkbox" name="nda_agreed" value="1" required class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <span class="ml-2 text-sm text-gray-700">I agree to the terms above *</span>
                                            </label>
                                            @error('nda_agreed')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @else
                                        <input type="hidden" name="nda_agreed" value="1">
                                    @endif
                                </div>

                                <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                        Submit Request
                                    </button>
                                    <button type="button" @click="showRequestModal = false; selectedDocuments = []" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <!-- Additional Content Blocks -->
        @php
            $contentBlockSlugs = ['security_practices', 'data_handling', 'subprocessors', 'faq', 'compliance_roadmap', 'contact'];
            $enabledBlocks = collect($contentBlockSlugs)->filter(fn($slug) => isset($contentBlocks[$slug]) && $contentBlocks[$slug]->is_enabled);
        @endphp
        @if($enabledBlocks->count() > 0)
            <section class="mb-12">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($enabledBlocks as $slug)
                        <div class="bg-white rounded-xl shadow-sm border p-6 flex flex-col">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ $contentBlocks[$slug]->title }}</h2>
                            <div class="prose prose-sm prose-blue max-w-none flex-1">
                                {!! $contentBlocks[$slug]->content !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} {{ $companyName ?: config('app.name') }}. All rights reserved.</p>
                <p class="mt-1">Powered by <a href="https://opengrc.org" target="_blank" class="text-blue-600 hover:text-blue-800">OpenGRC</a></p>
            </div>
        </div>
    </footer>
</body>
</html>
