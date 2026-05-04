<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccRegulatoryFeeResource\Pages\ManageFccRegulatoryFees;
use App\Models\FccRegulatoryFee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccRegulatoryFeeResource extends Resource
{
    protected static ?string $model = FccRegulatoryFee::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Regulatory Fees';

    protected static ?string $modelLabel = 'Regulatory Fee';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 140;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            TextInput::make('fiscal_year')->numeric()->required()->default(now()->year),
            TextInput::make('fee_category')->required()
                ->placeholder('e.g. AM Class B, FM Class C, TV Markets 1-10')
                ->helperText('Per FCC annual Reg Fee schedule'),
            TextInput::make('amount_due')->numeric()->prefix('$')->required(),
            TextInput::make('amount_paid')->numeric()->prefix('$')->default(0),
            DatePicker::make('due_date')->required(),
            DatePicker::make('paid_date'),
            TextInput::make('confirmation_number')->label('Pay.gov Confirmation #'),
            Select::make('status')->required()->options([
                'pending' => 'Pending',
                'paid' => 'Paid',
                'overdue' => 'Overdue',
                'waiver_requested' => 'Waiver Requested',
            ])->default('pending'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('fiscal_year')->label('FY')->sortable(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('fee_category')->limit(35),
            TextColumn::make('amount_due')->money('USD'),
            TextColumn::make('amount_paid')->money('USD'),
            TextColumn::make('due_date')->date('M d, Y')->sortable(),
            TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                'paid' => 'success', 'pending' => 'warning',
                'overdue' => 'danger', 'waiver_requested' => 'info', default => 'gray',
            })->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('confirmation_number')->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                'pending' => 'Pending', 'paid' => 'Paid',
                'overdue' => 'Overdue', 'waiver_requested' => 'Waiver',
            ]),
        ])->defaultSort('due_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccRegulatoryFees::route('/')];
    }
}
