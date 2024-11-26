<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewDataRequest extends ViewRecord
{
    protected static string $resource = DataRequestResource::class;

    protected static ?string $title = 'Data Request Viewer';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Split::make([
                    Forms\Components\Section::make('Request Details')
                        ->schema([

                            Forms\Components\Placeholder::make('Requested Information')
                                ->content(function ($record) {
                                    return new HtmlString($record->details);
                                })
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('Responses')
                                ->relationship('responses')
                                ->schema([
                                    Forms\Components\Placeholder::make('response')
                                        ->content(function ($record) {
                                            return new HtmlString($record->response);
                                        })
                                        ->label('Response'),
                                    Forms\Components\Repeater::make('Files')
                                        ->relationship('attachments')
                                        ->columns(2)
                                        ->schema([
                                            Forms\Components\Placeholder::make('file_name')
                                                ->content(function ($record) {
                                                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 inline-block align-middle">
                                                                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h5a.75.75 0 0 1 0 1.5h-5Z" clip-rule="evenodd"/>
                                                                <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 0 0 1.06.053L16.5 4.44v2.81a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-.75-.75h-4.5a.75.75 0 0 0 0 1.5h2.553l-9.056 8.194a.75.75 0 0 0-.053 1.06Z" clip-rule="evenodd"/>
                                                                </svg>';

                                                    $htmlString = "<a href='" . route('priv-storage', $record->file_path) . "' target='_blank' class='inline-flex items-center space-x-2'>
                                                                    <span class='align-middle'>{$record->file_name}</span> $icon
                                                                    </a>";

                                                    return new HtmlString($htmlString);
                                                }),
                                            Forms\Components\Placeholder::make('description')
                                                ->content(function ($record) {
                                                    return new HtmlString($record->description);
                                                }),
                                        ]),
                                ]),
                        ]),
                    Forms\Components\Section::make('Metadata')
                        ->schema([
                            Forms\Components\Placeholder::make('user_id')
                                ->content(function ($record) {
                                    return new HtmlString($record->createdBy->name);
                                })
                                ->label('Assigned To'),
                            Forms\Components\Placeholder::make('audit_item_id')
                                ->content(function ($record) {
                                    return new HtmlString($record->audit->title);
                                })
                                ->label('Audit name'),
                            Forms\Components\Placeholder::make('created_by_id')
                                ->content(function ($record) {
                                    return new HtmlString($record->createdBy->name);
                                })
                                ->label('Request Created By'),
                            Forms\Components\Placeholder::make('status')
                                ->label('Response Status')
                                ->content(function ($record) {
                                    foreach ($record->responses as $response) {
                                        //This is wrong. Need to rework.
                                        if ($response->status == 'Pending') {
                                            return new HtmlString('<span class="badge badge-primary">Pending</span>');
                                        } elseif ($response->status == 'Responded') {
                                            return new HtmlString('<span class="badge badge-info">Responded</span>');
                                        } elseif ($response->status == 'Accepted') {
                                            return new HtmlString('<span class="badge badge-success">Accepted</span>');
                                        } elseif ($response->status == 'Rejected') {
                                            return new HtmlString('<span class="badge badge-danger">Rejected</span>');
                                        }
                                    }
                                }),
                        ])->grow(false),
                ])->from('md'),

            ])->columns(1);

    }

    /**
     * @property DataRequestResponse $record
     */
    protected function getHeaderActions(): array
    {
        /** @var DataRequest $record */
        $record = $this->record;
        return [
            Action::make('back')
                ->label('Back to Audit Item')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->audit_item_id)),
        ];
    }
}
