<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\UserCreatedMail;
use App\Mail\UserForceResetMail;
use App\Models\User;
use Exception;
use Faker\Factory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.user');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique('users', 'email')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Placeholder::make('roles_list')
                    ->content(
                        function (Model $record) {
                            return $record->roles->pluck('name')->join(', ');
                        }
                    )
                    ->label('Roles')
                    ->disabled(),
                Forms\Components\Placeholder::make('last_activity')
                    ->content(
                        function (Model $record) {
                            return $record->last_activity ? $record->last_activity->format('Y-m-d H:i:s') : null;
                        }
                    )
                    ->label('Last Activity')
                    ->disabled(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_activity')
                    ->dateTime()
                    ->sortable(),
                // Roles
                Tables\Columns\TextColumn::make('roles')
                    ->searchable()
                    ->label('Roles')
                    ->badge()
                    ->sortable()
                    ->state(fn ($record) => $record->roles->pluck('name')->join(', ')),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\Action::make('reset_password')
                        ->label('Force Password Reset')
                        ->hidden(fn (User $record) => $record->last_activity == null)
                        ->action(fn (User $record) => UserResource::resetPasswordAction($record))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-key')
                        ->color('warning'),
                    Tables\Actions\Action::make('reinvite')
                        ->label('Re-invite User')
                        ->hidden(fn (User $record) => $record->last_activity !== null)
                        ->action(fn (User $record) => UserResource::reinviteUserAction($record))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-key')
                        ->color('primary'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function createDefaultPassword(): string
    {
        return Str::replace(' ', '-', implode(' ', Factory::create('en_US')->words(4)));
    }

    public static function resetPasswordAction(User $record): void
    {
        $password = UserResource::createDefaultPassword();
        $record->password_reset_required = true;
        $record->password = bcrypt($password);
        $record->save();

        // Send the email with the password to the user
        Mail::to($record->email)->send(new UserForceResetMail($record->email, $record->name, $password));

        Notification::make()
            ->title('Password reset forced for user')
            ->warning()
            ->send();
    }

    public static function reinviteUserAction(User $record): void
    {
        // Generate a new password for the user
        $password = UserResource::createDefaultPassword();
        $record->password_reset_required = true;
        $record->password = bcrypt($password);
        $record->save();

        // Send the email with the password to the user
        Mail::to($record->email)->send(new UserCreatedMail($record->email, $record->name, $password));

        Notification::make()
            ->title('User Re-invited')
            ->success()
            ->send();
    }
}
