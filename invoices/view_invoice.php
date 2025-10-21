<?php
include '../config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid invoice ID.");
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*, 
            s.name AS service_type_name 
        FROM invoices i
        LEFT JOIN service_type s ON i.service_type_name = s.name
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        die("Invoice not found.");
    }
} catch (PDOException $e) {
    die("Error fetching invoice: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
<link rel="stylesheet" href="../assets/css/invoice_view.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>


<a href="javascript:history.back()" class="back-btn">
  <i class="fas fa-arrow-left"></i> Back
</a>

<div class="receipt-container">
  <div class="receipt-header">
    <img src="../assets/img/bookkepz_logo.png" alt="Bookkepz Logo" class="logo">
    <div>
      <h1>Bookkepz</h1>
      <p>Smart Accounting & Billing Software</p>
    </div>
  </div>

  <div class="receipt-meta">
    <div>
      <strong>Invoice #:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?><br>
      <strong>Date:</strong> <?= htmlspecialchars(date('F d, Y', strtotime($invoice['invoice_date']))) ?><br>
      <strong>Created:</strong> <?= htmlspecialchars(date('F d, Y h:i A', strtotime($invoice['created_at']))) ?>
    </div>
    <div>
      <strong>Billed To:</strong><br>
      <?= htmlspecialchars($invoice['customer_name']) ?>
    </div>
  </div>

  <table class="receipt-table">
    <thead>
      <tr>
        <th>Service Type</th>
        <th>Description</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= htmlspecialchars($invoice['service_type_name'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($invoice['description']) ?></td>
        <td><?= htmlspecialchars($invoice['qty']) ?></td>
        <td>₱<?= number_format($invoice['rate'], 2) ?></td>
        <td>₱<?= number_format($invoice['subtotal'], 2) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="receipt-summary">
    <p><strong>Tax (12%):</strong> ₱<?= number_format($invoice['tax'], 2) ?></p>
    <h3><strong>Total:</strong> ₱<?= number_format($invoice['total'], 2) ?></h3>
  </div>

  
  <div class="attachment-section">
    <label><i class="fas fa-paperclip"></i> Attachment</label>
    <?php
      $file = $invoice['attachment'] ?? '';
      $fileUrl = "../uploads/" . $file;
      $filePath = realpath(__DIR__ . "/../uploads/" . $file);

      if (!empty($file) && file_exists($filePath)):
    ?>
      <div class="current-file-box">
        <i class="fas fa-paperclip"></i>
        <strong>Current File:</strong>
        <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="file-link">
          <?= htmlspecialchars($file) ?>
        </a>
      </div>
    <?php else: ?>
      <p class="no-file">No attachment found.</p>
    <?php endif; ?>
  </div>

  <div class="receipt-footer">
    <p>Thank you for your business!</p>
    <div class="receipt-buttons">
      <button onclick="window.print()" class="btn-print">
        <i class="fas fa-print"></i> Print Invoice
      </button>
    </div>
  </div>
</div>

<script>
  window.addEventListener("beforeprint", () => {
    document.body.style.background = "white";
  });
</script>

</body>
</html>
