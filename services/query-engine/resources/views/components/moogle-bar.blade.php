<div class="moogle-bar-container">
    <div class="top-part">
        @php
            $currentPage = request()->query('page', 1);
            $currentQuery = request()->query('q');
            $currentPath = request()->path();
            $currentSearchFunction = explode('/', $currentPath)[1];
        @endphp

        <a href="https://moogle.app/" class="logo-container">
            Moogle!
        </a>

        @php
            $currentAction = '/api/search';
            if ($currentSearchFunction == 'search_images') {
                $currentAction = '/api/search_images';
            }
        @endphp

        <form action="{{ $currentAction }}" method="GET" class="search-form px-3">
            <input type="text" id="search-bar" name="q" placeholder="Search..." autocomplete="off" />
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
            $searchQuery = $currentQuery ?: request()->query('processed_query');
            $images_url = '/api/search_images?q=' . $searchQuery;
            $pages_url = '/api/search?q=' . $searchQuery;
            $imagesActive = $currentSearchFunction !== 'search' ? 'active' : '';
            $pagesActive = $currentSearchFunction === 'search' ? 'active' : '';
        @endphp

        <a href="{{ $pages_url }}" class="tab {{ $pagesActive }}">
            <span>PAGES</span>
        </a>
        <a href="{{ $images_url }}" class="tab {{ $imagesActive }}">
            <span>IMAGES</span>
        </a>
    </div>
</div>

<style>
/* MOBILE STYLES ONLY */
@media (max-width: 768px) {
    .moogle-bar-container {
        padding: 1rem;
    }

    .top-part {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .logo-container {
        font-size: 1.5rem;
        text-align: center;
    }

    .search-form {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .search-form input[type="text"] {
        width: 100%;
        padding: 0.5rem;
        font-size: 1rem;
    }

    .button-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .button-group .btn {
        width: 100%;
        padding: 0.75rem;
        font-size: 1rem;
    }

    .bottom-part {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .bottom-part .tab {
        text-align: center;
        padding: 0.75rem;
        font-weight: bold;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchBar = document.getElementById('search-bar');
        const resultsContainer = document.getElementById('search-suggestions');
        const resultsList = document.getElementById('search-suggestions-list');
        let debounceTimer;

        function debounce(func, delay) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(func, delay);
        }

        searchBar.addEventListener('input', function () {
            debounce(() => {
                if (searchBar.value.length >= 2) {
                    fetchSuggestions(searchBar.value);
                } else {
                    resultsContainer.classList.add('hidden');
                }
            }, 300);
        });

        searchBar.addEventListener('focus', function () {
            if (searchBar.value.length >= 2) {
                fetchSuggestions(searchBar.value);
            }
        });

        document.addEventListener('click', function (event) {
            if (!resultsContainer.contains(event.target) && event.target !== searchBar) {
                resultsContainer.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                resultsContainer.classList.add('hidden');
                searchBar.blur();
            }
        });

        function fetchSuggestions(query) {
            fetch(`{{ route('get.search.suggestions') }}?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch');
                    }
                    return response.json();
                })
                .then(data => {
                    displayResults(data.searches);
                })
                .catch(() => {
                    resultsContainer.classList.add('hidden');
                });
        }

        function displayResults(results) {
            resultsList.innerHTML = '';
            results.forEach(result => {
                const li = document.createElement('li');
                li.className = 'search-suggestion';
                li.textContent = result;
                li.onclick = () => {
                    searchBar.value = result;
                    resultsContainer.classList.add('hidden');
                };
                resultsList.appendChild(li);
            });
            resultsContainer.classList.remove('hidden');
        }
    });
</script>

