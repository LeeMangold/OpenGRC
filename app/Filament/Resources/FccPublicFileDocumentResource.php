<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccPublicFileDocumentResource\Pages\ManageFccPublicFileDocuments;
use App\Models\FccPublicFileDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccPublicFileDocumentResource extends Resource
{
    protected static ?string $model = FccPublicFileDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Public File';

    protected static ?string $modelLabel = 'Public File Document';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            Select::make('document_type')->required()->options([
                'authorization' => 'License Authorization',
                'application' => 'Application',
                'contour_map' => 'Contour Map',
                'ownership_report' => 'Ownership Report (Form 323)',
                'biennial_ownership_report' => 'Biennial Ownership Report',
                'eeo_public_file' => 'EEO Public File Report',
                'political_file' => 'Political File',
                'issues_programs_list' => 'Issues/Programs List',
                'children_tv_report' => "Children's TV Report (Form 2100-H)",
                'shared_service_agreement' => 'Shared Service Agreement',
                'time_brokerage_agreement' => 'Time Brokerage Agreement',
                'joint_sales_agreement' => 'Joint Sales Agreement',
                'donor_list' => 'Donor List (NCE)',
                'station_id_announcement' => 'Station ID Announcement',
                'public_notice' => 'Public Notice',
                'letter_to_public' => 'Letter / Email to Public',
                'other' => 'Other',
            ])->helperText('Per 47 CFR §73.3526'),
            TextInput::make('title')->required()->columnSpanFull(),
            DatePicker::make('document_date')->label('Document Date'),
            DatePicker::make('uploaded_to_lms_date')->label('Uploaded to FCC LMS'),
            DatePicker::make('retention_until')->label('Retain Until')
                ->helperText('Typically license term'),
            TextInput::make('lms_url')->url()->columnSpanFull()
                ->placeholder('https://publicfiles.fcc.gov/...'),
            TextInput::make('uploaded_by'),
            Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('uploaded_to_lms_date')->date('M d, Y')->label('Uploaded')->sortable(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('document_type')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('title')->searchable()->limit(40),
            TextColumn::make('document_date')->date('M d, Y')->label('Doc Date'),
            TextColumn::make('uploaded_by')->toggleable(),
            TextColumn::make('retention_until')->date('M d, Y')->label('Retain Until')->toggleable(),
        ])->filters([
            SelectFilter::make('document_type')->options([
                'political_file' => 'Political File',
                'issues_programs_list' => 'Issues/Programs List',
                'eeo_public_file' => 'EEO Public File',
                'children_tv_report' => "Children's TV Report",
                'biennial_ownership_report' => 'Biennial Ownership',
                'authorization' => 'License Authorization',
            ]),
        ])->defaultSort('uploaded_to_lms_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccPublicFileDocuments::route('/')];
    }
}
