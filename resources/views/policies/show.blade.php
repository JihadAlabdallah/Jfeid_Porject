<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $policy->title ?? 'Policy Page' }}</title>
    {{-- typography plugin: styles the headings and lists written in the dashboard editor --}}
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
</head>

<body class="bg-gray-50 text-gray-800">

    <!-- Header -->
    <header class="bg-white shadow p-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">
            {{ $policy->title ?? 'Policy Page' }}
        </h1>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto p-6 mt-4 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow prose prose-lg prose-blue">
            @if ($policy && $policy->content)
                {!! $policy->content !!}
            @else
                <p class="text-gray-500 italic text-center">No content available.</p>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center text-gray-500 p-6 mt-8 border-t">
        &copy; {{ date('Y') }} Jfeid. All rights reserved.
    </footer>

</body>

</html>