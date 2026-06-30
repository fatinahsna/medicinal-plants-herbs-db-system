<?php
// view_all.php
$page_title = "View All Plants";
?>

<div class="search-bar-row">
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search medicinal plants...">
    <button onclick="performSearch()" title="Search">🔍</button>          
  </div>
  <div class="filter-dropdown">
    <button class="btn-icon" id="filterToggleBtn" title="Filter">⚙️</button>
    <div class="filter-panel" id="filterPanel">
      <label for="categoryFilter">Category</label>
      <select id="categoryFilter">
        <option value="">All Categories</option>
      </select>
      <label for="originFilter">Origin</label>
      <select id="originFilter">
        <option value="">All Origins</option>
      </select>
      <button class="btn btn-primary" style="width:100%; justify-content:center;" onclick="performSearch()">Apply Filters</button>
    </div>
  </div>
</div>

<div class="search-history" id="searchHistory"></div>

<?php if ($is_admin): ?>
<div class="explore-more-row" style="text-align:right;">
  <a href="add.php" class="btn btn-primary">+ Add Plant</a>
</div>
<?php endif; ?>


