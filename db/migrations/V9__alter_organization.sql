ALTER TABLE organization CHANGE incorporation_date incorporation_year DATE;
UPDATE organization SET incorporation_year = DATE(CONCAT(YEAR(incorporation_year), '-01-01'));
ALTER TABLE organization MODIFY incorporation_year YEAR;
ALTER TABLE organization CHANGE full_address location TEXT;
