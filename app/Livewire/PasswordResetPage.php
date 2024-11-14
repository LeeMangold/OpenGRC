<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Session;

class PasswordResetPage extends Component implements HasForms, HasActions
{

    use InteractsWithActions, InteractsWithForms;

    public ?array $data = [];

    public User $user;
    protected Form $form;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->required()
                    ->minLength(12)
                    ->same('password_confirmation')
                    ->live(),
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required()
                    ->minLength(12)
                    ->same('password')
                    ->live(),
            ]
            )
            ->statePath('data')
            ->model(User::class);
    }

    public function create(): Redirector
    {
        $data = $this->form->getState();
        $user = auth()->user();
        $user->password = bcrypt($data['password']);
        $user->password_reset_required = false;
        $user->save();
        Filament::auth()->logout();
        Session::invalidate();
        Session::regenerateToken();
        return redirect()->route('filament.app.auth.login');

    }


    public function render(): Factory|View|Application
    {
        return view('livewire.password-reset-page');
    }
}
