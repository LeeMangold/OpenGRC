<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Standard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {

        $audits_in_progress = Audit::all()->where('status', WorkflowStatus::INPROGRESS)->count();
        $audits_performed = Audit::all()->where('status', WorkflowStatus::COMPLETED)->count();
        $implementations = Implementation::count();
        $controls_in_scope = [];
        $controls_in_scope_tested = [];

        foreach (Standard::where('status', 'In Scope')->get() as $standard) {
            foreach (Control::where('standard_id', $standard->id)->where('effectiveness', '<>', 'Not Assessed')->get() as $control) {
                $controls_in_scope_tested[] = $control->id;
            }
        }

        foreach (Standard::where('status', 'In Scope')->get() as $standard) {
            foreach (Control::where('standard_id', $standard->id)->get() as $control) {
                if ($control->applicability !== Applicability::NOTAPPLICABLE->value) {
                    $controls_in_scope[] = $control->id;
                }
            }
        }

        $controls_in_scope_count = count(array_unique($controls_in_scope));
        $controls_in_scope_tested_count = count(array_unique($controls_in_scope_tested));

        // $controls_without_implementations = Control::where('applicability', 'Applicable')->whereDoesntHave('implementations')->count();

        return [
            Stat::make(__('widgets.stats.audits_in_progress'), $audits_in_progress),
            Stat::make(__('widgets.stats.audits_completed'), $audits_performed),
            Stat::make(__('widgets.stats.controls_in_scope'), $controls_in_scope_count),
            //            ->description('Controls that are part of in-scope standards and not determined to be Not-Applicable already')
            //            Stat::make('Controls Tested', $controls_in_scope_tested_count),
            Stat::make(__('widgets.stats.implementations'), $implementations),
            // Stat::make('Controls without Implementations', $controls_without_implementations),
        ];
    }
}
