<x-filament-panels::page>
    {{-- ============================================================== --}}
    {{-- OpenGRC FCC Compliance — custom dashboard view                  --}}
    {{-- Hand-built grid matching the FCC Compliance mockup, with all    --}}
    {{-- click-throughs wired to underlying Filament resources.          --}}
    {{-- ============================================================== --}}

    @php
        $licenseIndex = \App\Filament\Resources\FccLicenseResource::getUrl('index');
        $rulesIndex = \App\Filament\Resources\FccRuleResource::getUrl('index');
        $deadlinesIndex = \App\Filament\Resources\FccDeadlineResource::getUrl('index');

        $statusBadge = function (string $status) {
            return match ($status) {
                'active'        => ['label' => 'Active',        'dot' => 'bg-emerald-400', 'text' => 'text-emerald-300'],
                'expiring_soon' => ['label' => 'Expiring Soon', 'dot' => 'bg-amber-400',   'text' => 'text-amber-300'],
                'at_risk'       => ['label' => 'At Risk',       'dot' => 'bg-amber-400',   'text' => 'text-amber-300'],
                'non_compliant' => ['label' => 'Non-Compliant', 'dot' => 'bg-red-400',     'text' => 'text-red-300'],
                'silent'        => ['label' => 'Silent',        'dot' => 'bg-gray-400',    'text' => 'text-gray-300'],
                'cancelled'     => ['label' => 'Cancelled',     'dot' => 'bg-gray-500',    'text' => 'text-gray-400'],
                default         => ['label' => ucfirst($status),'dot' => 'bg-gray-400',    'text' => 'text-gray-300'],
            };
        };

        $serviceBadge = fn ($s) => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-400/15 text-amber-300 border border-amber-400/30';

        $scoreColor = fn ($pct) => $pct >= 95 ? 'text-emerald-400' : ($pct >= 80 ? 'text-amber-300' : 'text-red-400');
        $barColor   = fn ($pct) => $pct >= 95 ? 'bg-emerald-400'   : ($pct >= 80 ? 'bg-amber-400'   : 'bg-red-400');
    @endphp

    {{-- ============================================================ --}}
    {{-- Top stat row                                                  --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        {{-- Overall Compliance --}}
        <a href="{{ $licenseIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/20 p-4 hover:border-amber-400/50 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-amber-400 font-semibold">
                    <span>Overall Compliance</span>
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4 text-amber-400" />
                </div>
                <div class="mt-2 text-4xl font-bold {{ $scoreColor($overallPct) }}">{{ $overallPct }}%</div>
                <div class="mt-1 text-xs text-gray-400">Compliant</div>
            </div>
        </a>

        {{-- Licenses --}}
        <a href="{{ $licenseIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/20 p-4 hover:border-amber-400/50 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-amber-400 font-semibold">
                    <span>Licenses</span>
                    <x-filament::icon icon="heroicon-o-identification" class="h-4 w-4 text-amber-400" />
                </div>
                <div class="mt-2 text-4xl font-bold text-amber-400">{{ number_format($totalLicenses) }}</div>
                <div class="mt-1 text-xs text-gray-400">Active authorizations</div>
            </div>
        </a>

        {{-- Rule Requirements --}}
        <a href="{{ $rulesIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/20 p-4 hover:border-amber-400/50 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-amber-400 font-semibold">
                    <span>Rule Requirements</span>
                    <x-filament::icon icon="heroicon-o-scale" class="h-4 w-4 text-amber-400" />
                </div>
                <div class="mt-2 text-4xl font-bold text-amber-400">{{ number_format($totalRules) }}</div>
                <div class="mt-1 text-xs text-gray-400">Total tracked</div>
            </div>
        </a>

        {{-- Compliant --}}
        <a href="{{ $rulesIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-emerald-400/20 p-4 hover:border-emerald-400/50 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-emerald-400 font-semibold">
                    <span>Compliant</span>
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-emerald-400" />
                </div>
                <div class="mt-2 text-4xl font-bold text-emerald-400">{{ number_format($compliant) }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $totalCompliantPct }}%</div>
            </div>
        </a>

        {{-- At Risk --}}
        <a href="{{ $rulesIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/30 p-4 hover:border-amber-400/60 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-amber-400 font-semibold">
                    <span>At Risk</span>
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-amber-400" />
                </div>
                <div class="mt-2 text-4xl font-bold text-amber-400">{{ number_format($atRisk) }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $totalAtRiskPct }}%</div>
            </div>
        </a>

        {{-- Non-Compliant --}}
        <a href="{{ $rulesIndex }}" class="block group">
            <div class="rounded-xl bg-[#0c1f3d] border border-red-400/30 p-4 hover:border-red-400/60 transition">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-red-400 font-semibold">
                    <span>Non-Compliant</span>
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-4 w-4 text-red-400" />
                </div>
                <div class="mt-2 text-4xl font-bold text-red-400">{{ number_format($nonCompliant) }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $totalNonPct }}%</div>
            </div>
        </a>
    </div>

    {{-- ============================================================ --}}
    {{-- Middle row: License Compliance (8 cols) + Rule Category (4)   --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-2">
        {{-- License Compliance table --}}
        <div class="lg:col-span-8 rounded-xl bg-[#0c1f3d] border border-amber-400/15 overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between border-b border-amber-400/10">
                <div>
                    <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">License Compliance</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Real-time compliance status for all licensed operations</p>
                </div>
                <a href="{{ $licenseIndex }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 flex items-center gap-1">
                    View all licenses
                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3" />
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#050d1f]">
                        <tr class="text-amber-400 text-xs uppercase tracking-wide">
                            <th class="text-left py-2.5 px-5 font-semibold">Call Sign</th>
                            <th class="text-left py-2.5 px-3 font-semibold">Licensee</th>
                            <th class="text-left py-2.5 px-3 font-semibold">Service</th>
                            <th class="text-left py-2.5 px-3 font-semibold">Status</th>
                            <th class="text-left py-2.5 px-3 font-semibold">Expiration</th>
                            <th class="text-right py-2.5 px-5 font-semibold">Compliance Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($licenses as $license)
                            @php $sb = $statusBadge($license->status); @endphp
                            <tr class="border-b border-white/5 hover:bg-amber-400/5 transition">
                                <td class="py-3 px-5">
                                    <a href="{{ \App\Filament\Resources\FccLicenseResource::getUrl('view', ['record' => $license]) }}"
                                       class="font-semibold text-gray-100 hover:text-amber-300">
                                        {{ $license->call_sign }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 text-gray-300">{{ \Illuminate\Support\Str::limit($license->licensee, 28) }}</td>
                                <td class="py-3 px-3"><span class="{{ $serviceBadge($license->service) }}">{{ $license->service }}</span></td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center gap-1.5 {{ $sb['text'] }} text-xs font-medium">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $sb['dot'] }}"></span>
                                        {{ $sb['label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-gray-300">{{ optional($license->expiration_date)->format('M d, Y') }}</td>
                                <td class="py-3 px-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-24 h-1.5 bg-[#050d1f] rounded-full overflow-hidden">
                                            <div class="h-full {{ $barColor($license->compliance_score) }}" style="width: {{ min(100, $license->compliance_score) }}%"></div>
                                        </div>
                                        <span class="font-semibold {{ $scoreColor($license->compliance_score) }}">{{ number_format($license->compliance_score, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 text-xs text-gray-500 flex items-center justify-between border-t border-amber-400/10">
                <span>Showing 1 to {{ $licenses->count() }} of {{ $licenses->count() }} licenses</span>
                <a href="{{ $licenseIndex }}" class="text-amber-400 hover:text-amber-300">Open Licenses →</a>
            </div>
        </div>

        {{-- Compliance by Rule Category --}}
        <div class="lg:col-span-4 rounded-xl bg-[#0c1f3d] border border-amber-400/15 overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between border-b border-amber-400/10">
                <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">Compliance by Rule Category</h3>
                <a href="{{ $rulesIndex }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 flex items-center gap-1">
                    View full report
                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3" />
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#050d1f]">
                        <tr class="text-amber-400 text-[10px] uppercase tracking-wide">
                            <th class="text-left py-2 px-4 font-semibold">Category</th>
                            <th class="text-right py-2 px-2 font-semibold">Compliant</th>
                            <th class="text-right py-2 px-2 font-semibold">At Risk</th>
                            <th class="text-right py-2 px-2 font-semibold">Non-Comp.</th>
                            <th class="text-right py-2 px-4 font-semibold">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categoryRows as $row)
                            <tr class="border-b border-white/5 hover:bg-amber-400/5">
                                <td class="py-2.5 px-4 text-gray-200">{{ $row['label'] }}</td>
                                <td class="py-2.5 px-2 text-right text-emerald-400 font-semibold">{{ number_format($row['compliant']) }}</td>
                                <td class="py-2.5 px-2 text-right text-amber-300 font-semibold">{{ number_format($row['at_risk']) }}</td>
                                <td class="py-2.5 px-2 text-right text-red-400 font-semibold">{{ number_format($row['non_compliant']) }}</td>
                                <td class="py-2.5 px-4 text-right text-gray-200">{{ $row['percent'] }}%</td>
                            </tr>
                        @endforeach
                        <tr class="border-t border-amber-400/30 bg-[#050d1f]/40">
                            <td class="py-3 px-4 text-amber-400 font-bold uppercase text-xs">Total</td>
                            <td class="py-3 px-2 text-right text-emerald-400 font-bold">{{ number_format($compliant) }}</td>
                            <td class="py-3 px-2 text-right text-amber-300 font-bold">{{ number_format($atRisk) }}</td>
                            <td class="py-3 px-2 text-right text-red-400 font-bold">{{ number_format($nonCompliant) }}</td>
                            <td class="py-3 px-4 text-right text-amber-300 font-bold">{{ $overallPct }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- Bottom row: Donut + Top Non-Compliant + Deadlines + Activity --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-2">

        {{-- Rule Compliance Overview (donut + breakdown) --}}
        <div class="lg:col-span-4 rounded-xl bg-[#0c1f3d] border border-amber-400/15 p-5">
            <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">Rule Compliance Overview</h3>
            <p class="text-xs text-gray-400 mt-0.5 mb-4">Breakdown of compliance status across all FCC rules</p>

            @php
                $deg1 = ($compliant / $totalRules) * 360;
                $deg2 = $deg1 + ($atRisk / $totalRules) * 360;
            @endphp
            <div class="flex items-center gap-6">
                <div class="relative w-36 h-36 shrink-0">
                    <div class="absolute inset-0 rounded-full"
                         style="background: conic-gradient(#fbbf24 0deg, #fbbf24 {{ $deg1 }}deg, #f59e0b {{ $deg1 }}deg, #f59e0b {{ $deg2 }}deg, #ef4444 {{ $deg2 }}deg, #ef4444 360deg);">
                    </div>
                    <div class="absolute inset-3 rounded-full bg-[#0c1f3d] flex flex-col items-center justify-center">
                        <div class="text-2xl font-bold text-amber-400">{{ $overallPct }}%</div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Compliant</div>
                    </div>
                </div>
                <div class="flex-1 text-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span><span class="text-gray-300">Compliant</span></div>
                        <div class="text-right"><span class="text-amber-400 font-semibold">{{ $totalCompliantPct }}%</span> <span class="text-gray-500 ml-1">{{ number_format($compliant) }}</span></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span><span class="text-gray-300">At Risk</span></div>
                        <div class="text-right"><span class="text-amber-300 font-semibold">{{ $totalAtRiskPct }}%</span> <span class="text-gray-500 ml-1">{{ number_format($atRisk) }}</span></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-red-400"></span><span class="text-gray-300">Non-Compliant</span></div>
                        <div class="text-right"><span class="text-red-400 font-semibold">{{ $totalNonPct }}%</span> <span class="text-gray-500 ml-1">{{ number_format($nonCompliant) }}</span></div>
                    </div>
                    <div class="border-t border-white/10 pt-2 mt-2 flex items-center justify-between">
                        <span class="text-gray-400 text-xs uppercase tracking-wide">Total Requirements</span>
                        <span class="font-semibold text-gray-200">{{ number_format($totalRules) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Non-Compliant Rules --}}
        <div class="lg:col-span-4 rounded-xl bg-[#0c1f3d] border border-amber-400/15 overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between border-b border-amber-400/10">
                <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">Top Non-Compliant Rules</h3>
                <a href="{{ $rulesIndex }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300">View all →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-[#050d1f]">
                    <tr class="text-amber-400 text-[10px] uppercase tracking-wide">
                        <th class="text-left py-2 px-5 font-semibold">FCC Rule</th>
                        <th class="text-left py-2 px-2 font-semibold">Description</th>
                        <th class="text-right py-2 px-2 font-semibold">Affected</th>
                        <th class="text-right py-2 px-5 font-semibold">Severity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topNonCompliant as $rule)
                        @php
                            $sevColor = match ($rule->severity) {
                                'critical' => 'text-red-400 bg-red-400/10 border-red-400/30',
                                'high'     => 'text-amber-300 bg-amber-400/10 border-amber-400/30',
                                'medium'   => 'text-sky-300 bg-sky-400/10 border-sky-400/30',
                                default    => 'text-gray-400 bg-gray-400/10 border-gray-400/30',
                            };
                        @endphp
                        <tr class="border-b border-white/5 hover:bg-amber-400/5">
                            <td class="py-2.5 px-5 font-semibold text-amber-300">{{ $rule->rule_number }}</td>
                            <td class="py-2.5 px-2 text-gray-300">{{ \Illuminate\Support\Str::limit($rule->title, 32) }}</td>
                            <td class="py-2.5 px-2 text-right font-semibold text-gray-200">{{ $rule->affected_count }}</td>
                            <td class="py-2.5 px-5 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $sevColor }}">
                                    {{ ucfirst($rule->severity) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Upcoming FCC Deadlines + Compliance Activity stacked --}}
        <div class="lg:col-span-4 space-y-4">
            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/15 overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between border-b border-amber-400/10">
                    <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">Upcoming FCC Deadlines</h3>
                    <a href="{{ $deadlinesIndex }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300">View all →</a>
                </div>
                <ul class="divide-y divide-white/5">
                    @foreach ($deadlines as $d)
                        @php
                            $days = max(0, (int) now()->startOfDay()->diffInDays($d->due_date->startOfDay(), false));
                            $urgent = $days <= 14;
                        @endphp
                        <li class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-amber-400/5">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4 text-amber-400 shrink-0" />
                            <div class="text-amber-300 font-semibold w-24 shrink-0 text-xs uppercase">{{ $d->due_date->format('M d, Y') }}</div>
                            <div class="flex-1 text-gray-200 truncate">{{ $d->title }}</div>
                            <div class="text-xs {{ $urgent ? 'text-red-400 font-semibold' : 'text-gray-400' }} shrink-0">{{ $days }} days</div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-xl bg-[#0c1f3d] border border-amber-400/15 overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between border-b border-amber-400/10">
                    <h3 class="text-base font-semibold text-gray-100 uppercase tracking-wide">Compliance Activity</h3>
                    <span class="text-xs text-gray-500">audit trail</span>
                </div>
                <ul class="divide-y divide-white/5">
                    @foreach ($activity as $event)
                        @php
                            $iconMap = [
                                'technical_review_passed' => ['icon' => 'heroicon-o-check-circle',     'color' => 'text-emerald-400'],
                                'eas_test_filed'          => ['icon' => 'heroicon-o-shield-check',     'color' => 'text-emerald-400'],
                                'public_file_uploaded'    => ['icon' => 'heroicon-o-document-arrow-up', 'color' => 'text-sky-300'],
                                'report_generated'        => ['icon' => 'heroicon-o-document-text',    'color' => 'text-sky-300'],
                                'power_warning'           => ['icon' => 'heroicon-o-exclamation-triangle', 'color' => 'text-amber-300'],
                            ];
                            $cfg = $iconMap[$event->event_type] ?? ['icon' => 'heroicon-o-information-circle', 'color' => 'text-gray-400'];
                        @endphp
                        <li class="px-5 py-2.5 flex items-start gap-3 text-sm hover:bg-amber-400/5">
                            <x-filament::icon :icon="$cfg['icon']" class="h-4 w-4 {{ $cfg['color'] }} shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-200 truncate">{{ $event->summary }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $event->actor }} · {{ optional($event->occurred_at)->diffForHumans() }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
