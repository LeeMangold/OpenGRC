<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('type')->enum(ApplicationType::class)->options(collect(ApplicationType::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))->required(),
                Forms\Components\Textarea::make('description')->maxLength(65535),
                Forms\Components\Select::make('status')->enum(ApplicationStatus::class)->options(collect(ApplicationStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))->required(),
                Forms\Components\TextInput::make('url')->maxLength(512),
                Forms\Components\Textarea::make('notes')->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn($record) => $record->type->getColor()),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('url')->url(fn($record) => $record->url, true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 