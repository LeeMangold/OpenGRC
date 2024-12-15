<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Filament\Resources\DataRequestResponseResource;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewDataRequestResponse extends ViewRecord
{
    protected static string $resource = DataRequestResponseResource::class;

    /**
     * Get the header actions for the view.
     *
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        /** @var DataRequestResponse $record */
        $record = $this->record;

        return [
            Action::make('back')
                ->label('Back to Assessment')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->dataRequest->auditItem->audit_id)),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Responses')
                    ->schema([
                        Placeholder::make('response')
                            ->content(fn ($record): HtmlString => new HtmlString($record->response))
                            ->label('Response'),

                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                TextInput::make('name'),
                                TextInput::make('file_path'),
                                TextInput::make('size'),
                                TextInput::make('description'),
                                TextInput::make('uploaded_by'),
                            ])
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(true),
            ]);
    }
}
