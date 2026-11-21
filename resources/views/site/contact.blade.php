{{-- contact block: used on the home page and the support page --}}
<section id="contact" class="max-w-3xl mx-auto px-4 sm:px-6 py-14 text-center">
    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Contact us</h2>
    <p class="mt-3 text-gray-600">Questions about the app, your account or your car? We’re happy to help.</p>

    @php($email = config('jfeid.support_email'))
    @php($phone = config('jfeid.support_phone'))

    @if ($email || $phone)
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            @if ($email)
                <a href="mailto:{{ $email }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-3 font-semibold text-white hover:bg-amber-600">
                    ✉️ {{ $email }}
                </a>
            @endif
            @if ($phone)
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $phone) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-gray-900 hover:bg-gray-50">
                    📞 {{ $phone }}
                </a>
            @endif
        </div>
    @else
        <p class="mt-8 text-gray-500">Visit us at the Jfeid workshop.</p>
    @endif
</section>
