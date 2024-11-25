<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\WorkflowStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                TextArea::make('description')
                    ->label('Description')
                    ->columnSpanFull()
                    ->required(),
                FileUpload::make('file_path')
                    ->downloadable(true)
                    ->columnSpanFull()
                    ->label('File')
                    ->required()
                    ->multiple()
                    ->disk('private')
                    ->directory('attachments')
                    ->storeFileNamesIn('attachment_file_names'),
                DateTimePicker::make('uploaded_at')
                    ->label('Uploaded At')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')->label("File Name"),
                Tables\Columns\TextColumn::make('description')->html()->limit(100)->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded At'),
                Tables\Columns\TextColumn::make('uploaded_by')
                    ->label('Uploaded By')
                    ->getStateUsing(function ($record) {
                        $user = User::find($record->uploaded_by);
                        return $user ? $user->name : 'Unknown';
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
            ]);
    }

}
