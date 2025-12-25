<?php


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


function productImageUrl(?string $imagePath): string {
    $imagePath = trim((string)$imagePath);
    
  
    if ($imagePath === '') {
        return '../images/dog1.jpg'; 
    }
    
    
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath; 
    }
    
    
    if (strpos($imagePath, '../') === 0) {
        return $imagePath; 
    }

    
    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'image/') === 0) {
        return '../' . $imagePath; 
    }
    
    
    return '../../uploads/products/' . $imagePath; 
}

function formatReviewDate(?string $dateString): string {
    $dateString = trim((string)$dateString);
    if ($dateString === '') return 'Recent';
    $timestamp = strtotime($dateString);
    return ($timestamp !== false && $timestamp > 0) ? date('M Y', $timestamp) : 'Recent';
}
?>