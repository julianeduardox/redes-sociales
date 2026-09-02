<?php
/**
 * REST API: Analytics & Per-Post Performance Controller
 * Hardened with Multi-Tenant User Isolation, Security Headers & Meta Insights
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$pdo = Database::getConnection();

try {
    Security::requireRateLimit('analytics_api_' . $userId, 120, 60);

    $platform = Security::validateEnum($_GET['platform'] ?? 'all', ['all', 'instagram', 'facebook'], 'all');
    $sortBy = Security::validateEnum($_GET['sort'] ?? 'recent', ['recent', 'reach', 'engagement', 'comments', 'likes'], 'recent');
    $drillPostId = isset($_GET['post_id']) && is_numeric($_GET['post_id']) ? (int)$_GET['post_id'] : null;

    // 1. Overall Global Totals for Current User
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_comments,
            SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN is_highlighted = 1 OR highlight_score >= 80 THEN 1 ELSE 0 END) as highlighted_count,
            SUM(CASE WHEN sentiment = 'lead' OR intent LIKE 'lead_%' THEN 1 ELSE 0 END) as leads_count,
            SUM(CASE WHEN sentiment = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
            ROUND(AVG(highlight_score), 1) as avg_engagement_score,
            SUM(likes_count) as total_likes_on_comments
        FROM comments
        WHERE user_id = :uid
    ");
    $statsStmt->execute([':uid' => $userId]);
    $stats = $statsStmt->fetch();

    $repliedPercent = ($stats['total_comments'] > 0) ? round(($stats['replied_count'] / $stats['total_comments']) * 100, 1) : 0;
    $stats['reply_rate_percent'] = $repliedPercent;

    // Global Post Aggregates for Current User (Reach, Impressions, Saved)
    $postTotalsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_posts,
            SUM(total_likes) as total_post_likes,
            SUM(total_comments) as total_post_comments,
            SUM(total_shares) as total_post_shares,
            SUM(reach) as total_reach,
            SUM(impressions) as total_impressions,
            SUM(saved_count) as total_saved,
            ROUND(AVG(engagement_rate), 1) as avg_engagement_rate
        FROM posts
        WHERE user_id = :uid
    ");
    $postTotalsStmt->execute([':uid' => $userId]);
    $postTotals = $postTotalsStmt->fetch();

    $stats['total_posts'] = (int)($postTotals['total_posts'] ?? 0);
    $stats['total_post_likes'] = (int)($postTotals['total_post_likes'] ?? 0);
    $stats['total_reach'] = (int)($postTotals['total_reach'] ?? 0);
    $stats['total_impressions'] = (int)($postTotals['total_impressions'] ?? 0);
    $stats['total_saved'] = (int)($postTotals['total_saved'] ?? 0);
    $stats['avg_engagement_rate'] = (float)($postTotals['avg_engagement_rate'] ?? 0.0);

    // 2. Global Sentiment Breakdown for Current User
    $sentimentStmt = $pdo->prepare("
        SELECT sentiment, COUNT(*) as count 
        FROM comments 
        WHERE user_id = :uid
        GROUP BY sentiment
    ");
    $sentimentStmt->execute([':uid' => $userId]);
    $sentimentRows = $sentimentStmt->fetchAll();

    $sentiments = [
        'lead' => 0,
        'urgent' => 0,
        'positive' => 0,
        'question' => 0,
        'neutral' => 0
    ];
    foreach ($sentimentRows as $row) {
        $key = $row['sentiment'];
        if (isset($sentiments[$key])) {
            $sentiments[$key] = (int)$row['count'];
        }
    }

    // 3. Platform Breakdown for Current User
    $platformStmt = $pdo->prepare("
        SELECT platform, COUNT(*) as count, SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied
        FROM comments 
        WHERE user_id = :uid
        GROUP BY platform
    ");
    $platformStmt->execute([':uid' => $userId]);
    $platformRows = $platformStmt->fetchAll();

    // 4. Replies by Variant / Tone for Current User
    $variantStmt = $pdo->prepare("
        SELECT variant_type, COUNT(*) as count 
        FROM replies 
        WHERE user_id = :uid
        GROUP BY variant_type
    ");
    $variantStmt->execute([':uid' => $userId]);
    $variantRows = $variantStmt->fetchAll();

    // 5. Post-by-Post Comprehensive Analytics for Current User
    $orderClause = "p.posted_at DESC";
    if ($sortBy === 'reach') $orderClause = "p.reach DESC";
    elseif ($sortBy === 'engagement') $orderClause = "p.engagement_rate DESC";
    elseif ($sortBy === 'comments') $orderClause = "p.total_comments DESC";
    elseif ($sortBy === 'likes') $orderClause = "p.total_likes DESC";

    $postsSql = "
        SELECT 
            p.*,
            COUNT(c.id) as local_comments_count,
            SUM(CASE WHEN c.status = 'replied' THEN 1 ELSE 0 END) as post_replied_count,
            SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as post_pending_count,
            SUM(CASE WHEN c.sentiment = 'lead' OR c.intent LIKE 'lead_%' THEN 1 ELSE 0 END) as post_leads_count,
            SUM(CASE WHEN c.sentiment = 'urgent' OR c.intent = 'support' THEN 1 ELSE 0 END) as post_urgent_count,
            SUM(CASE WHEN c.sentiment = 'positive' THEN 1 ELSE 0 END) as post_positive_count,
            SUM(CASE WHEN c.sentiment = 'question' THEN 1 ELSE 0 END) as post_questions_count,
            ROUND(AVG(c.highlight_score), 1) as avg_post_score
        FROM posts p
        LEFT JOIN comments c ON c.post_id = p.id AND c.user_id = :uid
        WHERE p.user_id = :uid
    ";
    $postParams = [':uid' => $userId];

    if ($platform !== 'all') {
        $postsSql .= " AND p.platform = :platform";
        $postParams[':platform'] = $platform;
    }

    $postsSql .= " GROUP BY p.id ORDER BY $orderClause";

    $stmtPosts = $pdo->prepare($postsSql);
    $stmtPosts->execute($postParams);
    $allPosts = $stmtPosts->fetchAll();

    // 6. Drill-down for a single post if requested
    $drillPostData = null;
    if ($drillPostId !== null) {
        $stmtDrill = $pdo->prepare("SELECT * FROM posts WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmtDrill->execute([':id' => $drillPostId, ':uid' => $userId]);
        $drillPost = $stmtDrill->fetch();

        if ($drillPost) {
            $stmtCmt = $pdo->prepare("
                SELECT c.*, r.reply_text, r.created_at as reply_created_at
                FROM comments c
                LEFT JOIN replies r ON r.comment_id = c.id
                WHERE c.post_id = :id AND c.user_id = :uid
                ORDER BY c.highlight_score DESC, c.id DESC
            ");
            $stmtCmt->execute([':id' => $drillPostId, ':uid' => $userId]);
            $drillPost['comments'] = $stmtCmt->fetchAll();
            $drillPostData = $drillPost;
        }
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'sentiment_distribution' => $sentiments,
        'platform_distribution' => $platformRows,
        'variant_distribution' => $variantRows,
        'posts' => $allPosts,
        'drill_post' => $drillPostData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    Security::sendJsonError('Error al generar métricas de analítica y publicaciones.', $e);
}
