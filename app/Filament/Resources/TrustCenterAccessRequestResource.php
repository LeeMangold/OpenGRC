<?php

namespace App\Filament\Resources;

use App\Enums\AccessRequestStatus;
use App\Filament\Resources\TrustCenterAccessRequestResource\Pages;
use App\Mail\TrustCenterAccessApprovedMail;
use App\Mail\TrustCenterAccessRejectedMail;
use App\Models\TrustCenterAccessRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class TrustCenterAccessRequestResource extends Resource
{
    protected static ?string $model = TrustCenterAccessRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Requester Information'))
                    ->schema([
                        Forms\Components\TextInput::make('requester_name')
                            ->label(__('Name'))
                            ->disabled(),
                        Forms\Components\TextInput::make('requester_email')
                            ->label(__('Email'))
                            ->disabled(),
                        Forms\Components\TextInput::make('requester_company')
                            ->label(__('Company'))
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('Request Details'))
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('Reason for Access'))
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('nda_agreed')
                            ->label(__('NDA Agreed'))
                            ->disabled(),
                    ]),

                Forms\Components\Section::make(__('Requested Documents'))
                    ->schema([
                        Forms\Components\Select::make('documents')
                            ->label(__('Documents'))
                            ->relationship('documents', 'name')
                            ->multiple()
                            ->disabled(),
                    ]),

                Forms\Components\Section::make(__('Review'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->enum(AccessRequestStatus::class)
                            ->options(collect(AccessRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->disabled(),
                        Forms\Components\Textarea::make('review_notes')
                            ->label(__('Review Notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Requester'))
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
                    ->schema([
                        TextEntry::make('documents.name')
                            ->label(__('Documents'))
                            ->badge()
                            ->separator(', '),
                    ]),

                Section::make(__('Review Information'))
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
            ->columns([
                Tables\Columns\TextColumn::make('requester_name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester_company')
                    ->label(__('Company'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester_email')
                    ->label(__('Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (TrustCenterAccessRequest $record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label(__('Documents'))
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('nda_agreed')
                    ->label(__('NDA'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label(__('Reviewed By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(AccessRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Access Approved'))
                                ->body(__('Access approved but email failed to send: :error', ['error' => $e->getMessage()]))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isPending()),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Access Rejected'))
                                ->body(__('Request rejected but email failed to send: :error', ['error' => $e->getMessage()]))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->isPending()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListTrustCenterAccessRequests::route('/'),
            'view' => Pages\ViewTrustCenterAccessRequest::route('/{record}'),
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
