<?php
/**
 * Grant Archive Template - Site Design Matching Edition v8.1
 * File: archive-grant.php
 * 
 * 既存サイトデザインに完全マッチしたスタイル
 * 機能はそのまま、デザインを既存サイトに合わせて調整
 * 
 * @package Grant_Insight_Site_Match
 * @version 8.1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// 必要な関数の存在確認
$required_functions = [
    'gi_safe_get_meta',
    'gi_get_formatted_deadline',
    'gi_map_application_status_ui',
    'gi_get_user_favorites',
    'gi_get_grant_amount_display'
];

// URLパラメータから検索条件を取得
$search_params = [
    'search' => sanitize_text_field($_GET['s'] ?? ''),
    'category' => sanitize_text_field($_GET['category'] ?? ''),
    'prefecture' => sanitize_text_field($_GET['prefecture'] ?? ''),
    'amount' => sanitize_text_field($_GET['amount'] ?? ''),
    'status' => sanitize_text_field($_GET['status'] ?? ''),
    'difficulty' => sanitize_text_field($_GET['difficulty'] ?? ''),
    'success_rate' => sanitize_text_field($_GET['success_rate'] ?? ''),
    'application_method' => sanitize_text_field($_GET['method'] ?? ''),
    'is_featured' => sanitize_text_field($_GET['featured'] ?? ''),
    'sort' => sanitize_text_field($_GET['sort'] ?? 'date_desc'),
    'view' => sanitize_text_field($_GET['view'] ?? 'grid'),
    'page' => max(1, intval($_GET['paged'] ?? 1))
];

// 統計データ取得
$stats = function_exists('gi_get_cached_stats') ? gi_get_cached_stats() : [
    'total_grants' => wp_count_posts('grant')->publish ?? 0,
    'active_grants' => 0,
    'prefecture_count' => 47,
    'avg_success_rate' => 65
];

// お気に入りリスト取得
$user_favorites = function_exists('gi_get_user_favorites_cached') ? 
    gi_get_user_favorites_cached() : 
    (function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : []);

// 初期表示用クエリの構築
$initial_args = [
    'post_type' => 'grant',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'no_found_rows' => false
];

// 検索条件の適用
if (!empty($search_params['search'])) {
    $initial_args['s'] = $search_params['search'];
}

// タクソノミークエリ
$tax_query = ['relation' => 'AND'];
if (!empty($search_params['category'])) {
    $tax_query[] = [
        'taxonomy' => 'grant_category',
        'field' => 'slug',
        'terms' => explode(',', $search_params['category'])
    ];
}
if (!empty($search_params['prefecture'])) {
    $tax_query[] = [
        'taxonomy' => 'grant_prefecture',
        'field' => 'slug',
        'terms' => explode(',', $search_params['prefecture'])
    ];
}
if (count($tax_query) > 1) {
    $initial_args['tax_query'] = $tax_query;
}

// メタクエリ
$meta_query = ['relation' => 'AND'];

if (!empty($search_params['status'])) {
    $statuses = explode(',', $search_params['status']);
    $db_statuses = array_map(function($s) {
        return $s === 'active' ? 'open' : ($s === 'upcoming' ? 'upcoming' : $s);
    }, $statuses);
    
    $meta_query[] = [
        'key' => 'application_status',
        'value' => $db_statuses,
        'compare' => 'IN'
    ];
}

if (!empty($search_params['is_featured']) && $search_params['is_featured'] === '1') {
    $meta_query[] = [
        'key' => 'is_featured',
        'value' => '1',
        'compare' => '='
    ];
}

if (count($meta_query) > 1) {
    $initial_args['meta_query'] = $meta_query;
}

// ソート処理
switch($search_params['sort']) {
    case 'amount_desc':
        $initial_args['orderby'] = 'meta_value_num';
        $initial_args['meta_key'] = 'max_amount_numeric';
        $initial_args['order'] = 'DESC';
        break;
    case 'featured_first':
        $initial_args['orderby'] = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
        $initial_args['meta_key'] = 'is_featured';
        break;
    default:
        $initial_args['orderby'] = 'date';
        $initial_args['order'] = 'DESC';
}

// クエリ実行
$grants_query = new WP_Query($initial_args);

// タクソノミー取得
$all_categories = get_terms([
    'taxonomy' => 'grant_category',
    'hide_empty' => false,
    'orderby' => 'count',
    'order' => 'DESC'
    // 制限を削除してすべてのカテゴリを取得
]);

$all_prefectures = get_terms([
    'taxonomy' => 'grant_prefecture',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700;800;900&display=swap" as="style">
    
    <!-- Critical CSS -->
    <style>
    /* ===== Modern Design System - Matching Header Style ===== */
    :root {
        /* Primary Gradient Colors - Matching Header */
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --accent-gradient: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        
        /* Core Colors */
        --primary-blue: #667eea;
        --secondary-blue: #4f46e5;
        --light-blue: #f0f7ff;
        --accent-blue: #06b6d4;
        --lavender: #f5f3ff;
        --light-lavender: #faf8ff;
        
        /* Enhanced Neutral Colors */
        --white: #ffffff;
        --light-gray: #f8fafc;
        --medium-gray: #e2e8f0;
        --border-gray: #cbd5e1;
        --text-gray: #64748b;
        --dark-gray: #475569;
        --black: #0f0f23;
        
        /* Semantic Colors */
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
        
        /* Modern Typography */
        --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        --font-japanese: 'Noto Sans JP', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Yu Gothic Medium', 'Meiryo', sans-serif;
        
        /* Spacing */
        --spacing-xs: 0.25rem;
        --spacing-sm: 0.5rem;
        --spacing-md: 1rem;
        --spacing-lg: 1.5rem;
        --spacing-xl: 2rem;
        --spacing-2xl: 3rem;
        --spacing-3xl: 4rem;
        --spacing-4xl: 5rem;
        
        /* Border Radius */
        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --radius-2xl: 1.5rem;
        
        /* Shadows */
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        
        /* Transitions */
        --transition-fast: all 150ms ease-in-out;
        --transition-normal: all 300ms ease-in-out;
        --transition-slow: all 500ms ease-in-out;
    }
    
    /* Reset & Base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html {
        font-size: 16px;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        scroll-behavior: smooth;
    }
    
    body {
        font-family: var(--font-primary);
        color: var(--black);
        background: linear-gradient(135deg, var(--light-blue) 0%, var(--lavender) 100%);
        font-weight: 400;
        min-height: 100vh;
    }
    
    /* Container */
    .sm-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 var(--spacing-lg);
    }
    
    @media (min-width: 768px) {
        .sm-container {
            padding: 0 var(--spacing-xl);
        }
    }
    
    /* ===== MODERN HERO SECTION ===== */
    .sm-hero {
        background: var(--primary-gradient);
        padding: 6rem 0 4rem;
        position: relative;
        overflow: hidden;
        min-height: 60vh;
        display: flex;
        align-items: center;
    }
    
    .sm-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.05"><circle cx="30" cy="30" r="2"/></g></svg>');
        opacity: 0.3;
    }
    
    .sm-hero::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.03"><circle cx="30" cy="30" r="1.5"/></g></svg>');
        pointer-events: none;
    }
    
    .sm-hero-content {
        text-align: center;
        position: relative;
        z-index: 2;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .sm-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        color: var(--primary-blue);
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 50px;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transform: translateY(0);
        transition: var(--transition-normal);
    }
    
    .sm-hero-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
    }
    
    .sm-hero-title {
        font-size: clamp(2.5rem, 6vw, 4rem);
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        color: white;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        font-family: var(--font-primary);
    }
    
    .sm-hero-title .highlight {
        background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: none;
    }
    
    .sm-hero-subtitle {
        font-size: clamp(1.125rem, 2.5vw, 1.375rem);
        color: rgba(255, 255, 255, 0.9);
        max-width: 700px;
        margin: 0 auto 3rem;
        line-height: 1.6;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        font-weight: 400;
    }
    
    /* ===== FILTER SECTION ===== */
    .sm-filter-section {
        background: var(--white);
        padding: 2rem 0;
        box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }
    
    .sm-filter-card {
        background: var(--white);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        margin: 0 auto;
        max-width: 1200px;
    }
    
    .sm-stat-card:hover {

    
    /* ===== SIMPLE SEARCH ===== */
    .sm-search-wrapper {
        max-width: 600px;
        margin: 0 auto 0;
    }
    
    .sm-search-container {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 50px;
        padding: 1rem 1.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        transition: var(--transition-normal);
    }
    
    .sm-search-container:focus-within {
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
        border-color: rgba(102, 126, 234, 0.3);
        transform: translateY(-2px);
    }
    
    .sm-search-icon {
        color: var(--primary-blue);
        font-size: 1.125rem;
        margin-right: 1rem;
    }
    
    .sm-search-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 1rem;
        color: var(--black);
        outline: none;
        font-weight: 500;
    }
    
    .sm-search-input::placeholder {
        color: var(--text-gray);
        font-weight: 400;
    }
    
    .sm-search-clear {
        width: 2rem;
        height: 2rem;
        border: none;
        background: rgba(102, 126, 234, 0.1);
        color: var(--primary-blue);
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-fast);
        margin-left: 0.75rem;
    }
    
    .sm-search-clear:hover {
        background: var(--primary-blue);
        color: white;
        transform: scale(1.1);
    }
    
    /* ===== QUICK FILTERS - Modern Pill Style ===== */
        font-size: 1rem;
        font-weight: 500;
        background: var(--light-gray);
        transition: var(--transition-fast);
        outline: none;
    }
    
    .sm-quick-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        align-items: center;
        padding: 1rem 0;
    }
    
    .sm-filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: rgba(102, 126, 234, 0.1);
        color: var(--primary-blue);
        border: 2px solid transparent;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition-normal);
        backdrop-filter: blur(10px);
        white-space: nowrap;
    }
    
    .sm-filter-pill:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .sm-filter-pill.active {
        background: var(--primary-gradient);
        color: white;
        border-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        transform: translateY(-1px);
    }
    
    .sm-filter-pill-icon {
        font-size: 1rem;
        line-height: 1;
    }
    
    .sm-filter-pill-count {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 1.5rem;
        text-align: center;
    }
    
    .sm-filter-pill:not(.active) .sm-filter-pill-count {
        background: var(--primary-blue);
        color: white;
    }
    
    /* Quick Filters - Pill Design */
    .sm-quick-filters {
        display: flex;
        gap: var(--spacing-sm);
        justify-content: center;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .sm-filter-pill {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-lg);
        background: var(--light-gray);
        color: var(--dark-gray);
        border: 2px solid transparent;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: var(--radius-2xl);
        cursor: pointer;
        transition: var(--transition-fast);
        text-decoration: none;
        white-space: nowrap;
    }
    
    .sm-filter-pill:hover {
        background: var(--medium-gray);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    
    .sm-filter-pill.active {
        background: var(--primary-blue);
        color: var(--white);
        border-color: var(--primary-blue);
        box-shadow: var(--shadow-md);
    }
    
    .sm-filter-pill-icon {
        font-size: 1rem;
    }
    
    .sm-filter-pill-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 0 var(--spacing-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
    }
    
    /* ===== CONTROLS BAR ===== */
    .sm-controls {
        background: var(--white);
        padding: var(--spacing-lg) 0;
        border-bottom: 1px solid var(--border-gray);
    }
    
    .sm-controls-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--spacing-lg);
        flex-wrap: wrap;
    }
    
    .sm-controls-left,
    .sm-controls-right {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
    }
    
    .sm-select {
        padding: var(--spacing-sm) var(--spacing-lg);
        background: var(--white);
        border: 2px solid var(--border-gray);
        border-radius: var(--radius-md);
        color: var(--dark-gray);
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition-fast);
        outline: none;
        min-width: 160px;
    }
    
    .sm-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
    }
    
    .sm-filter-button {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-lg);
        background: var(--white);
        border: 2px solid var(--border-gray);
        border-radius: var(--radius-md);
        color: var(--dark-gray);
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition-fast);
    }
    
    .sm-filter-button:hover {
        border-color: var(--primary-blue);
        background: var(--light-blue);
    }
    
    .sm-filter-button.has-filters {
        background: var(--primary-blue);
        color: var(--white);
        border-color: var(--primary-blue);
    }
    
    .sm-view-toggle {
        display: flex;
        background: var(--light-gray);
        border-radius: var(--radius-md);
        padding: 2px;
        border: 1px solid var(--border-gray);
    }
    
    .sm-view-btn {
        padding: var(--spacing-sm) var(--spacing-md);
        background: transparent;
        border: none;
        color: var(--text-gray);
        cursor: pointer;
        border-radius: var(--radius-sm);
        transition: var(--transition-fast);
        font-size: 0.875rem;
    }
    
    .sm-view-btn:hover {
        color: var(--dark-gray);
        background: var(--medium-gray);
    }
    
    .sm-view-btn.active {
        background: var(--white);
        color: var(--primary-blue);
        box-shadow: var(--shadow-sm);
    }
    
    /* ===== MAIN LAYOUT ===== */
    .sm-main {
        padding: var(--spacing-xl) 0 var(--spacing-4xl);
        background: var(--white);
        min-height: 60vh;
    }
    
    .sm-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: var(--spacing-2xl);
        align-items: start;
    }
    
    /* ===== SIDEBAR FILTERS ===== */
    .sm-sidebar {
        position: sticky;
        top: 120px;
        max-height: calc(100vh - 140px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--border-gray) transparent;
    }
    
    .sm-filter-card {
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        border: 1px solid rgba(74, 144, 226, 0.1);
    }
    
    .sm-filter-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
        color: var(--white);
        padding: var(--spacing-lg);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .sm-filter-title {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }
    
    .sm-filter-close {
        display: none;
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        cursor: pointer;
        border-radius: 50%;
        transition: var(--transition-fast);
    }
    
    .sm-filter-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .sm-filter-body {
        padding: var(--spacing-lg);
    }
    
    .sm-filter-group {
        margin-bottom: var(--spacing-xl);
        padding-bottom: var(--spacing-lg);
        border-bottom: 1px solid var(--border-gray);
    }
    
    .sm-filter-group:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .sm-filter-group-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--primary-blue);
        margin-bottom: var(--spacing-md);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .sm-filter-option {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm);
        cursor: pointer;
        border-radius: var(--radius-sm);
        transition: var(--transition-fast);
    }
    
    .sm-filter-option:hover {
        background: var(--light-blue);
    }
    
    .sm-filter-checkbox,
    .sm-filter-radio {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-blue);
        cursor: pointer;
    }
    
    .sm-filter-label {
        flex: 1;
        font-size: 0.875rem;
        color: var(--dark-gray);
        font-weight: 500;
        cursor: pointer;
    }
    
    .sm-filter-count {
        background: var(--light-blue);
        color: var(--primary-blue);
        font-size: 0.75rem;
        font-weight: 700;
        padding: 2px var(--spacing-sm);
        border-radius: var(--radius-sm);
        min-width: 24px;
        text-align: center;
    }
    
    /* ===== RESULTS HEADER ===== */
    .sm-results-header {
        background: var(--white);
        padding: var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(74, 144, 226, 0.1);
    }
    
    .sm-results-info {
        display: flex;
        align-items: baseline;
        gap: var(--spacing-sm);
    }
    
    .sm-results-number {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-blue);
    }
    
    .sm-results-text {
        font-size: 1rem;
        color: var(--text-gray);
        font-weight: 500;
    }
    
    .sm-loading-indicator {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        color: var(--text-gray);
        font-size: 0.875rem;
    }
    
    .sm-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid var(--border-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* ===== GRANTS GRID ===== */
    .sm-grants-container {
        position: relative;
        min-height: 400px;
    }
    
    .sm-grants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: var(--spacing-xl);
        margin-bottom: var(--spacing-2xl);
    }
    
    .sm-grants-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-2xl);
    }
    
    /* Grant Card - Site Matching Style */
    .sm-grant-card {
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        border: 1px solid rgba(74, 144, 226, 0.1);
        transition: var(--transition-normal);
        cursor: pointer;
        position: relative;
    }
    
    .sm-grant-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
        opacity: 0;
        transition: var(--transition-fast);
    }
    
    .sm-grant-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }
    
    .sm-grant-card:hover::before {
        opacity: 1;
    }
    
    .sm-grant-card-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-gray);
    }
    
    .sm-grant-card-body {
        padding: var(--spacing-lg);
    }
    
    .sm-grant-card-footer {
        padding: var(--spacing-md) var(--spacing-lg);
        background: var(--light-gray);
        border-top: 1px solid var(--border-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* ===== PAGINATION ===== */
    .sm-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: var(--spacing-sm);
        margin-top: var(--spacing-2xl);
    }
    
    .sm-page-btn {
        min-width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--white);
        border: 2px solid var(--border-gray);
        color: var(--dark-gray);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: var(--radius-md);
        transition: var(--transition-fast);
        text-decoration: none;
    }
    
    .sm-page-btn:hover {
        border-color: var(--primary-blue);
        background: var(--light-blue);
        color: var(--primary-blue);
        transform: translateY(-2px);
    }
    
    .sm-page-btn.current {
        background: var(--primary-blue);
        color: var(--white);
        border-color: var(--primary-blue);
        box-shadow: var(--shadow-md);
    }
    
    /* ===== NO RESULTS ===== */
    .sm-no-results {
        text-align: center;
        padding: var(--spacing-4xl) var(--spacing-lg);
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        border: 2px dashed var(--border-gray);
    }
    
    .sm-no-results-icon {
        font-size: 4rem;
        color: var(--text-gray);
        margin-bottom: var(--spacing-lg);
        opacity: 0.5;
    }
    
    .sm-no-results-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--black);
        margin-bottom: var(--spacing-md);
    }
    
    .sm-no-results-text {
        color: var(--text-gray);
        margin-bottom: var(--spacing-xl);
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .sm-reset-button {
        padding: var(--spacing-md) var(--spacing-xl);
        background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
        color: var(--white);
        border: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-fast);
        box-shadow: var(--shadow-md);
    }
    
    .sm-reset-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 1024px) {
        .sm-layout {
            grid-template-columns: 1fr;
        }
        
        .sm-sidebar {
            position: static;
            max-height: none;
            margin-bottom: var(--spacing-xl);
        }
        
        .sm-grants-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .sm-container {
            padding: 0 var(--spacing-md);
        }
        
        .sm-hero {
            padding: var(--spacing-2xl) 0;
        }
        
        .sm-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-md);
        }
        
        .sm-search-card {
            padding: var(--spacing-lg);
        }
        
        .sm-controls-inner {
            flex-direction: column;
            align-items: stretch;
        }
        
        .sm-controls-left,
        .sm-controls-right {
            width: 100%;
            justify-content: space-between;
        }
        
        .sm-view-toggle {
            display: none;
        }
        
        .sm-grants-grid {
            grid-template-columns: 1fr;
            gap: var(--spacing-lg);
        }
        
        /* Mobile Sidebar */
        .sm-sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--white);
            z-index: 1000;
            transition: left var(--transition-normal);
            overflow-y: auto;
        }
        
        .sm-sidebar.active {
            left: 0;
        }
        
        .sm-filter-close {
            display: flex;
        }
        
        .sm-quick-filters {
            overflow-x: auto;
            justify-content: flex-start;
            padding: var(--spacing-sm) 0;
            scrollbar-width: none;
        }
        
        .sm-quick-filters::-webkit-scrollbar {
            display: none;
        }
    }
    
    /* ===== ANIMATIONS ===== */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .sm-fade-in {
        animation: fadeInUp 0.6s ease-out;
    }
    
    /* ===== LOADING OVERLAY ===== */
    .sm-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: var(--radius-xl);
    }
    
    .sm-loading-overlay .sm-spinner {
        width: 40px;
        height: 40px;
    }
    
    /* ===== UTILITY CLASSES ===== */
    .sm-hidden {
        display: none !important;
    }
    
    .sm-text-center {
        text-align: center;
    }
    
    .sm-font-bold {
        font-weight: 700;
    }
    
    /* ===== FOCUS STYLES ===== */
    button:focus-visible,
    input:focus-visible,
    select:focus-visible,
    a:focus-visible {
        outline: 2px solid var(--primary-blue);
        outline-offset: 2px;
    }
    
    /* ===== FILTER MORE BUTTON ===== */
    .sm-filter-more-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        transition: var(--transition-fast);
        padding: var(--spacing-sm);
        border-radius: var(--radius-sm);
        width: 100%;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: var(--primary-blue);
        font-weight: 500;
    }
    
    .sm-filter-more-btn:hover {
        background: var(--light-blue);
        color: var(--secondary-blue);
    }
    
    .sm-filter-more-btn:focus {
        outline: 2px solid var(--primary-blue);
        outline-offset: 2px;
    }
    
    .sm-filter-more-item {
        transition: opacity 0.3s ease, max-height 0.3s ease;
    }
    
    .sm-filter-more-item.hidden {
        display: none;
    }
    
    /* ===== PRINT STYLES ===== */
    @media print {
        .sm-hero,
        .sm-search-section,
        .sm-controls,
        .sm-sidebar,
        .sm-pagination {
            display: none;
        }
        
        .sm-layout {
            grid-template-columns: 1fr;
        }
        
        .sm-grants-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* ===== ENHANCED MOBILE RESPONSIVENESS ===== */
    @media (max-width: 768px) {
        .sm-search-wrapper {
            margin-bottom: 2rem;
        }
        
        .sm-search-container {
            padding: 0.875rem 1.25rem;
        }
        
        .sm-search-input {
            font-size: 1rem;
        }
        .sm-hero {
            padding: 4rem 0 3rem;
            min-height: 50vh;
        }
        
        .sm-hero-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .sm-hero-subtitle {
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }
        
        .sm-filter-section {
            padding: 1.5rem 0;
        }
        
        .sm-filter-card {
            padding: 1rem;
            border-radius: var(--radius-lg);
        }
        
        .sm-quick-filters {
            gap: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .sm-filter-pill {
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
        }
        
        .sm-container {
            padding: 0 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .sm-hero {
            padding: 3rem 0 2rem;
        }
        
        .sm-hero-title {
            font-size: 2rem;
        }
        
        .sm-hero-badge {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
        }
        
        .sm-search-container {
            padding: 0.75rem 1rem;
        }
        
        .sm-search-icon {
            font-size: 1rem;
            margin-right: 0.75rem;
        }
        
        .sm-quick-filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        .sm-filter-pill {
            justify-content: center;
            padding: 0.75rem 1rem;
        }
    }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body <?php body_class('sm-archive-page'); ?>>

<!-- Hero Section -->
<section class="sm-hero">
    <div class="sm-container">
        <div class="sm-hero-content">
            <div class="sm-hero-badge">
                <i class="fas fa-sparkles"></i>
                助成金・補助金インサイト
            </div>
            <h1 class="sm-hero-title">
                <span class="highlight">AI</span>が瞬時に発見<br>
                成功まで完全サポート
            </h1>
            <p class="sm-hero-subtitle">
                <?php 
                if (!empty($search_params['search']) || !empty($search_params['category']) || !empty($search_params['prefecture'])) {
                    echo '検索条件に該当する助成金・補助金を表示中。最適な助成金を見つけて、ビジネスの成長を加速させましょう。';
                } else {
                    echo '全国47都道府県の助成金・補助金を簡単検索。AIサポートであなたにピッタリの制度を見つけましょう。';
                }
                ?>
            </p>
            
            <!-- Simple Search -->
            <div class="sm-search-wrapper">
                <div class="sm-search-container">
                    <i class="fas fa-search sm-search-icon"></i>
                    <input type="text" 
                           id="sm-search-input" 
                           class="sm-search-input" 
                           placeholder="助成金名、キーワードで検索..." 
                           value="<?php echo esc_attr($search_params['search']); ?>"
                           autocomplete="off">
                    <button id="sm-search-clear" 
                            class="sm-search-clear" 
                            <?php echo empty($search_params['search']) ? 'style="display:none"' : ''; ?>>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="sm-filter-section">
    <div class="sm-container">
        <div class="sm-filter-card">
            <!-- Quick Filters -->
            <div class="sm-quick-filters">
                <button class="sm-filter-pill <?php echo empty($search_params['status']) && empty($search_params['is_featured']) ? 'active' : ''; ?>" data-filter="all">
                    <span class="sm-filter-pill-icon">📋</span>
                    すべて
                </button>
                <button class="sm-filter-pill <?php echo $search_params['is_featured'] === '1' ? 'active' : ''; ?>" data-filter="featured">
                    <span class="sm-filter-pill-icon">⭐</span>
                    おすすめ
                </button>
                <button class="sm-filter-pill <?php echo $search_params['status'] === 'active' ? 'active' : ''; ?>" data-filter="active">
                    <span class="sm-filter-pill-icon">🔴</span>
                    募集中
                    <?php if ($stats['active_grants'] > 0): ?>
                    <span class="sm-filter-pill-count"><?php echo $stats['active_grants']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="sm-filter-pill" data-filter="high-rate">
                    <span class="sm-filter-pill-icon">📈</span>
                    高採択率
                </button>
                <button class="sm-filter-pill" data-filter="large-amount">
                    <span class="sm-filter-pill-icon">💰</span>
                    1000万円以上
                </button>
                <button class="sm-filter-pill" data-filter="easy">
                    <span class="sm-filter-pill-icon">✨</span>
                    申請簡単
                </button>
                <button class="sm-filter-pill" data-filter="online">
                    <span class="sm-filter-pill-icon">💻</span>
                    オンライン申請
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Controls Bar -->
<section class="sm-controls">
    <div class="sm-container">
        <div class="sm-controls-inner">
            <div class="sm-controls-left">
                <select id="sm-sort-select" class="sm-select">
                    <option value="date_desc" <?php selected($search_params['sort'], 'date_desc'); ?>>新着順</option>
                    <option value="featured_first" <?php selected($search_params['sort'], 'featured_first'); ?>>おすすめ順</option>
                    <option value="amount_desc" <?php selected($search_params['sort'], 'amount_desc'); ?>>金額が高い順</option>
                    <option value="deadline_asc" <?php selected($search_params['sort'], 'deadline_asc'); ?>>締切が近い順</option>
                    <option value="success_rate_desc" <?php selected($search_params['sort'], 'success_rate_desc'); ?>>採択率順</option>
                </select>

                <button id="sm-filter-toggle" class="sm-filter-button">
                    <i class="fas fa-sliders-h"></i>
                    フィルター
                    <span id="sm-filter-count" class="sm-filter-pill-count" style="display:none">0</span>
                </button>
            </div>

            <div class="sm-controls-right">
                <div class="sm-view-toggle">
                    <button id="sm-grid-view" 
                            class="sm-view-btn <?php echo $search_params['view'] === 'grid' ? 'active' : ''; ?>" 
                            data-view="grid" 
                            title="グリッド表示">
                        <i class="fas fa-th"></i>
                    </button>
                    <button id="sm-list-view" 
                            class="sm-view-btn <?php echo $search_params['view'] === 'list' ? 'active' : ''; ?>" 
                            data-view="list" 
                            title="リスト表示">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="sm-main">
    <div class="sm-container">
        <div class="sm-layout">
            
            <!-- Sidebar Filters -->
            <aside id="sm-filter-sidebar" class="sm-sidebar">
                <div class="sm-filter-card">
                    <div class="sm-filter-header">
                        <h3 class="sm-filter-title">詳細フィルター</h3>
                        <button id="sm-filter-close" class="sm-filter-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="sm-filter-body">
                        
                        <!-- Special Filters -->
                        <div class="sm-filter-group">
                            <h4 class="sm-filter-group-title">特別フィルター</h4>
                            <label class="sm-filter-option">
                                <input type="checkbox" 
                                       name="is_featured" 
                                       value="1" 
                                       class="sm-filter-checkbox featured-checkbox"
                                       <?php checked($search_params['is_featured'], '1'); ?>>
                                <span class="sm-filter-label">おすすめの助成金のみ</span>
                            </label>
                        </div>
                        
                        <!-- Prefecture Filters -->
                        <?php if (!empty($all_prefectures) && !is_wp_error($all_prefectures)): ?>
                        <div class="sm-filter-group">
                            <h4 class="sm-filter-group-title">対象地域</h4>
                            <?php 
                            $prefecture_limit = 10;
                            $selected_prefectures = explode(',', $search_params['prefecture']);
                            $prefecture_count = count($all_prefectures);
                            
                            // 選択されている項目があれば最初から全て表示
                            $has_selected = !empty(array_filter($selected_prefectures));
                            $show_all_initially = $has_selected;
                            
                            foreach ($all_prefectures as $index => $prefecture): 
                                $is_selected = in_array($prefecture->slug, $selected_prefectures);
                                $is_hidden = !$show_all_initially && $index >= $prefecture_limit;
                            ?>
                            <label class="sm-filter-option <?php echo $is_hidden ? 'sm-filter-more-item hidden' : ''; ?>">
                                <input type="checkbox" 
                                       name="prefectures[]" 
                                       value="<?php echo esc_attr($prefecture->slug); ?>" 
                                       class="sm-filter-checkbox prefecture-checkbox"
                                       <?php checked($is_selected); ?>>
                                <span class="sm-filter-label"><?php echo esc_html($prefecture->name); ?></span>
                                <?php if ($prefecture->count > 0): ?>
                                <span class="sm-filter-count"><?php echo esc_html($prefecture->count); ?></span>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                            <?php if ($prefecture_count > $prefecture_limit): ?>
                            <button type="button" class="sm-filter-more-btn text-sm text-blue-600 hover:text-blue-800 mt-2 flex items-center gap-1" data-target="prefecture">
                                <span class="show-more-text <?php echo $show_all_initially ? 'hidden' : ''; ?>">さらに表示 (+<?php echo $prefecture_count - $prefecture_limit; ?>)</span>
                                <span class="show-less-text <?php echo !$show_all_initially ? 'hidden' : ''; ?>">表示を減らす</span>
                                <i class="fas fa-chevron-down show-more-icon <?php echo $show_all_initially ? 'hidden' : ''; ?>"></i>
                                <i class="fas fa-chevron-up show-less-icon <?php echo !$show_all_initially ? 'hidden' : ''; ?>"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Category Filters -->
                        <?php if (!empty($all_categories) && !is_wp_error($all_categories)): ?>
                        <div class="sm-filter-group">
                            <h4 class="sm-filter-group-title">カテゴリ</h4>
                            <?php 
                            $category_limit = 8;
                            $selected_categories = explode(',', $search_params['category']);
                            $category_count = count($all_categories);
                            
                            // 選択されている項目があれば最初から全て表示
                            $has_selected_cat = !empty(array_filter($selected_categories));
                            $show_all_cat_initially = $has_selected_cat;
                            
                            foreach ($all_categories as $index => $category): 
                                $is_selected_cat = in_array($category->slug, $selected_categories);
                                $is_hidden = !$show_all_cat_initially && $index >= $category_limit;
                            ?>
                            <label class="sm-filter-option <?php echo $is_hidden ? 'sm-filter-more-item hidden' : ''; ?>">
                                <input type="checkbox" 
                                       name="categories[]" 
                                       value="<?php echo esc_attr($category->slug); ?>" 
                                       class="sm-filter-checkbox category-checkbox"
                                       <?php checked($is_selected_cat); ?>>
                                <span class="sm-filter-label"><?php echo esc_html($category->name); ?></span>
                                <?php if ($category->count > 0): ?>
                                <span class="sm-filter-count"><?php echo esc_html($category->count); ?></span>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                            <?php if ($category_count > $category_limit): ?>
                            <button type="button" class="sm-filter-more-btn text-sm text-blue-600 hover:text-blue-800 mt-2 flex items-center gap-1" data-target="category">
                                <span class="show-more-text <?php echo $show_all_cat_initially ? 'hidden' : ''; ?>">さらに表示 (+<?php echo $category_count - $category_limit; ?>)</span>
                                <span class="show-less-text <?php echo !$show_all_cat_initially ? 'hidden' : ''; ?>">表示を減らす</span>
                                <i class="fas fa-chevron-down show-more-icon <?php echo $show_all_cat_initially ? 'hidden' : ''; ?>"></i>
                                <i class="fas fa-chevron-up show-less-icon <?php echo !$show_all_cat_initially ? 'hidden' : ''; ?>"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Amount Filters -->
                        <div class="sm-filter-group">
                            <h4 class="sm-filter-group-title">助成金額</h4>
                            <?php
                            $amount_ranges = [
                                '' => 'すべて',
                                '0-100' => '〜100万円',
                                '100-500' => '100〜500万円',
                                '500-1000' => '500〜1000万円',
                                '1000-3000' => '1000〜3000万円',
                                '3000+' => '3000万円以上'
                            ];
                            foreach ($amount_ranges as $value => $label):
                            ?>
                            <label class="sm-filter-option">
                                <input type="radio" 
                                       name="amount" 
                                       value="<?php echo esc_attr($value); ?>" 
                                       class="sm-filter-radio amount-radio"
                                       <?php checked($search_params['amount'], $value); ?>>
                                <span class="sm-filter-label"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="sm-content">
                <!-- Results Header -->
                <div class="sm-results-header">
                    <div class="sm-results-info">
                        <span id="sm-results-count" class="sm-results-number"><?php echo number_format($grants_query->found_posts); ?></span>
                        <span class="sm-results-text">件の助成金</span>
                    </div>
                    <div id="sm-loading" class="sm-loading-indicator sm-hidden">
                        <div class="sm-spinner"></div>
                        <span>更新中...</span>
                    </div>
                </div>

                <!-- Grants Container -->
                <div id="sm-grants-container" class="sm-grants-container">
                    <div id="sm-grants-display">
                        <?php if ($grants_query->have_posts()): ?>
                            <div class="<?php echo $search_params['view'] === 'grid' ? 'sm-grants-grid' : 'sm-grants-list'; ?>">
                                <?php
                                while ($grants_query->have_posts()):
                                    $grants_query->the_post();
                                    
                                    // 統一カードテンプレートを使用
                                    $GLOBALS['current_view'] = $search_params['view'];
                                    $GLOBALS['user_favorites'] = $user_favorites;
                                    
                                    // template-parts/grant-card-unified.phpを読み込み
                                    get_template_part('template-parts/grant-card-unified');
                                endwhile;
                                ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($grants_query->max_num_pages > 1): ?>
                            <div class="sm-pagination">
                                <?php
                                $pagination_args = [
                                    'total' => $grants_query->max_num_pages,
                                    'current' => max(1, $search_params['page']),
                                    'format' => '?paged=%#%',
                                    'prev_text' => '<i class="fas fa-chevron-left"></i>',
                                    'next_text' => '<i class="fas fa-chevron-right"></i>',
                                    'type' => 'array',
                                    'end_size' => 2,
                                    'mid_size' => 2
                                ];
                                
                                $pagination_links = paginate_links($pagination_args);
                                if ($pagination_links) {
                                    foreach ($pagination_links as $link) {
                                        $link = str_replace('class="page-numbers', 'class="sm-page-btn page-numbers', $link);
                                        $link = str_replace('class="sm-page-btn page-numbers current', 'class="sm-page-btn page-numbers current', $link);
                                        echo $link;
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="sm-no-results">
                                <div class="sm-no-results-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3 class="sm-no-results-title">該当する助成金が見つかりませんでした</h3>
                                <p class="sm-no-results-text">検索条件を変更して再度お試しください。AIが最適な助成金をお探しします。</p>
                                <button id="sm-reset-search" class="sm-reset-button">
                                    検索をリセット
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php wp_reset_postdata(); ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<!-- JavaScript -->
<script>
/**
 * Site Matching Grant Archive JavaScript
 * 既存サイトデザインに完全マッチした機能
 */
(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>',
        debounceDelay: 300,
        searchDelay: 500
    };
    
    // State Management
    const state = {
        currentView: '<?php echo $search_params['view']; ?>',
        currentPage: <?php echo $search_params['page']; ?>,
        isLoading: false,
        filters: {
            search: '<?php echo esc_js($search_params['search']); ?>',
            categories: <?php echo json_encode(array_filter(explode(',', $search_params['category']))); ?>,
            prefectures: <?php echo json_encode(array_filter(explode(',', $search_params['prefecture']))); ?>,
            amount: '<?php echo esc_js($search_params['amount']); ?>',
            status: <?php echo json_encode(array_filter(explode(',', $search_params['status']))); ?>,
            is_featured: '<?php echo esc_js($search_params['is_featured']); ?>',
            sort: '<?php echo esc_js($search_params['sort']); ?>'
        }
    };
    
    // DOM Elements
    const elements = {};
    
    // Timers
    let debounceTimer = null;
    let searchTimer = null;
    
    /**
     * Initialize
     */
    function init() {
        cacheElements();
        bindEvents();
        updateFilterCount();
        initializeCardInteractions();
    }
    
    /**
     * Cache DOM elements
     */
    function cacheElements() {
        const ids = [
            'sm-search-input', 'sm-search-clear',
            'sm-sort-select', 'sm-filter-toggle', 'sm-filter-sidebar',
            'sm-filter-close', 'sm-grid-view', 'sm-list-view',
            'sm-reset-search', 'sm-results-count', 'sm-loading',
            'sm-grants-container', 'sm-grants-display', 'sm-filter-count'
        ];
        
        ids.forEach(id => {
            elements[id.replace(/-/g, '_')] = document.getElementById(id);
        });
        
        elements.quickFilters = document.querySelectorAll('.sm-filter-pill');
        elements.filterCheckboxes = document.querySelectorAll('.sm-filter-checkbox');
        elements.filterRadios = document.querySelectorAll('.sm-filter-radio');
    }
    
    /**
     * Bind events
     */
    function bindEvents() {
        // Search

        // Search
        if (elements.sm_search_input) {
            elements.sm_search_input.addEventListener('input', handleSearchInput);
            elements.sm_search_input.addEventListener('keypress', handleSearchKeypress);
        }
        
        if (elements.sm_search_clear) {
            elements.sm_search_clear.addEventListener('click', handleSearchClear);
        }
        
        // Sort
        if (elements.sm_sort_select) {
            elements.sm_sort_select.addEventListener('change', handleSortChange);
        }
        
        // Filter toggle
        if (elements.sm_filter_toggle) {
            elements.sm_filter_toggle.addEventListener('click', toggleFilterSidebar);
        }
        
        if (elements.sm_filter_close) {
            elements.sm_filter_close.addEventListener('click', closeFilterSidebar);
        }
        
        // View switcher
        if (elements.sm_grid_view) {
            elements.sm_grid_view.addEventListener('click', () => switchView('grid'));
        }
        
        if (elements.sm_list_view) {
            elements.sm_list_view.addEventListener('click', () => switchView('list'));
        }
        
        // Quick filters
        elements.quickFilters.forEach(filter => {
            filter.addEventListener('click', handleQuickFilter);
        });
        
        // Filter inputs
        [...elements.filterCheckboxes, ...elements.filterRadios].forEach(input => {
            input.addEventListener('change', handleFilterChange);
        });
        
        // Reset
        if (elements.sm_reset_search) {
            elements.sm_reset_search.addEventListener('click', resetAllFilters);
        }
        
        // Pagination
        document.addEventListener('click', handlePaginationClick);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
        
        // More filters toggle (using event delegation)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.sm-filter-more-btn');
            if (btn) {
                handleMoreFiltersToggle(e);
            }
        });
    }
    

    
    /**
     * Handle search input
     */
    function handleSearchInput(e) {
        state.filters.search = e.target.value;
        elements.sm_search_clear.style.display = e.target.value ? 'block' : 'none';
        
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadGrants();
        }, config.searchDelay);
    }
    
    /**
     * Handle search keypress
     */
    function handleSearchKeypress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadGrants();
        }
    }
    
    /**
     * Handle search clear
     */
    function handleSearchClear() {
        elements.sm_search_input.value = '';
        elements.sm_search_clear.style.display = 'none';
        state.filters.search = '';
        loadGrants();
    }
    
    /**
     * Handle sort change
     */
    function handleSortChange(e) {
        state.filters.sort = e.target.value;
        state.currentPage = 1;
        loadGrants();
    }
    
    /**
     * Handle quick filter
     */
    function handleQuickFilter(e) {
        const filter = e.currentTarget.dataset.filter;
        
        elements.quickFilters.forEach(f => f.classList.remove('active'));
        e.currentTarget.classList.add('active');
        
        resetFiltersState();
        
        switch(filter) {
            case 'featured':
                state.filters.is_featured = '1';
                break;
            case 'active':
                state.filters.status = ['active'];
                break;
            default:
                break;
        }
        
        state.currentPage = 1;
        updateFilterCount();
        loadGrants();
    }
    
    /**
     * Handle filter change
     */
    function handleFilterChange() {
        updateFiltersFromForm();
        updateFilterCount();
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.currentPage = 1;
            loadGrants();
        }, config.debounceDelay);
    }
    
    /**
     * Update filters from form
     */
    function updateFiltersFromForm() {
        state.filters.categories = Array.from(
            document.querySelectorAll('.category-checkbox:checked')
        ).map(cb => cb.value);
        
        state.filters.prefectures = Array.from(
            document.querySelectorAll('.prefecture-checkbox:checked')
        ).map(cb => cb.value);
        
        const featuredCheckbox = document.querySelector('.featured-checkbox:checked');
        state.filters.is_featured = featuredCheckbox ? '1' : '';
        
        const amountRadio = document.querySelector('.amount-radio:checked');
        state.filters.amount = amountRadio ? amountRadio.value : '';
    }
    
    /**
     * Update filter count
     */
    function updateFilterCount() {
        const count = 
            state.filters.categories.length +
            state.filters.prefectures.length +
            (state.filters.amount ? 1 : 0) +
            state.filters.status.length +
            (state.filters.is_featured ? 1 : 0);
        
        if (elements.sm_filter_count) {
            elements.sm_filter_count.textContent = count;
            elements.sm_filter_count.style.display = count > 0 ? 'inline-block' : 'none';
        }
        
        if (elements.sm_filter_toggle) {
            elements.sm_filter_toggle.classList.toggle('has-filters', count > 0);
        }
    }
    
    /**
     * Switch view
     */
    function switchView(view) {
        if (state.currentView === view) return;
        
        state.currentView = view;
        
        elements.sm_grid_view.classList.toggle('active', view === 'grid');
        elements.sm_list_view.classList.toggle('active', view === 'list');
        
        loadGrants();
    }
    
    /**
     * Toggle filter sidebar
     */
    function toggleFilterSidebar() {
        elements.sm_filter_sidebar.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Close filter sidebar
     */
    function closeFilterSidebar() {
        elements.sm_filter_sidebar.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    /**
     * Reset all filters
     */
    function resetAllFilters() {
        resetFiltersState();
        

        elements.sm_search_clear.style.display = 'none';
        
        elements.filterCheckboxes.forEach(cb => cb.checked = false);
        elements.filterRadios.forEach(rb => rb.checked = rb.value === '');
        
        elements.quickFilters.forEach(f => f.classList.remove('active'));
        document.querySelector('.sm-filter-pill[data-filter="all"]')?.classList.add('active');
        
        // Clear search
        if (elements.sm_search_input) {
            elements.sm_search_input.value = '';
        }
        if (elements.sm_search_clear) {
            elements.sm_search_clear.style.display = 'none';
        }
        
        state.currentPage = 1;
        updateFilterCount();
        loadGrants();
    }
    
    /**
     * Reset filters state
     */
    function resetFiltersState() {
        state.filters = {
            search: '',
            categories: [],
            prefectures: [],
            amount: '',
            status: [],
            is_featured: '',
            sort: state.filters.sort
        };
    }
    
    /**
     * Handle pagination
     */
    function handlePaginationClick(e) {
        if (e.target.classList.contains('sm-page-btn') || e.target.closest('.sm-page-btn')) {
            e.preventDefault();
            
            const btn = e.target.classList.contains('sm-page-btn') ? e.target : e.target.closest('.sm-page-btn');
            const href = btn.getAttribute('href');
            
            if (href) {
                const url = new URL(href, window.location.origin);
                const page = parseInt(url.searchParams.get('paged')) || 1;
                
                if (page !== state.currentPage) {
                    state.currentPage = page;
                    loadGrants();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }
    }
    
    /**
     * Handle keyboard shortcuts
     */
    function handleKeyboardShortcuts(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();

        }
        
        if (e.key === 'Escape') {
            closeFilterSidebar();
        }
    }
    
    /**
     * Load grants via AJAX
     */
    async function loadGrants() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'gi_load_grants',
                    nonce: config.nonce,
                    search: state.filters.search,
                    categories: JSON.stringify(state.filters.categories),
                    prefectures: JSON.stringify(state.filters.prefectures),
                    amount: state.filters.amount,
                    status: JSON.stringify(state.filters.status),
                    only_featured: state.filters.is_featured,
                    sort: state.filters.sort,
                    view: state.currentView,
                    page: state.currentPage
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                renderGrants(data.data);
                updateURL();
            } else {
                showNoResults();
            }
        } catch (error) {
            console.error('Error loading grants:', error);
            showError();
        } finally {
            state.isLoading = false;
            hideLoading();
        }
    }
    
    /**
     * Render grants
     */
    function renderGrants(data) {
        const { grants, pagination, stats } = data;
        
        if (elements.sm_results_count) {
            elements.sm_results_count.textContent = stats?.total_found ? number_format(stats.total_found) : '0';
        }
        
        if (grants && grants.length > 0) {
            const containerClass = state.currentView === 'grid' ? 'sm-grants-grid' : 'sm-grants-list';
            elements.sm_grants_display.innerHTML = `
                <div class="${containerClass}">
                    ${grants.map(grant => grant.html).join('')}
                </div>
            `;
            
            initializeCardInteractions();
        } else {
            showNoResults();
        }
    }
    
    /**
     * Initialize card interactions
     */
    function initializeCardInteractions() {
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', handleFavoriteClick);
        });
        
        document.querySelectorAll('.sm-grant-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
    
    /**
     * Handle favorite click
     */
    async function handleFavoriteClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const postId = btn.dataset.postId;
        
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
        
        try {
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'gi_toggle_favorite',
                    nonce: config.nonce,
                    post_id: postId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                btn.textContent = data.data.is_favorite ? '♥' : '♡';
                btn.style.color = data.data.is_favorite ? '#dc3545' : '#6c757d';
                
                showNotification(data.data.message, 'success');
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            showNotification('エラーが発生しました', 'error');
        } finally {
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
    }
    
    /**
     * Show loading
     */
    function showLoading() {
        if (elements.sm_loading) {
            elements.sm_loading.classList.remove('sm-hidden');
        }
        
        const container = elements.sm_grants_container;
        if (container && !container.querySelector('.sm-loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'sm-loading-overlay';
            overlay.innerHTML = '<div class="sm-spinner"></div>';
            container.appendChild(overlay);
        }
    }
    
    /**
     * Hide loading
     */
    function hideLoading() {
        if (elements.sm_loading) {
            elements.sm_loading.classList.add('sm-hidden');
        }
        
        const overlay = document.querySelector('.sm-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    /**
     * Show no results
     */
    function showNoResults() {
        elements.sm_grants_display.innerHTML = `
            <div class="sm-no-results">
                <div class="sm-no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="sm-no-results-title">該当する助成金が見つかりませんでした</h3>
                <p class="sm-no-results-text">検索条件を変更して再度お試しください。AIが最適な助成金をお探しします。</p>
                <button class="sm-reset-button" onclick="SiteMatching.resetAllFilters()">
                    検索をリセット
                </button>
            </div>
        `;
    }
    
    /**
     * Show error
     */
    function showError() {
        elements.sm_grants_display.innerHTML = `
            <div class="sm-no-results">
                <div class="sm-no-results-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="sm-no-results-title">エラーが発生しました</h3>
                <p class="sm-no-results-text">しばらく時間をおいて再度お試しください</p>
                <button class="sm-reset-button" onclick="window.location.reload()">
                    ページを再読み込み
                </button>
            </div>
        `;
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#4a90e2'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 10000;
            font-weight: 500;
            animation: slideInUp 0.3s ease;
            max-width: 300px;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutDown 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    /**
     * Update URL
     */
    function updateURL() {
        const params = new URLSearchParams();
        
        if (state.filters.search) params.set('s', state.filters.search);
        if (state.filters.categories.length) params.set('category', state.filters.categories.join(','));
        if (state.filters.prefectures.length) params.set('prefecture', state.filters.prefectures.join(','));
        if (state.filters.amount) params.set('amount', state.filters.amount);
        if (state.filters.status.length) params.set('status', state.filters.status.join(','));
        if (state.filters.is_featured) params.set('featured', '1');
        if (state.filters.sort !== 'date_desc') params.set('sort', state.filters.sort);
        if (state.currentView !== 'grid') params.set('view', state.currentView);
        if (state.currentPage > 1) params.set('paged', state.currentPage);
        
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newURL);
    }
    
    /**
     * Handle more filters toggle
     */
    function handleMoreFiltersToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.target.closest('.sm-filter-more-btn');
        if (!btn) return;
        
        const filterGroup = btn.closest('.sm-filter-group');
        if (!filterGroup) return;
        
        const moreItems = filterGroup.querySelectorAll('.sm-filter-more-item');
        const showMoreText = btn.querySelector('.show-more-text');
        const showLessText = btn.querySelector('.show-less-text');
        const showMoreIcon = btn.querySelector('.show-more-icon');
        const showLessIcon = btn.querySelector('.show-less-icon');
        
        // 現在の状態を正しく判定: showMoreTextが見える（hiddenクラスがない）場合は折りたたまれている状態
        const isCollapsed = showMoreText && !showMoreText.classList.contains('hidden');
        
        if (isCollapsed) {
            // 折りたたまれている -> 展開する
            moreItems.forEach(item => {
                item.classList.remove('hidden');
            });
            if (showMoreText) showMoreText.classList.add('hidden');
            if (showLessText) showLessText.classList.remove('hidden');
            if (showMoreIcon) showMoreIcon.classList.add('hidden');
            if (showLessIcon) showLessIcon.classList.remove('hidden');
        } else {
            // 展開されている -> 折りたたむ
            moreItems.forEach(item => {
                item.classList.add('hidden');
            });
            if (showMoreText) showMoreText.classList.remove('hidden');
            if (showLessText) showLessText.classList.add('hidden');
            if (showMoreIcon) showMoreIcon.classList.remove('hidden');
            if (showLessIcon) showLessIcon.classList.add('hidden');
        }
    }
    
    /**
     * Format number
     */
    function number_format(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // Public API
    window.SiteMatching = {
        resetAllFilters,
        loadGrants,
        switchView
    };
    
    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Add animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutDown {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(20px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
})();
</script>

<?php get_footer(); ?>
