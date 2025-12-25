<?php


session_start();
require '../include/db.php';
require_once '../include/product_utils.php';

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');


if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$member_id = $_SESSION['member_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND member_id = ?");
    $stmt->execute([$order_id, $member_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
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

    
    $order_date = date('d M Y, h:i A', strtotime($order['order_date']));
    $shipping_fee = $shipping ? floatval($shipping['shipping_fee']) : 0.00;
    $discount = floatval($order['discount_amount']);
    $total = floatval($order['total_amount']);

    $items_html = '';
    foreach ($items as $item) {
        $item_total = floatval($item['unit_price']) * intval($item['quantity']);
        $items_html .= '
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px; vertical-align: top;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="font-weight: 600; color: #2F2F2F;">' . htmlspecialchars($item['name']) . '</div>
                    </div>
                    <div style="color: #666; font-size: 13px; margin-top: 4px;">Quantity: ' . $item['quantity'] . ' × RM ' . number_format($item['unit_price'], 2) . '</div>
                </td>
                <td style="padding: 12px; text-align: right; vertical-align: top; font-weight: 600; color: #2F2F2F;">
                    RM ' . number_format($item_total, 2) . '
                </td>
            </tr>';
    }

    $shipping_address = '';
    if ($shipping) {
        $shipping_address = htmlspecialchars($shipping['recipient_name']) . '<br>' .
                          htmlspecialchars($shipping['recipient_phone']) . '<br>' .
                          htmlspecialchars($shipping['address_line1']);
        if (!empty($shipping['address_line2'])) {
            $shipping_address .= '<br>' . htmlspecialchars($shipping['address_line2']);
        }
        $shipping_address .= '<br>' . htmlspecialchars($shipping['postcode']) . ' ' . htmlspecialchars($shipping['city']) .
                          '<br>' . htmlspecialchars($shipping['state']) . ', ' . htmlspecialchars($shipping['country']);
    }

    $payment_method = $payment ? htmlspecialchars($payment['method']) : 'N/A';
    $payment_ref = $payment ? htmlspecialchars($payment['reference_no']) : 'N/A';

    $email_html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #FFB774 0%, #E89C55 100%); padding: 30px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">PetBuddy</h1>
                                <p style="margin: 10px 0 0; color: #ffffff; font-size: 16px;">E-Receipt</p>
                            </td>
                        </tr>
                        
                        <!-- Order Confirmation -->
                        <tr>
                            <td style="padding: 30px;">
                                <div style="text-align: center; margin-bottom: 30px;">
                                    <div style="width: 60px; height: 60px; background-color: #4CAF50; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                        <span style="color: #ffffff; font-size: 30px;">TQ<i></span>
                                    </div>
                                    <h2 style="margin: 0 0 10px; color: #2F2F2F; font-size: 24px;">Payment Successful!</h2>
                                    <p style="margin: 0; color: #666; font-size: 15px;">Thank you for your purchase</p>
                                </div>
                                
                                <!-- Order Info -->
                                <div style="background-color: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding-bottom: 8px;">
                                                <span style="color: #666; font-size: 14px;">Order Number:</span>
                                                <strong style="color: #2F2F2F; font-size: 16px; margin-left: 8px;">#' . $order_id . '</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 8px;">
                                                <span style="color: #666; font-size: 14px;">Order Date:</span>
                                                <span style="color: #2F2F2F; font-size: 14px; margin-left: 8px;">' . $order_date . '</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span style="color: #666; font-size: 14px;">Status:</span>
                                                <span style="color: #4CAF50; font-size: 14px; font-weight: 600; margin-left: 8px; text-transform: capitalize;">' . ucfirst($order['status']) . '</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Shipping Address -->
                                <div style="margin-bottom: 25px;">
                                    <h3 style="margin: 0 0 12px; color: #2F2F2F; font-size: 16px; font-weight: 600;">Shipping Address</h3>
                                    <div style="color: #555; font-size: 14px; line-height: 1.6;">
                                        ' . $shipping_address . '
                                    </div>
                                </div>
                                
                                <!-- Order Items -->
                                <div style="margin-bottom: 25px;">
                                    <h3 style="margin: 0 0 15px; color: #2F2F2F; font-size: 16px; font-weight: 600;">Order Items</h3>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                                        ' . $items_html . '
                                    </table>
                                </div>
                                
                                <!-- Payment Summary -->
                                <div style="background-color: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <h3 style="margin: 0 0 15px; color: #2F2F2F; font-size: 16px; font-weight: 600;">Payment Summary</h3>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding: 8px 0; color: #666; font-size: 14px;">Subtotal:</td>
                                            <td style="padding: 8px 0; text-align: right; color: #2F2F2F; font-size: 14px;">RM ' . number_format($subtotal, 2) . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 0; color: #666; font-size: 14px;">Shipping Fee:</td>
                                            <td style="padding: 8px 0; text-align: right; color: #2F2F2F; font-size: 14px;">RM ' . number_format($shipping_fee, 2) . '</td>
                                        </tr>';
    
    if ($discount > 0) {
        $email_html .= '
                                        <tr>
                                            <td style="padding: 8px 0; color: #666; font-size: 14px;">Discount:</td>
                                            <td style="padding: 8px 0; text-align: right; color: #4CAF50; font-size: 14px;">- RM ' . number_format($discount, 2) . '</td>
                                        </tr>';
    }
    
    $email_html .= '
                                        <tr style="border-top: 2px solid #ddd;">
                                            <td style="padding: 12px 0 0; color: #2F2F2F; font-size: 16px; font-weight: 700;">Total:</td>
                                            <td style="padding: 12px 0 0; text-align: right; color: #FFB774; font-size: 18px; font-weight: 700;">RM ' . number_format($total, 2) . '</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Payment Info -->
                                <div style="margin-bottom: 25px;">
                                    <h3 style="margin: 0 0 12px; color: #2F2F2F; font-size: 16px; font-weight: 600;">Payment Information</h3>
                                    <div style="color: #555; font-size: 14px; line-height: 1.6;">
                                        <div style="margin-bottom: 6px;"><strong>Payment Method:</strong> ' . $payment_method . '</div>
                                        <div><strong>Reference Number:</strong> ' . $payment_ref . '</div>
                                    </div>
                                </div>
                                
                                <!-- Footer -->
                                <div style="text-align: center; padding-top: 25px; border-top: 1px solid #eee;">
                                    <p style="margin: 0 0 10px; color: #666; font-size: 13px;">Thank you for shopping with PetBuddy!</p>
                                    <p style="margin: 0; color: #999; font-size: 12px;">If you have any questions, please contact our support team.</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'BMIT2013IsaacLing@gmail.com';
        $mail->Password   = 'ndsf gvpz niaw czrk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('BMIT2013IsaacLing@gmail.com', 'PetBuddy');
        $mail->addAddress($member['email'], $member['full_name']);

        $mail->isHTML(true);
        $mail->Subject = 'Your E-Receipt - Order #' . $order_id . ' - PetBuddy';
        $mail->Body    = $email_html;
        $mail->AltBody = 'Thank you for your purchase! Order #' . $order_id . '. Total: RM ' . number_format($total, 2);

        $mail->send();
        
        echo json_encode([
            'success' => true, 
            'message' => 'E-Receipt has been sent to ' . $member['email']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email: ' . $mail->ErrorInfo
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

