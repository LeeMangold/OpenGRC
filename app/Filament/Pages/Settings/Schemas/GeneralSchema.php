<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class GeneralSchema
{
    public static function schema(): array
    {
        return [
            Section::make('General Configuration')
                ->schema([
                    TextInput::make('general.name')
                        ->default('ets')
                        ->minLength(2)
                        ->maxLength(16)
                        ->label('Application Name')
                        ->helperText('The name of your application')
                        ->required(),
                    TextInput::make('general.url')
                        ->default('http://localhost')
                        ->url()
                        ->label('Application URL')
                        ->helperText('The URL of your application')
                        ->required(),
                    TextInput::make('general.repo')
                        ->default('https://repo.opengrc.com')
                        ->url()
                        ->label('Update Repository URL')
                        ->helperText('The URL of the repository to check for content updates')
                        ->required(),
                ]),

            // Section::make('Report Configuration')
            //     ->schema([
            //         FileUpload::make('report.logo')
            //             ->label('Custom Report Logo (Optional)')
            //             ->image()
            //             ->disk(fn () => config('filesystems.default'))
            //             ->directory('report-assets')
            //             ->visibility('private')
            //             ->imageResizeMode('contain')
            //             ->imageCropAspectRatio('16:9')
            //             ->imageResizeTargetWidth('512')
            //             ->imageResizeTargetHeight('512')
            //             ->helperText('Upload a custom logo to be used in reports. Recommended size: 512x512px')
            //             ->deleteUploadedFileUsing(function ($state) {
            //                 if ($state) {
            //                     Storage::disk(config('filesystems.default'))->delete($state);
            //                 }
            //             }),
            //     ]),

            Section::make('Storage Configuration')
                ->schema([
                    Select::make('storage.driver')
                        ->label('Storage Driver')
                        ->options([
                            'private' => 'Local Private Storage',
                            's3' => 'Amazon S3'
                        ])
                        ->default('private')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if ($state === 'private') {
                                config()->set('filesystems.default', 'private');
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('storage.s3.key')
                                ->label('AWS Access Key ID')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        try {
                                            $component->state(Crypt::decryptString($state));
                                        } catch (\Exception $e) {
                                            $component->state('');
                                        }
                                    }
                                }),

                            TextInput::make('storage.s3.secret')
                                ->label('AWS Secret Access Key')
                                ->password()
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        try {
                                            $component->state(Crypt::decryptString($state));
                                        } catch (\Exception $e) {
                                            $component->state('');
                                        }
                                    }
                                }),

                            TextInput::make('storage.s3.region')
                                ->label('AWS Region')
                                ->placeholder('us-east-1')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3'),

                            TextInput::make('storage.s3.bucket')
                                ->label('S3 Bucket Name')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3'),
                        ]),
                ]),
        ];
    }
}
