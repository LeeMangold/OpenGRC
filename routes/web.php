<?php

use App\Livewire\PasswordResetPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/app/reset-password', PasswordResetPage::class)->name('password-reset-page');

    Route::get('/app/priv-storage/{filepath}', function ($filepath) {
        return Storage::disk('private')->download($filepath);
    })->where('filepath', '.*')->name('priv-storage');

});



// Add Socialite routes
Route::get('/auth/{provider}/redirect', function (string $provider) {
    return Socialite::driver($provider)->redirect();
})->name('socialite.redirect');

Route::get('/auth/{provider}', 'Auth\AuthController@redirectToProvider');
Route::get('/auth/{provider}/callback', 'Auth\AuthController@handleProviderCallback');


// Route::get('/auth/{provider}/callback', function (string $provider) {
//     try {
//         $socialiteUser = Socialite::driver($provider)->user();

//         dd($socialiteUser);
        
//         // Find or create user
//         $user = User::firstOrCreate(
//             ['email' => $socialiteUser->getEmail()],
//             [
//                 'name' => $socialiteUser->getName(),
//                 'password' => bcrypt(Str::random(16)),
//                 'email_verified_at' => now(),
//             ]
//         );

//         // Log the user in
//         Auth::login($user);

//         // Redirect to the dashboard
//         return redirect()->to('/app');

//     } catch (\Exception $e) {
//         return redirect()->to('/app/login')
//             ->with('error', 'Authentication failed. Please try again.');
//     }
// })->name('socialite.callback');
