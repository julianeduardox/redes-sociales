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

    $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) && (int)$_GET['account_id'] > 0 ? (int)$_GET['account_id'] : null;

    // 1. Filtered Comments Totals for Current User
    $statsSql = "
        SELECT 
            COUNT(*) as total_comments,
            SUM(CASE WHEN c.status = 'replied' THEN 1 ELSE 0 END) as replied_count,
            SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN c.is_highlighted = 1 OR c.highlight_score >= 80 THEN 1 ELSE 0 END) as highlighted_count,
            SUM(CASE WHEN c.sentiment = 'lead' OR c.intent LIKE 'lead_%' THEN 1 ELSE 0 END) as leads_count,
            SUM(CASE WHEN c.sentiment = 'urgent' OR c.intent = 'support' THEN 1 ELSE 0 END) as urgent_count,
            ROUND(AVG(c.highlight_score), 1) as avg_engagement_score,
            SUM(c.likes_count) as total_likes_on_comments
        FROM comments c
    ";
    if ($accountId !== null) {
        $statsSql .= " JOIN posts p ON c.post_id = p.id WHERE c.user_id = :uid AND p.account_id = :acc_id";
        $statsParams = [':uid' => $userId, ':acc_id' => $accountId];
    } else {
        $statsSql .= " WHERE c.user_id = :uid";
        $statsParams = [':uid' => $userId];
    }
    if ($platform !== 'all') {
        $statsSql .= " AND c.platform = :platform";
        $statsParams[':platform'] = $platform;
    }

    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch() ?: [];

    $stats['total_comments'] = (int)($stats['total_comments'] ?? 0);
    $stats['replied_count'] = (int)($stats['replied_count'] ?? 0);
    $stats['pending_count'] = (int)($stats['pending_count'] ?? 0);
    $stats['highlighted_count'] = (int)($stats['highlighted_count'] ?? 0);
    $stats['leads_count'] = (int)($stats['leads_count'] ?? 0);
    $stats['urgent_count'] = (int)($stats['urgent_count'] ?? 0);
    $stats['avg_engagement_score'] = (float)($stats['avg_engagement_score'] ?? 0.0);
    $stats['total_likes_on_comments'] = (int)($stats['total_likes_on_comments'] ?? 0);
    $stats['reply_rate_percent'] = ($stats['total_comments'] > 0) ? round(($stats['replied_count'] / $stats['total_comments']) * 100, 1) : 0.0;

    // Filtered Post Aggregates for Current User (Reach, Impressions, Saved, Engagement Rate)
    $postTotalsSql = "
        SELECT 
            COUNT(*) as total_posts,
            SUM(total_likes) as total_post_likes,
            SUM(total_comments) as total_post_comments,
            SUM(total_shares) as total_post_shares,
            SUM(reach) as total_reach,
            SUM(impressions) as total_impressions,
            SUM(saved_count) as total_saved,
            ROUND(AVG(engagement_rate), 1) as avg_engagement_rate
        FROM posts p
        WHERE p.user_id = :uid
    ";
    $postTotalsParams = [':uid' => $userId];
    if ($accountId !== null) {
        $postTotalsSql .= " AND p.account_id = :acc_id";
        $postTotalsParams[':acc_id'] = $accountId;
    }
    if ($platform !== 'all') {
        $postTotalsSql .= " AND p.platform = :platform";
        $postTotalsParams[':platform'] = $platform;
    }

    $postTotalsStmt = $pdo->prepare($postTotalsSql);
    $postTotalsStmt->execute($postTotalsParams);
    $postTotals = $postTotalsStmt->fetch() ?: [];

    $stats['total_posts'] = (int)($postTotals['total_posts'] ?? 0);
    $stats['total_post_likes'] = (int)($postTotals['total_post_likes'] ?? 0);
    $stats['total_reach'] = (int)($postTotals['total_reach'] ?? 0);
    $stats['total_impressions'] = (int)($postTotals['total_impressions'] ?? 0);
    $stats['total_saved'] = (int)($postTotals['total_saved'] ?? 0);
    $stats['avg_engagement_rate'] = (float)($postTotals['avg_engagement_rate'] ?? 0.0);

    // 2. Filtered Sentiment Breakdown for Current User
    $sentimentSql = "
        SELECT c.sentiment, COUNT(*) as count 
        FROM comments c
    ";
    if ($accountId !== null) {
        $sentimentSql .= " JOIN posts p ON c.post_id = p.id WHERE c.user_id = :uid AND p.account_id = :acc_id";
        $sentimentParams = [':uid' => $userId, ':acc_id' => $accountId];
    } else {
        $sentimentSql .= " WHERE c.user_id = :uid";
        $sentimentParams = [':uid' => $userId];
    }
    if ($platform !== 'all') {
        $sentimentSql .= " AND c.platform = :platform";
        $sentimentParams[':platform'] = $platform;
    }
    $sentimentSql .= " GROUP BY c.sentiment";

    $sentimentStmt = $pdo->prepare($sentimentSql);
    $sentimentStmt->execute($sentimentParams);
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
            COALESCE(a.account_name, 'Mi Cuenta') as account_name,
            COALESCE(a.account_handle, '') as account_handle,
            COALESCE(bv.brand_name, 'Voz de Marca') as brand_voice_name,
            COUNT(c.id) as local_comments_count,
            SUM(CASE WHEN c.status = 'replied' THEN 1 ELSE 0 END) as post_replied_count,
            SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as post_pending_count,
            SUM(CASE WHEN c.sentiment = 'lead' OR c.intent LIKE 'lead_%' THEN 1 ELSE 0 END) as post_leads_count,
            SUM(CASE WHEN c.sentiment = 'urgent' OR c.intent = 'support' THEN 1 ELSE 0 END) as post_urgent_count,
            SUM(CASE WHEN c.sentiment = 'positive' THEN 1 ELSE 0 END) as post_positive_count,
            SUM(CASE WHEN c.sentiment = 'question' THEN 1 ELSE 0 END) as post_questions_count,
            ROUND(AVG(c.highlight_score), 1) as avg_post_score
        FROM posts p
        LEFT JOIN accounts a ON p.account_id = a.id
        LEFT JOIN brand_voices bv ON COALESCE(p.brand_voice_id, a.brand_voice_id) = bv.id
        LEFT JOIN comments c ON c.post_id = p.id AND c.user_id = :uid
        WHERE p.user_id = :uid
    ";
    $postParams = [':uid' => $userId];

    $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) && (int)$_GET['account_id'] > 0 ? (int)$_GET['account_id'] : null;
    if ($accountId !== null) {
        $postsSql .= " AND p.account_id = :acc_id";
        $postParams[':acc_id'] = $accountId;
    }

    if ($platform !== 'all') {
        $postsSql .= " AND p.platform = :platform";
        $postParams[':platform'] = $platform;
    }

    $postsSql .= " GROUP BY p.id ORDER BY $orderClause";

    $stmtPosts = $pdo->prepare($postsSql);
    $stmtPosts->execute($postParams);
    $allPosts = $stmtPosts->fetchAll();

    $totalImpressionsAgg = 0;
    $totalReachAgg = 0;

    foreach ($allPosts as &$p) {
        $likes = (int)($p['total_likes'] ?? 0);
        $comments = (int)($p['total_comments'] ?? 0);
        $shares = (int)($p['total_shares'] ?? 0);
        $saved = (int)($p['saved_count'] ?? 0);
        $reach = (int)($p['reach'] ?? 0);
        $impressions = (int)($p['impressions'] ?? 0);

        if ($impressions === 0 && $reach > 0) {
            $impressions = $reach;
            $p['impressions'] = $impressions;
        }
        if ($reach === 0 && $impressions > 0) {
            $reach = $impressions;
            $p['reach'] = $reach;
        }
        $interactions = $likes + $comments + $shares + $saved;
        if ($interactions > 0 && $reach < $interactions) {
            $reach = max($reach, $interactions);
            $p['reach'] = $reach;
        }
        if ($reach > 0 && $impressions < $reach) {
            $impressions = $reach;
            $p['impressions'] = $impressions;
        }
        if ($reach > 0) {
            $p['engagement_rate'] = min(100.0, round(($interactions / $reach) * 100, 1));
        }

        $totalImpressionsAgg += $impressions;
        $totalReachAgg += $reach;
    }
    unset($p);

    if ($stats['total_impressions'] < $totalImpressionsAgg) {
        $stats['total_impressions'] = $totalImpressionsAgg;
    }
    if ($stats['total_reach'] < $totalReachAgg) {
        $stats['total_reach'] = $totalReachAgg;
    }

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
