<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource\Pages\CreateDataRequestResponse;
use App\Filament\Resources\DataRequestResponseResource\Pages\EditDataRequestResponse;
use App\Filament\Resources\DataRequestResponseResource\Pages\ListDataRequestResponses;
use App\Filament\Resources\DataRequestResponseResource\Pages\ViewDataRequestResponse;
use App\Models\Control;
use App\Models\DataRequestResponse;
use App\Models\Policy;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DataRequestResponseResource extends Resource
{
    protected static ?string $model = DataRequestResponse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([
                Section::make('Evidence Requested')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Placeholder::make('request.dataRequest.audit.name')
                            ->content(fn ($record) => $record->dataRequest->audit->title ?? 'No audit name available')
                            ->label('Audit Name'),
                        Placeholder::make('dataRequest.code')
                            ->content(fn ($record) => $record->dataRequest->code ?? 'No code')
                            ->label('Request Code'),
                        Section::make('Data Request Details')
                            ->columnSpanFull()
                            ->schema([
                                Placeholder::make('request.dataRequest.details')
                                    ->content(fn ($record) => new HtmlString($record->dataRequest->details ?? 'No details available'))
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                        Section::make(function ($record) {
                            // Check if any audit items are Controls
                            $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                return $item->auditable_type === Control::class;
                            });

                            // Fallback to single relationship
                            if (! $hasControl && $record->dataRequest->auditItem) {
                                $hasControl = $record->dataRequest->auditItem->auditable_type === Control::class;
                            }

                            return $hasControl ? 'Control Details' : 'Implementation Details';
                        })
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(function ($record) {
                                // Only collapse for Controls, expand for Implementations
                                $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                    return $item->auditable_type === Control::class;
                                });

                                if (! $hasControl && $record->dataRequest->auditItem) {
                                    $hasControl = $record->dataRequest->auditItem->auditable_type === Control::class;
                                }

                                return $hasControl;
                            })
                            ->schema([
                                Placeholder::make('request.dataRequest.auditItems.info')
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $items = $record->dataRequest->auditItems->map(function ($item) {
                                            if ($item->auditable) {
                                                $code = $item->auditable->code ?? '';
                                                $title = $item->auditable->title ?? '';
                                                // Implementation uses 'details', Control uses 'description'
                                                $details = $item->auditable->details ?? $item->auditable->description ?? '';

                                                $output = '';
                                                if ($code) {
                                                    $output .= '<strong>Code:</strong> '.e($code).'<br>';
                                                }
                                                if ($title) {
                                                    $output .= '<strong>Title:</strong> '.e($title).'<br>';
                                                }
                                                if ($details) {
                                                    $output .= '<strong>Details:</strong> '.$details;
                                                }

                                                return $output;
                                            }

                                            return null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($items) && $record->dataRequest->auditItem?->auditable) {
                                            $auditable = $record->dataRequest->auditItem->auditable;
                                            $code = $auditable->code ?? '';
                                            $title = $auditable->title ?? '';
                                            // Implementation uses 'details', Control uses 'description'
                                            $details = $auditable->details ?? $auditable->description ?? '';

                                            $output = '';
                                            if ($code) {
                                                $output .= '<strong>Code:</strong> '.e($code).'<br>';
                                            }
                                            if ($title) {
                                                $output .= '<strong>Title:</strong> '.e($title).'<br>';
                                            }
                                            if ($details) {
                                                $output .= '<strong>Details:</strong> '.$details;
                                            }
                                            $items = [$output];
                                        }

                                        return new HtmlString(! empty($items) ? implode('<hr class="my-4">', $items) : 'No details available');
                                    })
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Current Implementations')
                            ->columnSpanFull()
                            ->collapsible()
                            ->visible(function ($record) {
                                // Check if any audit items are Controls with implementations
                                foreach ($record->dataRequest->auditItems as $item) {
                                    if ($item->auditable_type === Control::class && $item->auditable?->implementations->isNotEmpty()) {
                                        return true;
                                    }
                                }

                                // Fallback to single relationship
                                if ($record->dataRequest->auditItem) {
                                    $auditItem = $record->dataRequest->auditItem;
                                    if ($auditItem->auditable_type === Control::class && $auditItem->auditable?->implementations->isNotEmpty()) {
                                        return true;
                                    }
                                }

                                return false;
                            })
                            ->schema([
                                Placeholder::make('implementations')
                                    ->content(function ($record) {
                                        $implementations = collect();

                                        // Gather implementations from all Control audit items
                                        foreach ($record->dataRequest->auditItems as $item) {
                                            if ($item->auditable_type === Control::class && $item->auditable) {
                                                $implementations = $implementations->merge($item->auditable->implementations);
                                            }
                                        }

                                        // Fallback to single relationship
                                        if ($implementations->isEmpty() && $record->dataRequest->auditItem) {
                                            $auditItem = $record->dataRequest->auditItem;
                                            if ($auditItem->auditable_type === Control::class && $auditItem->auditable) {
                                                $implementations = $auditItem->auditable->implementations;
                                            }
                                        }

                                        $items = $implementations->unique('id')->map(function ($impl) {
                                            $output = '';
                                            if ($impl->code) {
                                                $output .= '<strong>Code:</strong> '.e($impl->code).'<br>';
                                            }
                                            if ($impl->title) {
                                                $output .= '<strong>Title:</strong> '.e($impl->title).'<br>';
                                            }
                                            if ($impl->details) {
                                                $output .= '<strong>Details:</strong> '.$impl->details;
                                            }

                                            return $output;
                                        })->filter()->all();

                                        return new HtmlString(! empty($items) ? implode('<hr class="my-4">', $items) : 'No implementations available');
                                    })
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Response')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('response')
                            ->maxLength(65535)
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->required(function ($get, $record) {
                                if (is_null($record)) {
                                    return false;
                                }
                                $auditManagerId = $record->manager_id ?: 0;
                                $currentUserId = auth()->id();

                                return $currentUserId !== $auditManagerId;
                            }),

                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                Textarea::make('description')
                                    ->maxLength(1024)
                                    ->required(),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->required()
                                    ->preserveFilenames()
                                    ->disk(setting('storage.driver', 'private'))
                                    ->directory('data-request-attachments')
                                    ->storeFileNamesIn('file_name')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->deletable()
                                    ->reorderable()
                                    ->maxSize(10240) // 2MB max (matches PHP upload_max_filesize)
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $random = Str::random(8);

                                        return $random.'-'.$file->getClientOriginalName();
                                    })
                                    ->deleteUploadedFileUsing(function ($state) {
                                        if ($state) {
                                            Storage::disk(setting('storage.driver', 'private'))->delete($state);
                                        }
                                    }),

                                Hidden::make('uploaded_by')
                                    ->default(Auth::id()),
                                Hidden::make('audit_id')
                                    ->default(function ($livewire) {
                                        $drr = DataRequestResponse::where('id', $livewire->data['id'])->first();

                                        return $drr->dataRequest->audit_id;
                                    }),
                            ]),

                        Repeater::make('policyAttachments')
                            ->relationship('policyAttachments')
                            ->label('Policy Evidence')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                Select::make('policy_id')
                                    ->label('Policy')
                                    ->options(fn () => Policy::query()
                                        ->select(['id', 'code', 'name'])
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [$p->id => "({$p->code}) {$p->name}"]))
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                                Textarea::make('description')
                                    ->label('Relevance Description')
                                    ->helperText('Explain how this policy serves as evidence')
                                    ->maxLength(1024)
                                    ->required(),
                            ])
                            ->addActionLabel('Attach Policy')
                            ->defaultItems(0)
                            ->reorderable(false),

                    ]),
                Section::make('Comments')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        ViewField::make('comments')
                            ->view('filament.forms.components.inline-comments')
                            ->dehydrated(false),
                    ]),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('dataRequest.details')
                    ->label('Data Request Details')
                    ->wrap()
                    ->html()
                    ->limit(200),
                TextColumn::make('requester.name')
                    ->label('Requester'),
                TextColumn::make('requestee.name')
                    ->label('Requestee'),
                TextColumn::make('status')
                    ->badge()
                    ->label('Status'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ResponseStatus::class)
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataRequestResponses::route('/'),
            'create' => CreateDataRequestResponse::route('/create'),
            'edit' => EditDataRequestResponse::route('/{record}/edit'),
            'view' => ViewDataRequestResponse::route('/{record}'),
        ];
    }
}
