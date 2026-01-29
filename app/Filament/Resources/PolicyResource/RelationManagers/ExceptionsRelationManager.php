<?php

namespace App\Filament\Resources\PolicyResource\RelationManagers;

use App\Enums\PolicyExceptionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    protected static ?string $title = 'Policy Exceptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exception Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Exception Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('justification')
                            ->label('Business Justification')
                            ->helperText('Explain why this exception is necessary')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Risk & Mitigation')
                    ->schema([
                        Textarea::make('risk_assessment')
                            ->label('Risk Assessment')
                            ->helperText('Describe the risks associated with granting this exception')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('compensating_controls')
                            ->label('Compensating Controls')
                            ->helperText('Describe any mitigating controls in place')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Dates')
                    ->schema([
                        Select::make('status')
                            ->options(PolicyExceptionStatus::class)
                            ->default(PolicyExceptionStatus::Pending)
                            ->required(),
                        Select::make('requested_by')
                            ->label('Requested By')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => in_array($get('status'), [
                                PolicyExceptionStatus::Approved->value,
                                PolicyExceptionStatus::Approved,
                            ])),
                        DatePicker::make('requested_date')
                            ->label('Requested Date')
                            ->default(now()),
                        DatePicker::make('effective_date')
                            ->label('Effective Date'),
                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->helperText('Leave blank for no expiration'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['requester', 'approver']))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Exception')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('requested_date')
                    ->label('Requested')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('No expiration'),
                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PolicyExceptionStatus::class),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Exception'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel(),
                EditAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
