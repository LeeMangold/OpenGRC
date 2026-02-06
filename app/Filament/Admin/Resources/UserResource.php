<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages\CreateUser;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Filament\Admin\Resources\UserResource\Pages\ViewUser;
use App\Filament\Admin\Resources\UserResource\RelationManagers\RolesRelationManager;
use App\Mail\UserCreatedMail;
use App\Mail\UserForceResetMail;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.user');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.system');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique('users', 'email')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->label('Roles'),
                Placeholder::make('last_activity')
                    ->content(
                        function (User $record) {
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
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('last_activity')
                    ->dateTime()
                    ->sortable(),
                // Roles
                TextColumn::make('roles')
                    ->searchable()
                    ->label('Roles')
                    ->badge()
                    ->sortable()
                    ->state(fn ($record) => $record->roles->pluck('name')->join(', ')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                    Action::make('reset_password')
                        ->label('Force Password Reset')
                        ->hidden(fn (User $record) => $record->last_activity == null)
                        ->action(fn (User $record) => UserResource::resetPasswordAction($record))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-key')
                        ->color('warning'),
                    Action::make('reinvite')
                        ->label('Re-invite User')
                        ->hidden(fn (User $record) => $record->last_activity !== null)
                        ->action(fn (User $record) => UserResource::reinviteUserAction($record))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-key')
                        ->color('primary'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'roles' => RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
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
        $words = collect(range(1, 4))->map(fn () => Str::random(6))->implode('-');

        return $words;
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
