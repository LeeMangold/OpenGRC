<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected string $view = 'filament.resources.policy-resource.pages.view-policy-document';

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_policy')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                // ->size(Size::Small)
                ->color('primary')
                ->action(function () {
                    $policy = $this->getRecord();

                    $pdf = Pdf::loadView('reports.policy', [
                        'policy' => $policy,
                    ]);

                    $filename = $policy->code.'-'.str_replace(' ', '_', $policy->name).'-'.date('Y-m-d').'.pdf';

                    return response()->streamDownload(
                        function () use ($pdf) {
                            echo $pdf->output();
                        },
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),
            Action::make('view_details')
                ->label('View Details')
                ->url(fn () => route('filament.app.resources.policies.view-details', $this->getRecord()))
                ->icon('heroicon-o-eye'),
        ];
    }
}
