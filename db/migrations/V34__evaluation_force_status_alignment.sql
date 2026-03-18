UPDATE evaluation
SET status = 'REVISED'
WHERE forced_weighted_total_score IS NOT NULL
  AND status = 'SUBMITTED';
