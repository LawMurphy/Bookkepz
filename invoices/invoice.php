<?php
include '../config.php';
include '../topbar/header.php';

function generateInvoiceNumber($pdo) {
    $datePart = date('Ymd');
    $prefix = "INV-$datePart-";

    $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastInvoice = $stmt->fetchColumn();

    if ($lastInvoice) {
        preg_match('/-(\d{4})$/', $lastInvoice, $matches);
        $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = "0001";
    }

    return $prefix . $newNumber;
}

try {
    $stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching customers: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT id, name, description, price FROM service_type ORDER BY name ASC");
    $service_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching service types: " . $e->getMessage());
}
?>

<div class="invoice-wrapper">
  <div class="invoice-card">
    
    <div class="company-header">
      <img src="../assets/img/bookkepz_logo.png" alt="Bookkepz Logo" class="company-logo">
      <div class="company-info">
        <h1>Bookkepz</h1>
        <p>Smart Accounting & Billing Software</p>
      </div>
    </div>

    <div class="divider"></div>

    
    <div class="invoice-top">
      <h2><i class="fas fa-file-invoice-dollar"></i> Create New Invoice</h2>
      <p class="subtitle">Manage and track your customer invoices effortlessly</p>
    </div>

    <div class="invoice-info">
      <div class="info-item">
        <label>Invoice #</label>
        <input type="text" id="invoiceNumber" value="<?php echo generateInvoiceNumber($pdo); ?>" readonly>
      </div>
      <div class="info-item">
        <label>Date</label>
        <input type="date" id="invoiceDate" value="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="info-item">
        <label>Customer</label>
        <div class="customer-select-wrapper">
          <select id="customerSelect">
            <option value="">Select Customer</option>
            <?php foreach ($customers as $row): ?>
              <option value="<?= htmlspecialchars($row['id']) ?>">
                <?= htmlspecialchars($row['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button id="addCustomerBtn" class="btn-outline btn-small">
            <i class="fas fa-user-plus"></i>
          </button>
        </div>
      </div>
    </div>

    <table id="invoiceTable">
      <thead>
        <tr>
          <th>Service Type</th>
          <th>Description</th>
          <th>Qty</th>
          <th>Rate</th>
          <th>Amount</th>
          <th>Attachment</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="invoiceBody">
        <tr>
          <td>
            <div class="service-select-wrapper">
              <select class="service-type">
                <option value="">Select Service</option>
                <?php foreach ($service_type as $type): ?>
                  <option 
                    value="<?= htmlspecialchars($type['name']) ?>"
                    data-desc="<?= htmlspecialchars($type['description']) ?>"
                    data-price="<?= htmlspecialchars($type['price']) ?>">
                    <?= htmlspecialchars($type['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </td>
          <td><input type="text" class="item-desc" placeholder="Description"></td>
          <td><input type="number" class="item-qty" value="1" min="1"></td>
          <td><input type="number" class="item-rate" value="0" step="0.01"></td>
          <td class="item-amount">₱0.00</td>
          <td><input type="file" class="item-attachment" accept=".pdf,.xls,.xlsx,.doc,.docx"></td>
          <td><button class="btn-remove">✖</button></td>
        </tr>
      </tbody>
    </table>

    <button id="addItem" class="btn-outline">
      <i class="fas fa-plus"></i> Add Row
    </button>

    <button id="addServiceType" class="btn-outline">
      <i class="fas fa-plus"></i> Add Service Type
    </button>

    
    <div class="invoice-summary">
      <div class="invoice-line">
        <label>Subtotal:</label>
        <span id="subtotal">₱0.00</span>
      </div>
      <div class="invoice-line">
        <label>Tax (12%):</label>
        <span id="tax">₱0.00</span>
      </div>
      <div class="invoice-line total-row">
        <label>Total:</label>
        <span id="total">₱0.00</span>
      </div>
      <button id="saveInvoice" class="btn-primary">
        <i class="fas fa-save"></i> Save Invoice
      </button>
    </div>
  </div>
</div>


<div id="addCustomerModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3><i class="fas fa-user-plus"></i> Add New Customer</h3>

    <form id="addCustomerForm">
      <div>
        <label for="customer_name">Name</label>
        <input type="text" id="customer_name" name="name" required>
      </div>
      <div>
        <label for="customer_email">Email</label>
        <input type="email" id="customer_email" name="email">
      </div>
      <div>
        <label for="customer_phone">Phone</label>
        <input type="text" id="customer_phone" name="phone">
      </div>
      <div>
        <label for="customer_address">Address</label>
        <textarea id="customer_address" name="address"></textarea>
      </div>
      <button type="submit" class="btn-primary">Save Customer</button>
    </form>
  </div>
</div>

<div id="addServiceModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3><i class="fas fa-briefcase"></i> Add New Service Type</h3>

    <form id="addServiceForm">
      <div>
        <label for="service_name">Name of Service</label>
        <input type="text" id="service_name" name="name" required>
      </div>
      <div>
        <label for="service_description">Description</label>
        <textarea id="service_description" name="description" rows="3"></textarea>
      </div>
      <div>
        <label for="service_price">Price (₱)</label>
        <input type="number" id="service_price" name="price" step="0.01" required>
      </div>
      <button type="submit" class="btn-primary">
        <i class="fas fa-save"></i> Save Service Type
      </button>
    </form>
  </div>
</div>

<div id="notification" class="notification"></div>

<div class="invoice-history-section">
  <h2><i class="fas fa-history"></i> Invoice History</h2>
  <p class="subtitle">View, edit, or download your past invoices</p>

  <table class="invoice-history-table">
    <thead>
      <tr>
        <th>Invoice #</th>
        <th>Customer</th>
        <th>Date</th>
        <th>Service Type</th>
        <th>Total (₱)</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      try {
        $stmt = $pdo->query("
          SELECT 
            i.id,
            i.invoice_number,
            i.customer_name,
            i.invoice_date,
            COALESCE(s.name, i.service_type_name) AS service_type_name,
            i.total,
            i.created_at
          FROM invoices i
          LEFT JOIN service_type s ON i.service_type_name = s.name
          ORDER BY i.created_at DESC
        ");
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($invoices) {
          foreach ($invoices as $inv) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($inv['invoice_number']) . '</td>';
            echo '<td>' . htmlspecialchars($inv['customer_name']) . '</td>';
            echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($inv['invoice_date']))) . '</td>';
            echo '<td>' . htmlspecialchars($inv['service_type_name'] ?? 'N/A') . '</td>';
            echo '<td>' . number_format($inv['total'], 2) . '</td>';
            echo '<td>' . htmlspecialchars(date('M d, Y h:i A', strtotime($inv['created_at']))) . '</td>';
            echo '<td class="actions">
                    <a href="edit_invoice?id=' . $inv['id'] . '" class="btn-small btn-edit"><i class="fas fa-edit"></i> Edit</a>
                    <a href="view_invoice?id=' . $inv['id'] . '" class="btn-small btn-view"><i class="fas fa-eye"></i> View</a>
                  </td>';
            echo '</tr>';
          }
        } else {
          echo '<tr><td colspan="7" class="no-records">No invoices found</td></tr>';
        }
      } catch (PDOException $e) {
        echo '<tr><td colspan="7">Error fetching invoices: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<link rel="stylesheet" href="../assets/css/invoice.css">
<link rel="stylesheet" href="../assets/css/notification.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<script src="../assets/js/invoice.js"></script>
<script src="../assets/js/notification.js"></script>

<?php include '../topbar/footer.php'; ?>
