<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccDeadlineResource\Pages\ManageFccDeadlines;
use App\Models\FccDeadline;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccDeadlineResource extends Resource
{
    protected static ?string $model = FccDeadline::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Deadlines';

    protected static ?string $modelLabel = 'FCC Deadline';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->columnSpanFull(),
            Select::make('license_id')->relationship('license', 'call_sign')->searchable()
                ->label('License (optional)'),
            Select::make('deadline_type')->required()->options([
                'quarterly_eas_test' => 'Quarterly EAS Test Filing',
                'public_file_upload' => 'Public File Upload',
                'license_renewal' => 'License Renewal',
                'issues_programs_list' => 'Issues/Programs List',
                'ownership_report' => 'Biennial Ownership Report (Form 323)',
                'eeo_report' => 'EEO Public File Report',
                'children_tv_report' => "Children's TV Report (Form 2100-H)",
                'tower_lighting' => 'Tower Lighting Inspection',
                'other' => 'Other',
            ]),
            DatePicker::make('due_date')->required(),
            Select::make('status')->required()->options([
                'upcoming' => 'Upcoming',
                'due_soon' => 'Due Soon',
                'overdue' => 'Overdue',
                'completed' => 'Completed',
            ])->default('upcoming'),
            Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('due_date')->date('M d, Y')->sortable(),
            TextColumn::make('title')->searchable()->weight('bold')->limit(40),
            TextColumn::make('deadline_type')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('license.call_sign')->label('Call Sign')->searchable(),
            TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                'upcoming' => 'gray', 'due_soon' => 'warning',
                'overdue' => 'danger', 'completed' => 'success', default => 'gray',
            })->formatStateUsing(fn ($s) => str($s)->headline()),
        ])->filters([
            SelectFilter::make('deadline_type')->options([
                'quarterly_eas_test' => 'Quarterly EAS Test',
                'public_file_upload' => 'Public File Upload',
                'license_renewal' => 'License Renewal',
                'issues_programs_list' => 'Issues/Programs List',
            ]),
            SelectFilter::make('status')->options([
                'upcoming' => 'Upcoming', 'due_soon' => 'Due Soon',
                'overdue' => 'Overdue', 'completed' => 'Completed',
            ]),
        ])->defaultSort('due_date');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFccDeadlines::route('/'),
        ];
    }
}
