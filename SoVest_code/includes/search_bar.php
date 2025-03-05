<?php
/**
 * Reusable Search Bar Component
 * 
 * This component can be included in any page to provide search functionality.
 * 
 * Usage:
 * 1. Include this file in your page
 * 2. Add the search CSS and JS files
 * 3. Place the search bar HTML where needed
 */

// Check if user is authenticated
$isAuthenticated = isset($_COOKIE["userID"]);

// Function to render the search bar HTML
function renderSearchBar() {
    ob_start();
?>
    <div class="search-nav-container">
        <form action="search.php" method="GET" class="search-nav-form">
            <div class="input-group">
                <input type="text" class="form-control" name="query" 
                       id="navSearchInput" placeholder="Search..." 
                       autocomplete="off">
                <button class="btn btn-outline-success" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div id="navSearchSuggestions" class="nav-search-suggestions"></div>
        </form>
    </div>
<?php
    return ob_get_clean();
}

// Add the search functionality to the navigation
function addSearchToNav() {
    // Add the required CSS
    echo '<link rel="stylesheet" href="css/search.css">';
    
    // Add the required JavaScript
    echo '<script src="js/search.js" defer></script>';
}
?>