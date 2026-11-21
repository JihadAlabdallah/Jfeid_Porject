@extends('layouts.site')

@section('title', 'Jfeid | Your car’s service history, always with you')

@section('content')
    {{-- hero --}}
    <section class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Car maintenance, made simple</p>
            <h1 class="mt-3 text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight">
                Your car’s service history,<br class="hidden sm:block"> always with you
            </h1>
            <p class="mt-5 text-lg text-gray-600 max-w-xl mx-auto">
                Jfeid keeps every oil change and service of your cars in one place,
                and reminds you when the next one is due.
            </p>
            @include('site.store-buttons')
        </div>
    </section>

    {{-- features --}}
    <section id="features" class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center">What you get</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2">
            @foreach ([
                ['🛢️', 'Full service history', 'Engine oil, gear, differential, steering and transmission oil, batteries, wheels and more, with the date, reading and details of every service.'],
                ['⏰', 'Know what’s due next', 'See when each service is due next, by kilometres or miles, so nothing is missed.'],
                ['🚗', 'All your cars', 'Every car you bring to Jfeid in one account, each with its own plate number and service record.'],
                ['🔔', 'Reminders', 'A weekly reminder to check your car and oil, and a message when your car is ready.'],
            ] as [$icon, $title, $text])
                <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div class="text-3xl" aria-hidden="true">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold text-lg text-gray-900">{{ $title }}</h3>
                    <p class="mt-2 text-gray-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- how it works --}}
    <section class="bg-white border-y border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center">How it works</h2>
            <ol class="mt-10 grid gap-6 sm:grid-cols-3 text-center">
                @foreach ([
                    ['Create your account', 'Sign up in the app with your name and phone number.'],
                    ['Get your car serviced', 'The Jfeid team adds your car and every service done on it to your record.'],
                    ['Stay on schedule', 'Open the app any time to see your history and what’s due next.'],
                ] as $i => [$title, $text])
                    <li>
                        <div class="mx-auto h-10 w-10 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center">{{ $i + 1 }}</div>
                        <h3 class="mt-3 font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-1 text-gray-600 text-sm">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @include('site.contact')
@endsection
