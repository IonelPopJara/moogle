<div class="moogle-bar-container">
    <div class="top-part">
        @php
            $currentPage = request()->query('page', 1);
            $currentQuery = request()->query('q');
            $currentPath = request()->path();
            $currentSearchFunction = explode('/', $currentPath)[1];
        @endphp
        {{-- TODO: Change this for the actual url --}}
        <a href="https://moogle.app/" class="logo-container">
            Moogle!
        </a>
        @php
            $currentAction = '/api/search';
            if ($currentSearchFunction == 'search_images') {
                $currentAction = '/api/search_images';
            }
        @endphp
        <form action="{{ $currentAction }}" method="GET" class="px-3">
            <input type="text" id="search-bar" name="q" placeholder="Search..." autocomplete="off" />
            {{-- add search results --}}
            <div id="search-suggestions" class="absolute bg-white search-results hidden">
                <ul id="search-suggestions-list"></ul>
            </div>
            <button type="submit" class="btn" id="search-button">
                Moogle it!
            </button>
            <button type="button" class="btn" onclick="window.location.href='/api/cringe'">
                Life ain't cringe!
            </button>
        </form>

    </div>
    <div class="bottom-part">
        @php
            $searchQuery = $currentQuery;
            if (empty($searchQuery)) {
                $searchQuery = request()->query('processed_query');
            }

            $images_url = '/api/search_images?q=' . $searchQuery;
            $pages_url = '/api/search?q=' . $searchQuery;

            $imagesActive = '';
            $pagesActive = '';
            if ($currentSearchFunction == 'search') {
                $imagesActive = '';
                $pagesActive = 'active';
            } else {
                $imagesActive = 'active';
                $pagesActive = '';
            }
        @endphp
        <a href="{{ $pages_url }}" class="tab {{ $pagesActive }}">
            <span> PAGES </span>
        </a>
        <a href="{{ $images_url }}" class="tab {{ $imagesActive }}">
            <span> IMAGES </span>
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchBar = document.getElementById('search-bar');
        const resultsContainer = document.getElementById('search-suggestions');
        const resultsList = document.getElementById('search-suggestions-list');
        let debounceTimer;

        function debounce(func, delay) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(func, delay);
        }

        // Event listeners
        searchBar.addEventListener('input', function() {
            debounce(() => {
                if (searchBar.value.length >= 2) {
                    fetchSuggestions(searchBar.value);
                } else {
                    fetchTopSearches();
                }
            }, 300);
        });

        searchBar.addEventListener('focus', function() {
            if (searchBar.value.length < 2) {
                fetchTopSearches();
            } else {
                fetchSuggestions(searchBar.value);
            }
        });

        // Hide results when clicking outside the search bar
        document.addEventListener('click', function(event) {
            if (!resultsContainer.contains(event.target) && event.target !== searchBar) {
                hideResults();
            }
        });

        // Hide results when pressing escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideResults();
                // unfocus the search bar
                searchBar.blur();
            }
        });

        function hideResults() {
            resultsContainer.classList.add('hidden');
        }

        // Fetch search suggestions
        function fetchSuggestions(query) {
            fetch(`{{ route('get.search.suggestions') }}?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch search results');
                    }
                    return response.json();
                })
                .then(data => {
                    displayResults(data.searches);
                })
                .catch(error => {
                    hideResults();
                    console.error(error);
                });
        }

        function displayResults(results) {
            resultsList.innerHTML = '';

            results.forEach(result => {
                const li = document.createElement('li');
                li.className = 'search-suggestion';
                li.textContent = result;

                li.addEventListener('click', function() {
                    searchBar.value = result;
                    hideResults();
                });

                resultsList.appendChild(li);
            });

            resultsContainer.classList.remove('hidden');
        }

        function fetchTopSearches() {
            // fetch(`{{ route('get.top.searches') }}`)
            //     .then(response => {
            //         if (!response.ok) {
            //             throw new Error('Failed to fetch top searches');
            //         }
            //         return response.json();
            //     })
            //     .then(data => {
            //         displayResults(data.searches);
            //     })
            //     .catch(error => {
            //         hideResults();
            //         console.error(error);
            //     });
        }
    });
</script>
