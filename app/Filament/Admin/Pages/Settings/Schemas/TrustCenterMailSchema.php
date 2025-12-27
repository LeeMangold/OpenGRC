<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class TrustCenterMailSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('Access Request Notification'))
                ->description(__('Email sent to administrators when a new access request is submitted'))
                ->schema([
                    Placeholder::make('access_request_variables')
                        ->label(__('Available Variables'))
                        ->content(__('{{ $requesterName }}, {{ $requesterEmail }}, {{ $requesterCompany }}, {{ $reason }}, {{ $ndaAgreed }}, {{ $approvalUrl }}, {{ $documentCount }}'))
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.trust_center_access_request_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.trust_center_access_request_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Access Approved Email'))
                ->description(__('Email sent to the requester when their access request is approved'))
                ->schema([
                    Placeholder::make('access_approved_variables')
                        ->label(__('Available Variables'))
                        ->content(__('{{ $requesterName }}, {{ $requesterEmail }}, {{ $accessUrl }}, {{ $expiryHours }}, {{ $expiresAt }}, {{ $documentCount }}'))
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.trust_center_access_approved_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.trust_center_access_approved_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Access Rejected Email'))
                ->description(__('Email sent to the requester when their access request is rejected'))
                ->schema([
                    Placeholder::make('access_rejected_variables')
                        ->label(__('Available Variables'))
                        ->content(__('{{ $requesterName }}, {{ $requesterEmail }}, {{ $reviewNotes }}'))
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.trust_center_access_rejected_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.trust_center_access_rejected_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
