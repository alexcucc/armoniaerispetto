let searchIndex;
let pagesData;

async function initSearch() {
    try {
        console.log('Initializing search...');
        const response = await fetch('search_index.json');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        pagesData = await response.json();
        console.log('Data loaded:', pagesData);
        
        searchIndex = lunr(function() {
            this.use(lunr.it);
            this.ref('path');
            this.field('title', { boost: 10 });
            this.field('content', { boost: 5 });
            
            pagesData.forEach(doc => this.add(doc));
        });
        
        return true;
    } catch (error) {
        console.error('Search initialization failed:', error);
        return false;
    }
}

async function displayResults() {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer) {
        console.error('Results container not found');
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const query = params.get('q');
    console.log('Search query:', query);
    
    // Initialize search immediately
    if (!searchIndex) {
        await initSearch();
    }

    if (!query?.trim()) {
        resultsContainer.innerHTML = '<p>Inserisci un termine di ricerca.</p>';
        return;
    }

    resultsContainer.innerHTML = '<p class="loading">Ricerca in corso...</p>';

    try {

        console.log('Performing search for:', query);
        const results = searchIndex.search(query);
        console.log('Search results:', results);

        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="no-results">
                    <p>Nessun risultato trovato per "${query}"</p>
                    <p>Suggerimenti:</p>
                    <ul>
                        <li>Controlla che tutte le parole siano scritte correttamente</li>
                        <li>Prova con parole chiave diverse</li>
                        <li>Usa termini più generici</li>
                    </ul>
                </div>`;
            return;
        }

        const html = results.map(result => {
            const page = pagesData.find(p => p.path === result.ref);
            if (!page) {
                console.warn('Page not found:', result.ref);
                return '';
            }

            return `
                <div class="search-result">
                    <h2><a href="${page.path}">${page.title}</a></h2>
                    <p class="excerpt">${generateExcerpt(page.content, query)}</p>
                    <div class="result-meta">
                        <a class="result-link" href="${page.path}">${page.path}</a>
                        <span class="relevance">Rilevanza: ${Math.round(result.score * 100)}%</span>
                    </div>
                </div>`;
        }).join('');

        resultsContainer.innerHTML = html;
    } catch (error) {
        console.error('Search error:', error);
        resultsContainer.innerHTML = '<p class="error">Si è verificato un errore durante la ricerca.</p>';
    }
}

function generateExcerpt(content, query) {
    const maxLength = 200;
    const words = query.toLowerCase().split(/\s+/);
    let bestPosition = 0;
    let bestMatchCount = 0;

    // Find best excerpt position with most query word matches
    for (let i = 0; i < content.length - maxLength; i++) {
        const excerpt = content.substr(i, maxLength).toLowerCase();
        const matchCount = words.filter(word => excerpt.includes(word)).length;
        
        if (matchCount > bestMatchCount) {
            bestMatchCount = matchCount;
            bestPosition = i;
        }
    }

    let excerpt = content.substr(bestPosition, maxLength);
    if (bestPosition > 0) excerpt = '...' + excerpt;
    if (bestPosition + maxLength < content.length) excerpt += '...';

    return highlightText(excerpt, query);
}

function highlightText(text, query) {
    const words = query.toLowerCase().split(/\s+/).filter(word => word.length > 0);
    let highlighted = text;
    
    words.forEach(word => {
        const regex = new RegExp(`(${word})`, 'gi');
        highlighted = highlighted.replace(regex, '<mark>$1</mark>');
    });
    
    return highlighted;
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Change to check for ricerca.php instead of search.php
    if (window.location.pathname.includes('ricerca.php')) {
        console.log('Search page detected, initializing...');
        displayResults();
    }
});
