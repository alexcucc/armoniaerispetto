<?php
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['application_id'])) {
    exit('Error: application_id not set.');
}

require_once 'evaluation_models.php';
if (!defined('EVALUATION_FORM_DISPATCH')) {
    $applicationIdParam = isset($_GET['application_id']) ? (string) $_GET['application_id'] : '';
    header('Location: evaluation_form.php' . ($applicationIdParam !== '' ? '?application_id=' . urlencode($applicationIdParam) : ''));
    exit;
}

include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$applicationId = (int) $_GET['application_id'];
$rolePermissionManager = new RolePermissionManager($pdo);
$currentUserId = (int) $_SESSION['user_id'];
if ($rolePermissionManager->userHasPermission($currentUserId, RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']) === false) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT o.name AS organization_name, a.status, c.status AS call_status, a.application_pdf_path, a.budget_pdf_path, a.cronoprogramma_pdf_path, a.checklist_path, CASE WHEN cfe.evaluator_user_id IS NULL THEN 0 ELSE 1 END AS evaluator_assigned FROM application a LEFT JOIN organization o ON a.organization_id = o.id JOIN call_for_proposal c ON a.call_for_proposal_id = c.id LEFT JOIN call_for_proposal_evaluator cfe ON cfe.call_for_proposal_id = c.id AND cfe.evaluator_user_id = :evaluator_user_id WHERE a.id = :application_id"
);
$stmt->execute([':application_id' => $applicationId, ':evaluator_user_id' => $currentUserId]);
$applicationInfo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$applicationInfo) {
    $_SESSION['evaluation_error'] = 'Risposta al bando non trovata.';
    header('Location: evaluations.php');
    exit;
}
if (($applicationInfo['call_status'] ?? null) === 'CLOSED') {
    $_SESSION['evaluation_error'] = 'Il bando e chiuso e non e piu possibile valutare.';
    header('Location: evaluations.php');
    exit;
}
if (($applicationInfo['status'] ?? '') !== 'FINAL_VALIDATION') {
    $_SESSION['evaluation_error'] = 'E possibile valutare solo le risposte in stato "Convalida in definitiva".';
    header('Location: evaluations.php');
    exit;
}
if ((int) ($applicationInfo['evaluator_assigned'] ?? 0) !== 1) {
    $_SESSION['evaluation_error'] = 'Non sei abilitato a valutare il bando della domanda selezionata.';
    header('Location: evaluations.php');
    exit;
}

$sections = evaluationGetV4EnabledSections();
$entityName = trim((string) ($applicationInfo['organization_name'] ?? '')) ?: 'Soggetto proponente';
$applicationPdfPath = $applicationInfo['application_pdf_path'] ?? null;
$budgetPdfPath = $applicationInfo['budget_pdf_path'] ?? null;
$cronoprogrammaPdfPath = $applicationInfo['cronoprogramma_pdf_path'] ?? null;
$checklistPath = $applicationInfo['checklist_path'] ?? null;
$isBudgetPdf = !empty($budgetPdfPath) && strtolower((string) pathinfo((string) $budgetPdfPath, PATHINFO_EXTENSION)) === 'pdf';
$budgetViewHref = 'application_download.php?id=' . $applicationId . '&type=budget' . ($isBudgetPdf ? '&mode=inline' : '');

$existingEvaluationStmt = $pdo->prepare('SELECT id, status, model_version FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1');
$existingEvaluationStmt->execute([':application_id' => $applicationId, ':evaluator_id' => $currentUserId]);
$existingEvaluation = $existingEvaluationStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if ($existingEvaluation !== null && evaluationIsLegacyModel($existingEvaluation['model_version'] ?? null)) {
    include 'evaluation_form_legacy.php';
    return;
}

$evaluationData = evaluationV4CreateEmptyData();
$existingEvaluationId = null;
$existingEvaluationStatus = null;
if ($existingEvaluation !== null) {
    $existingEvaluationId = (int) $existingEvaluation['id'];
    $existingEvaluationStatus = (string) $existingEvaluation['status'];
    $evaluationData = evaluationV4LoadData($pdo, $existingEvaluationId);
}

$evaluationStatusLabels = ['SUBMITTED' => 'Inviata', 'REVISED' => 'Revisionata', 'DRAFT' => 'Bozza', 'PENDING' => 'Da iniziare'];
$evaluationStatusNotes = ['SUBMITTED' => 'Valutazione inviata: puoi modificarla e reinviarla se necessario.', 'REVISED' => 'Valutazione revisionata dopo un invio precedente.', 'DRAFT' => 'Bozza salvata: puoi continuare a modificare e inviare quando vuoi.', 'PENDING' => ''];
$displayStatusKey = $existingEvaluationId !== null ? ($existingEvaluationStatus ?? 'DRAFT') : 'PENDING';
if (!isset($evaluationStatusLabels[$displayStatusKey])) {
    $displayStatusKey = 'PENDING';
}
$displayStatusLabel = $evaluationStatusLabels[$displayStatusKey];
$displayStatusNote = $evaluationStatusNotes[$displayStatusKey];
$displayStatusClass = strtolower($displayStatusKey);
$isAlreadySubmittedEvaluation = in_array($displayStatusKey, ['SUBMITTED', 'REVISED'], true);
$thematicDisplayMaxScore = (float) (evaluationGetV4Definition()['thematic_display_max_score'] ?? 100.0);

$sectionsJson = [];
$sectionLabels = [];
foreach ($sections as $sectionKey => $sectionDefinition) {
    $sectionLabels[$sectionKey] = $sectionDefinition['label'];
    $criteriaJson = [];
    foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
        $bounds = evaluationGetV4FieldBounds($criterionDefinition);
        $criteriaJson[$fieldName] = ['weight' => $criterionDefinition['weight'] ?? null, 'min' => $bounds['min'], 'max' => $bounds['max']];
    }
    $sectionsJson[$sectionKey] = ['label' => $sectionDefinition['label'], 'type' => $sectionDefinition['type'], 'max' => $sectionDefinition['max'], 'criteria' => $criteriaJson];
}

function v4RenderScoreInput(string $sectionKey, string $fieldName, string $label, array $criterionDefinition, $value): void
{
    $bounds = evaluationGetV4FieldBounds($criterionDefinition);
    $name = $sectionKey . '[' . $fieldName . ']';
    $id = $sectionKey . '_' . $fieldName . '_score_input';
    $valueAttr = $value !== null && $value !== '' ? ' value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"' : '';
    $inputType = $bounds['min'] < 0 ? 'text' : 'number';
    $inputMode = $bounds['min'] < 0 ? 'text' : 'numeric';
    echo '<input type="' . $inputType . '" inputmode="' . $inputMode . '" class="score-input" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" min="' . htmlspecialchars((string) $bounds['min'], ENT_QUOTES, 'UTF-8') . '" max="' . htmlspecialchars((string) $bounds['max'], ENT_QUOTES, 'UTF-8') . '" step="1" autocomplete="off" spellcheck="false" data-section-key="' . htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') . '" data-field-name="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '" data-min="' . htmlspecialchars((string) $bounds['min'], ENT_QUOTES, 'UTF-8') . '" data-max="' . htmlspecialchars((string) $bounds['max'], ENT_QUOTES, 'UTF-8') . '"' . $valueAttr . '>';
}

function v4RenderBadges(array $criterionDefinition): void
{
    if (isset($criterionDefinition['weight'])) {
        echo '<span class="criteria-weight-badge">Peso: ' . htmlspecialchars((string) $criterionDefinition['weight'], ENT_QUOTES, 'UTF-8') . '</span><span class="criteria-weighted-score" aria-live="polite">Voto pesato: 0</span>';
    }
    $bounds = evaluationGetV4FieldBounds($criterionDefinition);
    echo '<span class="criteria-weight-badge criteria-weight-badge--range">Range: ' . htmlspecialchars((string) $bounds['min'], ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars((string) $bounds['max'], ENT_QUOTES, 'UTF-8') . '</span>';
}

function v4RenderSectionDescription(array $sectionDefinition): void
{
    $label = (string) ($sectionDefinition['label'] ?? '');
    $description = trim((string) ($sectionDefinition['description'] ?? ''));
    if ($description === '') {
        return;
    }

    if ($label !== "PESO E PROFONDITA' DELL' INTERVENTO") {
        echo '<p class="section-note-text">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>';
        return;
    }

    echo '<div class="section-note-panel">';
    echo '<p class="section-note-panel__title">Questa importante sezione si occupa di classificare gli interventi in base alla sistematicità del cambiamento che producono.</p>';
    echo '<div class="section-note-panel__example-list">';
    echo '<p class="section-note-panel__line"><strong>ESEMPIO</strong></p>';
    echo '<p class="section-note-panel__line"><strong>Livello 1 – Alleviare una sofferenza</strong><br>Salvare un cane ferito.<br>Beneficio enorme, ma circoscritto.</p>';
    echo '<p class="section-note-panel__line"><strong>Livello 2 – Eliminare una causa</strong><br>Sterilizzare una colonia felina.<br>Si evita che il problema si ripresenti.</p>';
    echo '<p class="section-note-panel__line"><strong>Livello 3 – Modificare un sistema</strong><br>Realizzare corridoi ecologici.<br>Il beneficio riguarda migliaia di animali nel tempo.</p>';
    echo '<p class="section-note-panel__line"><strong>Livello 4 – Cambiare la cultura</strong><br>Educazione, formazione, nuove norme.<br>Il beneficio può durare decenni.</p>';
    echo '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<?php include 'common-head.php'; ?>
<title>Invia la Valutazione</title>
<style>
.contact-form-container.evaluation-page{margin:.3rem 0 .2rem;padding:.25rem .72rem .3rem}.total-score-overlay{background:#fff;border:1px solid #d1d5db;border-radius:.65rem;padding:.45rem .62rem;box-shadow:0 10px 20px rgba(15,23,42,.12);font-weight:600;font-size:.84rem;color:#1f2937;text-align:center}.total-score-overlay__group{display:flex;flex-direction:column;gap:.05rem;padding:.08rem 0}.total-score-overlay__group--thematic{margin-top:.06rem;padding-top:.25rem;border-top:1px dashed #e5e7eb}.total-score-overlay__label{display:block;font-size:.74rem;font-weight:500;color:#4b5563;margin-bottom:.08rem;letter-spacing:.02em}.total-score-overlay__value-row{display:inline-flex;align-items:baseline;justify-content:center;gap:.28rem}.total-score-overlay__value{font-size:1.1rem;color:#0c4a6e}.total-score-overlay__separator{font-size:.95rem;color:#64748b}.total-score-overlay__max{font-size:.94rem;color:#0f172a}.evaluation-header{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center;gap:.45rem;margin-bottom:.12rem}.evaluation-header__main{grid-column:2;min-width:0;text-align:center}.evaluation-header__title-row{display:flex;align-items:center;justify-content:center;gap:.45rem;flex-wrap:wrap}.evaluation-header h2{margin:0;text-align:center;font-size:clamp(.98rem,1.45vw,1.18rem);line-height:1.2}.evaluation-subject-name{margin:0;display:inline-flex;align-items:center;justify-content:center;gap:.2rem;padding:.16rem .55rem;border-radius:9999px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;font-size:.84rem;font-weight:700;line-height:1.2}.evaluation-status-panel{grid-column:3;justify-self:end}.evaluation-status-panel__note,.criteria-range-note,.form-text p{margin-top:.18rem;font-size:.76rem;line-height:1.22;color:#64748b}.section-note-text{margin:.16rem 0 .24rem;font-size:.76rem;line-height:1.28;color:#475569;white-space:pre-wrap}.section-note-panel{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.4rem .5rem;margin:0 0 .28rem}.section-note-panel__title{margin:0 0 .24rem;font-size:.78rem;font-weight:700;color:#0f172a}.section-note-panel__lead,.section-note-panel__line{margin:0;font-size:.76rem;line-height:1.28;color:#475569}.section-note-panel__example-list{display:grid;gap:.2rem;margin-top:.28rem}.criteria-help-copy{margin:0;white-space:pre-wrap}.evaluation-shell{display:flex;flex-direction:column;gap:.2rem}.evaluation-layout{--evaluation-sidebar-width:200px;--evaluation-layout-gap:.75rem;display:grid;grid-template-columns:minmax(0,1fr) minmax(188px,var(--evaluation-sidebar-width));gap:var(--evaluation-layout-gap);align-items:start;flex:1 1 auto;overflow:hidden}.evaluation-content{min-width:0;max-height:calc(100vh - 7.6rem);overflow-y:auto;overflow-x:hidden;padding-right:.18rem}.evaluation-sidebar{position:sticky;top:calc(var(--header-height,70px) + .2rem);display:flex;flex-direction:column;gap:.4rem;align-items:stretch;min-width:188px;z-index:1100}.contact-form{padding-bottom:.25rem}.evaluation-step{display:none}.evaluation-step.active{display:block}.evaluation-step h3{margin:0 0 .22rem;transform:translateX(calc((var(--evaluation-sidebar-width) + var(--evaluation-layout-gap))/2));text-align:center;line-height:1.2;font-size:.95rem}.evaluation-step .form-group + .form-group{margin-top:.3rem}.evaluation-actions{background:rgba(255,255,255,.95);border:1px solid #e5e7eb;box-shadow:0 4px 12px rgba(15,23,42,.12);padding:.42rem .5rem;display:flex;flex-direction:column;gap:.35rem;border-radius:.55rem}.evaluation-actions__nav,.evaluation-actions__main{display:flex;align-items:stretch;gap:.28rem;flex-direction:column}.evaluation-actions__nav .page-button{background:#0ea5e9;color:#fff;border:none;padding:.55rem .85rem;border-radius:.4rem;font-weight:600;cursor:pointer;font-size:.92rem}.evaluation-actions__nav .page-button:disabled{opacity:.6;cursor:not-allowed}.evaluation-actions .submit-btn,.evaluation-actions .page-button{width:100%;min-width:7rem;padding:.34rem .52rem;font-size:.8rem}.score-input{width:100%;max-width:72px;padding:.28rem .36rem;border-radius:.4rem;border:1px solid #cbd5e1;font-weight:600;font-size:.84rem}.score-input:focus,.criterion-note-textarea:focus{outline:2px solid #0ea5e9;outline-offset:1px;border-color:#0ea5e9}.criteria-weight-badge,.section-weight-badge{display:inline-flex;align-items:center;padding:.08rem .4rem;margin-left:.22rem;border-radius:9999px;background:#ecfeff;color:#0ea5e9;font-weight:700;font-size:.78rem;border:1px solid #bae6fd}.criteria-weight-badge--negative{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.criteria-weight-badge--range{background:#f8fafc;border-color:#e2e8f0;color:#475569}.criteria-weighted-score{display:inline-flex;align-items:center;padding:.08rem .4rem;margin-left:.2rem;border-radius:9999px;background:#fef3c7;color:#b45309;font-weight:700;font-size:.78rem;border:1px solid #fde68a}.section-weight-badge{margin-left:.45rem}.criteria-row{display:flex;align-items:flex-start;gap:.34rem;flex-wrap:wrap}.criteria-row__label{flex:1 1 220px;min-width:0}.criteria-row__input{flex:0 0 74px;display:flex;align-items:center;justify-content:flex-end}.criteria-row__weight{flex:1 1 150px;display:flex;align-items:center;gap:.18rem;flex-wrap:wrap}.criteria-row__actions{display:flex;align-items:center;gap:.18rem;flex-wrap:wrap;margin-left:auto}.criteria-label{display:inline-flex;align-items:flex-start;gap:.2rem;margin:0;line-height:1.18;font-size:.8rem}.criteria-info-toggle,.criterion-note-toggle,.criteria-info-placeholder{background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;border-radius:.4rem;padding:.18rem .42rem;font-weight:700;font-size:.68rem;cursor:pointer}.criteria-info-placeholder{visibility:hidden;pointer-events:none}.criteria-info-content,.criterion-note-panel{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.34rem .48rem;font-size:.75rem;color:#0f172a;margin-top:.22rem}.criteria-info-text{margin:0;white-space:pre-wrap}.criterion-note-actions{margin-top:0}.criterion-note-panel{padding:.38rem .42rem}.criterion-note-textarea{display:block;width:100%;max-width:100%;box-sizing:border-box;min-height:92px;padding:.48rem .56rem;border-radius:.4rem;border:1px solid #cbd5e1;resize:vertical}.evaluation-success-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;z-index:9999}.evaluation-success-modal-content{background:#fff;border-radius:.75rem;padding:1.2rem 1.4rem;width:min(420px,calc(100vw - 2rem));text-align:center;box-shadow:0 18px 40px rgba(15,23,42,.22)}.evaluation-success-modal-icon{font-size:2rem;margin-bottom:.4rem}@media (max-width:1000px){.evaluation-header{grid-template-columns:1fr}.evaluation-header__main,.evaluation-status-panel{grid-column:1}.evaluation-status-panel{justify-self:stretch}.evaluation-layout{grid-template-columns:1fr;gap:.35rem;overflow:visible}.evaluation-step h3{transform:none}.evaluation-sidebar{order:-1;position:sticky;top:calc(var(--header-height,70px) + .2rem);min-width:0;z-index:1200}.evaluation-content{max-height:calc(100vh - 11.8rem);overflow-y:auto;padding-right:.08rem}.evaluation-status-panel__note{display:none}}@media (max-width:640px){.criteria-row__label,.criteria-row__input,.criteria-row__weight{flex:1 1 100%}.criteria-row__input{justify-content:flex-start}.score-input{max-width:100%}}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
<div class="contact-form-container evaluation-page"><div class="evaluation-shell"><div class="button-container"><a href="evaluations.php" class="page-button back-button evaluation-actions__back-link" data-destination="evaluations.php">Indietro</a></div><form id="evaluation-form" class="contact-form" action="evaluation_handler.php" method="post"><input type="hidden" name="application_id" value="<?php echo $applicationId; ?>"><?php if ($existingEvaluationId !== null): ?><input type="hidden" name="evaluation_id" value="<?php echo $existingEvaluationId; ?>"><?php endif; ?><div class="evaluation-header"><div class="evaluation-header__main"><div class="evaluation-header__title-row"><h2>Valutazione progetto</h2><p class="evaluation-subject-name">Ente: <strong><?php echo htmlspecialchars($entityName); ?></strong></p></div><div class="form-group"><label class="form-label">Documenti della risposta</label><div class="actions-cell document-actions"><?php if (!empty($applicationPdfPath)): ?><div class="document-action-group"><span class="document-action-label">Risposta</span><a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=application&mode=inline" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a></div><?php endif; ?><?php if (!empty($budgetPdfPath)): ?><div class="document-action-group"><span class="document-action-label">Budget</span><a class="page-button secondary-button page-button--icon" href="<?php echo htmlspecialchars($budgetViewHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a></div><?php endif; ?><?php if (!empty($cronoprogrammaPdfPath)): ?><div class="document-action-group"><span class="document-action-label">Cronoprogr.</span><a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=cronoprogramma&mode=inline" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a></div><?php endif; ?><?php if (!empty($checklistPath)): ?><div class="document-action-group"><span class="document-action-label">Checklist</span><a class="page-button secondary-button page-button--icon" href="application_checklist_download.php?id=<?php echo $applicationId; ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a></div><?php endif; ?></div></div></div><div class="evaluation-status-panel evaluation-status-panel--<?php echo htmlspecialchars($displayStatusClass); ?>"><span class="evaluation-status-panel__label">Stato valutazione</span><span class="evaluation-status-badge evaluation-status-badge--<?php echo htmlspecialchars($displayStatusClass); ?>"><?php echo htmlspecialchars($displayStatusLabel); ?></span><p class="evaluation-status-panel__note"><?php echo htmlspecialchars($displayStatusNote); ?></p></div></div><div class="evaluation-layout"><div class="evaluation-content">
<?php $stepIndex = 0; foreach ($sections as $sectionKey => $sectionDefinition): ?>
<div class="evaluation-step<?php echo $stepIndex === 0 ? ' active' : ''; ?>" data-step-index="<?php echo $stepIndex; ?>" data-section-key="<?php echo htmlspecialchars($sectionKey); ?>" data-section-type="<?php echo htmlspecialchars((string) $sectionDefinition['type']); ?>" data-section-max="<?php echo htmlspecialchars((string) $sectionDefinition['max']); ?>" data-score-step="1"><h3><?php echo htmlspecialchars($sectionDefinition['label']); ?><span class="section-weight-badge"><?php echo (($sectionDefinition['type'] ?? '') === 'thematic') ? 'Max categoria' : 'Max sezione'; ?>: <?php echo htmlspecialchars((string) $sectionDefinition['max']); ?></span></h3><?php v4RenderSectionDescription($sectionDefinition); ?><?php foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition): ?><div class="form-group"><label class="form-label<?php echo (($sectionDefinition['type'] ?? '') !== 'thematic') ? ' required' : ''; ?>"><?php echo htmlspecialchars($criterionDefinition['label']); ?> <?php v4RenderBadges($criterionDefinition); ?></label><?php v4RenderScoreInput($sectionKey, $fieldName, $criterionDefinition['label'], $criterionDefinition, $evaluationData[$sectionKey]['scores'][$fieldName] ?? null); ?><?php if (!empty($criterionDefinition['help']) || evaluationGetV4FieldBounds($criterionDefinition)['min'] < 0): $bounds = evaluationGetV4FieldBounds($criterionDefinition); ?><small class="form-text"><?php if (!empty($criterionDefinition['help'])): ?><p class="criteria-help-copy"><?php echo htmlspecialchars($criterionDefinition['help']); ?></p><?php endif; ?><p class="criteria-range-note">Intervallo consentito: <?php echo htmlspecialchars((string) $bounds['min']); ?> - <?php echo htmlspecialchars((string) $bounds['max']); ?></p></small><?php endif; ?></div><div class="form-group criterion-note-group"><?php $criterionNoteValue = (string) ($evaluationData[$sectionKey]['criterion_notes'][$fieldName] ?? ''); $hasCriterionNote = trim($criterionNoteValue) !== ''; ?><div class="criterion-note-actions"><button type="button" class="criterion-note-toggle" aria-expanded="<?php echo $hasCriterionNote ? 'true' : 'false'; ?>" aria-controls="<?php echo htmlspecialchars($sectionKey . '_' . $fieldName . '_note_panel'); ?>"><?php echo $hasCriterionNote ? 'Modifica nota' : 'Aggiungi nota'; ?></button></div><div class="criterion-note-panel" id="<?php echo htmlspecialchars($sectionKey . '_' . $fieldName . '_note_panel'); ?>"<?php echo $hasCriterionNote ? '' : ' hidden'; ?>><label class="form-label" for="<?php echo htmlspecialchars($sectionKey . '_' . $fieldName . '_note'); ?>">Nota (opzionale)</label><textarea class="criterion-note-textarea" id="<?php echo htmlspecialchars($sectionKey . '_' . $fieldName . '_note'); ?>" name="<?php echo htmlspecialchars($sectionKey . '_criterion_notes[' . $fieldName . ']'); ?>"><?php echo htmlspecialchars($criterionNoteValue); ?></textarea></div></div><?php endforeach; ?></div>
<?php $stepIndex++; endforeach; ?>

</div><div class="evaluation-sidebar"><div class="total-score-overlay" role="status" aria-live="polite"><div class="total-score-overlay__group"><span class="total-score-overlay__label">Totale punteggio / Totale max</span><span class="total-score-overlay__value-row"><span class="total-score-overlay__value" id="total-score-value">0</span><span class="total-score-overlay__separator">/</span><span class="total-score-overlay__max" id="total-score-max-value">0</span></span></div><div class="total-score-overlay__group"><span class="total-score-overlay__label">Totale sezione corrente / Max sezione corrente</span><span class="total-score-overlay__value-row"><span class="total-score-overlay__value" id="section-score-value">0</span><span class="total-score-overlay__separator">/</span><span class="total-score-overlay__max" id="section-score-max-value">0</span></span></div><div class="total-score-overlay__group total-score-overlay__group--thematic" id="thematic-score-group" hidden><span class="total-score-overlay__label">Totale criteri tematici / Max criteri tematici</span><span class="total-score-overlay__value-row"><span class="total-score-overlay__value" id="thematic-score-value">0</span><span class="total-score-overlay__separator">/</span><span class="total-score-overlay__max" id="thematic-score-max-value">0</span></span></div></div><div class="evaluation-actions"><div class="evaluation-actions__nav"><button type="button" class="page-button" id="previous-step-button">Sezione precedente</button><button type="button" class="page-button" id="next-step-button">Sezione successiva</button></div><div class="evaluation-actions__main"><?php if ($isAlreadySubmittedEvaluation): ?><button type="submit" class="submit-btn" name="action" value="save">Salva valutazione</button><?php else: ?><button type="submit" class="submit-btn secondary-button" name="action" value="save">Salva bozza</button><button type="submit" class="submit-btn" name="action" value="submit">Invia valutazione</button><?php endif; ?></div></div></div></div></form></div></div></main><?php include 'footer.php'; ?><div id="evaluation-success-modal" class="evaluation-success-modal" style="display:none;"><div class="evaluation-success-modal-content"><div class="evaluation-success-modal-icon">&#10003;</div><h3>Valutazione inviata</h3><p>Reindirizzamento in corso alla lista delle valutazioni.</p><button id="close-evaluation-modal" class="submit-btn">Vai subito</button></div></div>
<script>
(function(){const form=document.getElementById('evaluation-form');if(!form)return;const modal=document.getElementById('evaluation-success-modal');const closeButton=document.getElementById('close-evaluation-modal');const totalScoreElement=document.getElementById('total-score-value');const totalScoreMaxElement=document.getElementById('total-score-max-value');const sectionScoreElement=document.getElementById('section-score-value');const sectionScoreMaxElement=document.getElementById('section-score-max-value');const thematicScoreGroupElement=document.getElementById('thematic-score-group');const thematicScoreElement=document.getElementById('thematic-score-value');const thematicScoreMaxElement=document.getElementById('thematic-score-max-value');const previousStepButton=document.getElementById('previous-step-button');const nextStepButton=document.getElementById('next-step-button');const backLink=document.querySelector('.evaluation-actions__back-link');const stepElements=Array.from(document.querySelectorAll('.evaluation-step'));const sectionsConfig=<?php echo json_encode($sectionsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;const sectionLabels=<?php echo json_encode($sectionLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;const thematicDisplayMaxScore=<?php echo json_encode($thematicDisplayMaxScore); ?>;let activeStepIndex=0;let hasUnsavedChanges=false;const markUnsavedChanges=()=>{hasUnsavedChanges=true};const resetUnsavedChanges=()=>{hasUnsavedChanges=false};const getActiveStep=()=>stepElements[activeStepIndex]||null;const isScoreStep=(step)=>step&&step.dataset.scoreStep==='1';const getSectionKey=(step)=>step?(step.dataset.sectionKey||null):null;const isThematicStep=(step)=>step&&step.dataset.sectionType==='thematic';const formatScore=(value)=>{if(!Number.isFinite(value))return'0';return Math.abs(value-Math.round(value))<.00001?String(Math.round(value)):value.toFixed(2).replace('.',',')};const readScore=(input)=>{const raw=(input.value||'').trim();if(raw===''||!/^-?\d+$/.test(raw))return null;const parsed=Number.parseInt(raw,10);if(Number.isNaN(parsed))return null;const min=Number.parseInt(input.dataset.min||'0',10);const max=Number.parseInt(input.dataset.max||'10',10);return Math.min(Math.max(parsed,min),max)};const enforceInputBounds=(input)=>{const raw=(input.value||'').trim();if(raw===''){input.value='';return;}const min=Number.parseInt(input.dataset.min||'0',10);if(raw==='-'&&min<0){return;}const score=readScore(input);input.value=score===null?'':String(score)};const weightedCriterion=(input)=>{const step=input.closest('.evaluation-step');const sectionKey=getSectionKey(step);const fieldName=input.dataset.fieldName||'';const sectionConfig=sectionKey?sectionsConfig[sectionKey]:null;const criterionConfig=sectionConfig&&sectionConfig.criteria?sectionConfig.criteria[fieldName]:null;if(!criterionConfig||criterionConfig.weight===null||criterionConfig.weight===undefined)return null;const score=readScore(input);return score===null?0:(score*Number(criterionConfig.weight))/10};const updateCriterionWeightedScore=(input)=>{const badge=input.closest('.form-group')?.querySelector('.criteria-weighted-score');if(!badge)return;const weighted=weightedCriterion(input);badge.textContent='Voto pesato: '+formatScore(weighted===null?0:weighted)};const calculateSectionScore=(step)=>{if(!isScoreStep(step))return 0;const inputs=Array.from(step.querySelectorAll('input.score-input'));return inputs.reduce((t,input)=>{const weighted=weightedCriterion(input);return t+(weighted===null?0:weighted)},0)};const calculateSectionMaxScore=(step)=>{if(!isScoreStep(step))return 0;const sectionKey=getSectionKey(step);const sectionConfig=sectionKey?sectionsConfig[sectionKey]:null;if(!sectionConfig)return 0;return Number(sectionConfig.max||0)};const sum=(predicate,calculator)=>stepElements.reduce((t,step)=>predicate(step)?t+calculator(step):t,0);const calculateNonThematicTotalScore=()=>sum((s)=>isScoreStep(s)&&!isThematicStep(s),calculateSectionScore);const calculateNonThematicMaxScore=()=>sum((s)=>isScoreStep(s)&&!isThematicStep(s),calculateSectionMaxScore);const calculateThematicRawTotalScore=()=>sum((s)=>isScoreStep(s)&&isThematicStep(s),calculateSectionScore);const calculateThematicRawMaxScore=()=>sum((s)=>isScoreStep(s)&&isThematicStep(s),calculateSectionMaxScore);const refreshTotals=()=>{const activeStep=getActiveStep();const thematicScore=Math.min(calculateThematicRawTotalScore(),thematicDisplayMaxScore);const thematicMax=Math.min(calculateThematicRawMaxScore(),thematicDisplayMaxScore);sectionScoreElement.textContent=formatScore(calculateSectionScore(activeStep));sectionScoreMaxElement.textContent=formatScore(calculateSectionMaxScore(activeStep));thematicScoreElement.textContent=formatScore(thematicScore);thematicScoreMaxElement.textContent=formatScore(thematicMax);totalScoreElement.textContent=formatScore(calculateNonThematicTotalScore()+thematicScore);totalScoreMaxElement.textContent=formatScore(calculateNonThematicMaxScore()+thematicMax);thematicScoreGroupElement.hidden=!isThematicStep(activeStep)};const updateNavigationState=()=>{previousStepButton.disabled=activeStepIndex<=0;nextStepButton.disabled=activeStepIndex>=stepElements.length-1};const scrollToPageTop=()=>{const content=document.querySelector('.evaluation-content');if(content&&typeof content.scrollTo==='function')content.scrollTo({top:0,behavior:'smooth'});window.scrollTo({top:0,behavior:'smooth'})};const setActiveStep=(targetIndex)=>{if(targetIndex<0||targetIndex>=stepElements.length)return;activeStepIndex=targetIndex;stepElements.forEach((step,index)=>step.classList.toggle('active',index===activeStepIndex));updateNavigationState();refreshTotals();scrollToPageTop()};const transformCriteriaLayout=()=>{Array.from(document.querySelectorAll('.form-group')).forEach((group)=>{if(group.classList.contains('criterion-note-group'))return;const label=group.querySelector(':scope > label');const input=group.querySelector(':scope > input.score-input');const description=group.querySelector(':scope > small');if(!label||!input)return;const noteGroup=group.nextElementSibling&&group.nextElementSibling.classList.contains('criterion-note-group')?group.nextElementSibling:null;const noteButton=noteGroup?noteGroup.querySelector('.criterion-note-toggle'):null;const notePanel=noteGroup?noteGroup.querySelector('.criterion-note-panel'):null;const badges=Array.from(label.querySelectorAll('.criteria-weight-badge, .criteria-weighted-score'));badges.forEach((b)=>b.remove());const row=document.createElement('div');row.className='criteria-row';const labelWrapper=document.createElement('div');labelWrapper.className='criteria-row__label';label.classList.add('criteria-label');labelWrapper.appendChild(label);const inputWrapper=document.createElement('div');inputWrapper.className='criteria-row__input';inputWrapper.appendChild(input);const weightWrapper=document.createElement('div');weightWrapper.className='criteria-row__weight';badges.forEach((b)=>weightWrapper.appendChild(b));const actionsWrapper=document.createElement('div');actionsWrapper.className='criteria-row__actions';row.appendChild(labelWrapper);row.appendChild(inputWrapper);row.appendChild(weightWrapper);row.appendChild(actionsWrapper);group.innerHTML='';group.appendChild(row);if(description){description.classList.add('criteria-info-text');const infoButton=document.createElement('button');infoButton.type='button';infoButton.className='criteria-info-toggle';infoButton.textContent='Info';infoButton.setAttribute('aria-expanded','false');const infoContent=document.createElement('div');infoContent.className='criteria-info-content';infoContent.hidden=true;infoContent.appendChild(description);infoButton.addEventListener('click',()=>{const hidden=infoContent.hidden;infoContent.hidden=!hidden;infoButton.setAttribute('aria-expanded',String(hidden))});actionsWrapper.appendChild(infoButton);group.appendChild(infoContent)}else if(noteButton){const infoPlaceholder=document.createElement('span');infoPlaceholder.className='criteria-info-placeholder';infoPlaceholder.textContent='Info';infoPlaceholder.setAttribute('aria-hidden','true');actionsWrapper.appendChild(infoPlaceholder)}if(noteButton){actionsWrapper.appendChild(noteButton)}if(notePanel){group.appendChild(notePanel);noteGroup.remove()}})};const getIncompleteSectionLabels=()=>{const missing=[];stepElements.forEach((step)=>{if(!isScoreStep(step))return;const sectionKey=getSectionKey(step);if(isThematicStep(step))return;const inputs=Array.from(step.querySelectorAll('input.score-input'));if(inputs.some((input)=>readScore(input)===null))missing.push(sectionLabels[sectionKey]||sectionKey)});return missing};const bindCriterionNoteToggles=()=>{document.querySelectorAll('.criterion-note-toggle').forEach((button)=>{button.addEventListener('click',()=>{const panelId=button.getAttribute('aria-controls');const panel=panelId?document.getElementById(panelId):null;if(!panel)return;const willOpen=panel.hidden;panel.hidden=!willOpen;button.setAttribute('aria-expanded',willOpen?'true':'false');button.textContent=willOpen?'Modifica nota':'Aggiungi nota';if(willOpen){const textarea=panel.querySelector('textarea');if(textarea)textarea.focus();}})})};transformCriteriaLayout();bindCriterionNoteToggles();form.querySelectorAll('input, textarea, select').forEach((field)=>{field.addEventListener('input',()=>{markUnsavedChanges();if(field.classList.contains('score-input')){enforceInputBounds(field);updateCriterionWeightedScore(field);refreshTotals();}});field.addEventListener('change',()=>{markUnsavedChanges();if(field.classList.contains('score-input')){enforceInputBounds(field);updateCriterionWeightedScore(field);refreshTotals();}})});form.querySelectorAll('input.score-input').forEach((input)=>{enforceInputBounds(input);updateCriterionWeightedScore(input)});if(backLink){backLink.addEventListener('click',(event)=>{event.preventDefault();if(!hasUnsavedChanges){window.location.assign(backLink.dataset.destination||'evaluations.php');return;}if(window.confirm('Hai modificato la valutazione. Uscendo senza salvare perderai le modifiche. Vuoi continuare?'))window.location.assign(backLink.dataset.destination||'evaluations.php')})}previousStepButton.addEventListener('click',()=>setActiveStep(activeStepIndex-1));nextStepButton.addEventListener('click',()=>setActiveStep(activeStepIndex+1));updateNavigationState();refreshTotals();form.addEventListener('submit',async(event)=>{const submitter=event.submitter||null;const actionValue=submitter?submitter.value:null;if(actionValue!=='submit'){resetUnsavedChanges();return;}event.preventDefault();const missing=getIncompleteSectionLabels();if(missing.length>0){alert('valutazione incompleta: valutazione non inviabile\nSezioni non completate:\n- '+missing.join('\n- '));return;}const formData=new FormData(form);if(submitter&&submitter.name)formData.set(submitter.name,submitter.value);try{const response=await fetch(form.getAttribute('action')||window.location.href,{method:form.getAttribute('method')||'post',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}});const data=await response.json();if(data.success){resetUnsavedChanges();modal.style.display='flex';let redirected=false;const goHome=()=>{if(redirected)return;redirected=true;window.location.href=data.redirect||'evaluations.php'};closeButton.onclick=goHome;window.setTimeout(goHome,2500)}else{alert(data.message||'Errore nell\'invio della valutazione.')}}catch(error){alert('Errore: '+error)}})})();
</script>
</body>
</html>














