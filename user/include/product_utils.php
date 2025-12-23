<?php
/**
 * Product Utility Functions
 */

function formatPrice(?float $price): string {
    if ($price === null || $price < 0) {
        return 'MYR 0.00';
    }
    return 'MYR ' . number_format((float)$price, 2);
}

function truncateText(?string $text, int $limit = 90): string {
    $text = trim(strip_tags((string)$text));
    if ($text === '') return 'No description available';
    if (strlen($text) <= $limit) return $text;
    return substr($text, 0, $limit) . '...';
}

/**
 * ✨✨✨ 修复版：移除了内部的 htmlspecialchars ✨✨✨
 * 只负责逻辑判断和路径拼接，不修改字符
 */
function productImageUrl(?string $imagePath): string {
    $imagePath = trim((string)$imagePath);
    
    // 1. 空值检查
    if ($imagePath === '') {
        return '../images/dog1.jpg'; 
    }
    
    // 2. HTTP 链接检查
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath; // 移除 htmlspecialchars
    }
    
    // 3. 旧数据兼容 (../ 开头)
    if (strpos($imagePath, '../') === 0) {
        return $imagePath; // 移除 htmlspecialchars
    }

    // 4. 旧数据兼容 (images/ 开头)
    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'image/') === 0) {
        return '../' . $imagePath; // 移除 htmlspecialchars
    }
    
    // 5. 新数据 (uploads)
    return '../../uploads/products/' . $imagePath; // 移除 htmlspecialchars
}

function formatReviewDate(?string $dateString): string {
    $dateString = trim((string)$dateString);
    if ($dateString === '') return 'Recent';
    $timestamp = strtotime($dateString);
    return ($timestamp !== false && $timestamp > 0) ? date('M Y', $timestamp) : 'Recent';
}
?>