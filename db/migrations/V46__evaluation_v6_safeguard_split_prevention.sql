ALTER TABLE evaluation_v4_thematic_safeguard
    ADD COLUMN prevention_denunciation_score INT NULL AFTER prevention_operational_tactical_notes,
    ADD COLUMN prevention_denunciation_notes TEXT NULL AFTER prevention_denunciation_score,
    ADD COLUMN prevention_action_score INT NULL AFTER prevention_denunciation_notes,
    ADD COLUMN prevention_action_notes TEXT NULL AFTER prevention_action_score;
