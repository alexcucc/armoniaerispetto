ALTER TABLE application
    ADD COLUMN rejection_reason TEXT NULL AFTER status;

-- Clean up any existing empty strings that may have been stored before the column was
-- populated so that application logic can rely on NULL when the motivation is absent.
UPDATE application
SET rejection_reason = NULL
WHERE rejection_reason = '';
