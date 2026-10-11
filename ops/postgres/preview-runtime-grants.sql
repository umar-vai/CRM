-- Apply after the existing Laravel migrations have run as novaflow_migrator.
-- Never substitute blanket ALL privileges for an application permission error.
SET LOCAL ROLE novaflow_migrator;

GRANT SELECT, INSERT, UPDATE, DELETE ON
    novaflow.users, novaflow.password_reset_tokens, novaflow.sessions,
    novaflow.cache, novaflow.cache_locks, novaflow.passkeys,
    novaflow.tenant_memberships
    TO novaflow_runtime;
GRANT SELECT, INSERT, UPDATE ON
    novaflow.tenants, novaflow.leads, novaflow.workspace_invitations
    TO novaflow_runtime;
GRANT SELECT, INSERT ON novaflow.configuration_versions, novaflow.audit_logs
    TO novaflow_runtime;
GRANT USAGE ON SEQUENCE novaflow.users_id_seq, novaflow.passkeys_id_seq,
    novaflow.tenant_memberships_id_seq TO novaflow_runtime;

-- sync queue mode: runtime does not need persistent job-table privileges.
-- Runtime has no migrations-table privilege, DDL, TRUNCATE, REFERENCES,
-- TRIGGER, or ownership privilege. Future features need explicit reviewed grants.
RESET ROLE;

DO $verify$
DECLARE
    protected_table text;
BEGIN
    FOREACH protected_table IN ARRAY ARRAY['audit_logs', 'configuration_versions'] LOOP
        IF NOT has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'SELECT')
            OR NOT has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'INSERT')
            OR has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'UPDATE')
            OR has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'DELETE')
            OR has_table_privilege('novaflow_runtime', 'novaflow.' || protected_table, 'TRUNCATE') THEN
            RAISE EXCEPTION 'Unexpected protected-history table privileges';
        END IF;
    END LOOP;
    IF has_table_privilege('novaflow_runtime', 'novaflow.migrations', 'SELECT')
        OR has_table_privilege('novaflow_runtime', 'novaflow.jobs', 'INSERT') THEN
        RAISE EXCEPTION 'Unexpected runtime migration/queue permissions';
    END IF;
END;
$verify$;
