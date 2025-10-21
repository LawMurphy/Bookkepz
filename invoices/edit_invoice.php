<?php
include '../config.php';
include '../topbar/header.php';


$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid invoice ID.");
}


try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) {
        die("Invoice not found.");
    }
} catch (PDOException $e) {
    die("Error fetching invoice: " . $e->getMessage());
}


$stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer'] ?? '';
    $invoice_date = $_POST['invoice_date'] ?? '';
    $service_type_name = $_POST['service_type_name'] ?? $invoice['service_type_name'];
    $description = $_POST['description'] ?? '';
    $qty = (float)($_POST['qty'] ?? 1);
    $rate = (float)($_POST['rate'] ?? 0);

    $subtotal = $qty * $rate;
    $tax = $subtotal * 0.12;
    $total = $subtotal + $tax;

    $remove_attachment = (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] === '1');
    $new_attachment = $invoice['attachment'] ?? null;

    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($remove_attachment) {
        $oldFilePath = realpath($uploadDir . $invoice['attachment']);
        if ($oldFilePath && file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
        $new_attachment = null;
    }

    if (!empty($_FILES['new_attachment']['name'])) {
        $rawName = basename($_FILES['new_attachment']['name']);
        $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $rawName);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['new_attachment']['tmp_name'], $targetFilePath)) {
            $new_attachment = $fileName;
            if (!empty($invoice['attachment']) && file_exists($uploadDir . $invoice['attachment'])) {
                @unlink($uploadDir . $invoice['attachment']);
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE invoices 
            SET customer_name=?, invoice_date=?, service_type_name=?, description=?, qty=?, rate=?, subtotal=?, tax=?, total=?, attachment=? 
            WHERE id=?");
        $stmt->execute([
            $customer_name,
            $invoice_date,
            $service_type_name,
            $description,
            $qty,
            $rate,
            $subtotal,
            $tax,
            $total,
            $new_attachment,
            $id
        ]);

        
        header("Location: edit_invoice.php?id=$id&updated=1");
        exit;
    } catch (PDOException $e) {
        die('Error updating invoice: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
  <link rel="stylesheet" href="../assets/css/edit_invoice.css">
  <link rel="stylesheet" href="../assets/css/notification.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="edit-container">
  <form class="edit-form" method="POST" enctype="multipart/form-data">
    <h2><i class="fas fa-pen"></i> Edit Invoice</h2>

    <div class="form-group">
      <label>Invoice #</label>
      <input type="text" value="<?= htmlspecialchars($invoice['invoice_number']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Date</label>
      <input type="date" name="invoice_date" value="<?= htmlspecialchars($invoice['invoice_date']) ?>" required>
    </div>

    <div class="form-group">
      <label>Customer</label>
      <select name="customer" required>
        <option value="">Select Customer</option>
        <?php foreach ($customers as $cust): ?>
          <option value="<?= htmlspecialchars($cust['name']) ?>" <?= ($cust['name'] == $invoice['customer_name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cust['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Service Type</label>
      <select name="service_type_name" required>
        <option value="">Select Service Type</option>
        <?php
        $stmt = $pdo->query("SELECT name FROM service_type ORDER BY name ASC");
        $service_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $currentService = $invoice['service_type_name'] ?? '';

        foreach ($service_types as $type):
            $selected = ($type['name'] === $currentService) ? 'selected' : '';
        ?>
            <option value="<?= htmlspecialchars($type['name']) ?>" <?= $selected ?>>
              <?= htmlspecialchars($type['name']) ?>
            </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="3"><?= htmlspecialchars($invoice['description']) ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group half">
        <label>Quantity</label>
        <input type="number" name="qty" id="qty" value="<?= htmlspecialchars($invoice['qty']) ?>" min="1" required>
      </div>

      <div class="form-group half">
        <label>Rate</label>
        <input type="number" name="rate" id="rate" step="0.01" value="<?= htmlspecialchars($invoice['rate']) ?>" required>
      </div>
    </div>

    <!-- Attachment -->
    <div class="form-group attachment-box">
      <label class="section-title">Attachment</label>

      <?php if (!empty($invoice['attachment'])): ?>
        <div class="attachment-preview" id="currentAttachment">
          <p class="file-name">
            <i class="fas fa-paperclip"></i>
            <strong>Current File:</strong>
            <a href="../uploads/<?= htmlspecialchars($invoice['attachment']) ?>" target="_blank">
              <?= htmlspecialchars($invoice['attachment']) ?>
            </a>
          </p>

          <button type="button" class="btn-remove" id="removeAttachmentBtn">
            <i class="fas fa-trash"></i> Remove Current Attachment
          </button>
        </div>
      <?php else: ?>
        <p class="no-attachment">No file currently attached.</p>
      <?php endif; ?>

      <div class="upload-new" id="uploadSection">
        <label for="new_attachment" class="upload-label" id="uploadLabel">
          <i class="fas fa-upload"></i> Upload New File
        </label>
        <input type="file" name="new_attachment" id="new_attachment" accept=".pdf,.jpg,.png,.doc,.docx">
      </div>

      <input type="hidden" name="remove_attachment" id="removeAttachmentInput" value="0">
    </div>

    <div class="form-summary">
      <div>
        <label>Subtotal</label>
        <input type="text" id="subtotal" readonly>
      </div>
      <div>
        <label>Tax (12%)</label>
        <input type="text" id="tax" readonly>
      </div>
      <div>
        <label>Total</label>
        <input type="text" id="total" readonly>
      </div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
      <button type="submit" class="btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<!-- ✅ Confirmation Modal -->
<div id="confirmRemoveModal" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <h3>Remove Attachment?</h3>
    <p>Are you sure you want to remove the current attachment?</p>
    <div class="modal-actions">
      <button id="confirmRemoveYes" class="btn-yes">Yes</button>
      <button id="confirmRemoveCancel" class="btn-cancel">Cancel</button>
    </div>
  </div>
</div>


<script src="../assets/js/invoice.js"></script>
<script src="../assets/js/notification.js"></script>

</body>
</html>
