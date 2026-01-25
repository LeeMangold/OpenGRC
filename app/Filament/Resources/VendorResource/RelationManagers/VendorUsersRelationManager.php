<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Mail\VendorInvitationMail;
use App\Mail\VendorMagicLinkMail;
use App\Models\VendorUser;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class VendorUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorUsers';

    protected static ?string $title = 'Portal Users';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (VendorUser $record): string => $record->hasPassword() ? 'Active' : 'Pending')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Contact'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Invite User')
                    ->after(function (VendorUser $record) {
                        try {
                            Mail::send(new VendorInvitationMail($record));
                            Notification::make()
                                ->title('Invitation sent to '.$record->email)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('User created but email failed')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('resend_invitation')
                        ->label('Resend Invitation')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn (VendorUser $record): bool => ! $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
                            try {
                                Mail::send(new VendorInvitationMail($record));
                                Notification::make()
                                    ->title('Invitation sent to '.$record->email)
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Failed to send invitation')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('send_magic_link')
                        ->label('Send Magic Link')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
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
                        }),
                    Tables\Actions\Action::make('make_primary')
                        ->label('Make Primary')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn (VendorUser $record): bool => ! $record->is_primary)
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
                            // Remove primary from other users
                            $record->vendor->vendorUsers()->update(['is_primary' => false]);
                            $record->update(['is_primary' => true]);

                            // Update vendor's primary contact
                            $record->vendor->update(['primary_contact_id' => $record->id]);

                            Notification::make()
                                ->title($record->name.' is now the primary contact')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Revoke Access'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Revoke Access'),
                ]),
            ]);
    }
}
