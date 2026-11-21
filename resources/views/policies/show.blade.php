@extends('layouts.site')

@section('title', ($policy->title ?? 'Policy') . ' | Jfeid')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 text-center mb-8">
            {{ $policy->title ?? 'Policy Page' }}
        </h1>

        {{-- max-w-none: the card fills the centred column instead of sticking to the left --}}
        <article class="bg-white p-6 sm:p-10 rounded-2xl shadow-sm border border-gray-200 prose prose-gray max-w-none
                        prose-headings:text-gray-900 prose-h2:text-xl prose-h2:mt-10 prose-a:text-amber-600">
            @if ($policy && $policy->content)
                {!! $policy->content !!}
            @else
                <p class="text-gray-500 italic text-center">No content available.</p>
            @endif
        </article>
    </div>
@endsection
