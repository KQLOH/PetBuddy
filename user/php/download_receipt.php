<?php

session_start();
require '../include/db.php';
require_once '../include/product_utils.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die('Invalid order ID');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND member_id = ?");
    $stmt->execute([$order_id, $member_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Order not found');
    }

    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT s.*, ma.recipient_name, ma.recipient_phone, ma.address_line1, ma.address_line2, 
               ma.city, ma.state, ma.postcode, ma.country
        FROM shipping s
        JOIN member_addresses ma ON s.address_id = ma.address_id
        WHERE s.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $shipping = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += floatval($item['unit_price']) * intval($item['quantity']);
    }
    $shipping_fee = $shipping ? floatval($shipping['shipping_fee']) : 0.00;
    $discount = floatval($order['discount_amount']);
    $total = floatval($order['total_amount']);

    $tcpdf_path = '../TCPDF/tcpdf.php';
    
    if (file_exists($tcpdf_path)) {
        require_once($tcpdf_path);
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('PetBuddy');
        $pdf->SetAuthor('PetBuddy');
        $pdf->SetTitle('E-Receipt - Order #' . $order_id);
        $pdf->SetSubject('Order Receipt');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', '', 10);
        
        $primary_color = array(255, 183, 116);
        $dark_color = array(47, 47, 47);
        $success_color = array(76, 175, 80);
        
        $pdf->SetFillColor($primary_color[0], $primary_color[1], $primary_color[2]);
        $pdf->Rect(0, 0, 210, 40, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetXY(10, 10);
        $pdf->Cell(0, 10, 'PetBuddy', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetXY(10, 20);
        $pdf->Cell(0, 10, 'E-Receipt', 0, 1, 'L');
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(50);
        
        $pdf->SetFillColor($success_color[0], $success_color[1], $success_color[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Payment Successful!', 0, 1, 'C', true);
        $pdf->Ln(5);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(249, 249, 249);
        $pdf->Rect(10, $pdf->GetY(), 190, 25, 'F');
        $pdf->SetXY(15, $pdf->GetY() + 5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Order Number: #' . $order_id, 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(0, 5, 'Order Date: ' . date('d M Y, h:i A', strtotime($order['order_date'])), 0, 1);
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor($success_color[0], $success_color[1], $success_color[2]);
        $pdf->Cell(0, 5, 'Status: ' . ucfirst($order['status']), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        
        if ($shipping) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Shipping Address', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $shipping_text = $shipping['recipient_name'] . "\n" .
                           $shipping['recipient_phone'] . "\n" .
                           $shipping['address_line1'];
            if (!empty($shipping['address_line2'])) {
                $shipping_text .= "\n" . $shipping['address_line2'];
            }
            $shipping_text .= "\n" . $shipping['postcode'] . ' ' . $shipping['city'] .
                            "\n" . $shipping['state'] . ', ' . $shipping['country'];
            $pdf->MultiCell(0, 5, $shipping_text, 0, 'L');
            $pdf->Ln(5);
        }
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Order Items', 0, 1);
        $pdf->Ln(2);
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(120, 8, 'Product', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Price', 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 9);
        foreach ($items as $item) {
            $item_total = floatval($item['unit_price']) * intval($item['quantity']);
            $pdf->Cell(120, 7, $item['name'], 1, 0, 'L');
            $pdf->Cell(30, 7, $item['quantity'] . ' × RM ' . number_format($item['unit_price'], 2), 1, 0, 'C');
            $pdf->Cell(40, 7, 'RM ' . number_format($item_total, 2), 1, 1, 'R');
        }
        $pdf->Ln(5);
        
        $pdf->SetFillColor(249, 249, 249);
        $pdf->Rect(10, $pdf->GetY(), 190, 50, 'F');
        $pdf->SetXY(15, $pdf->GetY() + 5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Payment Summary', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX(15);
        $pdf->Cell(100, 6, 'Subtotal:', 0, 0, 'L');
        $pdf->Cell(0, 6, 'RM ' . number_format($subtotal, 2), 0, 1, 'R');
        $pdf->SetX(15);
        $pdf->Cell(100, 6, 'Shipping Fee:', 0, 0, 'L');
        $pdf->Cell(0, 6, 'RM ' . number_format($shipping_fee, 2), 0, 1, 'R');
        if ($discount > 0) {
            $pdf->SetX(15);
            $pdf->SetTextColor($success_color[0], $success_color[1], $success_color[2]);
            $pdf->Cell(100, 6, 'Discount:', 0, 0, 'L');
            $pdf->Cell(0, 6, '- RM ' . number_format($discount, 2), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0);
        }
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor($primary_color[0], $primary_color[1], $primary_color[2]);
        $pdf->Cell(100, 8, 'Total:', 0, 0, 'L');
        $pdf->Cell(0, 8, 'RM ' . number_format($total, 2), 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
        
        if ($payment) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Payment Information', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Payment Method: ' . $payment['method'], 0, 1);
            $pdf->Cell(0, 6, 'Reference Number: ' . $payment['reference_no'], 0, 1);
            $pdf->Ln(5);
        }
        
        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Thank you for shopping with PetBuddy!', 0, 1, 'C');
        $pdf->Cell(0, 5, 'If you have any questions, please contact our support team.', 0, 1, 'C');
        
        $filename = 'PetBuddy_Receipt_Order_' . $order_id . '_' . date('Ymd') . '.pdf';
        $pdf->Output($filename, 'D');
        
    } else {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>E-Receipt - Order #<?= $order_id ?></title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                @media print {
                    body { margin: 0; }
                    .no-print { 
                        display: none !important; 
                    }
                    .no-print * { 
                        display: none !important; 
                    }
                }
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 20px auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .receipt-container {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #FFB774 0%, #E89C55 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .header h1 { margin: 0; font-size: 28px; }
                .header p { margin: 5px 0 0; font-size: 16px; }
                .success-badge {
                    background: #4CAF50;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    font-weight: bold;
                }
                .info-box {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .info-box strong { color: #2F2F2F; }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table th {
                    background: #f0f0f0;
                    padding: 10px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                .summary {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                }
                .total {
                    font-size: 18px;
                    font-weight: bold;
                    color: #FFB774;
                    border-top: 2px solid #ddd;
                    padding-top: 10px;
                    margin-top: 10px;
                }
                .btn-print {
                    background: #2196F3;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 600;
                    transition: all 0.2s;
                }
                .btn-print:hover {
                    background: #1976D2;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                }
                .no-print a {
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="max-width: 800px; margin: 20px auto; padding: 0 20px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
                <button onclick="history.back()" class="btn-print" style="background: #FFB774; text-decoration: none; display: inline-block; padding: 12px 24px; border: none; cursor: pointer;">
                    <img src="../images/back.png" alt="Back" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Back
                </button>
                <a href="home.php" class="btn-print" style="background: #6c757d; text-decoration: none; display: inline-block; padding: 12px 24px;">
                    <img src="../images/shopping-bag.png" alt="Shopping" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Continue Shopping
                </a>
                <button class="btn-print" onclick="window.print()">
                    <img src="../images/pdf.png" alt="PDF" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Download / Save as PDF
                </button>
            </div>
            
            <div class="receipt-container">
                <div class="header">
                    <h1>PetBuddy</h1>
                    <p>E-Receipt</p>
                </div>
                
                <div class="success-badge">Payment Successful!</div>
                
                <div class="info-box">
                    <strong>Order Number:</strong> #<?= $order_id ?><br>
                    <strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?><br>
                    <strong>Status:</strong> <span style="color: #4CAF50;"><?= ucfirst($order['status']) ?></span>
                </div>
                
                <?php if ($shipping): ?>
                <div>
                    <h3>Shipping Address</h3>
                    <p>
                        <?= htmlspecialchars($shipping['recipient_name']) ?><br>
                        <?= htmlspecialchars($shipping['recipient_phone']) ?><br>
                        <?= htmlspecialchars($shipping['address_line1']) ?><br>
                        <?php if (!empty($shipping['address_line2'])): ?>
                            <?= htmlspecialchars($shipping['address_line2']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($shipping['postcode']) ?> <?= htmlspecialchars($shipping['city']) ?><br>
                        <?= htmlspecialchars($shipping['state']) ?>, <?= htmlspecialchars($shipping['country']) ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th style="text-align: right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $item_total = floatval($item['unit_price']) * intval($item['quantity']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['quantity'] ?> × RM <?= number_format($item['unit_price'], 2) ?></td>
                            <td style="text-align: right;">RM <?= number_format($item_total, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="summary">
                    <h3>Payment Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping Fee:</span>
                        <span>RM <?= number_format($shipping_fee, 2) ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="summary-row" style="color: #4CAF50;">
                        <span>Discount:</span>
                        <span>- RM <?= number_format($discount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>RM <?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <?php if ($payment): ?>
                <div>
                    <h3>Payment Information</h3>
                    <p>
                        <strong>Payment Method:</strong> <?= htmlspecialchars($payment['method']) ?><br>
                        <strong>Reference Number:</strong> <?= htmlspecialchars($payment['reference_no']) ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 30px; color: #666; font-size: 12px;">
                    <p>Thank you for shopping with PetBuddy!</p>
                    <p>If you have any questions, please contact our support team.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

