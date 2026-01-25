<?php

namespace App\Filament\Resources;

use App\Enums\AccessRequestStatus;
use App\Filament\Resources\TrustCenterAccessRequestResource\Pages\ListTrustCenterAccessRequests;
use App\Filament\Resources\TrustCenterAccessRequestResource\Pages\ViewTrustCenterAccessRequest;
use App\Mail\TrustCenterAccessApprovedMail;
use App\Mail\TrustCenterAccessRejectedMail;
use App\Models\TrustCenterAccessRequest;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class TrustCenterAccessRequestResource extends Resource
{
    protected static ?string $model = TrustCenterAccessRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    // Hide from navigation - access via Trust Center Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Access Requests');
    }

    public static function getNavigationGroup(): string
    {
        return __('Trust Center');
    }

    public static function getModelLabel(): string
    {
        return __('Access Request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Access Requests');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Requester Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('requester_name')
                            ->label(__('Name'))
                            ->disabled(),
                        TextInput::make('requester_email')
                            ->label(__('Email'))
                            ->disabled(),
                        TextInput::make('requester_company')
                            ->label(__('Company'))
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make(__('Request Details'))
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('Reason for Access'))
                            ->disabled()
                            ->columnSpanFull(),
                        Toggle::make('nda_agreed')
                            ->label(__('NDA Agreed'))
                            ->disabled(),
                    ]),

                Section::make(__('Requested Documents'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('documents')
                            ->label(__('Documents'))
                            ->relationship('documents', 'name')
                            ->multiple()
                            ->disabled(),
                    ]),

                Section::make(__('Review'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->enum(AccessRequestStatus::class)
                            ->options(collect(AccessRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->disabled(),
                        Textarea::make('review_notes')
                            ->label(__('Review Notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Requester'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('requester_name')
                            ->label(__('Name')),
                        TextEntry::make('requester_email')
                            ->label(__('Email'))
                            ->url(fn (?TrustCenterAccessRequest $record) => $record?->requester_email ? "mailto:{$record->requester_email}" : null),
                        TextEntry::make('requester_company')
                            ->label(__('Company')),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (TrustCenterAccessRequest $record) => $record->status->getColor()),
                    ])
                    ->columns(4),

                Section::make(__('Request Details'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('reason')
                            ->label(__('Reason for Access'))
                            ->columnSpanFull()
                            ->placeholder(__('No reason provided')),
                        IconEntry::make('nda_agreed')
                            ->label(__('NDA Agreed'))
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label(__('Submitted'))
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make(__('Requested Documents'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('documents.name')
                            ->label(__('Documents'))
                            ->badge()
                            ->separator(', '),
                    ]),

                Section::make(__('Review Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('reviewer.name')
                            ->label(__('Reviewed By'))
                            ->placeholder(__('Not reviewed')),
                        TextEntry::make('reviewed_at')
                            ->label(__('Reviewed At'))
                            ->dateTime()
                            ->placeholder(__('Not reviewed')),
                        TextEntry::make('review_notes')
                            ->label(__('Review Notes'))
                            ->columnSpanFull()
                            ->placeholder(__('No notes')),
                    ])
                    ->columns(2)
                    ->visible(fn (TrustCenterAccessRequest $record) => ! $record->isPending()),

                Section::make(__('Access Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('access_expires_at')
                            ->label(__('Access Expires'))
                            ->dateTime()
                            ->color(fn (TrustCenterAccessRequest $record) => $record->isAccessValid() ? 'success' : 'danger'),
                        TextEntry::make('access_count')
                            ->label(__('Access Count')),
                        TextEntry::make('last_accessed_at')
                            ->label(__('Last Accessed'))
                            ->dateTime()
                            ->placeholder(__('Never')),
                        TextEntry::make('magic_link')
                            ->label(__('Magic Link'))
                            ->state(fn (TrustCenterAccessRequest $record) => $record->isAccessValid() ? $record->getAccessUrl() : __('Expired'))
                            ->copyable()
                            ->copyMessage(__('Link copied!'))
                            ->copyMessageDuration(1500)
                            ->url(fn (TrustCenterAccessRequest $record) => $record->isAccessValid() ? $record->getAccessUrl() : null, shouldOpenInNewTab: true)
                            ->color(fn (TrustCenterAccessRequest $record) => $record->isAccessValid() ? 'primary' : 'danger')
                            ->columnSpanFull()
                            ->helperText(fn (TrustCenterAccessRequest $record) => $record->isAccessValid()
                                ? __('Click to open or copy to share with the requester.')
                                : __('This access link has expired.')),
                    ])
                    ->columns(3)
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isApproved()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('requester_name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requester_company')
                    ->label(__('Company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requester_email')
                    ->label(__('Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (TrustCenterAccessRequest $record) => $record->status->getColor()),
                TextColumn::make('documents_count')
                    ->label(__('Documents'))
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('nda_agreed')
                    ->label(__('NDA'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label(__('Reviewed By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(AccessRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label(__('Notes (Optional)'))
                            ->rows(3),
                    ])
                    ->action(function (TrustCenterAccessRequest $record, array $data) {
                        $record->approve(auth()->user(), $data['review_notes'] ?? null);

                        try {
                            Mail::send(new TrustCenterAccessApprovedMail($record));

                            Notification::make()
                                ->title(__('Access Approved'))
                                ->body(__('Access approved and email sent to :email', ['email' => $record->requester_email]))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('Access Approved'))
                                ->body(__('Access approved but email failed to send: :error', ['error' => $e->getMessage()]))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isPending()),
                Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label(__('Reason for Rejection'))
                            ->rows(3),
                    ])
                    ->action(function (TrustCenterAccessRequest $record, array $data) {
                        $record->reject(auth()->user(), $data['review_notes'] ?? null);

                        try {
                            Mail::send(new TrustCenterAccessRejectedMail($record));

                            Notification::make()
                                ->title(__('Access Rejected'))
                                ->body(__('Request rejected and email sent to :email', ['email' => $record->requester_email]))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('Access Rejected'))
                                ->body(__('Request rejected but email failed to send: :error', ['error' => $e->getMessage()]))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isPending()),
                Action::make('revoke')
                    ->label(__('Revoke Access'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Revoke Access'))
                    ->modalDescription(__('Are you sure you want to revoke this user\'s access? Their magic link will be invalidated immediately.'))
                    ->schema([
                        Textarea::make('review_notes')
                            ->label(__('Reason for Revocation (Optional)'))
                            ->rows(3),
                    ])
                    ->action(function (TrustCenterAccessRequest $record, array $data) {
                        $record->revoke(auth()->user(), $data['review_notes'] ?? null);

                        Notification::make()
                            ->title(__('Access Revoked'))
                            ->body(__('Access has been revoked for :name', ['name' => $record->requester_name]))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isApproved()),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrustCenterAccessRequests::route('/'),
            'view' => ViewTrustCenterAccessRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
