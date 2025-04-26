<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResource\Pages;
use App\Models\Audit;
use App\Models\DataRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DataRequestResource extends Resource
{
    protected static ?string $model = DataRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Foundations';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Assigned To')
                    ->options(User::pluck('name', 'id')->toArray())
                    ->searchable(),
                Forms\Components\Select::make('audit_item_id')
                    ->label('Audit name')
                    ->options(Audit::pluck('title', 'id')->toArray())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('created_by_id')
                    ->label('Created By')
                    ->options(User::pluck('name', 'id')->toArray())
                    ->default(auth()->id())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Response Status')
                    ->options(ResponseStatus::class)
                    ->default(ResponseStatus::PENDING)
                    ->required(),
                Forms\Components\RichEditor::make('details')
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('audit_item_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataRequests::route('/'),
            'create' => Pages\CreateDataRequest::route('/create'),
            'view' => Pages\ViewDataRequest::route('/{record}'),
            'edit' => Pages\EditDataRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function createResponses(DataRequest $record, ?string $dueDate = null): void
    {
        $record->responses()->create([
            'requester_id' => $record->created_by_id,
            'requestee_id' => $record->assigned_to_id,
            'data_request_id' => $record->id,
            'due_at' => $dueDate,
            'status' => ResponseStatus::PENDING,
        ]);
    }

    public static function getEditForm(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Placeholder::make('Requested Information')
                            ->content(function ($record) {
                                return new HtmlString($record->details ?? '');
                            })
                            ->columnSpanFull(),
                        Placeholder::make('control')
                            ->label('Control')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                return $record->auditItem->auditable->code.' - '.$record->auditItem->auditable->title;
                            }),
                        Placeholder::make('control_description')
                            ->label('Control Description')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                return new HtmlString($record->auditItem->auditable->description);
                            }),
                        Forms\Components\Repeater::make('Responses')
                            ->label('Responses')
                            ->relationship('responses')
                            ->addable(false)
                            ->columns()
                            ->deletable(false)
                            ->schema([
                                Select::make('requestee_id')
                                    ->label('Assigned To')
                                    ->relationship('requestee', 'name')
                                    ->required(),
                                ToggleButtons::make('status')
                                    ->label('Status')
                                    ->options(ResponseStatus::class)
                                    ->default(ResponseStatus::PENDING)
                                    ->grouped()
                                    ->required(),
                                Forms\Components\DatePicker::make('due_at')
                                    ->label('Due Date')
                                    ->required(),
                                Placeholder::make('response')
                                    ->label('Text Response')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        return new HtmlString($record ? $record->response : '');
                                    })
                                    ->label('Response'),
                                Placeholder::make('attachments')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        $output = "<table class='min-w-full divide-y divide-gray-200'>
                                        <thead class='bg-gray-50'>
                                            <tr>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-auto'>File</th>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class='bg-white divide-y divide-gray-200'>";
                                        if ($record) {
                                            foreach ($record->attachments as $attachment) {
                                                $storage = Storage::disk(config('filesystems.default'));
                                                $downloadUrl = null;
                                                
                                                if ($storage->exists($attachment->file_path)) {
                                                    $driver = config('filesystems.default');
                                                    if (in_array($driver, ['s3', 'minio'])) {
                                                        $downloadUrl = $storage->temporaryUrl($attachment->file_path, now()->addMinutes(5));
                                                    } else {
                                                        $downloadUrl = $storage->url($attachment->file_path);
                                                    }
                                                }
                                                
                                                $output .= "<tr>
                                                <td class='px-6 py-4 whitespace-nowrap w-auto'>";
                                                if ($downloadUrl) {
                                                    $output .= "<a href='{$downloadUrl}' class='text-indigo-600 hover:text-indigo-900' target='_blank'>{$attachment->file_name}</a>";
                                                } else {
                                                    $output .= "<span class='text-gray-400'>{$attachment->file_name} (not available)</span>";
                                                }
                                                $output .= "</td>
                                                <td class='px-6 py-4 whitespace-nowrap'>{$attachment->description}</td>
                                            </tr>";
                                            }
                                        }
                                        $output .= '</tbody></table>';

                                        return new HtmlString($output);
                                    })
                                    ->label('Attachments'),
                            ]),
                    ]),
            ]);

    }
}
