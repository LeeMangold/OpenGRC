<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {

        $settings = [
            [
                'key' => 'general.name',
                'value' => 'OpenGRC',
            ],
            [
                'key' => 'general.url',
                'value' => 'https://localhost',
            ],
            [
                'key' => 'mail.host',
                'value' => 'smtp.mailtrap.io',
            ],
            [
                'key' => 'mail.username',
                'value' => 'username',
            ],
            [
                'key' => 'mail.password',
                'value' => 'password',
            ],
            [
                'key' => 'mail.encryption',
                'value' => 'tls',
            ],
            [
                'key' => 'mail.port',
                'value' => '2525',
            ],
            [
                'key' => 'mail.from',
                'value' => 'no-reply@opengrc.com',
            ],
            [
                'key' => 'mail.templates.password_reset_subject',
                'value' => 'OpenGRC Password Reset',
            ],
            [
                'key' => 'mail.templates.password_reset_body',
                'value' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>Account Created</title>\n</head>\n<body>\n<h1>OpenGRC Password Reset</h1>\n<p>Hello, {{ $name }}!</p>\n<p>An administrator has performed a password reset on your account. </p>\n<p>Your temporary login details are:</p>\n<ul>\n    <li><strong>URL:</strong> {{ $url }}</li>\n    <li><strong>Email:</strong> {{ $email }}</li>\n    <li><strong>Password:</strong> {{ $password }}</li>\n</ul>\n<p>After logging in you will be prompted to change your password. You will then be asked to re-login with your new secret password before continuing.</p>\n</body>\n</html>',
            ],
            [
                'key' => 'mail.templates.new_account_subject',
                'value' => 'OpenGRC Account Created',
            ],
            [
                'key' => 'mail.templates.new_account_body',
                'value' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>Account Created</title>\n</head>\n<body>\n<h1>OpenGRC Account Created</h1>\n<p>Hello, {{ $name }}!</p>\n<p>An OpenGRC account has been created for you. You may your account using the credentials provided below. </p>\n<p>Your login details are:</p>\n<ul>\n    <li><strong>URL:</strong> {{ $url }}</li>\n    <li><strong>Email:</strong> {{ $email }}</li>\n    <li><strong>Password:</strong> {{ $password }}</li>\n</ul>\n<p>After logging in you will be prompted to change your password. You will then be asked to re-login with your new secret password before continuing.</p>\n</body>\n</html>',
            ],
        ];

        foreach ($settings as $setting) {
            $value = stripcslashes($setting['value']);
//            setting([$setting['key'] => $setting['value']]);

            DB::table('settings')->insert(['key' => $setting['key'], 'value' => json_encode($value)]);
        }

        Artisan::call('cache:clear');

    }
}