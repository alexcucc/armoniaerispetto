<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_CREATE'])) {
    header('Location: index.php');
    exit();
}

// Load all open calls for proposals
$stmt = $pdo->prepare('SELECT id, title, description, start_date, end_date FROM call_for_proposal WHERE status = "OPEN"');
$stmt->execute();
$availableCalls = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedCallId = filter_input(INPUT_GET, 'call_id', FILTER_VALIDATE_INT);

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

if (isset($formData['call_id']) && $formData['call_id']) {
    $selectedCallId = (int) $formData['call_id'];
}

$selectedOrganizationId = $formData['organization_id'] ?? null;
$selectedSupervisorId = $formData['supervisor_id'] ?? null;
$projectNameValue = $formData['project_name'] ?? '';

// Load organizations
$orgStmt = $pdo->prepare('SELECT id, name FROM organization ORDER BY name');
$orgStmt->execute();
$organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);
$selectedOrganizationName = '';
foreach ($organizations as $org) {
    if ((int) $org['id'] === (int) $selectedOrganizationId) {
        $selectedOrganizationName = $org['name'];
        break;
    }
}

// Load supervisors
$supStmt = $pdo->prepare('SELECT s.id, u.first_name, u.last_name FROM supervisor s JOIN user u ON s.user_id = u.id ORDER BY u.first_name, u.last_name');
$supStmt->execute();
$supervisors = $supStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Carica Risposta al bando</title>
  </head>
  <body class="management-page">
    <?php include 'header.php'; ?>
    <main>
      <div class="contact-form-container">
        <h2>Carica risposta al bando</h2>
        <?php if (!empty($errorMessage)): ?>
        <div class="message error">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php endif; ?>
        <div id="duplicate-warning" class="message error" style="display: none;"></div>
        <form class="contact-form" action="application_submit_handler.php" method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label class="form-label required" for="call_id">Bando</label>
            <select id="call_id" name="call_id" class="form-input" required>
              <option value="" disabled <?php echo $selectedCallId ? '' : 'selected'; ?>></option>
              <?php foreach ($availableCalls as $call): ?>
              <option value="<?php echo $call['id']; ?>" data-title="<?php echo htmlspecialchars($call['title'], ENT_QUOTES); ?>" data-description="<?php echo htmlspecialchars($call['description'], ENT_QUOTES); ?>" data-start="<?php echo htmlspecialchars(date('d/m/Y', strtotime($call['start_date']))); ?>" data-end="<?php echo htmlspecialchars(date('d/m/Y', strtotime($call['end_date']))); ?>" <?php if ($selectedCallId == $call['id']) echo 'selected'; ?>><?php echo htmlspecialchars($call['title']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="position: relative;">
            <label class="form-label required" for="organization_name">Ente</label>
            <input
              type="text"
              id="organization_name"
              class="form-input"
              name="organization_name"
              list="organization-options"
              value="<?php echo htmlspecialchars($selectedOrganizationName); ?>"
              placeholder="Inizia a digitare il nome dell'ente"
              autocomplete="off"
              required
            >
            <input type="hidden" id="organization_id" name="organization_id" value="<?php echo htmlspecialchars($selectedOrganizationId); ?>">
            <datalist id="organization-options"></datalist>
          </div>
          <div class="form-group">
            <label class="form-label required" for="supervisor_id">Convalidatore</label>
            <select id="supervisor_id" name="supervisor_id" class="form-input" required>
              <option value="" disabled <?php echo $selectedSupervisorId ? '' : 'selected'; ?>></option>
              <?php foreach ($supervisors as $sup): ?>
              <option value="<?php echo $sup['id']; ?>" <?php if ($selectedSupervisorId == $sup['id']) echo 'selected'; ?>><?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label required" for="project_name">Nome del Progetto</label>
            <input type="text" id="project_name" name="project_name" class="form-input" value="<?php echo htmlspecialchars($projectNameValue); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label required" for="application_pdf">PDF della risposta al bando</label>
            <input type="file" id="application_pdf" name="application_pdf" class="form-input" accept="application/pdf" required>
          </div>
          <div class="form-group">
            <label class="form-label required" for="budget_pdf">PDF del modulo budget</label>
            <input type="file" id="budget_pdf" name="budget_pdf" class="form-input" accept="application/pdf" required>
          </div>
          <br>
          <div class="button-container">
            <a href="applications.php" class="page-button" style="background-color: #007bff;">Indietro</a>
            <button type="submit" id="application-submit" class="page-button">Carica</button>
          </div>
        </form>
      </div>
    </main>
    <?php include 'footer.php'; ?>
    <script>
      (function() {
        const organizationInput = document.getElementById('organization_name');
        const organizationIdInput = document.getElementById('organization_id');
        const callSelect = document.getElementById('call_id');
        const organizationOptions = document.getElementById('organization-options');
        const duplicateWarning = document.getElementById('duplicate-warning');
        const submitButton = document.getElementById('application-submit');
        const form = document.querySelector('form.contact-form');

        let currentDuplicateCheckController = null;
        const organizations = <?php echo json_encode($organizations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        const clearDuplicateWarning = () => {
          duplicateWarning.style.display = 'none';
          duplicateWarning.textContent = '';
          submitButton.disabled = false;
        };

        const showDuplicateWarning = (message) => {
          duplicateWarning.textContent = message;
          duplicateWarning.style.display = 'block';
          submitButton.disabled = true;
        };

        const checkDuplicateApplication = async () => {
          const callId = callSelect.value;
          const organizationId = organizationIdInput.value;

          if (!callId || !organizationId) {
            clearDuplicateWarning();
            return;
          }

          if (currentDuplicateCheckController) {
            currentDuplicateCheckController.abort();
          }

          currentDuplicateCheckController = new AbortController();

          try {
            const params = new URLSearchParams({ call_id: callId, organization_id: organizationId });
            const response = await fetch(`application_duplicate_check.php?${params.toString()}`, {
              method: 'GET',
              headers: { 'Accept': 'application/json' },
              signal: currentDuplicateCheckController.signal,
            });

            if (!response.ok) {
              clearDuplicateWarning();
              return;
            }

            const data = await response.json();

            if (data.exists) {
              showDuplicateWarning(data.message || 'Esiste già una risposta al bando per questo ente.');
            } else {
              clearDuplicateWarning();
            }
          } catch (error) {
            if (error.name !== 'AbortError') {
              clearDuplicateWarning();
            }
          }
        };

        const renderOrganizationOptions = (searchValue) => {
          const query = searchValue.trim().toLowerCase();
          organizationOptions.innerHTML = '';

          organizations
            .filter((org) => query.length === 0 || org.name.toLowerCase().startsWith(query))
            .forEach((org) => {
              const option = document.createElement('option');
              option.value = org.name;
              organizationOptions.appendChild(option);
            });
        };

        const syncOrganizationId = (value) => {
          const normalized = value.trim().toLowerCase();
          const match = organizations.find((org) => org.name.toLowerCase() === normalized);
          if (match) {
            organizationIdInput.value = match.id;
            organizationInput.setCustomValidity('');
            checkDuplicateApplication();
            return;
          }

          organizationIdInput.value = '';
          clearDuplicateWarning();
        };

        form.addEventListener('submit', (event) => {
          if (!organizationIdInput.value) {
            event.preventDefault();
            organizationInput.setCustomValidity('Seleziona un ente valido dall’elenco.');
            organizationInput.reportValidity();
          }
        });

        organizationInput.addEventListener('input', (event) => {
          const value = event.target.value;
          organizationInput.setCustomValidity('');
          renderOrganizationOptions(value);
          syncOrganizationId(value);
        });

        callSelect.addEventListener('change', checkDuplicateApplication);

        if (callSelect.value && organizationIdInput.value) {
          checkDuplicateApplication();
        }

        renderOrganizationOptions(organizationInput.value);
        syncOrganizationId(organizationInput.value);

      })();
    </script>
  </body>
</html>
