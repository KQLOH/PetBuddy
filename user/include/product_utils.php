<?php
/**
 * Product Utility Functions
 * Shared utility functions for product-related pages
 */

/**
 * Format price with MYR currency
 * @param float|null $price The price to format
 * @return string Formatted price string
 */
function formatPrice(?float $price): string {
    if ($price === null || $price < 0) {
        return 'MYR 0.00';
    }
    return 'MYR ' . number_format((float)$price, 2);
}

/**
 * Truncate text to specified length
 * @param string|null $text The text to truncate
 * @param int $limit Maximum character length
 * @return string Truncated text with ellipsis if needed
 */
function truncateText(?string $text, int $limit = 90): string {
    $text = trim(strip_tags((string)$text));
    if ($text === '') {
        return 'No description available';
    }
    
    if (function_exists('mb_strlen')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }
    
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

/**
 * Generate image URL from database path
 * Handles various path formats and converts to relative URL
 * @param string|null $imagePath The image path from database
 * @return string Relative URL to the image
 */
function productImageUrl(?string $imagePath): string {
    $imagePath = trim((string)$imagePath);
    
    // Default placeholder if empty
    if ($imagePath === '') {
        return '../image/dog.jpg';
    }
    
    // If already a full HTTP/HTTPS URL, return directly
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    }
    
    // If already has relative path prefix, return as is
    if (strpos($imagePath, '../image/') === 0 || strpos($imagePath, '../') === 0) {
        return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    }
    
    // If starts with image/, add ../ prefix and encode path
    if (strpos($imagePath, 'image/') === 0) {
        $imagePath = '../' . $imagePath;
        // URL encode path parts to handle spaces
        $parts = explode('/', $imagePath);
        $encodedParts = array_map(function($part) {
            return rawurlencode($part);
        }, $parts);
        $imagePath = implode('/', $encodedParts);
        return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    }
    
    // If contains path separators (dog/ or cat/ or dry food/)
    if (strpos($imagePath, '/') !== false || strpos($imagePath, '\\') !== false) {
        if (strpos($imagePath, 'dog/') === 0 || strpos($imagePath, 'cat/') === 0) {
            $imagePath = '../image/' . $imagePath;
        } elseif (strpos($imagePath, 'dry food/') !== false || strpos($imagePath, 'dryfood/') !== false) {
            $imagePath = '../image/' . $imagePath;
        } else {
            $imagePath = '../image/' . $imagePath;
        }
        // URL encode path parts to handle spaces and special characters
        $parts = explode('/', $imagePath);
        $encodedParts = array_map(function($part) {
            return rawurlencode($part);
        }, $parts);
        $imagePath = implode('/', $encodedParts);
        return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    }
    
    // Just filename - default to dog/dry food directory
    // URL encode the path parts to handle spaces
    $imagePath = '../image/dog/' . rawurlencode('dry food') . '/' . rawurlencode($imagePath);
    return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
}

/**
 * Format review date to readable format
 * @param string|null $dateString The date string from database
 * @return string Formatted date or 'Recent' if invalid
 */
function formatReviewDate(?string $dateString): string {
    $dateString = trim((string)$dateString);
    if ($dateString === '') {
        return 'Recent';
    }
    
    $timestamp = strtotime($dateString);
    if ($timestamp !== false && $timestamp > 0) {
        return date('M Y', $timestamp);
    }
    
    return 'Recent';
}
