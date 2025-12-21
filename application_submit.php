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
            <label class="form-label required" for="organization_id">Ente</label>
            <select
              id="organization_id"
              name="organization_id"
              class="form-input"
              required
            >
              <option value="" disabled <?php echo $selectedOrganizationId ? '' : 'selected'; ?>></option>
              <?php foreach ($organizations as $org): ?>
              <option value="<?php echo $org['id']; ?>" <?php echo ((int) $selectedOrganizationId === (int) $org['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($org['name']); ?></option>
              <?php endforeach; ?>
            </select>
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
        const organizationSelect = document.getElementById('organization_id');
        const callSelect = document.getElementById('call_id');
        const duplicateWarning = document.getElementById('duplicate-warning');
        const submitButton = document.getElementById('application-submit');
        const form = document.querySelector('form.contact-form');
        let typedSequence = '';
        let typeaheadTimeout = null;

        let currentDuplicateCheckController = null;

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
          const organizationId = organizationSelect.value;

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

        form.addEventListener('submit', (event) => {
          if (!organizationSelect.value) {
            event.preventDefault();
            organizationSelect.reportValidity();
          }
        });

        organizationSelect.addEventListener('change', () => {
          clearDuplicateWarning();
          checkDuplicateApplication();
        });

        organizationSelect.addEventListener('keydown', (event) => {
          const key = event.key;

          if (key && key.length === 1 && /^[a-zA-Z0-9]$/.test(key)) {
            typedSequence += key.toLowerCase();
            if (typeaheadTimeout) {
              clearTimeout(typeaheadTimeout);
            }
            typeaheadTimeout = setTimeout(() => {
              typedSequence = '';
            }, 600);

            const availableOptions = Array.from(organizationSelect.options).filter((option) => !option.disabled && option.value !== '');
            const matchingOption = availableOptions.find((option) =>
              option.textContent.trim().toLowerCase().startsWith(typedSequence)
            );

            if (matchingOption) {
              organizationSelect.value = matchingOption.value;
              organizationSelect.dispatchEvent(new Event('change', { bubbles: true }));
              event.preventDefault();
            }
          } else {
            typedSequence = '';
          }
        });

        callSelect.addEventListener('change', checkDuplicateApplication);

        if (callSelect.value && organizationSelect.value) {
          checkDuplicateApplication();
        }

      })();
    </script>
  </body>
</html>
