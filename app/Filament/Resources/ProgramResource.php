<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.program');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.foundations');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('program_manager_id')
                    ->label('Program Manager')
                    ->relationship('programManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('scope_status')
                    ->options([
                        'In Scope' => 'In Scope',
                        'Out of Scope' => 'Out of Scope',
                        'Pending Review' => 'Pending Review',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(new class implements \Illuminate\Contracts\Support\Htmlable
            {
                public function toHtml()
                {
                    return "<div class='fi-section-content p-6'>
                        Programs serve as comprehensive frameworks that unite security standards and controls into cohesive, manageable
                        initiatives within an organization. They represent structured approaches to achieving specific security objectives
                        by combining relevant standards (which define 'what' needs to be done) with appropriate controls (which specify
                        'how' to do it). Each program typically addresses a distinct area of compliance, risk management, or security
                        enhancement, making it easier to track progress, measure effectiveness, and ensure accountability. For example,
                        a Data Privacy Program might incorporate standards from GDPR and CCPA, implementing specific controls like
                        data encryption, access management, and regular audits to meet these requirements. By organizing security
                        measures into programs, organizations can better coordinate their security efforts, allocate resources
                        effectively, and maintain clear oversight of their security posture across different domains and objectives.
                        </div>";
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('programManager.name')
                    ->label('Program Manager')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_audit_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope_status')
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
                //
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\StandardsRelationManager::class,
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\RisksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
