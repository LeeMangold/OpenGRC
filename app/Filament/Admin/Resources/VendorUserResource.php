<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VendorUserResource\Pages;
use App\Mail\VendorInvitationMail;
use App\Mail\VendorMagicLinkMail;
use App\Models\VendorUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class VendorUserResource extends Resource
{
    protected static ?string $model = VendorUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Vendor Users';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Vendor User';

    protected static ?string $pluralModelLabel = 'Vendor Users';

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.system');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Contact')
                    ->helperText('Primary contacts receive all vendor communications'),
                Forms\Components\Placeholder::make('status')
                    ->label('Account Status')
                    ->content(fn (?VendorUser $record): string => $record?->hasPassword() ? 'Active' : 'Pending Activation')
                    ->visibleOn(['view', 'edit']),
                Forms\Components\Placeholder::make('last_login_at')
                    ->label('Last Login')
                    ->content(fn (?VendorUser $record): string => $record?->last_login_at?->format('Y-m-d H:i:s') ?? 'Never')
                    ->visibleOn(['view', 'edit']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_password')
                    ->label('Activated')
                    ->state(fn (VendorUser $record): bool => $record->hasPassword())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Contact'),
                Tables\Filters\TernaryFilter::make('activated')
                    ->label('Activated')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('password'),
                        false: fn (Builder $query) => $query->whereNull('password'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('resend_invitation')
                        ->label('Resend Invitation')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn (VendorUser $record): bool => ! $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::resendInvitation($record)),
                    Tables\Actions\Action::make('send_magic_link')
                        ->label('Send Magic Link')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::sendMagicLink($record)),
                    Tables\Actions\Action::make('reset_password')
                        ->label('Send Password Reset')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::sendPasswordReset($record)),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('resend_invitations')
                        ->label('Resend Invitations')
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->hasPassword()) {
                                    static::resendInvitation($record, false);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("Sent {$count} invitation(s)")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorUsers::route('/'),
            'create' => Pages\CreateVendorUser::route('/create'),
            'view' => Pages\ViewVendorUser::route('/{record}'),
            'edit' => Pages\EditVendorUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function resendInvitation(VendorUser $record, bool $notify = true): void
    {
        try {
            Mail::send(new VendorInvitationMail($record));

            if ($notify) {
                Notification::make()
                    ->title('Invitation sent to '.$record->email)
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            if ($notify) {
                Notification::make()
                    ->title('Failed to send invitation')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public static function sendMagicLink(VendorUser $record): void
    {
        try {
            Mail::send(new VendorMagicLinkMail($record));

            Notification::make()
                ->title('Magic link sent to '.$record->email)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send magic link')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function sendPasswordReset(VendorUser $record): void
    {
        try {
            $status = Password::broker('vendor_users')->sendResetLink(
                ['email' => $record->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                Notification::make()
                    ->title('Password reset sent to '.$record->email)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to send password reset')
                    ->body(__($status))
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send password reset')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
