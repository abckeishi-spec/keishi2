<?php
/**
 * Grant Insight Site Match - 統一カードテンプレート（既存サイトデザイン完全対応版）
 * template-parts/grant-card-unified-site-match.php
 * 
 * ・既存サイトデザインに完全マッチしたスタイル
 * ・ホバー詳細のスクロール機能完全保持
 * ・クリック範囲の最適化（詳細ボタンのみ）
 * ・AI要約と詳細ボタンにサイトテーマのアクセント色適用
 * ・全機能完全保持
 *
 * @package Grant_Insight_Site_Match
 * @version 10.1.0 - Site Design Complete Match Edition
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// グローバル変数から必要データを取得（既存システム連携）
global $post, $user_favorites, $current_view, $display_mode;

$post_id = get_the_ID();
if (!$post_id) return;

// 表示モードの判定（既存システムから取得、デフォルトはcard）
$display_mode = $display_mode ?? (isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'card');
$view_class = 'grant-view-' . $display_mode;

// 既存のヘルパー関数を最大限活用
$grant_data = function_exists('gi_get_complete_grant_data') 
    ? gi_get_complete_grant_data($post_id)
    : gi_get_all_grant_meta($post_id);

// お気に入り情報（既存システム）
$user_favorites = $user_favorites ?? (function_exists('gi_get_user_favorites_safe') 
    ? gi_get_user_favorites_safe() 
    : gi_get_user_favorites());

$is_favorite = in_array($post_id, $user_favorites);

// 既存フィールド名に合わせたデータマッピング
$title = get_the_title($post_id);
$permalink = get_permalink($post_id);
$excerpt = get_the_excerpt($post_id);

// ACFフィールド（既存のgi_get_acf_field_safely使用）
$ai_summary = gi_get_acf_field_safely($post_id, 'ai_summary', '');
$max_amount = gi_get_acf_field_safely($post_id, 'max_amount', '');
$max_amount_numeric = gi_get_acf_field_safely($post_id, 'max_amount_numeric', 0);
$application_status = gi_get_acf_field_safely($post_id, 'application_status', 'open');
$organization = gi_get_acf_field_safely($post_id, 'organization', '');
$grant_target = gi_get_acf_field_safely($post_id, 'grant_target', '');
$subsidy_rate = gi_get_acf_field_safely($post_id, 'subsidy_rate', '');
$grant_difficulty = gi_get_acf_field_safely($post_id, 'grant_difficulty', 'normal');
$grant_success_rate = gi_get_acf_field_safely($post_id, 'grant_success_rate', 0);
$official_url = gi_get_acf_field_safely($post_id, 'official_url', '');
$eligible_expenses = gi_get_acf_field_safely($post_id, 'eligible_expenses', '');
$application_method = gi_get_acf_field_safely($post_id, 'application_method', '');
$required_documents = gi_get_acf_field_safely($post_id, 'required_documents', '');
$contact_info = gi_get_acf_field_safely($post_id, 'contact_info', '');
$is_featured = gi_get_acf_field_safely($post_id, 'is_featured', false);
$priority_order = gi_get_acf_field_safely($post_id, 'priority_order', 100);
$application_period = gi_get_acf_field_safely($post_id, 'application_period', '');

// 締切日の処理（統一版）
$deadline_raw = gi_get_acf_field_safely($post_id, 'deadline', '');
$deadline_timestamp = 0;
$deadline_formatted = '';

if (!empty($deadline_raw)) {
    // Ymd形式（例：20241231）の場合
    if (is_numeric($deadline_raw) && strlen($deadline_raw) == 8) {
        $year = substr($deadline_raw, 0, 4);
        $month = substr($deadline_raw, 4, 2);
        $day = substr($deadline_raw, 6, 2);
        $deadline_timestamp = mktime(0, 0, 0, $month, $day, $year);
        $deadline_formatted = sprintf('%s年%d月%d日', $year, intval($month), intval($day));
    }
    // UNIXタイムスタンプの場合
    elseif (is_numeric($deadline_raw) && $deadline_raw > 946684800) { // 2000年1月1日以降
        $deadline_timestamp = intval($deadline_raw);
        $deadline_formatted = date('Y年n月j日', $deadline_timestamp);
    }
    // 文字列形式の日付
    else {
        $deadline_timestamp = strtotime($deadline_raw);
        if ($deadline_timestamp !== false) {
            $deadline_formatted = date('Y年n月j日', $deadline_timestamp);
        }
    }
} else {
    // deadline_dateフィールドをフォールバック
    $deadline_date_numeric = gi_get_acf_field_safely($post_id, 'deadline_date', 0);
    if ($deadline_date_numeric > 0) {
        $deadline_timestamp = intval($deadline_date_numeric);
        $deadline_formatted = date('Y年n月j日', $deadline_timestamp);
    }
}

// 締切日がない場合のデフォルト
if (empty($deadline_formatted)) {
    $deadline_formatted = function_exists('gi_get_formatted_deadline') 
        ? gi_get_formatted_deadline($post_id) : '未定';
}

// タクソノミーデータ
$categories = gi_get_post_categories($post_id, 'grant_category');
$main_category = !empty($categories) ? $categories[0]['name'] : '';

$prefectures = gi_get_post_categories($post_id, 'grant_prefecture');
$prefecture = !empty($prefectures) ? $prefectures[0]['name'] : '全国';

$industries = gi_get_post_categories($post_id, 'grant_industry');
$main_industry = !empty($industries) ? $industries[0]['name'] : '';

// 既存のフォーマッター関数を使用
$amount_display = function_exists('gi_format_amount_unified') 
    ? gi_format_amount_unified($max_amount_numeric, $max_amount)
    : gi_get_grant_amount_display($post_id);

// ステータス表示（既存関数）
$status_display = function_exists('gi_map_application_status_ui') 
    ? gi_map_application_status_ui($application_status)
    : gi_get_status_name($application_status);

// 締切日情報の処理
$deadline_info = array();
if ($deadline_timestamp > 0) {
    $current_timestamp = current_time('timestamp');
    $days_remaining = ceil(($deadline_timestamp - $current_timestamp) / (60 * 60 * 24));
    
    if ($days_remaining <= 0) {
        $deadline_info = array('class' => 'expired', 'text' => '募集終了', 'icon' => 'fa-times-circle');
    } elseif ($days_remaining <= 3) {
        $deadline_info = array('class' => 'critical', 'text' => '残り'.$days_remaining.'日', 'icon' => 'fa-exclamation-triangle');
    } elseif ($days_remaining <= 7) {
        $deadline_info = array('class' => 'urgent', 'text' => '残り'.$days_remaining.'日', 'icon' => 'fa-clock');
    } elseif ($days_remaining <= 30) {
        $deadline_info = array('class' => 'warning', 'text' => '残り'.$days_remaining.'日', 'icon' => 'fa-calendar-alt');
    } else {
        $deadline_info = array('class' => 'normal', 'text' => $deadline_formatted, 'icon' => 'fa-calendar');
    }
}

// 難易度表示の設定（サイトマッチ版）
$difficulty_config = array(
    'easy' => array('label' => '易しい', 'color' => '#28a745', 'icon' => 'fa-smile'),
    'normal' => array('label' => '普通', 'color' => '#4a90e2', 'icon' => 'fa-meh'),
    'hard' => array('label' => '難しい', 'color' => '#ffc107', 'icon' => 'fa-frown'),
    'expert' => array('label' => '専門的', 'color' => '#dc3545', 'icon' => 'fa-dizzy')
);
$difficulty_data = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];

// CSS・JSの重複防止
static $assets_loaded = false;
?>

<?php if (!$assets_loaded): $assets_loaded = true; ?>
<style>
/* Grant Insight - Site Match Design System */
:root {
    /* サイトマッチング配色（既存サイトに合わせた色調） */
    --sm-primary-blue: #4a90e2;
    --sm-secondary-blue: #357abd;
    --sm-light-blue: #e8f4fd;
    --sm-accent-blue: #2c5aa0;
    --sm-lavender: #f5f3ff;
    --sm-light-lavender: #faf8ff;
    
    /* ニュートラルカラー */
    --sm-white: #ffffff;
    --sm-light-gray: #f8f9fa;
    --sm-medium-gray: #e9ecef;
    --sm-border-gray: #dee2e6;
    --sm-text-gray: #6c757d;
    --sm-dark-gray: #495057;
    --sm-black: #212529;
    
    /* セマンティックカラー */
    --sm-success: #28a745;
    --sm-warning: #ffc107;
    --sm-danger: #dc3545;
    --sm-info: #17a2b8;
    
    /* グラデーション（サイトテーマに合わせて） */
    --sm-gradient-primary: linear-gradient(135deg, #4a90e2 0%, #2c5aa0 100%);
    --sm-gradient-secondary: linear-gradient(135deg, #e8f4fd 0%, #f5f3ff 100%);
    --sm-gradient-light: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    --sm-gradient-accent: linear-gradient(135deg, #357abd 0%, #4a90e2 100%);
    
    /* シャドウ（サイト風） */
    --sm-shadow-sm: 0 2px 4px rgba(74, 144, 226, 0.08);
    --sm-shadow-md: 0 4px 6px -1px rgba(74, 144, 226, 0.1), 0 2px 4px -1px rgba(74, 144, 226, 0.06);
    --sm-shadow-lg: 0 10px 15px -3px rgba(74, 144, 226, 0.1), 0 4px 6px -2px rgba(74, 144, 226, 0.05);
    --sm-shadow-xl: 0 20px 25px -5px rgba(74, 144, 226, 0.1), 0 10px 10px -5px rgba(74, 144, 226, 0.04);
    --sm-shadow-2xl: 0 25px 50px -12px rgba(74, 144, 226, 0.25);
    
    /* ボーダー */
    --sm-border-light: 1px solid rgba(74, 144, 226, 0.1);
    --sm-border-medium: 1px solid rgba(74, 144, 226, 0.2);
    --sm-border-dark: 1px solid rgba(74, 144, 226, 0.3);
    
    /* 角丸（サイトスタイルに合わせて） */
    --sm-radius-sm: 0.375rem;
    --sm-radius-md: 0.75rem;
    --sm-radius-lg: 1rem;
    --sm-radius-xl: 1.5rem;
    --sm-radius-2xl: 2rem;
    
    /* トランジション */
    --sm-transition-fast: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --sm-transition-normal: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    --sm-transition-slow: all 500ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* ============================================
   カード表示モード（サイトマッチ版）
============================================ */
.grant-view-card .grant-card-unified {
    position: relative;
    width: 100%;
    height: auto;
    min-height: 520px;
    background: var(--sm-white);
    border-radius: var(--sm-radius-xl);
    overflow: hidden;
    box-shadow: var(--sm-shadow-lg);
    transition: var(--sm-transition-slow);
    cursor: default;
    display: flex;
    flex-direction: column;
    border: var(--sm-border-light);
}

.grant-view-card .grant-card-unified:hover {
    transform: translateY(-8px) scale(1.01);
    box-shadow: var(--sm-shadow-2xl);
    border-color: var(--sm-primary-blue);
}

/* ============================================
   リスト表示モード（サイトマッチ版）
============================================ */
.grant-view-list .grant-card-unified {
    position: relative;
    width: 100%;
    background: var(--sm-white);
    border-radius: var(--sm-radius-lg);
    box-shadow: var(--sm-shadow-md);
    transition: var(--sm-transition-normal);
    cursor: default;
    display: flex;
    flex-direction: row;
    align-items: stretch;
    min-height: 160px;
    margin-bottom: 20px;
    border: var(--sm-border-light);
}

.grant-view-list .grant-card-unified:hover {
    box-shadow: var(--sm-shadow-xl);
    transform: translateX(6px);
    border-color: var(--sm-primary-blue);
}

.grant-view-list .grant-status-header {
    width: 8px;
    height: auto;
    padding: 0;
    writing-mode: vertical-rl;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sm-gradient-primary);
}

.grant-view-list .grant-card-content {
    flex: 1;
    padding: 20px;
    display: flex;
    flex-direction: row;
    gap: 24px;
}

.grant-view-list .grant-main-info {
    flex: 1;
    min-width: 0;
}

.grant-view-list .grant-title {
    font-size: 18px;
    margin-bottom: 12px;
    -webkit-line-clamp: 2;
}

.grant-view-list .grant-ai-summary {
    display: block;
    max-height: 60px;
}

.grant-view-list .grant-info-grid {
    display: flex;
    gap: 20px;
    margin: 16px 0;
    flex-wrap: wrap;
}

.grant-view-list .grant-info-item {
    background: transparent;
    padding: 8px 12px;
    gap: 8px;
    border-radius: var(--sm-radius-sm);
    background: var(--sm-light-gray);
}

.grant-view-list .grant-info-icon {
    width: 20px;
    height: 20px;
    font-size: 14px;
}

.grant-view-list .grant-card-footer {
    padding: 20px;
    background: transparent;
    border: none;
    border-left: var(--sm-border-light);
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 200px;
    justify-content: center;
}

/* ============================================
   コンパクト表示モード（サイトマッチ版）
============================================ */
.grant-view-compact .grant-card-unified {
    position: relative;
    width: 100%;
    background: var(--sm-white);
    border-radius: var(--sm-radius-md);
    box-shadow: var(--sm-shadow-sm);
    transition: var(--sm-transition-fast);
    cursor: default;
    padding: 16px;
    margin-bottom: 12px;
    border: var(--sm-border-light);
}

.grant-view-compact .grant-card-unified:hover {
    background: var(--sm-light-blue);
    box-shadow: var(--sm-shadow-md);
    border-color: var(--sm-primary-blue);
}

.grant-view-compact .grant-status-header {
    display: none;
}

.grant-view-compact .grant-card-content {
    padding: 0;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 16px;
}

.grant-view-compact .grant-title {
    font-size: 16px;
    margin: 0;
    -webkit-line-clamp: 1;
}

.grant-view-compact .grant-ai-summary,
.grant-view-compact .grant-info-grid,
.grant-view-compact .grant-success-rate {
    display: none;
}

.grant-view-compact .grant-card-footer {
    padding: 0;
    background: transparent;
    border: none;
    flex-direction: row;
    gap: 12px;
    min-width: auto;
}

.grant-view-compact .grant-btn {
    padding: 8px 16px;
    font-size: 14px;
}

/* ============================================
   共通スタイル（サイトマッチ版）
============================================ */

/* ステータスヘッダー */
.grant-status-header {
    position: relative;
    height: 60px;
    background: var(--sm-gradient-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    overflow: hidden;
}

.grant-status-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -50%;
    width: 200%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.grant-status-header.status--closed {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.grant-status-header.status--urgent {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.grant-status-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--sm-white);
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.grant-deadline-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 25px;
    color: var(--sm-white);
    font-size: 13px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* カードコンテンツ */
.grant-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 24px;
    overflow: hidden;
}

/* タイトルセクション */
.grant-title-section {
    margin-bottom: 20px;
}

.grant-category-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--sm-gradient-primary);
    color: var(--sm-white);
    border-radius: var(--sm-radius-xl);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
    box-shadow: var(--sm-shadow-sm);
}

.grant-title {
    font-size: 20px;
    font-weight: 700;
    line-height: 1.4;
    color: var(--sm-black);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 56px;
}

.grant-title a {
    color: inherit;
    text-decoration: none;
    transition: var(--sm-transition-fast);
}

.grant-title a:hover {
    color: var(--sm-primary-blue);
}

/* AI要約セクション（サイトテーマ適用） */
.grant-ai-summary {
    position: relative;
    padding: 16px;
    background: var(--sm-gradient-secondary);
    border-radius: var(--sm-radius-lg);
    margin-bottom: 20px;
    border: var(--sm-border-medium);
    min-height: 90px;
    max-height: 90px;
    overflow: hidden;
    transition: var(--sm-transition-normal);
}

.grant-ai-summary:hover {
    transform: translateY(-2px);
    box-shadow: var(--sm-shadow-md);
    border-color: var(--sm-primary-blue);
}

.grant-ai-summary-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--sm-accent-blue);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.grant-ai-summary-text {
    color: var(--sm-dark-gray);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 情報グリッド */
.grant-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.grant-info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--sm-white);
    border-radius: var(--sm-radius-md);
    transition: var(--sm-transition-normal);
    position: relative;
    overflow: hidden;
    border: var(--sm-border-light);
    box-shadow: var(--sm-shadow-sm);
}

.grant-info-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 100%;
    background: linear-gradient(90deg, transparent, var(--sm-light-blue), transparent);
    transition: width 0.3s ease;
}

.grant-info-item:hover::before {
    width: 100%;
}

.grant-info-item:hover {
    transform: translateX(4px);
    box-shadow: var(--sm-shadow-md);
    border-color: var(--sm-primary-blue);
}

.grant-info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sm-light-gray);
    border-radius: var(--sm-radius-md);
    color: var(--sm-text-gray);
    font-size: 18px;
    flex-shrink: 0;
    transition: var(--sm-transition-normal);
}

.grant-info-item:hover .grant-info-icon {
    transform: rotate(10deg) scale(1.05);
}

.grant-info-item--amount .grant-info-icon {
    background: var(--sm-gradient-primary);
    color: var(--sm-white);
}

.grant-info-item--target .grant-info-icon {
    background: linear-gradient(135deg, var(--sm-success) 0%, #20c997 100%);
    color: var(--sm-white);
}

.grant-info-item--location .grant-info-icon {
    background: linear-gradient(135deg, var(--sm-info) 0%, #138496 100%);
    color: var(--sm-white);
}

.grant-info-item--rate .grant-info-icon {
    background: linear-gradient(135deg, var(--sm-warning) 0%, #e0a800 100%);
    color: var(--sm-white);
}

.grant-info-content {
    flex: 1;
    min-width: 0;
}

.grant-info-label {
    display: block;
    font-size: 11px;
    color: var(--sm-text-gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
    font-weight: 600;
}

.grant-info-value {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--sm-black);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* アクションフッター（サイトマッチ版） */
.grant-card-footer {
    padding: 20px 24px;
    background: var(--sm-gradient-light);
    border-top: var(--sm-border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    position: relative;
    z-index: 10;
}

.grant-actions {
    display: flex;
    gap: 12px;
    flex: 1;
}

.grant-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: var(--sm-radius-md);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--sm-transition-normal);
    text-decoration: none;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    z-index: 20;
}

.grant-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.4s ease, height 0.4s ease;
    z-index: -1;
}

.grant-btn:hover::before {
    width: 200px;
    height: 200px;
}

/* 詳細ボタン（サイトテーマ適用） */
.grant-btn--primary {
    background: var(--sm-gradient-primary);
    color: var(--sm-white);
    box-shadow: var(--sm-shadow-md);
    border: 2px solid transparent;
}

.grant-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--sm-shadow-lg);
    background: var(--sm-gradient-accent);
}

.grant-btn--secondary {
    background: var(--sm-white);
    color: var(--sm-primary-blue);
    border: 2px solid var(--sm-primary-blue);
    box-shadow: var(--sm-shadow-sm);
}

.grant-btn--secondary:hover {
    background: var(--sm-light-blue);
    border-color: var(--sm-accent-blue);
    color: var(--sm-accent-blue);
    transform: translateY(-2px);
}

.favorite-btn {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sm-white);
    border: 2px solid var(--sm-border-gray);
    border-radius: 50%;
    color: var(--sm-text-gray);
    cursor: pointer;
    transition: var(--sm-transition-normal);
    flex-shrink: 0;
    position: relative;
    box-shadow: var(--sm-shadow-sm);
    z-index: 20;
}

.favorite-btn::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid var(--sm-primary-blue);
    opacity: 0;
    animation: favorite-ripple 0.6s ease-out;
}

.favorite-btn:hover {
    background: var(--sm-light-blue);
    border-color: var(--sm-primary-blue);
    color: var(--sm-primary-blue);
    transform: scale(1.1);
}

.favorite-btn.favorited {
    background: var(--sm-gradient-primary);
    border-color: var(--sm-primary-blue);
    color: var(--sm-white);
    animation: favorite-bounce 0.4s ease;
}

@keyframes favorite-bounce {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.3) rotate(-15deg); }
    75% { transform: scale(1.1) rotate(15deg); }
}

@keyframes favorite-ripple {
    from {
        opacity: 1;
        transform: scale(0.8);
    }
    to {
        opacity: 0;
        transform: scale(1.5);
    }
}

/* ホバー時の詳細表示（サイトマッチ版） */
.grant-hover-details {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 249, 250, 0.98) 100%);
    backdrop-filter: blur(20px);
    padding: 0;
    opacity: 0;
    visibility: hidden;
    transition: var(--sm-transition-slow);
    overflow: hidden;
    z-index: 5;
    border-radius: var(--sm-radius-xl);
    display: flex;
    flex-direction: column;
    pointer-events: none;
}

.grant-card-unified:hover .grant-hover-details {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

/* スクロール可能なコンテンツエリア */
.grant-hover-scrollable {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 28px;
    height: 100%;
}

/* スクロールバーのカスタマイズ（サイトテーマ） */
.grant-hover-scrollable::-webkit-scrollbar {
    width: 8px;
}

.grant-hover-scrollable::-webkit-scrollbar-track {
    background: var(--sm-light-gray);
    border-radius: 4px;
}

.grant-hover-scrollable::-webkit-scrollbar-thumb {
    background: var(--sm-primary-blue);
    border-radius: 4px;
}

.grant-hover-scrollable::-webkit-scrollbar-thumb:hover {
    background: var(--sm-accent-blue);
}

.grant-hover-details::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--sm-gradient-primary);
    animation: gradient-shift 3s ease infinite;
    z-index: 10;
}

@keyframes gradient-shift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.grant-hover-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    padding-top: 8px;
    position: sticky;
    top: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 249, 250, 0.98) 100%);
    z-index: 10;
    padding-bottom: 16px;
}

.grant-hover-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--sm-black);
    line-height: 1.3;
    flex: 1;
    padding-right: 16px;
}

.grant-hover-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sm-light-gray);
    border-radius: 50%;
    color: var(--sm-text-gray);
    cursor: pointer;
    flex-shrink: 0;
    transition: var(--sm-transition-normal);
    border: none;
    pointer-events: auto;
}

.grant-hover-close:hover {
    background: var(--sm-primary-blue);
    color: var(--sm-white);
    transform: rotate(90deg);
}

/* クイック情報バー */
.grant-quick-stats {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: var(--sm-white);
    border-radius: var(--sm-radius-lg);
    margin-bottom: 20px;
    box-shadow: var(--sm-shadow-sm);
    border: var(--sm-border-light);
}

.grant-stat-item {
    flex: 1;
    text-align: center;
    padding: 12px;
    border-right: var(--sm-border-light);
    position: relative;
}

.grant-stat-item:last-child {
    border-right: none;
}

.grant-stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: var(--sm-primary-blue);
    margin-bottom: 6px;
}

.grant-stat-label {
    display: block;
    font-size: 11px;
    color: var(--sm-text-gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.grant-detail-sections {
    display: flex;
    flex-direction: column;
    gap: 20px;
    flex: 1;
}

.grant-detail-section {
    padding: 16px;
    background: var(--sm-white);
    border-radius: var(--sm-radius-md);
    box-shadow: var(--sm-shadow-sm);
    transition: var(--sm-transition-normal);
    border: var(--sm-border-light);
}

.grant-detail-section:hover {
    box-shadow: var(--sm-shadow-md);
    transform: translateY(-2px);
    border-color: var(--sm-primary-blue);
}

.grant-detail-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--sm-primary-blue);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.grant-detail-value {
    font-size: 14px;
    color: var(--sm-dark-gray);
    line-height: 1.6;
}

/* ステータスインジケーター */
.grant-status-indicator {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--sm-success);
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
    animation: pulse 2s infinite;
    z-index: 10;
}

.grant-status-indicator.closed {
    background: var(--sm-text-gray);
    animation: none;
    box-shadow: none;
}

/* 注目バッジ */
.grant-featured-badge {
    position: absolute;
    top: 80px;
    right: -35px;
    background: var(--sm-gradient-primary);
    color: var(--sm-white);
    padding: 6px 45px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transform: rotate(45deg);
    box-shadow: var(--sm-shadow-md);
    z-index: 10;
}

/* 難易度インジケーター */
.grant-difficulty-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 12px;
    background: var(--sm-white);
    border-radius: var(--sm-radius-sm);
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: var(--sm-shadow-sm);
    z-index: 10;
    border: var(--sm-border-light);
}

/* プログレスバー（採択率） */
.grant-success-rate {
    margin-top: auto;
    padding-top: 16px;
}

.grant-success-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--sm-text-gray);
    margin-bottom: 8px;
    font-weight: 600;
}

.grant-success-bar {
    height: 6px;
    background: var(--sm-medium-gray);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}

.grant-success-fill {
    height: 100%;
    background: var(--sm-gradient-primary);
    border-radius: 3px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.grant-success-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: shimmer 2s infinite;
}

/* タグシステム */
.grant-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.grant-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--sm-light-blue);
    color: var(--sm-primary-blue);
    border-radius: var(--sm-radius-xl);
    font-size: 11px;
    font-weight: 600;
    transition: var(--sm-transition-fast);
    border: var(--sm-border-light);
}

.grant-tag:hover {
    background: var(--sm-primary-blue);
    color: var(--sm-white);
    transform: scale(1.05);
}

/* アニメーション */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.5);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grant-card-unified {
    animation: slideIn 0.6s ease-out;
    animation-fill-mode: both;
}

.grant-card-unified:nth-child(1) { animation-delay: 0.05s; }
.grant-card-unified:nth-child(2) { animation-delay: 0.1s; }
.grant-card-unified:nth-child(3) { animation-delay: 0.15s; }
.grant-card-unified:nth-child(4) { animation-delay: 0.2s; }
.grant-card-unified:nth-child(5) { animation-delay: 0.25s; }
.grant-card-unified:nth-child(6) { animation-delay: 0.3s; }

/* トースト通知 */
.grant-toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 20px 24px;
    background: var(--sm-primary-blue);
    color: var(--sm-white);
    border-radius: var(--sm-radius-lg);
    box-shadow: var(--sm-shadow-xl);
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 15px;
    font-weight: 600;
    z-index: 9999;
    opacity: 0;
    transform: translateY(100%);
    transition: var(--sm-transition-slow);
    border: var(--sm-border-medium);
}

.grant-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.grant-toast-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sm-white);
    border-radius: 50%;
    color: var(--sm-primary-blue);
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .grants-grid {
        grid-template-columns: 1fr;
        padding: 20px;
        gap: 20px;
    }
    
    .grant-view-card .grant-card-unified {
        height: auto;
        min-height: 500px;
    }
    
    .grant-info-grid {
        grid-template-columns: 1fr;
    }
    
    .grant-hover-details {
        display: none !important;
    }
    
    .grant-view-list .grant-card-unified {
        flex-direction: column;
    }
    
    .grant-view-list .grant-status-header {
        width: 100%;
        height: 48px;
        writing-mode: initial;
    }
    
    .grant-view-list .grant-card-footer {
        border-left: none;
        border-top: var(--sm-border-light);
        min-width: auto;
        flex-direction: row;
    }
    
    .grant-card-content {
        padding: 20px;
    }
    
    .grant-title {
        font-size: 18px;
    }
    
    .grant-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .favorite-btn {
        width: 42px;
        height: 42px;
    }
    
    /* モバイルでタップで詳細表示 */
    .grant-card-unified {
        cursor: pointer;
    }
}

/* ダークモード対応（サイトテーマ維持） */
@media (prefers-color-scheme: dark) {
    :root {
        --sm-white: #1a1a1a;
        --sm-light-gray: #2a2a2a;
        --sm-medium-gray: #3a3a3a;
        --sm-border-gray: #4a4a4a;
        --sm-text-gray: #9ca3af;
        --sm-dark-gray: #e5e7eb;
        --sm-black: #f9fafb;
    }
    
    .grant-card-unified {
        background: var(--sm-white);
        color: var(--sm-black);
        border-color: var(--sm-border-gray);
    }
    
    .grant-title {
        color: var(--sm-black);
    }
    
    .grant-info-item {
        background: var(--sm-light-gray);
        border-color: var(--sm-border-gray);
    }
    
    .grant-info-value {
        color: var(--sm-dark-gray);
    }
    
    .grant-card-footer {
        background: linear-gradient(135deg, var(--sm-light-gray) 0%, var(--sm-medium-gray) 100%);
        border-top-color: var(--sm-border-gray);
    }
    
    .grant-hover-details,
    .grant-hover-scrollable {
        background: linear-gradient(135deg, rgba(26, 26, 26, 0.98) 0%, rgba(42, 42, 42, 0.98) 100%);
    }
    
    .grant-hover-title {
        color: var(--sm-black);
    }
    
    .grant-detail-section {
        background: var(--sm-light-gray);
        border-color: var(--sm-border-gray);
    }
    
    .grant-detail-value {
        color: var(--sm-dark-gray);
    }
}

/* 印刷対応 */
@media print {
    .grant-card-unified {
        break-inside: avoid;
        page-break-inside: avoid;
        background: white !important;
        color: black !important;
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    .grant-hover-details,
    .favorite-btn,
    .grant-featured-badge {
        display: none !important;
    }
    
    .grant-btn {
        background: transparent !important;
        color: black !important;
        border: 1px solid #000 !important;
    }
}

/* 高コントラストモード対応 */
@media (prefers-contrast: high) {
    .grant-card-unified {
        border-width: 3px;
        border-color: var(--sm-primary-blue);
    }
    
    .grant-btn {
        border-width: 3px;
    }
    
    .grant-info-item {
        border-width: 2px;
        border-color: var(--sm-primary-blue);
    }
    
    .grant-title a:hover {
        color: var(--sm-primary-blue);
        text-decoration: underline;
    }
}

/* 減らされたモーション設定対応 */
@media (prefers-reduced-motion: reduce) {
    .grant-card-unified,
    .grant-btn,
    .favorite-btn,
    .grant-info-item {
        transition: none;
        animation: none;
    }
    
    .grant-card-unified:hover {
        transform: none;
    }
    
    .grant-success-fill::after {
        animation: none;
    }
    
    .grant-status-header::before {
        animation: none;
    }
}

/* フォーカス管理（アクセシビリティ） */
.grant-btn:focus,
.favorite-btn:focus,
.grant-hover-close:focus {
    outline: 3px solid var(--sm-primary-blue);
    outline-offset: 2px;
}

/* セレクション色 */
::selection {
    background: var(--sm-light-blue);
    color: var(--sm-primary-blue);
}

::-moz-selection {
    background: var(--sm-light-blue);
    color: var(--sm-primary-blue);
}

/* スムーススクロール */
.grant-hover-scrollable {
    scroll-behavior: smooth;
}

/* フォーカストラップ（詳細表示時） */
.grant-hover-details.show-details {
    pointer-events: auto;
}

.grant-hover-details.show-details * {
    pointer-events: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // お気に入り機能（クリック範囲を明確に）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.favorite-btn')) {
            e.preventDefault();
            e.stopPropagation();
            
            const btn = e.target.closest('.favorite-btn');
            const postId = btn.dataset.postId;
            
            // 既存のAJAX関数を呼び出し
            if (typeof gi_toggle_favorite === 'function') {
                gi_toggle_favorite(postId, btn);
            } else if (typeof window.toggleFavorite === 'function') {
                window.toggleFavorite(postId, btn);
            } else {
                // フォールバック：ローカルストレージ
                toggleLocalFavorite(postId, btn);
            }
        }
    });
    
    // カードクリック処理（詳細ボタンのみでページ遷移）
    document.addEventListener('click', function(e) {
        // 詳細ボタンがクリックされた場合のみページ遷移
        if (e.target.closest('.grant-btn--primary')) {
            const btn = e.target.closest('.grant-btn--primary');
            const href = btn.getAttribute('href');
            if (href) {
                // クリックエフェクト
                btn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    window.location.href = href;
                }, 150);
            }
        }
    });
    
    // ホバー詳細の表示・非表示制御（デスクトップのみ）
    function isDesktop() {
        return window.innerWidth > 768 && !('ontouchstart' in window);
    }
    
    // ホバーイベント（デスクトップのみ）
    document.querySelectorAll('.grant-card-unified').forEach(card => {
        let hoverTimeout;
        
        card.addEventListener('mouseenter', function() {
            if (!isDesktop()) return;
            
            clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(() => {
                const details = this.querySelector('.grant-hover-details');
                if (details) {
                    details.classList.add('show-details');
                    details.style.opacity = '1';
                    details.style.visibility = 'visible';
                }
            }, 300);
        });
        
        card.addEventListener('mouseleave', function() {
            clearTimeout(hoverTimeout);
            const details = this.querySelector('.grant-hover-details');
            if (details) {
                details.classList.remove('show-details');
                details.style.opacity = '0';
                details.style.visibility = 'hidden';
            }
        });
    });
    
    // モバイルでのタップ詳細表示
    let tapCount = 0;
    let tapTimeout;
    
    document.addEventListener('touchend', function(e) {
        if (!e.target.closest('.grant-card-unified')) return;
        if (e.target.closest('.grant-btn, .favorite-btn')) return;
        
        tapCount++;
        
        if (tapCount === 1) {
            tapTimeout = setTimeout(() => {
                tapCount = 0;
            }, 300);
        } else if (tapCount === 2) {
            clearTimeout(tapTimeout);
            tapCount = 0;
            
            // ダブルタップで詳細表示
            const card = e.target.closest('.grant-card-unified');
            const details = card.querySelector('.grant-hover-details');
            if (details) {
                if (details.style.opacity === '1') {
                    details.style.opacity = '0';
                    details.style.visibility = 'hidden';
                    details.classList.remove('show-details');
                } else {
                    details.classList.add('show-details');
                    details.style.opacity = '1';
                    details.style.visibility = 'visible';
                }
            }
        }
    });
    
    // ローカルお気に入り処理（強化版）
    function toggleLocalFavorite(postId, btn) {
        let favorites = JSON.parse(localStorage.getItem('gi_favorites') || '[]');
        const isFavorited = btn.classList.contains('favorited');
        
        if (isFavorited) {
            favorites = favorites.filter(id => id !== postId);
            btn.classList.remove('favorited');
            btn.innerHTML = '<i class="far fa-heart"></i>';
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-label', 'お気に入りに追加');
            showToast('お気に入りから削除しました', 'remove');
        } else {
            favorites.push(postId);
            btn.classList.add('favorited');
            btn.innerHTML = '<i class="fas fa-heart"></i>';
            btn.setAttribute('aria-pressed', 'true');
            btn.setAttribute('aria-label', 'お気に入りから削除');
            
            // ハートアニメーション
            animateHeart(btn);
            showToast('お気に入りに追加しました', 'add');
        }
        
        localStorage.setItem('gi_favorites', JSON.stringify(favorites));
        updateFavoriteCount(favorites.length);
    }
    
    // ハートアニメーション（サイトテーマ版）
    function animateHeart(btn) {
        btn.style.transform = 'scale(1.3) rotate(-15deg)';
        btn.style.filter = 'brightness(1.2)';
        setTimeout(() => {
            btn.style.transform = 'scale(1.1) rotate(15deg)';
        }, 150);
        setTimeout(() => {
            btn.style.transform = '';
            btn.style.filter = '';
        }, 300);
        
        // パーティクルエフェクト
        createHeartParticles(btn);
    }
    
    // ハートパーティクル生成（サイトテーマ版）
    function createHeartParticles(btn) {
        const rect = btn.getBoundingClientRect();
        const particles = 8;
        
        for (let i = 0; i < particles; i++) {
            const particle = document.createElement('div');
            particle.className = 'heart-particle';
            particle.innerHTML = '♥';
            particle.style.cssText = `
                position: fixed;
                left: ${rect.left + rect.width / 2}px;
                top: ${rect.top + rect.height / 2}px;
                color: #4a90e2;
                font-size: 18px;
                pointer-events: none;
                z-index: 9999;
                animation: particle-float 1.2s ease-out forwards;
                transform: rotate(${Math.random() * 360}deg);
            `;
            
            document.body.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 1200);
        }
    }
    
    // トースト通知（サイトテーマ版）
    function showToast(message, type) {
        const existingToast = document.querySelector('.grant-toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = 'grant-toast';
        toast.innerHTML = `
            <div class="grant-toast-icon">
                <i class="fas fa-${type === 'add' ? 'check' : 'times'}"></i>
            </div>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 3000);
    }
    
    // お気に入り数更新
    function updateFavoriteCount(count) {
        const countElement = document.querySelector('.favorite-count');
        if (countElement) {
            countElement.textContent = count;
            countElement.style.transform = 'scale(1.2)';
            countElement.style.filter = 'brightness(1.2)';
            setTimeout(() => {
                countElement.style.transform = '';
                countElement.style.filter = '';
            }, 200);
        }
    }
    
    // ホバー詳細の閉じるボタン
    document.addEventListener('click', function(e) {
        if (e.target.closest('.grant-hover-close')) {
            e.preventDefault();
            e.stopPropagation();
            const details = e.target.closest('.grant-hover-details');
            if (details) {
                details.style.opacity = '0';
                details.style.visibility = 'hidden';
                details.classList.remove('show-details');
            }
        }
    });
    
    // ESCキーで詳細を閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.grant-hover-details.show-details').forEach(details => {
                details.style.opacity = '0';
                details.style.visibility = 'hidden';
                details.classList.remove('show-details');
            });
        }
    });
    
    // 詳細表示外をクリックで閉じる
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('grant-hover-details')) {
            e.target.style.opacity = '0';
            e.target.style.visibility = 'hidden';
            e.target.classList.remove('show-details');
        }
    });
    
    // 採択率バーのアニメーション（改良版）
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bar = entry.target.querySelector('.grant-success-fill');
                if (bar && !bar.dataset.animated) {
                    const rate = parseFloat(bar.dataset.rate);
                    bar.dataset.animated = 'true';
                    
                    // アニメーション開始
                    let currentRate = 0;
                    const increment = rate / 50;
                    const timer = setInterval(() => {
                        currentRate += increment;
                        if (currentRate >= rate) {
                            currentRate = rate;
                            clearInterval(timer);
                        }
                        bar.style.width = currentRate + '%';
                    }, 20);
                }
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.grant-success-rate').forEach(el => {
        observer.observe(el);
    });
    
    // 表示モード切り替え（既存システム連携）
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-view-mode]')) {
            const mode = e.target.closest('[data-view-mode]').dataset.viewMode;
            switchViewMode(mode);
        }
    });
    
    function switchViewMode(mode) {
        const container = document.querySelector('.grants-container');
        if (container) {
            // 既存クラスを削除
            container.className = container.className.replace(/grant-view-\w+/g, '');
            // 新しいクラスを追加
            container.classList.add('grant-view-' + mode);
            
            // LocalStorageに保存
            localStorage.setItem('gi_view_mode', mode);
            
            // アニメーション再トリガー
            document.querySelectorAll('.grant-card-unified').forEach((card, index) => {
                card.style.animation = 'none';
                setTimeout(() => {
                    card.style.animation = '';
                }, index * 50);
            });
        }
    }
    
    // 初期お気に入り状態の設定
    function initFavorites() {
        const favorites = JSON.parse(localStorage.getItem('gi_favorites') || '[]');
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            const postId = btn.dataset.postId;
            if (favorites.includes(postId)) {
                btn.classList.add('favorited');
                btn.innerHTML = '<i class="fas fa-heart"></i>';
                btn.setAttribute('aria-pressed', 'true');
                btn.setAttribute('aria-label', 'お気に入りから削除');
            }
        });
        updateFavoriteCount(favorites.length);
    }
    
    // キーボードショートカット
    document.addEventListener('keydown', function(e) {
        // Ctrl + 1-4で表示モード切り替え
        if (e.ctrlKey && e.key >= '1' && e.key <= '4') {
            e.preventDefault();
            const modes = ['card', 'list', 'compact', 'grid'];
            switchViewMode(modes[parseInt(e.key) - 1]);
        }
    });
    
    // パフォーマンス最適化：遅延読み込み
    const lazyLoadObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const card = entry.target;
                if (!card.dataset.loaded) {
                    card.dataset.loaded = 'true';
                    card.style.opacity = '1';
                }
            }
        });
    }, {
        rootMargin: '50px'
    });
    
    document.querySelectorAll('.grant-card-unified').forEach(card => {
        lazyLoadObserver.observe(card);
        card.style.opacity = '0.8';
    });
    
    // ボタンのフォーカス管理（強化版）
    document.querySelectorAll('.grant-btn, .favorite-btn, .grant-hover-close').forEach(btn => {
        btn.addEventListener('focus', function() {
            this.style.outline = '3px solid var(--sm-primary-blue)';
            this.style.outlineOffset = '2px';
            this.style.filter = 'brightness(1.1)';
        });
        
        btn.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
            this.style.filter = '';
        });
        
        // キーボードでのアクティベート
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // スクロール位置の保存・復元（詳細表示時）
    const detailsScrollPositions = new Map();
    
    document.addEventListener('scroll', function(e) {
        if (e.target.classList.contains('grant-hover-scrollable')) {
            const cardId = e.target.closest('.grant-card-unified').dataset.postId;
            detailsScrollPositions.set(cardId, e.target.scrollTop);
        }
    });
    
    // 詳細表示時にスクロール位置を復元
    document.querySelectorAll('.grant-card-unified').forEach(card => {
        const postId = card.dataset.postId;
        card.addEventListener('mouseenter', function() {
            setTimeout(() => {
                const scrollable = this.querySelector('.grant-hover-scrollable');
                if (scrollable && detailsScrollPositions.has(postId)) {
                    scrollable.scrollTop = detailsScrollPositions.get(postId);
                }
            }, 350);
        });
    });
    
    // スムーズなスクロール動作の最適化
    document.querySelectorAll('.grant-hover-scrollable').forEach(scrollable => {
        let isScrolling = false;
        
        scrollable.addEventListener('scroll', function() {
            if (!isScrolling) {
                isScrolling = true;
                requestAnimationFrame(() => {
                    isScrolling = false;
                });
            }
        });
        
        // スクロール中の最適化
        scrollable.addEventListener('wheel', function(e) {
            e.stopPropagation();
        });
    });
    
    // 初期化
    initFavorites();
    
    // カードの初期表示アニメーション
    setTimeout(() => {
        document.querySelectorAll('.grant-card-unified').forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 100);
    
    // ウィンドウリサイズ対応
    window.addEventListener('resize', function() {
        // モバイル・デスクトップ切り替え時に詳細表示をリセット
        document.querySelectorAll('.grant-hover-details').forEach(details => {
            if (!isDesktop()) {
                details.style.opacity = '0';
                details.style.visibility = 'hidden';
                details.classList.remove('show-details');
            }
        });
    });
});

// パーティクルアニメーション用CSS追加（サイトテーマ版）
const style = document.createElement('style');
style.textContent = `
    @keyframes particle-float {
        0% {
            opacity: 1;
            transform: translateY(0) translateX(0) scale(1);
        }
        100% {
            opacity: 0;
            transform: translateY(-80px) translateX(${Math.random() * 80 - 40}px) scale(0.3);
        }
    }
    
    /* ロード中アニメーション */
    .grant-card-loading {
        opacity: 0.6;
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 0.3; }
    }
    
    /* ドラッグ無効化 */
    .grant-card-unified * {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
    }
    
    /* テキストのみ選択可能 */
    .grant-title a,
    .grant-ai-summary-text,
    .grant-detail-value {
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
`;
document.head.appendChild(style);
</script>
<?php endif; ?>

<!-- サイトマッチカード本体（完全版） -->
<article class="grant-card-unified <?php echo esc_attr($view_class); ?>" 
         data-post-id="<?php echo esc_attr($post_id); ?>"
         data-priority="<?php echo esc_attr($priority_order); ?>"
         role="article"
         aria-label="助成金情報カード">
    
    <!-- ステータスヘッダー -->
    <header class="grant-status-header <?php echo $application_status === 'closed' ? 'status--closed' : ''; ?> <?php echo !empty($deadline_info) && $deadline_info['class'] === 'critical' ? 'status--urgent' : ''; ?>">
        <div class="grant-status-badge">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <span><?php echo esc_html($status_display); ?></span>
        </div>
        <?php if (!empty($deadline_info)): ?>
        <div class="grant-deadline-indicator">
            <i class="fas <?php echo esc_attr($deadline_info['icon']); ?>" aria-hidden="true"></i>
            <span><?php echo esc_html($deadline_info['text']); ?></span>
        </div>
        <?php endif; ?>
    </header>
    
    <!-- ステータスインジケーター -->
    <div class="grant-status-indicator <?php echo $application_status === 'closed' ? 'closed' : ''; ?>" 
         aria-label="<?php echo $application_status === 'closed' ? '募集終了' : '募集中'; ?>"></div>
    
    <!-- 注目バッジ -->
    <?php if ($is_featured): ?>
    <div class="grant-featured-badge" aria-label="注目の助成金">FEATURED</div>
    <?php endif; ?>
    
    <!-- 難易度バッジ -->
    <?php if ($grant_difficulty && $grant_difficulty !== 'normal'): ?>
    <div class="grant-difficulty-badge" style="color: <?php echo esc_attr($difficulty_data['color']); ?>">
        <i class="fas <?php echo esc_attr($difficulty_data['icon']); ?>" aria-hidden="true"></i>
        <span><?php echo esc_html($difficulty_data['label']); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- カードコンテンツ -->
    <div class="grant-card-content">
        <div class="grant-main-info">
            <!-- タイトルセクション -->
            <div class="grant-title-section">
                <?php if ($main_category): ?>
                <div class="grant-category-tag">
                    <i class="fas fa-tag" aria-hidden="true"></i>
                    <span><?php echo esc_html($main_category); ?></span>
                </div>
                <?php endif; ?>
                <h3 class="grant-title">
                    <a href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>の詳細ページ" tabindex="-1">
                        <?php echo esc_html($title); ?>
                    </a>
                </h3>
            </div>
            
            <!-- AI要約 -->
            <?php if ($ai_summary || $excerpt): ?>
            <div class="grant-ai-summary">
                <div class="grant-ai-summary-label">
                    <i class="fas fa-robot" aria-hidden="true"></i>
                    <span>AI要約</span>
                </div>
                <p class="grant-ai-summary-text">
                    <?php echo esc_html(wp_trim_words($ai_summary ?: $excerpt, 50, '...')); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- 情報グリッド -->
            <div class="grant-info-grid">
                <!-- 助成金額 -->
                <?php if ($amount_display): ?>
                <div class="grant-info-item grant-info-item--amount">
                    <div class="grant-info-icon" aria-hidden="true">
                        <i class="fas fa-yen-sign"></i>
                    </div>
                    <div class="grant-info-content">
                        <span class="grant-info-label">助成額</span>
                        <span class="grant-info-value"><?php echo esc_html($amount_display); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 対象者 -->
                <?php if ($grant_target): ?>
                <div class="grant-info-item grant-info-item--target">
                    <div class="grant-info-icon" aria-hidden="true">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="grant-info-content">
                        <span class="grant-info-label">対象</span>
                        <span class="grant-info-value"><?php echo esc_html($grant_target); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 地域 -->
                <div class="grant-info-item grant-info-item--location">
                    <div class="grant-info-icon" aria-hidden="true">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="grant-info-content">
                        <span class="grant-info-label">地域</span>
                        <span class="grant-info-value"><?php echo esc_html($prefecture); ?></span>
                    </div>
                </div>
                
                <!-- 補助率 -->
                <?php if ($subsidy_rate): ?>
                <div class="grant-info-item grant-info-item--rate">
                    <div class="grant-info-icon" aria-hidden="true">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="grant-info-content">
                        <span class="grant-info-label">補助率</span>
                        <span class="grant-info-value"><?php echo esc_html($subsidy_rate); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- タグ -->
            <?php if ($main_industry || $application_period): ?>
            <div class="grant-tags">
                <?php if ($main_industry): ?>
                <span class="grant-tag">
                    <i class="fas fa-industry" aria-hidden="true"></i>
                    <?php echo esc_html($main_industry); ?>
                </span>
                <?php endif; ?>
                <?php if ($application_period): ?>
                <span class="grant-tag">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                    <?php echo esc_html($application_period); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 採択率プログレスバー -->
            <?php if ($grant_success_rate > 0): ?>
            <div class="grant-success-rate">
                <div class="grant-success-label">
                    <span>採択率</span>
                    <span><?php echo esc_html($grant_success_rate); ?>%</span>
                </div>
                <div class="grant-success-bar" role="progressbar" aria-valuenow="<?php echo esc_attr($grant_success_rate); ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="grant-success-fill" data-rate="<?php echo esc_attr($grant_success_rate); ?>" style="width: 0;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- アクションフッター -->
    <footer class="grant-card-footer">
        <div class="grant-actions">
            <a href="<?php echo esc_url($permalink); ?>" class="grant-btn grant-btn--primary" role="button">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>詳細を見る</span>
            </a>
            <?php if ($official_url): ?>
            <a href="<?php echo esc_url($official_url); ?>" class="grant-btn grant-btn--secondary" target="_blank" rel="noopener noreferrer" role="button">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span>公式サイト</span>
            </a>
            <?php endif; ?>
        </div>
        <button class="favorite-btn <?php echo $is_favorite ? 'favorited' : ''; ?>" 
                data-post-id="<?php echo esc_attr($post_id); ?>" 
                title="お気に入り"
                aria-label="<?php echo $is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'; ?>"
                aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>">
            <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart" aria-hidden="true"></i>
        </button>
    </footer>
    
    <!-- ホバー時の詳細表示（スクロール対応版） -->
    <div class="grant-hover-details" aria-hidden="true">
        <div class="grant-hover-scrollable">
            <div class="grant-hover-header">
                <h3 class="grant-hover-title"><?php echo esc_html($title); ?></h3>
                <button class="grant-hover-close" aria-label="詳細を閉じる">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            
            <!-- クイック統計 -->
            <div class="grant-quick-stats">
                <?php if ($amount_display): ?>
                <div class="grant-stat-item">
                    <span class="grant-stat-value"><?php echo esc_html($amount_display); ?></span>
                    <span class="grant-stat-label">最大助成額</span>
                </div>
                <?php endif; ?>
                <?php if ($subsidy_rate): ?>
                <div class="grant-stat-item">
                    <span class="grant-stat-value"><?php echo esc_html($subsidy_rate); ?></span>
                    <span class="grant-stat-label">補助率</span>
                </div>
                <?php endif; ?>
                <?php if ($grant_success_rate > 0): ?>
                <div class="grant-stat-item">
                    <span class="grant-stat-value"><?php echo esc_html($grant_success_rate); ?>%</span>
                    <span class="grant-stat-label">採択率</span>
                </div>
                <?php endif; ?>
                <?php if ($deadline_formatted): ?>
                <div class="grant-stat-item">
                    <span class="grant-stat-value"><?php echo esc_html($deadline_formatted); ?></span>
                    <span class="grant-stat-label">締切日</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grant-detail-sections">
                <?php if ($ai_summary): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-robot" aria-hidden="true"></i>
                        <span>AI要約（完全版）</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($ai_summary); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($application_period): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <span>申請期間</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($application_period); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($eligible_expenses): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-list-check" aria-hidden="true"></i>
                        <span>対象経費</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($eligible_expenses); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($required_documents): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                        <span>必要書類</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($required_documents); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($application_method): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        <span>申請方法</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($application_method); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($contact_info): ?>
                <div class="grant-detail-section">
                    <div class="grant-detail-label">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <span>お問い合わせ</span>
                    </div>
                    <div class="grant-detail-value">
                        <?php echo esc_html($contact_info); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>