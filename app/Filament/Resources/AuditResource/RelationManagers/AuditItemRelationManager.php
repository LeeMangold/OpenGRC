<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\AuditItem;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'AuditItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Control Information')
                    ->schema([
                        Placeholder::make('control_code')
                            ->label('Control Code')
                            ->content(fn (AuditItem $record): string => $record->control->code),
                        Placeholder::make('control_title')
                            ->label('Control Title')
                            ->content(fn (AuditItem $record): string => $record->control->title),
                        Placeholder::make('control_desc')
                            ->label('Control Description')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(optional($record->control)->description ?? ''))
                            ->columnSpanFull(),
                        Placeholder::make('control_discussion')
                            ->label('Control Discussion')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(optional($record->control)->discussion ?? ''))
                            ->columnSpanFull(),

                    ])->columns(2)->collapsible(true),

                Forms\Components\Section::make('Evaluation')
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options(WorkflowStatus::class)
                            ->default('Not Started')
                            ->grouped(),
                        ToggleButtons::make('effectiveness')
                            ->label('Effectiveness')
                            ->options(Effectiveness::class)
                            ->default('Not Effective')
                            ->grouped(),
                        ToggleButtons::make('applicability')
                            ->label('Applicability')
                            ->options(Applicability::class)
                            ->default('Applicable')
                            ->grouped(),
                        RichEditor::make('auditor_notes')
                            ->columnSpanFull()
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles'
                            ])
                            ->label('Auditor Notes'),
                    ]),

                Forms\Components\Section::make('Audit Evidence')
                    ->schema([

                        // Todo: This can be replaced with a Repeater component when nested relationships are
                        // supported in Filament - potentially in v4.x
                        Placeholder::make('control.implementations')
                            ->label('Documented Implementations')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString($this->implementationsTable($record)))
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Implementations that a related to this control.'),

                        Placeholder::make('data_requests')
                            ->label('Data Requests Issued')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString($this->dataRequestsTable($record)))
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Data Requests that have been issued.'),

                    ])->collapsible(true),
            ]);
    }

    protected function implementationsTable(AuditItem $record): HtmlString
    {
        // Assuming $record->dataRequests returns an array or collection of data
        $dataRequests = $record->control->implementations;

        // Start building the table HTML
        $html = '<table class="table-auto w-full border-collapse border border-gray-200">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border px-4 py-2">Code</th>';
        $html .= '<th class="border px-4 py-2">Title</th>';
        $html .= '<th class="border px-4 py-2">Details</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Loop through dataRequests and generate table rows
        foreach ($dataRequests as $request) {
            $html .= '<tr>';
            $html .= '<td class="border px-4 py-2">'.e($request->code).'</td>';
            $html .= '<td class="border px-4 py-2">'.'<a target="_blank"   href='.
                route('filament.app.resources.implementations.view', $request->id).
                '>'.e($request->title).'</a></td>';
            $html .= '<td class="border px-4 py-2">'.$request->details.'</td>';
            $html .= '</tr>';
        }

        // Close the table
        $html .= '</tbody>';
        $html .= '</table>';

        // Return the generated HTML as an HtmlString
        return new HtmlString($html);
    }

    protected function dataRequestsTable(AuditItem $record): HtmlString
    {
        // Assuming $record->dataRequests returns an array or collection of data
        $dataRequests = $record->dataRequests;

        // Start building the table HTML
        $html = '<table class="table-auto w-full border-collapse border border-gray-200">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border px-4 py-2">Request</th>';
        $html .= '<th class="border px-4 py-2">Response</th>';
        $html .= '<th class="border px-4 py-2">Status</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Loop through dataRequests and generate table rows
        foreach ($dataRequests as $request) {
            $html .= '<tr>';
            $html .= '<td class="border px-4 py-2">'.e($request->details).'</td>';
            $html .= '<td class="border px-4 py-2">';
            foreach ($request->responses as $r) {
                $html .= '<div>'.$r->response.'</div>';
                if (isset($r->attachments)) {
                    foreach ($r->attachments as $attachment) {
                        $html .= '***<a href="#">'.$attachment->description.'</a>';
                    }
                }
            }

            $html .= '</td>';
            $html .= '<td class="border px-4 py-2">'.$request->status.'</td>';
            $html .= '</tr>';
        }

        // Close the table
        $html .= '</tbody>';
        $html .= '</table>';

        // Return the generated HTML as an HtmlString
        return new HtmlString($html);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable);
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable->code);
                    })
                    ->label('Code'),
                Tables\Columns\TextColumn::make('title')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable->title);
                    }),
                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('applicability')->sortable(),
                Tables\Columns\TextColumn::make('effectiveness')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Assess control')
                    ->visible(fn (AuditItem $record): bool => $record->audit->status === WorkflowStatus::INPROGRESS)
                    ->url(fn (AuditItem $record): string => route('filament.app.resources.audit-items.edit', ['record' => $record->id])),
            ])
            ->bulkActions([]);

    }
}
