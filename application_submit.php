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
if ($selectedOrganizationId !== null) {
    foreach ($organizations as $org) {
        if ((int) $org['id'] === (int) $selectedOrganizationId) {
            $selectedOrganizationName = $org['name'];
            break;
        }
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
            <label class="form-label required" for="organization-search">Ente</label>
            <input
              type="text"
              id="organization-search"
              class="form-input"
              name="organization-search"
              placeholder="Filtra per nome ente"
              aria-label="Filtra e seleziona l'ente per nome"
              autocomplete="off"
              required
              value="<?php echo htmlspecialchars($selectedOrganizationName); ?>"
            >
            <button type="button" id="organization-dropdown-toggle" class="autocomplete-toggle" aria-label="Mostra tutti gli enti disponibili">▼</button>
            <input type="hidden" id="organization_id" name="organization_id" value="<?php echo htmlspecialchars((string) $selectedOrganizationId); ?>">
            <div id="organization-suggestions" class="autocomplete-list" role="listbox"></div>
            <datalist id="available-organizations">
              <?php foreach ($organizations as $org): ?>
              <option data-organization-id="<?php echo $org['id']; ?>" value="<?php echo htmlspecialchars($org['name']); ?>"></option>
              <?php endforeach; ?>
            </datalist>
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
          <div class="button-container">
            <a href="applications.php" class="page-button" style="background-color: #007bff;">Indietro</a>
            <button type="submit" class="page-button">Carica</button>
          </div>
        </form>
      </div>
    </main>
    <?php include 'footer.php'; ?>
    <script>
      (function() {
        const organizationInput = document.getElementById('organization-search');
        const organizationIdInput = document.getElementById('organization_id');
        const options = Array.from(document.querySelectorAll('#available-organizations option'));
        const form = document.querySelector('form.contact-form');
        const suggestionBox = document.getElementById('organization-suggestions');
        const toggleButton = document.getElementById('organization-dropdown-toggle');
        const organizations = options.map((option) => ({
          id: option.dataset.organizationId,
          label: option.value,
        }));

        const ensureStyles = () => {
          if (document.getElementById('autocomplete-styles')) return;
          const style = document.createElement('style');
          style.id = 'autocomplete-styles';
          style.textContent = `
                .autocomplete-list {
                    border: 1px solid #ccc;
                    border-top: none;
                    max-height: 200px;
                    overflow-y: auto;
                    background: #fff;
                    position: absolute;
                    width: 100%;
                    z-index: 2;
                    display: none;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    left: 0;
                    top: calc(100% - 1px);
                }
                .autocomplete-item {
                    padding: 2px 3px;
                    cursor: pointer;
                }
                .autocomplete-item:hover,
                .autocomplete-item:focus {
                    background-color: #f0f0f0;
                }
                .autocomplete-toggle {
                    position: absolute;
                    right: 8px;
                    top: 34px;
                    background: transparent;
                    border: none;
                    cursor: pointer;
                    font-size: 0.9rem;
                    padding: 4px;
                    color: #555;
                }
                .form-group .form-input {
                    padding-right: 32px;
                }
            `;
          document.head.appendChild(style);
        };

        let showAllOnEmpty = organizationInput.value.trim() === '';

        const selectOrganization = (organization) => {
          organizationInput.value = organization.label;
          organizationIdInput.value = organization.id;
          organizationInput.setCustomValidity('');
          suggestionBox.style.display = 'none';
          showAllOnEmpty = false;
        };

        const renderSuggestions = (forceShowAll = false) => {
          const query = organizationInput.value.trim().toLowerCase();
          const shouldShowAll = forceShowAll || showAllOnEmpty;
          const filtered = query === ''
            ? (shouldShowAll ? organizations : [])
            : organizations.filter((organization) => organization.label.toLowerCase().includes(query));

          suggestionBox.innerHTML = '';

          filtered.forEach((organization) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.textContent = organization.label;
            item.tabIndex = 0;
            item.setAttribute('role', 'option');
            item.addEventListener('mousedown', (event) => {
              event.preventDefault();
              selectOrganization(organization);
            });
            suggestionBox.appendChild(item);
          });

          suggestionBox.style.display = filtered.length ? 'block' : 'none';
        };

        form.addEventListener('submit', (event) => {
          if (!organizationIdInput.value) {
            event.preventDefault();
            organizationInput.reportValidity();
          }
        });

        organizationInput.addEventListener('input', () => {
          organizationIdInput.value = '';
          organizationInput.setCustomValidity("Seleziona un ente dalla lista.");
          showAllOnEmpty = false;
          renderSuggestions();
        });

        organizationInput.addEventListener('focus', () => renderSuggestions());

        organizationInput.addEventListener('blur', () => {
          setTimeout(() => {
            suggestionBox.style.display = 'none';
            showAllOnEmpty = false;
          }, 150);
        });

        toggleButton.addEventListener('click', () => {
          const isOpen = suggestionBox.style.display === 'block';
          if (isOpen) {
            suggestionBox.style.display = 'none';
            showAllOnEmpty = false;
            return;
          }

          showAllOnEmpty = true;
          renderSuggestions(true);
          organizationInput.focus();
        });

        if (organizationInput.value && organizationIdInput.value) {
          organizationInput.setCustomValidity('');
        }

        ensureStyles();
      })();
    </script>
  </body>
</html>
