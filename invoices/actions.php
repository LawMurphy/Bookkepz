<?php
include '../config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        exit;
    }

    switch ($action) {

        case 'add_customer':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'Customer name required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $address]);

            echo json_encode([
                'status' => 'success',
                'id' => $pdo->lastInsertId(),
                'name' => $name
            ]);
            break;


        case 'add_service_type':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);

            if (empty($name) || $price <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Service name and price are required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO service_type (name, description, price) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $price]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Service type added successfully!',
                'id' => $pdo->lastInsertId(),
                'name' => $name,
                'description' => $description,
                'price' => $price
            ]);
            break;

        case 'save_invoice':
            if (!isset($_POST['data'])) {
                echo json_encode(['status' => 'error', 'message' => 'No invoice data received']);
                exit;
            }

            $data = json_decode($_POST['data'], true);
            $invoice_number = $data['invoiceNumber'];
            $invoice_date   = $data['invoiceDate'];
            $customer_name  = $data['customerName'];
            $subtotal       = (float)$data['subtotal'];
            $tax            = (float)$data['tax'];
            $total          = (float)$data['total'];

            try {
                
                $pdo->beginTransaction();

                
                $check = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_number = ?");
                $check->execute([$invoice_number]);
                if ($check->fetchColumn() > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invoice already exists. Please refresh or generate a new one.'
                    ]);
                    exit;
                }

                $insert = $pdo->prepare("
                    INSERT INTO invoices (
                        invoice_number, customer_name, invoice_date,
                        service_type_name, description, qty, rate, subtotal, tax, total, attachment
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $uploadDir = "../uploads/";
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                foreach ($data['items'] as $index => $item) {
                    $service_type_name = trim($item['name']);
                    $desc              = trim($item['desc']);
                    $qty               = (float)$item['qty'];
                    $rate              = (float)$item['rate'];
                    $attachment        = null;

                    $fileKey = "file_" . $index;
                    if (!empty($_FILES[$fileKey]['name'])) {
                        $fileName = time() . "_item{$index}_" . basename($_FILES[$fileKey]['name']);
                        $filePath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $filePath)) {
                            $attachment = $fileName;
                        }
                    }

                    $insert->execute([
                        $invoice_number,
                        $customer_name,
                        $invoice_date,
                        $service_type_name,
                        $desc,
                        $qty,
                        $rate,
                        $subtotal,
                        $tax,
                        $total,
                        $attachment
                    ]);
                }

                $pdo->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Invoice saved successfully!',
                    'invoice_number' => $invoice_number
                ]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;


        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
