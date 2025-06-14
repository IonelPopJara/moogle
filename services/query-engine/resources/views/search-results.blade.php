<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <title>Search Results for "{{ $originalQuery }}"</title>
</head>

<body class="min-h-screen flex flex-col">
    <x-moogle-bar />
    <div class="results-counter">
        @php
            $suggestion = '';
            if ($suggestions) {
                $suggestion = "Did you mean $query? ";
            }
        @endphp
        <span id="suggestion">{{ $suggestion }}</span><span>Showing {{ $total }} results for
            {{ $query }}</span>
        @if ($suggestions)
            <p class="mt-2">Search for <a id="suggestion-link"
                    href="{{ route('search_force', ['processed_query' => $originalQuery]) }}">{{ $originalQuery }}</a>
                instead
            </p>
        @endif
    </div>
    <div class="results-container">
        <div class="pages-container ">
            @if (count($results) > 0)
                <ul class="space-y-4">
                    @foreach ($results as $res)
                        <x-search-result url="{{ $res->_id }}" title="{{ $res->title }}"
                            text="{{ $res->summary_text }}" />
                    @endforeach
                </ul>
            @else
                <p>No results found</p>
            @endif
        </div>
        @if ($page != null && $page == 1)
            <div class="images-container ">
                <h2 class="top-images-text"> {{ ucwords($query) }} </h2>
                @foreach ($topImages as $res)
                    <div>
                        <x-image-container url="{{ $res->_id }}" alt="{{ $res->alt }}"
                            title="{{ $res->page_title }}" page="{{ $res->page_url }}"
                            text="{{ $res->page_text }}" />
                    </div>
                @endforeach
            </div>
        @endif
        <div class="flex flex-col justify-center items-center mt-8">
            <x-pagination-bar totalResults="{{ $total }}" />
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <p> <a href="https://github.com/IonelPopJara/search-engine">Support the project!</a></p>
        <p>
            <a href="https://x.com/ionelalexandr12">Twitter</a> -
            <a href="https://www.youtube.com/multselmesco">YouTube</a> -
            <a href="https://github.com/IonelPopJara">GitHub</a>
        </p>
        <p id="copyright">©2025</p>
    </footer>
</body>

</html>
