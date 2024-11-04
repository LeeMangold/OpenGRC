<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\DataRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;

class ImportIrl extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms, HasWizard;

    protected static string $resource = AuditResource::class;
    protected static string $view = 'filament.resources.audit-resource.pages.import-irl';

    public ?array $data = [];
    public ?array $errorData = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill();

    }

    public function form(Form $form): Form
    {
        $users = User::get()->pluck('name', 'id');
        $currentDataRequests = DataRequest::get()->where("audit_id", $this->record->id);
        $auditItems = $this->record->auditItems;

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('IRL File')
                        ->schema([
                            Placeholder::make('Introduction')
                                ->columnSpanFull()
                                ->label(new HtmlString("
                                        <p><strong>IRL Import Instructions</strong></p>"))
                                ->content(new HtmlString("<p>IRL Instructions here...</p>")),
                            FileUpload::make("irl_file")
                                ->label("IRL File")
                                ->acceptedFileTypes(["text/csv"]),
                        ]),
                    Wizard\Step::make('Review Data')
                        ->schema([
                            Placeholder::make('Changes to be made')
                                ->columnSpanFull()
                                ->label(new HtmlString("
                                        <p><strong>Changes to be made</strong></p>"))
                                ->view('filament.resources.audit-resource.pages.import-irl-table', [
                                    'data' => $this->data ?? [],
                                    'users' => $users ?? [],
                                    'currentDataRequests' => $currentDataRequests ?? [],
                                    'auditItems' => $auditItems ?? [],
                                ])
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make("submit")
                ->label("Upload IRL")
                ->submit("save"),
        ];
    }

    public function save()
    {
        try {
            $this->validate([
                "irl_file" => "required|file|mimes:csv"
            ]);

            $file = $this->data["irl_file"];
            $path = $file->storeAs("irls", $file->getClientOriginalName());

            $this->record->irl_file = $path;
            $this->record->save();

        } catch (Exception $e) {
            $this->addError("irl_file", $e->getMessage());
        }


    }

//    public function deleteAction(): Action
//    {
//        return Action::make('delete')
//            ->requiresConfirmation()
//            ->action(fn () => $this->post->delete());
//    }


}
