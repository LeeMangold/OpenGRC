<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource\Pages;
use App\Models\Audit;
use App\Models\DataRequest;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

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
                    ->options(WorkflowStatus::class)
                    ->default(ResponseStatus::PENDING)
                    ->required(),
                Forms\Components\RichEditor::make('details')
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

    public static function createResponses(DataRequest $record): void
    {
        $record->responses()->create([
            'requester_id' => $record->created_by_id,
            'requestee_id' => $record->assigned_to_id,
            'data_request_id' => $record->id,
            'status' => ResponseStatus::PENDING,
        ]);
    }

}
