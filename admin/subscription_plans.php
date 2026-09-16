<?php
/**
 * Subscription Plans Management (Super Admin CRUD)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Subscription Plans Master';
$pageHeading = 'Agent Subscription Plans & Packages';
$activePage = 'subscription_plans';

$action = $_GET['action'] ?? 'list';

// --- ACTION CONTROLLERS ---

// 1. Delete Plan
if ($action === 'delete' && isset($_GET['id'])) {
    $planId = (int)$_GET['id'];
    try {
        $stmtFetch = $db->prepare("SELECT name FROM subscription_plans WHERE id = ?");
        $stmtFetch->execute([$planId]);
        $planName = $stmtFetch->fetchColumn();

        if ($planName) {
            $stmt = $db->prepare("DELETE FROM subscription_plans WHERE id = ?");
            $stmt->execute([$planId]);
            logActivity('Delete Subscription Plan', "Deleted plan: {$planName} (ID: {$planId})");
            $_SESSION['alert_success'] = "Subscription plan '{$planName}' deleted successfully!";
        } else {
            $_SESSION['alert_error'] = 'Subscription plan not found.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Cannot delete plan: ' . $e->getMessage();
    }
    redirect('subscription_plans.php');
}

// 2. Toggle Status
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $planId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT status, name FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if ($plan) {
            $newStatus = ($plan['status'] === 'active') ? 'inactive' : 'active';
            $stmtUpdate = $db->prepare("UPDATE subscription_plans SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $planId]);
            logActivity('Toggle Plan Status', "Changed status of {$plan['name']} to {$newStatus}.");
            $_SESSION['alert_success'] = "Plan '{$plan['name']}' status changed to {$newStatus}!";
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Error updating status: ' . $e->getMessage();
    }
    redirect('subscription_plans.php');
}

// 3. Create or Edit Plan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $durationType = trim($_POST['duration_type'] ?? 'months');
    $durationValue = max(1, (int)($_POST['duration_value'] ?? 1));
    $price = max(0, (float)($_POST['price'] ?? 0));
    $smsCredits = max(0, (int)($_POST['sms_credits'] ?? 0));
    $badge = trim($_POST['badge'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name)) {
        $_SESSION['alert_error'] = 'Plan name is required.';
    } elseif ($price < 0) {
        $_SESSION['alert_error'] = 'Plan price cannot be negative.';
    } else {
        try {
            if ($formAction === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO subscription_plans (name, duration_type, duration_value, price, sms_credits, badge, description, features, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $durationType, $durationValue, $price, $smsCredits, $badge, $description, $features, $status]);
                logActivity('Create Subscription Plan', "Created new plan: {$name} (₹{$price} for {$durationValue} {$durationType})");
                $_SESSION['alert_success'] = "Subscription plan '{$name}' created successfully!";
            } elseif ($formAction === 'edit') {
                $planId = (int)($_POST['plan_id'] ?? 0);
                $stmt = $db->prepare("
                    UPDATE subscription_plans 
                    SET name = ?, duration_type = ?, duration_value = ?, price = ?, sms_credits = ?, badge = ?, description = ?, features = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $durationType, $durationValue, $price, $smsCredits, $badge, $description, $features, $status, $planId]);
                logActivity('Update Subscription Plan', "Updated plan: {$name} (ID: {$planId})");
                $_SESSION['alert_success'] = "Subscription plan '{$name}' updated successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
        redirect('subscription_plans.php');
    }
}

// Fetch all plans
$plans = getAllSubscriptionPlans(false);
$totalActive = 0;
$totalPlans = count($plans);
foreach ($plans as $p) {
    if ($p['status'] === 'active') $totalActive++;
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Overview -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-crown"></i>
            </div>
            <p class="stat-title">Total Plans</p>
            <h3 class="stat-value text-primary"><?php echo $totalPlans; ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <p class="stat-title">Active Plans</p>
            <h3 class="stat-value text-success"><?php echo $totalActive; ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <p class="stat-title">Subscription Logs</p>
            <a href="subscriptions.php" class="btn btn-sm btn-outline-info font-weight-600 mt-1">
                View All Subscriptions <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-plus"></i>
            </div>
            <p class="stat-title">Quick Action</p>
            <button type="button" class="btn btn-sm btn-warning font-weight-700 mt-1 text-dark" data-bs-toggle="modal" data-bs-target="#planModal" onclick="prepareAddPlan()">
                <i class="fa-solid fa-plus me-1"></i> Add New Plan
            </button>
        </div>
    </div>
</div>

<!-- Plan Master Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-layer-group text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Configured Subscription Packages</h5>
        </div>
        <button type="button" class="btn btn-primary font-weight-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#planModal" onclick="prepareAddPlan()">
            <i class="fa-solid fa-plus me-1"></i> Create Plan
        </button>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Plan Name & Badge</th>
                    <th>Duration</th>
                    <th>Price (₹)</th>
                    <th>Bonus SMS</th>
                    <th>Key Highlights</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $row): 
                    $featureItems = array_filter(array_map('trim', explode("\n", (string)$row['features'])));
                ?>
                    <tr>
                        <!-- Plan Name & Badge -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-dark fs-6"><?php echo sanitize($row['name']); ?></strong>
                                <?php if (!empty($row['badge'])): ?>
                                    <span class="badge bg-warning text-dark font-weight-700"><?php echo sanitize($row['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($row['description'])): ?>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?php echo sanitize($row['description']); ?></small>
                            <?php endif; ?>
                        </td>

                        <!-- Duration -->
                        <td>
                            <span class="badge bg-light text-dark border font-weight-600 px-2.5 py-1.5 fs-6">
                                <?php echo $row['duration_value'] . ' ' . ucfirst($row['duration_type']); ?>
                            </span>
                        </td>

                        <!-- Price -->
                        <td>
                            <strong class="text-success fs-5">₹<?php echo number_format($row['price'], 2); ?></strong>
                        </td>

                        <!-- Bonus SMS -->
                        <td>
                            <?php if ($row['sms_credits'] > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-weight-700">
                                    +<?php echo number_format($row['sms_credits']); ?> SMS
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>

                        <!-- Highlights -->
                        <td>
                            <small class="text-muted">
                                <?php if (!empty($featureItems)): ?>
                                    <i class="fa-solid fa-check text-success me-1"></i><?php echo sanitize($featureItems[0]); ?>
                                    <?php if (count($featureItems) > 1): ?>
                                        <span class="badge bg-light text-muted border ms-1">+<?php echo count($featureItems) - 1; ?> more</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Standard features
                                <?php endif; ?>
                            </small>
                        </td>

                        <!-- Status -->
                        <td>
                            <a href="subscription_plans.php?action=toggle_status&id=<?php echo $row['id']; ?>" class="badge <?php echo $row['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> text-decoration-none" title="Click to toggle status">
                                <i class="fa-solid <?php echo $row['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                <?php echo strtoupper($row['status']); ?>
                            </a>
                        </td>

                        <!-- Actions -->
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="prepareEditPlan(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)" title="Edit Plan">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')" title="Delete Plan">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Plan Add / Edit Modal -->
<div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-700" id="planModalLabel">
                    <i class="fa-solid fa-crown me-2"></i> Add Subscription Plan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="planForm">
                <input type="hidden" name="form_action" id="form_action" value="add">
                <input type="hidden" name="plan_id" id="plan_id" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Plan Name -->
                        <div class="col-12 col-md-8">
                            <label for="modal_name" class="form-label font-weight-600">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_name" name="name" required placeholder="e.g. 6 Months Pro, 1 Year Enterprise">
                        </div>

                        <!-- Badge -->
                        <div class="col-12 col-md-4">
                            <label for="modal_badge" class="form-label font-weight-600">Highlight Badge</label>
                            <input type="text" class="form-control" id="modal_badge" name="badge" placeholder="e.g. Popular, Best Value, Starter">
                        </div>

                        <!-- Duration Value -->
                        <div class="col-12 col-md-6">
                            <label for="modal_duration_value" class="form-label font-weight-600">Duration Value <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_duration_value" name="duration_value" min="1" step="1" value="1" required>
                        </div>

                        <!-- Duration Type -->
                        <div class="col-12 col-md-6">
                            <label for="modal_duration_type" class="form-label font-weight-600">Duration Unit <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_duration_type" name="duration_type" required>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                                <option value="days">Days</option>
                            </select>
                        </div>

                        <!-- Price (₹) -->
                        <div class="col-12 col-md-6">
                            <label for="modal_price" class="form-label font-weight-600">Price (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="modal_price" name="price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>

                        <!-- Bonus SMS Credits -->
                        <div class="col-12 col-md-6">
                            <label for="modal_sms_credits" class="form-label font-weight-600">Bonus Free SMS Credits</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-gift text-success"></i></span>
                                <input type="number" class="form-control" id="modal_sms_credits" name="sms_credits" min="0" step="1" value="0" placeholder="0">
                            </div>
                            <div class="form-text">Will be automatically credited to agent wallet on plan activation.</div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="modal_description" class="form-label font-weight-600">Short Summary / Subtitle</label>
                            <input type="text" class="form-control" id="modal_description" name="description" placeholder="Brief tagline describing target agency">
                        </div>

                        <!-- Features (One per line) -->
                        <div class="col-12">
                            <label for="modal_features" class="form-label font-weight-600">Plan Features / Inclusions (One per line)</label>
                            <textarea class="form-control font-monospace small" id="modal_features" name="features" rows="4" placeholder="Full Portal Access&#10;Unlimited Vehicle Records&#10;Pollution Certificate Management&#10;Automated WhatsApp Reminders"></textarea>
                            <div class="form-text">Enter each feature on a separate line. These will display as bullet points.</div>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label for="modal_status" class="form-label font-weight-600">Status</label>
                            <select class="form-select" id="modal_status" name="status">
                                <option value="active">Active (Available for Purchase)</option>
                                <option value="inactive">Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-600 px-4" id="savePlanBtn">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareAddPlan() {
    document.getElementById('planModalLabel').innerHTML = '<i class="fa-solid fa-crown me-2"></i> Add Subscription Plan';
    document.getElementById('form_action').value = 'add';
    document.getElementById('plan_id').value = '';
    document.getElementById('planForm').reset();
    document.getElementById('modal_status').value = 'active';
    document.getElementById('savePlanBtn').innerText = 'Create Plan';
}

function prepareEditPlan(plan) {
    document.getElementById('planModalLabel').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i> Edit Subscription Plan';
    document.getElementById('form_action').value = 'edit';
    document.getElementById('plan_id').value = plan.id;
    document.getElementById('modal_name').value = plan.name;
    document.getElementById('modal_badge').value = plan.badge || '';
    document.getElementById('modal_duration_value').value = plan.duration_value;
    document.getElementById('modal_duration_type').value = plan.duration_type;
    document.getElementById('modal_price').value = plan.price;
    document.getElementById('modal_sms_credits').value = plan.sms_credits || 0;
    document.getElementById('modal_description').value = plan.description || '';
    document.getElementById('modal_features').value = plan.features || '';
    document.getElementById('modal_status').value = plan.status;
    document.getElementById('savePlanBtn').innerText = 'Update Plan';

    const modal = new bootstrap.Modal(document.getElementById('planModal'));
    modal.show();
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Plan?',
        text: "Are you sure you want to delete '" + name + "'? Existing agent subscriptions won't be affected.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Delete'
    }).then((res) => {
        if (res.isConfirmed) {
            window.location.href = 'subscription_plans.php?action=delete&id=' + id;
        }
    });
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
