<?php

declare(strict_types=1);

if (function_exists('getUserDefaultCallForProposalId')) {
    return;
}

function getUserDefaultCallForProposalId(PDO $pdo, ?int $userId): ?int
{
    if ($userId === null || $userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT default_call_for_proposal_id FROM user WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $value = $stmt->fetchColumn();

    if ($value === false || $value === null) {
        return null;
    }

    $callId = (int) $value;

    return $callId > 0 ? $callId : null;
}

function resolveCallFilterSelection(array $query, string $paramName, ?int $defaultCallId, array $availableCallIds): array
{
    $availableCallSet = [];
    foreach ($availableCallIds as $availableCallId) {
        $normalizedId = (int) $availableCallId;
        if ($normalizedId > 0) {
            $availableCallSet[$normalizedId] = true;
        }
    }

    $isProvided = array_key_exists($paramName, $query);
    $rawValue = $isProvided ? trim((string) $query[$paramName]) : '';
    $normalizedRawValue = strtolower($rawValue);

    if ($isProvided) {
        if ($rawValue === '' || $normalizedRawValue === 'all') {
            return [
                'selected_value' => 'all',
                'effective_call_id' => null,
                'used_default' => false,
            ];
        }

        if (ctype_digit($rawValue)) {
            $callId = (int) $rawValue;
            if ($callId > 0 && isset($availableCallSet[$callId])) {
                return [
                    'selected_value' => (string) $callId,
                    'effective_call_id' => $callId,
                    'used_default' => false,
                ];
            }
        }

        return [
            'selected_value' => 'all',
            'effective_call_id' => null,
            'used_default' => false,
        ];
    }

    if ($defaultCallId !== null && $defaultCallId > 0 && isset($availableCallSet[$defaultCallId])) {
        return [
            'selected_value' => (string) $defaultCallId,
            'effective_call_id' => $defaultCallId,
            'used_default' => true,
        ];
    }

    return [
        'selected_value' => 'all',
        'effective_call_id' => null,
        'used_default' => false,
    ];
}

function syncUserDefaultCallForProposalFromFilter(
    PDO $pdo,
    ?int $userId,
    array $query,
    string $paramName,
    array $availableCallIds
): void {
    if ($userId === null || $userId <= 0 || !array_key_exists($paramName, $query)) {
        return;
    }

    $rawValue = trim((string) $query[$paramName]);
    $normalizedRawValue = strtolower($rawValue);
    $newDefaultCallId = null;

    if ($rawValue !== '' && $normalizedRawValue !== 'all') {
        if (!ctype_digit($rawValue)) {
            return;
        }

        $candidateCallId = (int) $rawValue;
        if ($candidateCallId <= 0) {
            return;
        }

        $availableCallSet = [];
        foreach ($availableCallIds as $availableCallId) {
            $normalizedId = (int) $availableCallId;
            if ($normalizedId > 0) {
                $availableCallSet[$normalizedId] = true;
            }
        }

        if (!isset($availableCallSet[$candidateCallId])) {
            return;
        }

        $newDefaultCallId = $candidateCallId;
    }

    $currentDefaultCallId = getUserDefaultCallForProposalId($pdo, $userId);
    if ($currentDefaultCallId === $newDefaultCallId) {
        return;
    }

    $updateStmt = $pdo->prepare('UPDATE user SET default_call_for_proposal_id = :default_call_id WHERE id = :user_id');
    $updateStmt->bindValue(':default_call_id', $newDefaultCallId, $newDefaultCallId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $updateStmt->execute();
}

function normalizeRedirectPath(?string $redirect, string $fallbackPath): string
{
    $fallback = ltrim($fallbackPath, '/');

    if ($redirect === null) {
        return $fallback;
    }

    $value = trim($redirect);
    if ($value === '') {
        return $fallback;
    }

    $parsed = parse_url($value);
    if ($parsed === false) {
        return $fallback;
    }

    if (isset($parsed['scheme']) || isset($parsed['host'])) {
        return $fallback;
    }

    $normalized = ltrim($value, '/');
    if ($normalized === '' || str_starts_with($normalized, '/')) {
        return $fallback;
    }

    return $normalized;
}
