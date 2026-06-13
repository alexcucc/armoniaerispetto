<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';
require_once 'call_for_proposal_winner_utils.php';

function buildWinnerFormState(
    array $eligibleApplications,
    array $dbWinners,
    array $imagesByWinnerId,
    ?array $formData
): array {
    if (is_array($formData) && isset($formData['winners']) && is_array($formData['winners'])) {
        $winners = [];
        foreach ($formData['winners'] as $winnerKey => $winnerData) {
            if (!is_array($winnerData)) {
                continue;
            }

            $winnerId = isset($winnerData['id']) && ctype_digit((string) $winnerData['id']) ? (int) $winnerData['id'] : null;
            $existingImages = [];
            if ($winnerId !== null && isset($imagesByWinnerId[$winnerId])) {
                $existingImages = $imagesByWinnerId[$winnerId];
            }

            $imagesById = [];
            foreach ($existingImages as $image) {
                $imagesById[(int) $image['id']] = $image;
            }

            if (isset($winnerData['existing_images']) && is_array($winnerData['existing_images'])) {
                foreach ($winnerData['existing_images'] as $imageId => $imageData) {
                    $imageId = (int) $imageId;
                    if (!isset($imagesById[$imageId]) || !is_array($imageData)) {
                        continue;
                    }

                    $imagesById[$imageId]['display_order'] = $imageData['display_order'] ?? $imagesById[$imageId]['display_order'];
                    $imagesById[$imageId]['alt_text'] = $imageData['alt_text'] ?? $imagesById[$imageId]['alt_text'];
                    $imagesById[$imageId]['caption'] = $imageData['caption'] ?? $imagesById[$imageId]['caption'];
                    $imagesById[$imageId]['delete'] = !empty($imageData['delete']);
                }
            }

            $winners[] = [
                'form_key' => (string) $winnerKey,
                'id' => $winnerId,
                'application_id' => $winnerData['application_id'] ?? '',
                'display_order' => $winnerData['display_order'] ?? '',
                'public_title' => $winnerData['public_title'] ?? '',
                'description' => $winnerData['description'] ?? '',
                'delete' => !empty($winnerData['delete']),
                'pending_new_images' => isset($winnerData['new_images_meta']) && is_array($winnerData['new_images_meta'])
                    ? array_values(array_map(
                        static fn (array $imageData): array => [
                            'display_order' => $imageData['display_order'] ?? '',
                            'alt_text' => $imageData['alt_text'] ?? '',
                            'caption' => $imageData['caption'] ?? '',
                        ],
                        array_filter($winnerData['new_images_meta'], 'is_array')
                    ))
                    : [],
                'existing_images' => array_values($imagesById),
            ];
        }

        return $winners;
    }

    $winners = [];
    foreach ($dbWinners as $winner) {
        $winnerId = (int) $winner['id'];
        $winners[] = [
            'form_key' => 'existing_' . $winnerId,
            'id' => $winnerId,
            'application_id' => (string) $winner['application_id'],
            'display_order' => (string) $winner['display_order'],
            'public_title' => (string) $winner['public_title'],
            'description' => (string) $winner['description'],
            'delete' => false,
            'pending_new_images' => [],
            'existing_images' => $imagesByWinnerId[$winnerId] ?? [],
        ];
    }

    if ($winners === [] && $eligibleApplications !== []) {
        $winners[] = [
            'form_key' => 'new_0',
            'id' => null,
            'application_id' => '',
            'display_order' => '',
            'public_title' => '',
            'description' => '',
            'delete' => false,
            'pending_new_images' => [],
            'existing_images' => [],
        ];
    }

    return $winners;
}

$rolePermissionManager = new RolePermissionManager($pdo);
if (
    !isset($_SESSION['user_id'])
    || !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_UPDATE']
    )
) {
    header('Location: index.php');
    exit();
}

$callId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$callId) {
    header('Location: call_for_proposals.php');
    exit();
}

$publicationStatusColumnExists = callForProposalWinnerPublicationStatusColumnExists($pdo);
$callStatusSelect = $publicationStatusColumnExists ? ', winner_publication_status' : '';
$callStmt = $pdo->prepare('SELECT id, title, status' . $callStatusSelect . ' FROM call_for_proposal WHERE id = :id');
$callStmt->execute([':id' => $callId]);
$call = $callStmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: call_for_proposals.php');
    exit();
}

if (($call['status'] ?? '') !== 'CLOSED') {
    $_SESSION['call_for_proposal_winner_message'] = [
        'type' => 'error',
        'text' => 'Puoi configurare i vincitori solo per i bandi chiusi.',
    ];
    header('Location: call_for_proposals.php');
    exit();
}

$message = $_SESSION['call_for_proposal_winner_message'] ?? null;
unset($_SESSION['call_for_proposal_winner_message']);

$formError = $_SESSION['call_for_proposal_winner_form_error'] ?? null;
unset($_SESSION['call_for_proposal_winner_form_error']);

$formData = $_SESSION['call_for_proposal_winner_form_data'] ?? null;
unset($_SESSION['call_for_proposal_winner_form_data']);

$winnerPublicationStatus = $publicationStatusColumnExists
    ? (string) ($call['winner_publication_status'] ?? 'DRAFT')
    : 'PUBLISHED';
if (
    is_array($formData)
    && isset($formData['publication_status'])
    && in_array((string) $formData['publication_status'], ['DRAFT', 'PUBLISHED'], true)
) {
    $winnerPublicationStatus = (string) $formData['publication_status'];
}

$eligibleApplicationsStmt = $pdo->prepare(
    'SELECT a.id, a.project_name, o.name AS organization_name '
    . 'FROM application a '
    . 'JOIN organization o ON o.id = a.organization_id '
    . 'WHERE a.call_for_proposal_id = :call_id AND a.status = "FINAL_VALIDATION" '
    . 'ORDER BY o.name ASC, a.project_name ASC'
);
$eligibleApplicationsStmt->execute([':call_id' => $callId]);
$eligibleApplications = $eligibleApplicationsStmt->fetchAll(PDO::FETCH_ASSOC);

$winnersStmt = $pdo->prepare(
    'SELECT w.id, w.application_id, w.display_order, w.public_title, w.description, '
    . 'a.project_name, o.name AS organization_name '
    . 'FROM call_for_proposal_winner w '
    . 'JOIN application a ON a.id = w.application_id '
    . 'JOIN organization o ON o.id = a.organization_id '
    . 'WHERE w.call_for_proposal_id = :call_id '
    . 'ORDER BY w.display_order ASC, w.id ASC'
);
$winnersStmt->execute([':call_id' => $callId]);
$dbWinners = $winnersStmt->fetchAll(PDO::FETCH_ASSOC);

$winnerIds = array_map(static fn (array $winner): int => (int) $winner['id'], $dbWinners);
$imagesByWinnerId = [];
if ($winnerIds !== []) {
    $imagesStmt = $pdo->prepare(
        'SELECT id, winner_id, alt_text, caption, display_order '
        . 'FROM call_for_proposal_winner_image '
        . 'WHERE winner_id IN (' . implode(',', array_fill(0, count($winnerIds), '?')) . ') '
        . 'ORDER BY winner_id ASC, display_order ASC, id ASC'
    );
    $imagesStmt->execute($winnerIds);
    foreach ($imagesStmt->fetchAll(PDO::FETCH_ASSOC) as $image) {
        $winnerId = (int) $image['winner_id'];
        if (!isset($imagesByWinnerId[$winnerId])) {
            $imagesByWinnerId[$winnerId] = [];
        }
        $imagesByWinnerId[$winnerId][] = $image;
    }
}

$winnerForms = buildWinnerFormState($eligibleApplications, $dbWinners, $imagesByWinnerId, $formData);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Gestione Vincitori</title>
</head>
<body class="management-page management-page--scroll">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Vincitori - <?php echo htmlspecialchars($call['title']); ?></h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="button-container">
                    <a href="call_for_proposals.php" class="page-button back-button">Indietro</a>
                    <?php if ($eligibleApplications !== []): ?>
                        <button type="button" class="page-button" data-add-winner>Aggiungi vincitore</button>
                    <?php endif; ?>
                    <button type="button" class="page-button secondary-button" data-save-draft>Salva bozza browser</button>
                    <button type="button" class="page-button secondary-button" data-restore-draft>Ripristina bozza browser</button>
                    <button type="button" class="page-button secondary-button" data-clear-draft>Cancella bozza browser</button>
                    <?php if ($dbWinners !== []): ?>
                        <a href="call_for_proposal_winners.php?id=<?php echo urlencode((string) $callId); ?>" class="page-button secondary-button" target="_blank" rel="noopener noreferrer">Anteprima pubblica</a>
                    <?php endif; ?>
                </div>
                <p class="winner-draft-note">La bozza locale salva campi, ordine e struttura della pagina, ma non i file selezionati nei campi immagine.</p>
                <div class="winner-draft-status" data-draft-status hidden></div>

                <?php if ($message !== null && isset($message['text'])): ?>
                    <div class="message <?php echo ($message['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>" style="display:block;">
                        <?php echo htmlspecialchars((string) $message['text']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($formError !== null): ?>
                    <div class="flash-message" style="margin-bottom:1.5rem;padding:1rem;border-radius:8px;background-color:#fdecea;color:#611a15;">
                        <?php echo htmlspecialchars((string) $formError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($eligibleApplications === [] && $dbWinners === []): ?>
                    <p>Non ci sono candidature in convalida definitiva per questo bando. I vincitori possono essere selezionati solo tra le candidature validate.</p>
                <?php else: ?>
                    <form action="call_for_proposal_winner_bulk_save.php" method="POST" enctype="multipart/form-data" data-winners-form>
                        <input type="hidden" name="call_id" value="<?php echo htmlspecialchars((string) $callId); ?>">
                        <input type="hidden" name="publication_status" value="<?php echo htmlspecialchars($winnerPublicationStatus); ?>" data-publication-status-input>

                        <?php if ($publicationStatusColumnExists): ?>
                            <section class="winner-publication-panel">
                                <div>
                                    <h2>Stato pubblicazione</h2>
                                    <p class="winner-publication-panel__text">
                                        <?php echo $winnerPublicationStatus === 'PUBLISHED'
                                            ? 'I vincitori sono attualmente pubblicati sul sito pubblico.'
                                            : 'I vincitori sono in bozza e non sono ancora visibili sul sito pubblico.'; ?>
                                    </p>
                                </div>
                                <span class="winner-publication-badge <?php echo $winnerPublicationStatus === 'PUBLISHED' ? 'is-published' : 'is-draft'; ?>" data-publication-badge>
                                    <?php echo $winnerPublicationStatus === 'PUBLISHED' ? 'Pubblicato' : 'Bozza'; ?>
                                </span>
                            </section>
                        <?php endif; ?>

                        <div class="winner-config-stack" data-winner-stack data-next-new-index="<?php echo htmlspecialchars((string) count($winnerForms)); ?>">
                            <?php foreach ($winnerForms as $winner): ?>
                                <?php
                                    $formKey = (string) $winner['form_key'];
                                    $winnerId = $winner['id'] !== null ? (int) $winner['id'] : null;
                                    $heading = trim((string) $winner['display_order']) !== ''
                                        ? 'Vincitore ' . $winner['display_order']
                                        : 'Nuovo vincitore';
                                ?>
                                <section class="winner-config-card" data-winner-card draggable="true" id="winner-form-<?php echo htmlspecialchars($formKey); ?>">
                                    <input type="hidden" name="winners[<?php echo htmlspecialchars($formKey); ?>][id]" value="<?php echo htmlspecialchars((string) ($winnerId ?? '')); ?>">
                                    <input type="hidden" name="winners[<?php echo htmlspecialchars($formKey); ?>][form_key]" value="<?php echo htmlspecialchars($formKey); ?>">
                                    <div class="winner-config-card__header">
                                        <div>
                                            <h2 data-role="winner-heading"><?php echo htmlspecialchars($heading); ?></h2>
                                            <?php if ($winnerId !== null): ?>
                                                <p class="winner-config-card__summary">
                                                    <?php echo htmlspecialchars((string) $winner['organization_name']); ?> - <?php echo htmlspecialchars((string) $winner['project_name']); ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="winner-config-card__summary">Configura un nuovo vincitore e salvalo insieme agli altri.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="winner-config-card__actions">
                                            <button type="button" class="page-button secondary-button" data-move-winner-up>Su</button>
                                            <button type="button" class="page-button secondary-button" data-move-winner-down>Giu</button>
                                            <?php if ($winnerId !== null): ?>
                                                <label class="winner-config-card__delete-toggle">
                                                    <input type="checkbox" name="winners[<?php echo htmlspecialchars($formKey); ?>][delete]" value="1" <?php echo !empty($winner['delete']) ? 'checked' : ''; ?> data-role="delete-toggle">
                                                    Elimina vincitore
                                                </label>
                                            <?php else: ?>
                                                <button type="button" class="delete-btn" data-remove-new-winner><i class="fas fa-trash"></i> Rimuovi</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label required" for="application_id_<?php echo htmlspecialchars($formKey); ?>">Candidatura</label>
                                        <select id="application_id_<?php echo htmlspecialchars($formKey); ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][application_id]" class="form-input" required>
                                            <option value="">Seleziona una candidatura</option>
                                            <?php foreach ($eligibleApplications as $application): ?>
                                                <?php $applicationId = (int) $application['id']; ?>
                                                <option value="<?php echo htmlspecialchars((string) $applicationId); ?>" <?php echo (int) ($winner['application_id'] ?? 0) === $applicationId ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($application['organization_name'] . ' - ' . $application['project_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label required" for="display_order_<?php echo htmlspecialchars($formKey); ?>">Posizione</label>
                                        <input type="number" min="1" id="display_order_<?php echo htmlspecialchars($formKey); ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][display_order]" class="form-input" required value="<?php echo htmlspecialchars((string) $winner['display_order']); ?>" data-role="display-order">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label required" for="public_title_<?php echo htmlspecialchars($formKey); ?>">Titolo pubblico</label>
                                        <input type="text" id="public_title_<?php echo htmlspecialchars($formKey); ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][public_title]" class="form-input" required value="<?php echo htmlspecialchars((string) $winner['public_title']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label required" for="description_<?php echo htmlspecialchars($formKey); ?>">Descrizione</label>
                                        <textarea id="description_<?php echo htmlspecialchars($formKey); ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][description]" class="form-input" required><?php echo htmlspecialchars((string) $winner['description']); ?></textarea>
                                    </div>

                                    <?php if ($winnerId !== null): ?>
                                        <div class="form-group">
                                            <label class="form-label">Immagini correnti</label>
                                            <?php if ($winner['existing_images'] === []): ?>
                                                <p class="text-muted">Nessuna immagine caricata.</p>
                                            <?php else: ?>
                                                <div class="winner-image-admin-list">
                                                    <?php foreach ($winner['existing_images'] as $image): ?>
                                                        <?php $imageId = (int) $image['id']; ?>
                                                        <div class="winner-image-admin-card" data-existing-image-card draggable="true">
                                                            <img src="call_for_proposal_winner_image.php?id=<?php echo urlencode((string) $imageId); ?>" alt="<?php echo htmlspecialchars((string) $image['alt_text']); ?>" class="winner-image-admin-card__preview">
                                                            <div class="winner-image-admin-card__fields">
                                                                <div class="form-group">
                                                                    <label class="form-label" for="existing_image_order_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>">Ordine</label>
                                                                    <input type="number" min="1" id="existing_image_order_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][existing_images][<?php echo $imageId; ?>][display_order]" class="form-input" value="<?php echo htmlspecialchars((string) $image['display_order']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label" for="existing_image_alt_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>">Testo alternativo</label>
                                                                    <input type="text" id="existing_image_alt_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][existing_images][<?php echo $imageId; ?>][alt_text]" class="form-input" value="<?php echo htmlspecialchars((string) $image['alt_text']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label" for="existing_image_caption_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>">Didascalia</label>
                                                                    <input type="text" id="existing_image_caption_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageId; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][existing_images][<?php echo $imageId; ?>][caption]" class="form-input" value="<?php echo htmlspecialchars((string) ($image['caption'] ?? '')); ?>">
                                                                </div>
                                                                <label class="winner-image-admin-card__delete">
                                                                    <input type="checkbox" name="winners[<?php echo htmlspecialchars($formKey); ?>][existing_images][<?php echo $imageId; ?>][delete]" value="1" <?php echo !empty($image['delete']) ? 'checked' : ''; ?>>
                                                                    Elimina immagine
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <div class="winner-new-images__header">
                                            <label class="form-label">Nuove immagini (opzionali)</label>
                                            <button type="button" class="page-button secondary-button winner-new-images__add" data-add-image-row>Aggiungi immagine</button>
                                        </div>
                                        <div class="winner-new-images" data-image-stack data-next-image-index="<?php echo htmlspecialchars((string) count($winner['pending_new_images'])); ?>">
                                            <?php foreach ($winner['pending_new_images'] as $imageIndex => $pendingImage): ?>
                                                <div class="winner-new-image-row" data-image-row draggable="true">
                                                    <div class="form-group">
                                                        <label class="form-label" for="new_image_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>">File immagine</label>
                                                        <input type="file" id="new_image_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>" name="new_images[<?php echo htmlspecialchars($formKey); ?>][<?php echo $imageIndex; ?>]" class="form-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="new_image_display_order_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>">Ordine</label>
                                                        <input type="number" min="1" id="new_image_display_order_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][new_images_meta][<?php echo $imageIndex; ?>][display_order]" class="form-input" value="<?php echo htmlspecialchars((string) ($pendingImage['display_order'] ?? '')); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="new_image_alt_text_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>">Testo alternativo</label>
                                                        <input type="text" id="new_image_alt_text_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][new_images_meta][<?php echo $imageIndex; ?>][alt_text]" class="form-input" value="<?php echo htmlspecialchars((string) ($pendingImage['alt_text'] ?? '')); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="new_image_caption_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>">Didascalia</label>
                                                        <input type="text" id="new_image_caption_<?php echo htmlspecialchars($formKey); ?>_<?php echo $imageIndex; ?>" name="winners[<?php echo htmlspecialchars($formKey); ?>][new_images_meta][<?php echo $imageIndex; ?>][caption]" class="form-input" value="<?php echo htmlspecialchars((string) ($pendingImage['caption'] ?? '')); ?>">
                                                    </div>
                                                    <div class="winner-new-image-row__actions">
                                                        <button type="button" class="page-button secondary-button" data-move-image-up>Su</button>
                                                        <button type="button" class="page-button secondary-button" data-move-image-down>Giu</button>
                                                        <button type="button" class="delete-btn" data-remove-image-row><i class="fas fa-trash"></i> Rimuovi</button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <template data-image-row-template>
                                            <div class="winner-new-image-row" data-image-row draggable="true">
                                                <div class="form-group">
                                                    <label class="form-label" for="new_image___FORM_KEY____IMAGE_INDEX__">File immagine</label>
                                                    <input type="file" id="new_image___FORM_KEY____IMAGE_INDEX__" name="new_images[__FORM_KEY__][__IMAGE_INDEX__]" class="form-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label" for="new_image_display_order___FORM_KEY____IMAGE_INDEX__">Ordine</label>
                                                    <input type="number" min="1" id="new_image_display_order___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][display_order]" class="form-input" value="">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label" for="new_image_alt_text___FORM_KEY____IMAGE_INDEX__">Testo alternativo</label>
                                                    <input type="text" id="new_image_alt_text___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][alt_text]" class="form-input" value="">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label" for="new_image_caption___FORM_KEY____IMAGE_INDEX__">Didascalia</label>
                                                    <input type="text" id="new_image_caption___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][caption]" class="form-input" value="">
                                                </div>
                                                <div class="winner-new-image-row__actions">
                                                    <button type="button" class="page-button secondary-button" data-move-image-up>Su</button>
                                                    <button type="button" class="page-button secondary-button" data-move-image-down>Giu</button>
                                                    <button type="button" class="delete-btn" data-remove-image-row><i class="fas fa-trash"></i> Rimuovi</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>

                        <div class="button-container winner-config-footer">
                            <?php if ($publicationStatusColumnExists): ?>
                                <button type="submit" class="page-button secondary-button" data-submit-publication-status="DRAFT">Salva bozza</button>
                                <button type="submit" class="page-button" data-submit-publication-status="PUBLISHED">Pubblica vincitori</button>
                            <?php else: ?>
                                <button type="submit" class="page-button">Salva tutti i vincitori</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <template id="winner-card-template">
                        <section class="winner-config-card" data-winner-card draggable="true" id="winner-form-__KEY__">
                            <input type="hidden" name="winners[__KEY__][id]" value="">
                            <input type="hidden" name="winners[__KEY__][form_key]" value="__KEY__">
                            <div class="winner-config-card__header">
                                <div>
                                    <h2 data-role="winner-heading">Nuovo vincitore</h2>
                                    <p class="winner-config-card__summary">Configura un nuovo vincitore e salvalo insieme agli altri.</p>
                                </div>
                                <div class="winner-config-card__actions">
                                    <button type="button" class="page-button secondary-button" data-move-winner-up>Su</button>
                                    <button type="button" class="page-button secondary-button" data-move-winner-down>Giu</button>
                                    <button type="button" class="delete-btn" data-remove-new-winner><i class="fas fa-trash"></i> Rimuovi</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="application_id___KEY__">Candidatura</label>
                                <select id="application_id___KEY__" name="winners[__KEY__][application_id]" class="form-input" required>
                                    <option value="">Seleziona una candidatura</option>
                                    <?php foreach ($eligibleApplications as $application): ?>
                                        <option value="<?php echo htmlspecialchars((string) ((int) $application['id'])); ?>">
                                            <?php echo htmlspecialchars($application['organization_name'] . ' - ' . $application['project_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="display_order___KEY__">Posizione</label>
                                <input type="number" min="1" id="display_order___KEY__" name="winners[__KEY__][display_order]" class="form-input" required value="" data-role="display-order">
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="public_title___KEY__">Titolo pubblico</label>
                                <input type="text" id="public_title___KEY__" name="winners[__KEY__][public_title]" class="form-input" required value="">
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="description___KEY__">Descrizione</label>
                                <textarea id="description___KEY__" name="winners[__KEY__][description]" class="form-input" required></textarea>
                            </div>

                            <div class="form-group">
                                <div class="winner-new-images__header">
                                    <label class="form-label">Nuove immagini (opzionali)</label>
                                    <button type="button" class="page-button secondary-button winner-new-images__add" data-add-image-row>Aggiungi immagine</button>
                                </div>
                                <div class="winner-new-images" data-image-stack data-next-image-index="0"></div>
                                <template data-image-row-template>
                                    <div class="winner-new-image-row" data-image-row draggable="true">
                                        <div class="form-group">
                                            <label class="form-label" for="new_image___FORM_KEY____IMAGE_INDEX__">File immagine</label>
                                            <input type="file" id="new_image___FORM_KEY____IMAGE_INDEX__" name="new_images[__FORM_KEY__][__IMAGE_INDEX__]" class="form-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="new_image_display_order___FORM_KEY____IMAGE_INDEX__">Ordine</label>
                                            <input type="number" min="1" id="new_image_display_order___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][display_order]" class="form-input" value="">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="new_image_alt_text___FORM_KEY____IMAGE_INDEX__">Testo alternativo</label>
                                            <input type="text" id="new_image_alt_text___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][alt_text]" class="form-input" value="">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="new_image_caption___FORM_KEY____IMAGE_INDEX__">Didascalia</label>
                                            <input type="text" id="new_image_caption___FORM_KEY____IMAGE_INDEX__" name="winners[__FORM_KEY__][new_images_meta][__IMAGE_INDEX__][caption]" class="form-input" value="">
                                        </div>
                                        <div class="winner-new-image-row__actions">
                                            <button type="button" class="page-button secondary-button" data-move-image-up>Su</button>
                                            <button type="button" class="page-button secondary-button" data-move-image-down>Giu</button>
                                            <button type="button" class="delete-btn" data-remove-image-row><i class="fas fa-trash"></i> Rimuovi</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </template>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-winners-form]');
        const stack = document.querySelector('[data-winner-stack]');
        const addWinnerButton = document.querySelector('[data-add-winner]');
        const template = document.getElementById('winner-card-template');
        const saveDraftButton = document.querySelector('[data-save-draft]');
        const restoreDraftButton = document.querySelector('[data-restore-draft]');
        const clearDraftButton = document.querySelector('[data-clear-draft]');
        const draftStatus = document.querySelector('[data-draft-status]');
        const publicationStatusInput = document.querySelector('[data-publication-status-input]');
        const publicationBadge = document.querySelector('[data-publication-badge]');
        const publicationButtons = document.querySelectorAll('[data-submit-publication-status]');
        const draftKey = 'call_for_proposal_winners_draft_<?php echo htmlspecialchars((string) $callId, ENT_QUOTES); ?>';

        if (!form || !stack || !template || !addWinnerButton) {
            return;
        }

        let nextIndex = Number.parseInt(stack.getAttribute('data-next-new-index') || '0', 10);
        if (!Number.isFinite(nextIndex) || nextIndex < 0) {
            nextIndex = 0;
        }
        let initialSnapshot = '';

        const showDraftStatus = (message, isError = false) => {
            if (!draftStatus) {
                return;
            }

            draftStatus.hidden = false;
            draftStatus.textContent = message;
            draftStatus.classList.toggle('is-error', isError);
            draftStatus.classList.toggle('is-success', !isError);
        };

        const hideDraftStatus = () => {
            if (!draftStatus) {
                return;
            }

            draftStatus.hidden = true;
            draftStatus.textContent = '';
            draftStatus.classList.remove('is-error', 'is-success');
        };

        const updatePublicationBadge = () => {
            if (!publicationStatusInput || !publicationBadge) {
                return;
            }

            const isPublished = publicationStatusInput.value === 'PUBLISHED';
            publicationBadge.textContent = isPublished ? 'Pubblicato' : 'Bozza';
            publicationBadge.classList.toggle('is-published', isPublished);
            publicationBadge.classList.toggle('is-draft', !isPublished);
        };

        const getControlsSnapshot = () => {
            const controls = Array.from(form.elements).filter(element => {
                return element instanceof HTMLElement
                    && 'name' in element
                    && typeof element.name === 'string'
                    && element.name !== ''
                    && !(element instanceof HTMLInputElement && element.type === 'file');
            });

            return controls.map(element => {
                if (element instanceof HTMLInputElement && element.type === 'checkbox') {
                    return {
                        name: element.name,
                        type: 'checkbox',
                        checked: element.checked,
                    };
                }

                return {
                    name: element.name,
                    type: 'value',
                    value: 'value' in element ? element.value : '',
                };
            });
        };

        const getFormSnapshot = () => JSON.stringify(getControlsSnapshot());

        const getControlsByName = name => Array.from(form.elements).filter(element => {
            return element instanceof HTMLElement && 'name' in element && element.name === name;
        });

        const updateDraftButtons = () => {
            const hasDraft = localStorage.getItem(draftKey) !== null;
            if (restoreDraftButton) {
                restoreDraftButton.disabled = !hasDraft;
            }
            if (clearDraftButton) {
                clearDraftButton.disabled = !hasDraft;
            }
        };

        const saveDraft = () => {
            const payload = {
                savedAt: new Date().toISOString(),
                stackHtml: stack.innerHTML,
                nextWinnerIndex: stack.getAttribute('data-next-new-index') || String(nextIndex),
                publicationStatus: publicationStatusInput ? publicationStatusInput.value : '',
                controls: getControlsSnapshot(),
            };
            localStorage.setItem(draftKey, JSON.stringify(payload));
            updateDraftButtons();
            showDraftStatus('Bozza salvata localmente.');
        };

        const restoreDraft = () => {
            const rawDraft = localStorage.getItem(draftKey);
            if (!rawDraft) {
                showDraftStatus('Nessuna bozza disponibile da ripristinare.', true);
                return false;
            }

            try {
                const draft = JSON.parse(rawDraft);
                if (!draft || typeof draft.stackHtml !== 'string' || !Array.isArray(draft.controls)) {
                    throw new Error('invalid draft');
                }

                stack.innerHTML = draft.stackHtml;
                stack.setAttribute('data-next-new-index', String(draft.nextWinnerIndex ?? nextIndex));
                nextIndex = Number.parseInt(stack.getAttribute('data-next-new-index') || '0', 10);
                if (!Number.isFinite(nextIndex) || nextIndex < 0) {
                    nextIndex = 0;
                }
                if (publicationStatusInput && (draft.publicationStatus === 'DRAFT' || draft.publicationStatus === 'PUBLISHED')) {
                    publicationStatusInput.value = draft.publicationStatus;
                    updatePublicationBadge();
                }

                draft.controls.forEach(controlState => {
                    if (!controlState || typeof controlState.name !== 'string') {
                        return;
                    }

                    const controls = getControlsByName(controlState.name);
                    controls.forEach(control => {
                        if (control instanceof HTMLInputElement && control.type === 'checkbox') {
                            control.checked = Boolean(controlState.checked);
                            return;
                        }

                        if ('value' in control && typeof controlState.value === 'string') {
                            control.value = controlState.value;
                        }
                    });
                });

                stack.querySelectorAll('[data-winner-card]').forEach(attachCardEvents);
                refreshCardHeadings();
                initialSnapshot = getFormSnapshot();
                updateDraftButtons();
                showDraftStatus('Bozza ripristinata. Ricorda di ricaricare i file immagine se necessario.');
                return true;
            } catch (error) {
                showDraftStatus('La bozza salvata non è valida e non può essere ripristinata.', true);
                return false;
            }
        };

        const getActiveWinnerCards = () => Array.from(stack.querySelectorAll('[data-winner-card]')).filter(card => {
            const deleteToggle = card.querySelector('[data-role="delete-toggle"]');
            return !(deleteToggle && deleteToggle.checked);
        });

        const makeDraggableSort = ({ container, itemSelector, onSorted }) => {
            if (!container) {
                return;
            }

            let draggedItem = null;

            const getItems = () => Array.from(container.querySelectorAll(itemSelector));

            const clearDragClasses = () => {
                getItems().forEach(item => {
                    item.classList.remove('is-dragging', 'drag-over-before', 'drag-over-after');
                });
            };

            const getDropTarget = (clientY, currentTarget) => {
                const candidates = getItems().filter(item => item !== draggedItem);
                let target = null;

                for (const item of candidates) {
                    const rect = item.getBoundingClientRect();
                    if (clientY < rect.top + rect.height / 2) {
                        target = { element: item, position: 'before' };
                        break;
                    }
                }

                if (target !== null) {
                    return target;
                }

                if (currentTarget && currentTarget !== draggedItem) {
                    return { element: currentTarget, position: 'after' };
                }

                return null;
            };

            container.addEventListener('dragstart', event => {
                const item = event.target instanceof Element ? event.target.closest(itemSelector) : null;
                if (!item) {
                    return;
                }

                draggedItem = item;
                item.classList.add('is-dragging');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', '');
                }
            });

            container.addEventListener('dragend', () => {
                clearDragClasses();
                draggedItem = null;
                if (typeof onSorted === 'function') {
                    onSorted();
                }
            });

            container.addEventListener('dragover', event => {
                if (!draggedItem) {
                    return;
                }

                event.preventDefault();
                const currentTarget = event.target instanceof Element ? event.target.closest(itemSelector) : null;
                clearDragClasses();
                draggedItem.classList.add('is-dragging');

                const dropTarget = getDropTarget(event.clientY, currentTarget);
                if (!dropTarget) {
                    return;
                }

                dropTarget.element.classList.add(
                    dropTarget.position === 'before' ? 'drag-over-before' : 'drag-over-after'
                );
            });

            container.addEventListener('drop', event => {
                if (!draggedItem) {
                    return;
                }

                event.preventDefault();
                const currentTarget = event.target instanceof Element ? event.target.closest(itemSelector) : null;
                const dropTarget = getDropTarget(event.clientY, currentTarget);

                clearDragClasses();

                if (!dropTarget) {
                    container.appendChild(draggedItem);
                } else if (dropTarget.position === 'before') {
                    container.insertBefore(draggedItem, dropTarget.element);
                } else if (dropTarget.element.nextElementSibling) {
                    container.insertBefore(draggedItem, dropTarget.element.nextElementSibling);
                } else {
                    container.appendChild(draggedItem);
                }

                if (typeof onSorted === 'function') {
                    onSorted();
                }
            });
        };

        const refreshCardHeadings = () => {
            getActiveWinnerCards().forEach((card, index) => {
                const heading = card.querySelector('[data-role="winner-heading"]');
                const orderInput = card.querySelector('[data-role="display-order"]');
                if (!heading || !orderInput) {
                    return;
                }

                const nextOrder = String(index + 1);
                orderInput.value = nextOrder;
                heading.textContent = 'Vincitore ' + nextOrder;
            });
        };

        const attachImageStackEvents = card => {
            const imageStack = card.querySelector('[data-image-stack]');
            const imageTemplate = card.querySelector('[data-image-row-template]');
            const addImageButton = card.querySelector('[data-add-image-row]');
            const existingImageList = card.querySelector('.winner-image-admin-list');
            const formKeyInput = card.querySelector('input[name$="[form_key]"]');
            const formKey = formKeyInput ? formKeyInput.value : '';

            if (!imageStack || !imageTemplate || !addImageButton || formKey === '') {
                return;
            }

            const syncImageOrders = () => {
                const imageRows = Array.from(imageStack.querySelectorAll('[data-image-row]'));
                imageRows.forEach((row, index) => {
                    const orderInput = row.querySelector('input[type="number"]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
            };

            const attachImageRowEvents = row => {
                const removeImageButton = row.querySelector('[data-remove-image-row]');
                if (removeImageButton) {
                    removeImageButton.addEventListener('click', () => {
                        row.remove();
                        syncImageOrders();
                    });
                }

                const moveUpButton = row.querySelector('[data-move-image-up]');
                if (moveUpButton) {
                    moveUpButton.addEventListener('click', () => {
                        const previousRow = row.previousElementSibling;
                        if (previousRow) {
                            imageStack.insertBefore(row, previousRow);
                            syncImageOrders();
                        }
                    });
                }

                const moveDownButton = row.querySelector('[data-move-image-down]');
                if (moveDownButton) {
                    moveDownButton.addEventListener('click', () => {
                        const nextRow = row.nextElementSibling;
                        if (nextRow) {
                            imageStack.insertBefore(nextRow, row);
                            syncImageOrders();
                        }
                    });
                }
            };

            imageStack.querySelectorAll('[data-image-row]').forEach(attachImageRowEvents);
            syncImageOrders();
            makeDraggableSort({
                container: imageStack,
                itemSelector: '[data-image-row]',
                onSorted: syncImageOrders,
            });

            if (existingImageList) {
                const syncExistingImageOrders = () => {
                    const existingCards = Array.from(existingImageList.querySelectorAll('[data-existing-image-card]'));
                    existingCards.forEach((imageCard, index) => {
                        const orderInput = imageCard.querySelector('input[type="number"]');
                        if (orderInput) {
                            orderInput.value = String(index + 1);
                        }
                    });
                };

                makeDraggableSort({
                    container: existingImageList,
                    itemSelector: '[data-existing-image-card]',
                    onSorted: syncExistingImageOrders,
                });
                syncExistingImageOrders();
            }

            addImageButton.addEventListener('click', () => {
                let nextImageIndex = Number.parseInt(imageStack.getAttribute('data-next-image-index') || '0', 10);
                if (!Number.isFinite(nextImageIndex) || nextImageIndex < 0) {
                    nextImageIndex = 0;
                }

                imageStack.setAttribute('data-next-image-index', String(nextImageIndex + 1));
                const html = imageTemplate.innerHTML
                    .replaceAll('__FORM_KEY__', formKey)
                    .replaceAll('__IMAGE_INDEX__', String(nextImageIndex));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const row = wrapper.firstElementChild;
                if (!row) {
                    return;
                }

                imageStack.appendChild(row);
                attachImageRowEvents(row);
                syncImageOrders();
                hideDraftStatus();
            });
        };

        const attachCardEvents = card => {
            const removeButton = card.querySelector('[data-remove-new-winner]');
            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    card.remove();
                    refreshCardHeadings();
                    hideDraftStatus();
                });
            }

            const moveUpButton = card.querySelector('[data-move-winner-up]');
            if (moveUpButton) {
                moveUpButton.addEventListener('click', () => {
                    const previousCard = card.previousElementSibling;
                    if (previousCard) {
                        stack.insertBefore(card, previousCard);
                        refreshCardHeadings();
                        hideDraftStatus();
                    }
                });
            }

            const moveDownButton = card.querySelector('[data-move-winner-down]');
            if (moveDownButton) {
                moveDownButton.addEventListener('click', () => {
                    const nextCard = card.nextElementSibling;
                    if (nextCard) {
                        stack.insertBefore(nextCard, card);
                        refreshCardHeadings();
                        hideDraftStatus();
                    }
                });
            }

            const orderInput = card.querySelector('[data-role="display-order"]');
            if (orderInput) {
                orderInput.addEventListener('input', () => {
                    const normalizedValue = String(orderInput.value || '').trim();
                    const heading = card.querySelector('[data-role="winner-heading"]');
                    if (heading) {
                        heading.textContent = normalizedValue !== '' ? 'Vincitore ' + normalizedValue : 'Nuovo vincitore';
                    }
                });
            }

            const deleteToggle = card.querySelector('[data-role="delete-toggle"]');
            if (deleteToggle) {
                deleteToggle.addEventListener('change', refreshCardHeadings);
            }

            attachImageStackEvents(card);
        };

        stack.querySelectorAll('[data-winner-card]').forEach(attachCardEvents);
        makeDraggableSort({
            container: stack,
            itemSelector: '[data-winner-card]',
            onSorted: refreshCardHeadings,
        });
        refreshCardHeadings();
        updatePublicationBadge();
        updateDraftButtons();
        initialSnapshot = getFormSnapshot();

        addWinnerButton.addEventListener('click', () => {
            const key = 'new_' + nextIndex;
            nextIndex += 1;
            const html = template.innerHTML.replaceAll('__KEY__', key);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const card = wrapper.firstElementChild;
            if (!card) {
                return;
            }

            stack.appendChild(card);
            attachCardEvents(card);
            refreshCardHeadings();
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            hideDraftStatus();
        });

        if (saveDraftButton) {
            saveDraftButton.addEventListener('click', saveDraft);
        }

        if (restoreDraftButton) {
            restoreDraftButton.addEventListener('click', restoreDraft);
        }

        if (clearDraftButton) {
            clearDraftButton.addEventListener('click', () => {
                localStorage.removeItem(draftKey);
                updateDraftButtons();
                showDraftStatus('Bozza locale cancellata.');
            });
        }

        publicationButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (!publicationStatusInput) {
                    return;
                }

                publicationStatusInput.value = button.getAttribute('data-submit-publication-status') || 'DRAFT';
                updatePublicationBadge();
            });
        });

        form.addEventListener('submit', () => {
            localStorage.removeItem(draftKey);
            updateDraftButtons();
        });

        window.addEventListener('beforeunload', event => {
            if (getFormSnapshot() === initialSnapshot) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });
    });
</script>
</body>
</html>
