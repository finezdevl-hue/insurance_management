<?php
/**
 * Public Testing Centers & Outlets Directory
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/includes/functions.php';

$db = getDBConnection();

$agentId = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;
$shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;

// If shop_id is passed, resolve its parent agent ID
if ($shopId > 0 && $agentId === 0) {
    $stmtP = $db->prepare("SELECT parent_agent_id FROM users WHERE id = ? AND role = 'shop'");
    $stmtP->execute([$shopId]);
    $resolvedParent = $stmtP->fetchColumn();
    if ($resolvedParent) {
        $agentId = (int)$resolvedParent;
    }
}

// Fetch Parent Agent details if agent_id is provided
$agentInfo = null;
if ($agentId > 0) {
    $stmtA = $db->prepare("SELECT id, shop_name, shop_owner_name, mobile_number, email, shop_address, city, state FROM users WHERE id = ? AND role = 'agent'");
    $stmtA->execute([$agentId]);
    $agentInfo = $stmtA->fetch(PDO::FETCH_ASSOC);
}

// Fetch all active Sub-Shops / Centers under this Agent (or all if agentId is 0)
if ($agentId > 0) {
    $stmtS = $db->prepare("SELECT * FROM users WHERE parent_agent_id = ? AND role = 'shop' AND status = 'active' ORDER BY shop_name ASC");
    $stmtS->execute([$agentId]);
    $shops = $stmtS->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmtS = $db->query("SELECT s.*, p.shop_name as parent_shop_name FROM users s LEFT JOIN users p ON s.parent_agent_id = p.id WHERE s.role = 'shop' AND s.status = 'active' ORDER BY s.shop_name ASC");
    $shops = $stmtS->fetchAll(PDO::FETCH_ASSOC);
}

$agencyName = $agentInfo['shop_name'] ?? 'Authorized Vehicle Testing & Renewal Centers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($agencyName); ?> - Authorized Testing Centers</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/stylesheet/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark);
            min-height: 100vh;
        }

        h1, h2, h3, h4, .brand-title {
            font-family: 'Outfit', sans-serif;
        }

        .hero-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
            color: white;
            padding: 2.5rem 1rem 3.5rem 1rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.3);
        }

        .center-card {
            background: white;
            border: 1px solid var(--card-border);
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.25s ease;
            overflow: hidden;
        }

        .center-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .badge-verified {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .btn-action {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.6rem 1rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-maps {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .btn-maps:hover {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .btn-call {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .btn-call:hover {
            background-color: #d1fae5;
            color: #065f46;
        }

        .btn-wa {
            background-color: #25d366;
            color: white;
            border: none;
        }

        .btn-wa:hover {
            background-color: #1ebc57;
            color: white;
        }
    </style>
</head>
<body>

    <!-- Hero Banner -->
    <header class="hero-banner text-center mb-4">
        <div class="container" style="max-width: 700px;">
            <span class="badge bg-white text-primary rounded-pill px-3 py-2 font-weight-600 mb-3 shadow-sm">
                <i class="fa-solid fa-shield-halved me-1 text-primary"></i> Official Partner Outlets
            </span>
            <h2 class="fw-bold mb-2 brand-title"><?php echo sanitize($agencyName); ?></h2>
            <p class="opacity-90 m-0 small">Visit any of our authorized testing centers below for Vehicle Pollution (PUC) & Insurance Renewals</p>
        </div>
    </header>

    <main class="container mb-5" style="max-width: 850px;">
        
        <?php if (count($shops) > 0): ?>
            <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                <span class="text-muted font-weight-600 small">
                    <i class="fa-solid fa-store me-1 text-primary"></i> <?php echo count($shops); ?> Testing Center Outlets Available
                </span>
            </div>

            <div class="row g-4">
                <?php foreach ($shops as $sh): 
                    $fullAddress = trim(($sh['shop_address'] ? $sh['shop_address'] . ', ' : '') . ($sh['city'] ? $sh['city'] . ', ' : '') . ($sh['state'] ? $sh['state'] . ' ' : '') . $sh['pincode']);
                    $encodedAddress = urlencode($sh['shop_name'] . ' ' . $fullAddress);
                    $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . $encodedAddress;
                    $phone = $sh['mobile_number'];
                    $waNumber = preg_replace('/[^0-9]/', '', $sh['whatsapp_number'] ?: $phone);
                    if (strlen($waNumber) === 10) {
                        $waNumber = '91' . $waNumber;
                    }
                ?>
                    <div class="col-12">
                        <div class="center-card p-4">
                            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold m-0 text-dark"><?php echo sanitize($sh['shop_name']); ?></h5>
                                        <span class="badge-verified"><i class="fa-solid fa-circle-check me-1"></i>Verified Center</span>
                                    </div>
                                    <?php if (!empty($sh['shop_owner_name'])): ?>
                                        <div class="text-muted small">
                                            <i class="fa-solid fa-user-gear me-1"></i> In-Charge: <strong><?php echo sanitize($sh['shop_owner_name']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-md-end">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                        <i class="fa-solid fa-clock me-1"></i> Open Today: 9:00 AM - 7:00 PM
                                    </span>
                                </div>
                            </div>

                            <!-- Address Box -->
                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-location-dot text-danger mt-1"></i>
                                    <div class="small">
                                        <strong class="d-block text-dark mb-1">Testing Center Address:</strong>
                                        <span class="text-secondary"><?php echo sanitize($fullAddress ?: 'Address available at testing center'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row g-2">
                                <div class="col-12 col-sm-4">
                                    <a href="<?php echo $mapsUrl; ?>" target="_blank" class="btn-action btn-maps w-100">
                                        <i class="fa-solid fa-map-location-dot"></i> Google Maps
                                    </a>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <a href="tel:<?php echo sanitize($phone); ?>" class="btn-action btn-call w-100">
                                        <i class="fa-solid fa-phone"></i> Call Center
                                    </a>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <a href="https://wa.me/<?php echo $waNumber; ?>?text=Hello,%20I%20want%20to%20renew%20my%20vehicle%20Pollution%20Certificate" target="_blank" class="btn-action btn-wa w-100">
                                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                <i class="fa-solid fa-store-slash text-muted fs-1 mb-3"></i>
                <h5 class="fw-bold">No Testing Centers Found</h5>
                <p class="text-muted small">Please contact customer support for center locations.</p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-5 pt-3 border-top text-muted small">
            <p class="m-0">&copy; <?php echo date('Y'); ?> Vehicle Details & Insurance Renewal System. All rights reserved.</p>
        </div>
    </main>

</body>
</html>
