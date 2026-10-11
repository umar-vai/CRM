-- Reviewed operator step ONLY after Laravel's CRM workflow migration succeeds.
-- Run atomically as administrator; this does not activate migration-role LOGIN.
SET LOCAL ROLE novaflow_migrator;
GRANT SELECT, INSERT ON novaflow.customers, novaflow.lead_activities TO novaflow_runtime;
RESET ROLE;

DO $verify$
DECLARE
    protected_table text;
    forbidden_privilege text;
BEGIN
    FOREACH protected_table IN ARRAY ARRAY['customers', 'lead_activities'] LOOP
        IF NOT has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'SELECT')
            OR NOT has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'INSERT') THEN
            RAISE EXCEPTION 'Required CRM workflow privileges missing';
        END IF;
        FOREACH forbidden_privilege IN ARRAY ARRAY['UPDATE', 'DELETE', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] LOOP
            IF has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, forbidden_privilege) THEN
                RAISE EXCEPTION 'Unexpected CRM snapshot/history mutation privileges';
            END IF;
        END LOOP;
    END LOOP;
END;
$verify$;
