<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="mask-icon" type="image/svg+xml" href="{{asset('favicon.svg')}}" color="#000000">
    <title>Life Ain't Cringe</title>
</head>

<body class="min-h-screen font-mono" style="background-color: var(--bg); color: var(--text);">

    <x-moogle-bar />

    <h1 id="cringe-title" class="text-4xl text-center mt-8 mb-6 font-bold" style="color: var(--title);">
        Life Ain't Cringe... or is it?
    </h1>

    <div class="flex justify-center px-4">
        <div class="flex w-full max-w-6xl gap-6">
            <!-- Top Searches -->
            <div class="w-2/5 p-4 rounded-xl shadow-lg border"
                style="background-color: var(--bg-2); border-color: var(--orange);">
                <h2 id="cringe-total-searches" class="text-2xl mb-4 font-semibold" style="color: var(--orange);">
                    Total Searches
                </h2>
                <div class="text-lg mb-4" style="color: var(--text);">
                    <p>
                        {{ $totalSearches }} searches
                    </p>
                </div>
                <h2 id="cringe-top-searches" class="text-2xl mb-4 font-semibold" style="color: var(--orange);">
                    Top Searches (Deprecated Feature)
                </h2>

                <ul class="space-y-2">
                    <p id="note">
                        Due to the top searches being used for spam and displaying inappropriate results, the top
                        searches feature has been disabled.<br>
                        If you'd like to contribute a new feature to replace it, you can go to the
                        related <a href="https://github.com/IonelPopJara/moogle/issues/7" target="_blank"
                            id="github">GitHub
                            issue</a>.
                    </p>
                    {{-- @foreach ($topSearches as $search)
                        <li>
                            <a href="{{ route('search_force', ['processed_query' => $search]) }}"
                                class="transition hover:underline" style="color: var(--text);"
                                onmouseover="this.style.color='var(--orange)';"
                                onmouseout="this.style.color='var(--text)';">
                                - {{ $search }}
                            </a>
                        </li>
                    @endforeach --}}
                </ul>
            </div>

            <!-- Random Article -->
            @if ($randomPage != null)
                <a href="https://{{ $randomPage['url'] }}" target="_blank" rel="noopener noreferrer"
                    class="w-3/5 p-4 rounded-xl shadow-lg border transition hover:shadow-xl hover:underline"
                    style="background-color: var(--bg-2); border-color: var(--url); text-decoration: none;">
                    <div>
                        <h2 class="text-2xl mb-4 font-semibold" style="color: var(--url);">
                            {{ $randomPage['title'] }}
                        </h2>
                        <div class="italic text-sm" style="color: var(--blue);">
                            {{ $randomPage['url'] }}
                        </div>
                        <div class="mt-4 text-lg" style="color: var(--text);">
                            <p>
                                {{-- use only the first 300 characters of summary text --}}
                                @if ($randomPage['summary_text'] != null)
                                    {{-- use Str::limit to limit the summary text to 600 characters --}}
                                    {{ Str::limit($randomPage['summary_text'], 600, '...') }}
                                @else
                                    No summary available.
                                @endif
                            </p>
                        </div>
                    </div>
                </a>
            @else
                <div class="w-3/5 p-4 rounded-xl shadow-lg border"
                    style="background-color: var(--bg-2); border-color: var(--url);">
                    <h2 class="text-2xl mb-4 font-semibold" style="color: var(--url);">
                        No Random Article Available
                    </h2>
                    <div class="mt-4 text-lg" style="color: var(--text);">
                        <p>
                            No random article available.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

</body>

</html>
