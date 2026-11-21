@if (config('jfeid.app_store_url') || config('jfeid.play_store_url'))
    <div class="mt-8 flex flex-wrap gap-3 justify-center">
        @if (config('jfeid.app_store_url'))
            <a href="{{ config('jfeid.app_store_url') }}" class="inline-flex items-center rounded-xl bg-gray-900 px-6 py-3 font-semibold text-white hover:bg-gray-700">
                Download on the App Store
            </a>
        @endif
        @if (config('jfeid.play_store_url'))
            <a href="{{ config('jfeid.play_store_url') }}" class="inline-flex items-center rounded-xl bg-gray-900 px-6 py-3 font-semibold text-white hover:bg-gray-700">
                Get it on Google Play
            </a>
        @endif
    </div>
@endif
