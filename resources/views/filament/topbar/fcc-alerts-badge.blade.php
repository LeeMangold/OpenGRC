@php
    use App\Models\FccDeadline;
    use App\Models\FccLicense;
    use App\Models\FccRegulatoryFee;

    $overdueDeadlines = FccDeadline::query()
        ->where('status', '!=', 'completed')
        ->where('due_date', '<', now()->addDays(14))
        ->count();

    $nonCompliantLicenses = FccLicense::query()
        ->whereIn('status', ['non_compliant'])
        ->count();

    $overdueFees = class_exists(FccRegulatoryFee::class)
        ? FccRegulatoryFee::query()->where('status', 'overdue')->count()
        : 0;

    $alertCount = $overdueDeadlines + $nonCompliantLicenses + $overdueFees;
    $deadlinesUrl = \App\Filament\Resources\FccDeadlineResource::getUrl('index');
@endphp

@if ($alertCount > 0)
    <a href="{{ $deadlinesUrl }}"
       class="fi-fcc-alerts-badge {{ $nonCompliantLicenses > 0 ? 'is-critical' : '' }}"
       title="{{ $nonCompliantLicenses }} non-compliant licenses, {{ $overdueDeadlines }} urgent deadlines, {{ $overdueFees }} overdue fees">
        <x-filament::icon icon="heroicon-o-bell-alert" class="h-4 w-4" />
        {{ $alertCount }} Alert{{ $alertCount === 1 ? '' : 's' }}
    </a>
@endif
