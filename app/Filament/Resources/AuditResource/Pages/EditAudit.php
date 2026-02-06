<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditAudit extends EditRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Audit Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->hint('Give the audit a distinctive title.')
                            ->required()
                            ->columns(1)
                            ->placeholder('2023 SOC 2 Type II Audit')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Select::make('manager_id')
                            ->label('Audit Manager')
                            ->hint('Who will be managing this audit?')
                            ->options(User::optionsWithDeactivated())
                            ->columns(1)
                            ->searchable(),
                        Select::make('members')
                            ->relationship('members')
                            ->label('Additional Members')
                            ->hint('Who else should have full access to the Audit?')
                            ->helperText('Note: You don\'t need to add evidence people who are only fulfilling requests here.')
                            ->options(User::optionsWithDeactivated())
                            ->columns(1)
                            ->multiple()
                            ->searchable(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        DatePicker::make('start_date')
                            ->default(now())
                            ->required(),
                        DatePicker::make('end_date')
                            ->default(now()->addDays(30))
                            ->required(),
                        AuditResource::taxonomySelect('Department', 'department')
                            ->nullable()
                            ->columnSpan(1),
                        AuditResource::taxonomySelect('Scope', 'scope')
                            ->nullable()
                            ->columnSpan(1),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [$this->record]);
    }
}
