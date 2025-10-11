DELETE e1 FROM evaluation e1
INNER JOIN evaluation e2
    ON e1.application_id = e2.application_id
   AND e1.evaluator_id = e2.evaluator_id
   AND e1.id > e2.id;

ALTER TABLE evaluation
    ADD CONSTRAINT evaluation_unique_application_evaluator UNIQUE (application_id, evaluator_id);
