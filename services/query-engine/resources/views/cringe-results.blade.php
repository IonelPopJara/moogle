<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <title>Life Ain't Cringe</title>
    <style>
        @media (max-width: 768px) {
            .responsive-wrapper {
                flex-direction: column;
            }

            .responsive-wrapper > * {
                width: 100% !important;
            }

            #cringe-title {
                font-size: 1.75rem;
                padding: 0 1rem;
            }

            #note {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body class="min-h-screen font-mono" style="background-color: var(--bg); color: var(--text);">

    <x-moogle-bar />

    <h1 id="cringe-title" class="text-4xl text-center mt-8 mb-6 font-bold" style="color: var(--title);">
        Life Ain't Cringe... or is it?
    </h1>

    <div class="flex justify-center px-4">
        <div class="flex w-full max-w-6xl gap-6 responsive-wrapper">
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
                                @if ($randomPage['summary_text'] != null)
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

