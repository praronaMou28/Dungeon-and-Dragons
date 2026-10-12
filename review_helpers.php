<?php

function renderStars(float $rating, int $count = -1): string
{
    $rounded = (int) round($rating);
    $html = '<span class="star-display">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rounded ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star empty"></i>';
    }
    $html .= '</span>';
    if ($count >= 0) {
        $html .= ' <span class="text-muted" style="font-size:0.76rem;">(' . $count . ')</span>';
    }
    return $html;
}

function getRatingsFor(PDO $pdo, string $orderType, array $targetIds): array
{
    if (empty($targetIds)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
    $stmt = $pdo->prepare("SELECT target_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                            FROM reviews WHERE order_type = ? AND target_id IN ($placeholders)
                            GROUP BY target_id");
    $stmt->execute(array_merge([$orderType], $targetIds));

    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $results[$row['target_id']] = ['avg' => (float) $row['avg_rating'], 'count' => (int) $row['review_count']];
    }
    return $results;
}
