<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileAttachmentResource\Pages;
use App\Models\FileAttachment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FileAttachmentResource extends Resource
{
    protected static ?string $model = FileAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('description')
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->preserveFilenames()
                    ->disk('private')
                    ->directory(function () {
                        $rand = Carbon::now()->timestamp.'-'.Str::random(2);

                        return 'attachments/'.$rand;
                    })
                    ->downloadable()
                    ->visibility('private')
                    ->openable()
                    ->deletable()
                    ->reorderable()
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->limit()
                    ->wrap(),
                Tables\Columns\TextColumn::make('file_path')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uploaded_by')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->searchable()
                    ->sortable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFileAttachments::route('/'),
            'create' => Pages\CreateFileAttachment::route('/create'),
            'edit' => Pages\EditFileAttachment::route('/{record}/edit'),
        ];
    }
}
