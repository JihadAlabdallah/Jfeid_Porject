<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jfeid')</title>
    <meta name="description" content="@yield('description', 'Jfeid keeps your car’s full service history in your pocket and reminds you when the next service is due.')">
    {{-- typography plugin: styles the headings and lists written in the dashboard editor --}}
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
</head>

<body class="min-h-screen flex flex-col bg-gray-50 text-gray-800 antialiased">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2 font-bold text-xl text-gray-900">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white text-lg">J</span>
                Jfeid
            </a>
            <nav class="flex gap-4 sm:gap-6 text-sm font-medium text-gray-600">
                <a href="{{ url('/') }}#features" class="hover:text-gray-900">Features</a>
                <a href="{{ url('/support') }}" class="hover:text-gray-900">Support</a>
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row gap-3 items-center justify-between text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} Jfeid. All rights reserved.</p>
            <nav class="flex gap-5">
                <a href="{{ route('policy.show', 'privacy-policy') }}" class="hover:text-gray-900">Privacy Policy</a>
                <a href="{{ route('policy.show', 'terms-conditions') }}" class="hover:text-gray-900">Terms</a>
                <a href="{{ url('/support') }}" class="hover:text-gray-900">Support</a>
            </nav>
        </div>
    </footer>

</body>

</html>
