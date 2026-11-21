@extends('layouts.site')

@section('title', 'Support | Jfeid')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 text-center">Support</h1>
        <p class="mt-3 text-center text-gray-600">Answers to common questions about the Jfeid app.</p>

        <div class="mt-10 space-y-4">
            @foreach ([
                ['How do I create an account?', 'Download the Jfeid app, tap Sign up and enter your name, phone number and a password. You can then add your cars.'],
                ['My service is missing from the app.', 'Services done at Jfeid are added by our team. If one is missing or wrong, contact us below with your car plate and the date of the service.'],
                ['How do I add another car?', 'You can add more cars from the app. Each car has its own plate number and service history.'],
                ['I’m not getting notifications.', 'Make sure notifications are allowed for Jfeid in your phone’s settings, and that you are signed in to the app.'],
                ['I forgot my password.', 'Use the “Forgot password” option on the sign-in screen, or contact us below.'],
                ['How do I delete my account?', 'You can delete your account from within the app. This removes your profile, cars and service records. See our Privacy Policy for details.'],
            ] as [$q, $a])
                <details class="group bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <summary class="cursor-pointer list-none flex items-center justify-between font-semibold text-gray-900">
                        {{ $q }}
                        <span class="ml-4 text-gray-400 transition group-open:rotate-45 text-xl leading-none">+</span>
                    </summary>
                    <p class="mt-3 text-gray-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>

    @include('site.contact')
@endsection
