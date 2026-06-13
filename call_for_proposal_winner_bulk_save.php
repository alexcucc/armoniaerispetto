<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';
require_once 'call_for_proposal_winner_utils.php';

function redirectWinnerBulkFormWithError(int $callId, string $message): void
{
    $_SESSION['call_for_proposal_winner_form_error'] = $message;
    $_SESSION['call_for_proposal_winner_form_data'] = $_POST;
    header('Location: call_for_proposal_winners_manage.php?id=' . urlencode((string) $callId));
    exit();
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: call_for_proposals.php');
    exit();
}

$callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$callId) {
    header('Location: call_for_proposals.php');
    exit();
}

$publicationStatusColumnExists = callForProposalWinnerPublicationStatusColumnExists($pdo);
$publicationStatus = strtoupper(trim((string) ($_POST['publication_status'] ?? 'DRAFT')));
if (!in_array($publicationStatus, ['DRAFT', 'PUBLISHED'], true)) {
    $publicationStatus = 'DRAFT';
}

$rawWinners = $_POST['winners'] ?? [];
if (!is_array($rawWinners)) {
    redirectWinnerBulkFormWithError($callId, 'Formato vincitori non valido.');
}

$callStatusSelect = $publicationStatusColumnExists ? ', winner_publication_status' : '';
$callStmt = $pdo->prepare('SELECT id, status' . $callStatusSelect . ' FROM call_for_proposal WHERE id = :id');
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

$preparedWinners = [];
$usedApplicationIds = [];
$usedDisplayOrders = [];
$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

foreach ($rawWinners as $winnerKey => $winnerData) {
    if (!is_array($winnerData)) {
        continue;
    }

    $winnerId = isset($winnerData['id']) && ctype_digit((string) $winnerData['id']) ? (int) $winnerData['id'] : null;
    $deleteWinner = !empty($winnerData['delete']) && $winnerId !== null;

    $applicationId = filter_var($winnerData['application_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $displayOrder = filter_var($winnerData['display_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $publicTitle = trim((string) ($winnerData['public_title'] ?? ''));
    $description = trim((string) ($winnerData['description'] ?? ''));

    $existingImages = $winnerData['existing_images'] ?? [];
    if (!is_array($existingImages)) {
        $existingImages = [];
    }

    $newImagesMeta = $winnerData['new_images_meta'] ?? [];
    if (!is_array($newImagesMeta)) {
        $newImagesMeta = [];
    }

    $preparedNewImages = [];
    $newImagesFileInfo = $_FILES['new_images'] ?? null;
    foreach ($newImagesMeta as $imageKey => $imageMeta) {
        if (!is_array($imageMeta)) {
            continue;
        }

        $fileError = is_array($newImagesFileInfo)
            && isset($newImagesFileInfo['error'][$winnerKey][$imageKey])
            ? $newImagesFileInfo['error'][$winnerKey][$imageKey]
            : UPLOAD_ERR_NO_FILE;
        $fileUploaded = $fileError !== UPLOAD_ERR_NO_FILE;

        $displayOrderValue = trim((string) ($imageMeta['display_order'] ?? ''));
        $altTextValue = trim((string) ($imageMeta['alt_text'] ?? ''));
        $captionValue = trim((string) ($imageMeta['caption'] ?? ''));

        $isCompletelyEmptyImage = !$fileUploaded
            && $displayOrderValue === ''
            && $altTextValue === ''
            && $captionValue === '';
        if ($isCompletelyEmptyImage) {
            continue;
        }

        if (!$fileUploaded) {
            redirectWinnerBulkFormWithError($callId, 'Per aggiungere una nuova immagine devi caricare anche il file.');
        }

        $preparedNewImages[] = [
            'key' => (string) $imageKey,
            'display_order' => $displayOrderValue,
            'alt_text' => $altTextValue,
            'caption' => $captionValue,
        ];
    }

    $isCompletelyEmptyNewWinner = $winnerId === null
        && !$deleteWinner
        && $applicationId === false
        && $displayOrder === false
        && $publicTitle === ''
        && $description === ''
        && $preparedNewImages === [];

    if ($isCompletelyEmptyNewWinner) {
        continue;
    }

    if (!$deleteWinner) {
        if (!$applicationId || !$displayOrder || $publicTitle === '' || $description === '') {
            redirectWinnerBulkFormWithError($callId, 'Ogni vincitore non eliminato deve avere candidatura, posizione, titolo pubblico e descrizione.');
        }

        if (isset($usedApplicationIds[$applicationId])) {
            redirectWinnerBulkFormWithError($callId, 'La stessa candidatura non può essere usata due volte tra i vincitori.');
        }
        $usedApplicationIds[$applicationId] = true;

        if (isset($usedDisplayOrders[$displayOrder])) {
            redirectWinnerBulkFormWithError($callId, 'La posizione dei vincitori deve essere univoca.');
        }
        $usedDisplayOrders[$displayOrder] = true;
    }

    $preparedWinners[] = [
        'key' => (string) $winnerKey,
        'id' => $winnerId,
        'delete' => $deleteWinner,
        'application_id' => $applicationId ?: null,
        'display_order' => $displayOrder ?: null,
        'public_title' => $publicTitle,
        'description' => $description,
        'existing_images' => $existingImages,
        'new_images' => $preparedNewImages,
    ];
}

$applicationIdsToValidate = array_values(array_unique(array_filter(array_map(
    static fn (array $winner): ?int => $winner['delete'] ? null : $winner['application_id'],
    $preparedWinners
))));

if ($applicationIdsToValidate !== []) {
    $applicationCheckStmt = $pdo->prepare(
        'SELECT id FROM application WHERE call_for_proposal_id = ? AND status = "FINAL_VALIDATION" AND id IN ('
        . implode(',', array_fill(0, count($applicationIdsToValidate), '?')) . ')'
    );
    $applicationCheckStmt->execute(array_merge([$callId], $applicationIdsToValidate));
    $validApplicationIds = array_map('intval', $applicationCheckStmt->fetchAll(PDO::FETCH_COLUMN));
    sort($validApplicationIds);
    $expectedApplicationIds = $applicationIdsToValidate;
    sort($expectedApplicationIds);
    if ($validApplicationIds !== $expectedApplicationIds) {
        redirectWinnerBulkFormWithError($callId, 'Una o più candidature selezionate non appartengono a questo bando o non sono in convalida definitiva.');
    }
}

$newlyMovedFiles = [];
$filesToDeleteAfterCommit = [];
$directoriesToDeleteAfterCommit = [];

try {
    $pdo->beginTransaction();

    if ($publicationStatusColumnExists) {
        $updatePublicationStatusStmt = $pdo->prepare(
            'UPDATE call_for_proposal SET winner_publication_status = :publication_status WHERE id = :id'
        );
        $updatePublicationStatusStmt->execute([
            ':publication_status' => $publicationStatus,
            ':id' => $callId,
        ]);
    }

    $existingWinnersStmt = $pdo->prepare('SELECT id FROM call_for_proposal_winner WHERE call_for_proposal_id = :call_id');
    $existingWinnersStmt->execute([':call_id' => $callId]);
    $existingWinnerIds = array_map('intval', $existingWinnersStmt->fetchAll(PDO::FETCH_COLUMN));
    $existingWinnerIdLookup = array_fill_keys($existingWinnerIds, true);

    foreach ($preparedWinners as &$winner) {
        if ($winner['id'] !== null && !isset($existingWinnerIdLookup[$winner['id']])) {
            $pdo->rollBack();
            redirectWinnerBulkFormWithError($callId, 'Uno dei vincitori selezionati non esiste.');
        }

        if ($winner['delete']) {
            if ($winner['id'] !== null) {
                $deleteWinnerStmt = $pdo->prepare('DELETE FROM call_for_proposal_winner WHERE id = :id');
                $deleteWinnerStmt->execute([':id' => $winner['id']]);
                $directoriesToDeleteAfterCommit[] = getCallForProposalWinnerDirectory($callId, $winner['id']);
            }
            continue;
        }

        if ($winner['id'] !== null) {
            $updateWinnerStmt = $pdo->prepare(
                'UPDATE call_for_proposal_winner '
                . 'SET application_id = :application_id, display_order = :display_order, public_title = :public_title, description = :description '
                . 'WHERE id = :id'
            );
            $updateWinnerStmt->execute([
                ':application_id' => $winner['application_id'],
                ':display_order' => $winner['display_order'],
                ':public_title' => $winner['public_title'],
                ':description' => $winner['description'],
                ':id' => $winner['id'],
            ]);
        } else {
            $insertWinnerStmt = $pdo->prepare(
                'INSERT INTO call_for_proposal_winner (call_for_proposal_id, application_id, display_order, public_title, description) '
                . 'VALUES (:call_id, :application_id, :display_order, :public_title, :description)'
            );
            $insertWinnerStmt->execute([
                ':call_id' => $callId,
                ':application_id' => $winner['application_id'],
                ':display_order' => $winner['display_order'],
                ':public_title' => $winner['public_title'],
                ':description' => $winner['description'],
            ]);
            $winner['id'] = (int) $pdo->lastInsertId();
        }

        $currentImagesStmt = $pdo->prepare(
            'SELECT id, image_path, alt_text, caption, display_order '
            . 'FROM call_for_proposal_winner_image WHERE winner_id = :winner_id'
        );
        $currentImagesStmt->execute([':winner_id' => $winner['id']]);
        $currentImagesById = [];
        foreach ($currentImagesStmt->fetchAll(PDO::FETCH_ASSOC) as $currentImage) {
            $currentImagesById[(int) $currentImage['id']] = $currentImage;
        }

        $survivingImages = [];
        $deletedImageIds = [];
        $processedImageIds = [];

        foreach ($winner['existing_images'] as $imageId => $imageData) {
            $imageId = (int) $imageId;
            if (!isset($currentImagesById[$imageId]) || !is_array($imageData)) {
                continue;
            }

            $processedImageIds[$imageId] = true;
            $shouldDelete = !empty($imageData['delete']);
            if ($shouldDelete) {
                $deletedImageIds[] = $imageId;
                $filesToDeleteAfterCommit[] = (string) $currentImagesById[$imageId]['image_path'];
                continue;
            }

            $existingDisplayOrder = filter_var($imageData['display_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $existingAltText = trim((string) ($imageData['alt_text'] ?? ''));
            $existingCaption = trim((string) ($imageData['caption'] ?? ''));

            if (!$existingDisplayOrder || $existingAltText === '') {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Ogni immagine esistente deve avere ordine e testo alternativo.');
            }

            if (isset($survivingImages[$existingDisplayOrder])) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'L’ordine delle immagini deve essere univoco per ciascun vincitore.');
            }

            $survivingImages[$existingDisplayOrder] = [
                'id' => $imageId,
                'alt_text' => $existingAltText,
                'caption' => $existingCaption,
            ];
        }

        foreach ($currentImagesById as $imageId => $currentImage) {
            if (isset($processedImageIds[$imageId])) {
                continue;
            }

            $currentDisplayOrder = (int) ($currentImage['display_order'] ?? 0);
            if ($currentDisplayOrder < 1 || isset($survivingImages[$currentDisplayOrder])) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Le immagini correnti hanno un ordinamento non valido.');
            }

            $survivingImages[$currentDisplayOrder] = [
                'id' => $imageId,
                'alt_text' => (string) ($currentImage['alt_text'] ?? ''),
                'caption' => (string) ($currentImage['caption'] ?? ''),
            ];
        }

        if ($currentImagesById !== []) {
            $bumpOrdersStmt = $pdo->prepare(
                'UPDATE call_for_proposal_winner_image SET display_order = display_order + 1000000 WHERE winner_id = :winner_id'
            );
            $bumpOrdersStmt->execute([':winner_id' => $winner['id']]);
        }

        if ($deletedImageIds !== []) {
            $deleteImageStmt = $pdo->prepare('DELETE FROM call_for_proposal_winner_image WHERE id = :id');
            foreach ($deletedImageIds as $deletedImageId) {
                $deleteImageStmt->execute([':id' => $deletedImageId]);
            }
        }

        if ($survivingImages !== []) {
            $updateImageStmt = $pdo->prepare(
                'UPDATE call_for_proposal_winner_image '
                . 'SET display_order = :display_order, alt_text = :alt_text, caption = :caption '
                . 'WHERE id = :id'
            );
            ksort($survivingImages);
            foreach ($survivingImages as $imageOrder => $imageInfo) {
                $updateImageStmt->execute([
                    ':display_order' => $imageOrder,
                    ':alt_text' => $imageInfo['alt_text'],
                    ':caption' => $imageInfo['caption'] !== '' ? $imageInfo['caption'] : null,
                    ':id' => $imageInfo['id'],
                ]);
            }
        }

        foreach ($winner['new_images'] as $newImage) {
            $newImageError = $_FILES['new_images']['error'][$winner['key']][$newImage['key']] ?? UPLOAD_ERR_NO_FILE;
            if ($newImageError !== UPLOAD_ERR_OK) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Errore durante il caricamento di una nuova immagine.');
            }

            $newImageOrder = filter_var($newImage['display_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$newImageOrder || $newImage['alt_text'] === '') {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Per ogni nuova immagine sono obbligatori ordine e testo alternativo.');
            }

            if (isset($survivingImages[$newImageOrder])) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'L’ordine di una nuova immagine è già utilizzato.');
            }

            $uploadedImageName = (string) ($_FILES['new_images']['name'][$winner['key']][$newImage['key']] ?? '');
            $uploadedExtension = strtolower(pathinfo($uploadedImageName, PATHINFO_EXTENSION));
            if (!in_array($uploadedExtension, $allowedImageExtensions, true)) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Le nuove immagini devono essere JPG, PNG o WEBP.');
            }

            $tmpName = (string) ($_FILES['new_images']['tmp_name'][$winner['key']][$newImage['key']] ?? '');
            $imageInfo = @getimagesize($tmpName);
            if ($imageInfo === false) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Uno dei file caricati non è un’immagine valida.');
            }

            $winnerDirectory = getCallForProposalWinnerDirectory($callId, $winner['id']);
            if (!ensureDirectoryExists($winnerDirectory)) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Impossibile creare la cartella delle immagini di un vincitore.');
            }

            $newImageFileName = 'winner_image_' . bin2hex(random_bytes(8)) . '.' . $uploadedExtension;
            $newImagePath = $winnerDirectory . '/' . $newImageFileName;

            if (!move_uploaded_file($tmpName, $newImagePath)) {
                $pdo->rollBack();
                redirectWinnerBulkFormWithError($callId, 'Errore durante il salvataggio di una nuova immagine.');
            }

            $newlyMovedFiles[] = $newImagePath;

            $insertImageStmt = $pdo->prepare(
                'INSERT INTO call_for_proposal_winner_image (winner_id, image_path, alt_text, caption, display_order) '
                . 'VALUES (:winner_id, :image_path, :alt_text, :caption, :display_order)'
            );
            $insertImageStmt->execute([
                ':winner_id' => $winner['id'],
                ':image_path' => $newImagePath,
                ':alt_text' => $newImage['alt_text'],
                ':caption' => $newImage['caption'] !== '' ? $newImage['caption'] : null,
                ':display_order' => $newImageOrder,
            ]);

            $survivingImages[$newImageOrder] = [
                'id' => null,
                'alt_text' => $newImage['alt_text'],
                'caption' => $newImage['caption'],
            ];
        }
    }
    unset($winner);

    $pdo->commit();

    foreach ($filesToDeleteAfterCommit as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    foreach ($directoriesToDeleteAfterCommit as $directory) {
        deleteDirectoryRecursively($directory);
    }

    unset($_SESSION['call_for_proposal_winner_form_data'], $_SESSION['call_for_proposal_winner_form_error']);
    $_SESSION['call_for_proposal_winner_message'] = [
        'type' => 'success',
        'text' => $publicationStatusColumnExists && $publicationStatus === 'PUBLISHED'
            ? 'Vincitori salvati e pubblicati con successo.'
            : 'Bozza vincitori salvata con successo.',
    ];
    header('Location: call_for_proposal_winners_manage.php?id=' . urlencode((string) $callId));
    exit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($newlyMovedFiles as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    $message = 'Errore durante il salvataggio dei vincitori.';
    if ($exception->getCode() === '23000') {
        $message = 'Posizione o candidatura già utilizzata per questo bando.';
    }

    redirectWinnerBulkFormWithError($callId, $message);
}
?>
