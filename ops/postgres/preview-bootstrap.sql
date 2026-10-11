-- One-time infrastructure bootstrap, not a Laravel application migration.
-- Target: the selected synthetic-data preview project only.
-- Run atomically as its existing database administrator. Fail on existing names;
-- never silently adopt another application's schema or overwrite its roles.
-- No passwords, login credentials, business tables or customer data are created.
DO $guard$
BEGIN
    IF current_database() <> 'postgres'
        OR EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'novaflow')
        OR EXISTS (SELECT 1 FROM pg_roles WHERE rolname IN ('novaflow_migrator', 'novaflow_runtime')) THEN
        RAISE EXCEPTION 'Wrong database or preview bootstrap names already exist; inspect before proceeding';
    END IF;
END;
$guard$;

CREATE ROLE novaflow_migrator NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE
    NOINHERIT NOREPLICATION NOBYPASSRLS CONNECTION LIMIT 2 PASSWORD NULL;
CREATE ROLE novaflow_runtime NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE
    NOINHERIT NOREPLICATION NOBYPASSRLS CONNECTION LIMIT 5 PASSWORD NULL;

-- Existing administrator can provision/verify the new roles. No application
-- role inherits administrator or migration-owner capabilities.
GRANT novaflow_migrator TO postgres WITH SET TRUE, INHERIT FALSE;
GRANT novaflow_runtime TO postgres WITH SET TRUE, INHERIT FALSE;

CREATE SCHEMA novaflow AUTHORIZATION novaflow_migrator;
GRANT CONNECT ON DATABASE postgres TO novaflow_migrator, novaflow_runtime;

ALTER ROLE novaflow_migrator IN DATABASE postgres SET search_path = novaflow;
ALTER ROLE novaflow_runtime IN DATABASE postgres SET search_path = novaflow;

-- Defaults belong to the creator role, not the role that happens to run this
-- bootstrap. Global function/type defaults must be revoked globally for this
-- NEW role; a per-schema revoke cannot remove PostgreSQL's global PUBLIC grant.
SET LOCAL ROLE novaflow_migrator;
REVOKE ALL ON SCHEMA novaflow FROM PUBLIC, anon, authenticated, service_role;
GRANT USAGE ON SCHEMA novaflow TO novaflow_runtime;
ALTER DEFAULT PRIVILEGES FOR ROLE novaflow_migrator
    REVOKE ALL ON TABLES FROM PUBLIC, anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES FOR ROLE novaflow_migrator
    REVOKE ALL ON SEQUENCES FROM PUBLIC, anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES FOR ROLE novaflow_migrator
    REVOKE ALL ON FUNCTIONS FROM PUBLIC, anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES FOR ROLE novaflow_migrator
    REVOKE ALL ON TYPES FROM PUBLIC, anon, authenticated, service_role;

-- Intentionally no default DML grant to runtime. After Laravel migrations,
-- grant only required privileges on named tables/sequences, with separate
-- insert/select-only audit permissions. No blanket ALL/TRUNCATE/DDL grants.
COMMENT ON SCHEMA novaflow IS
    'Private NovaFlow preview schema. Do not expose through the Supabase Data API.';
RESET ROLE;

-- Verification is transactional: an unexpected permission aborts bootstrap.
DO $verify$
DECLARE
    denied_role text;
BEGIN
    IF pg_get_userbyid((SELECT nspowner FROM pg_namespace WHERE nspname = 'novaflow')) <> 'novaflow_migrator'
        OR NOT has_schema_privilege('novaflow_runtime', 'novaflow', 'USAGE')
        OR has_schema_privilege('novaflow_runtime', 'novaflow', 'CREATE')
        OR pg_has_role('novaflow_runtime', 'novaflow_migrator', 'MEMBER')
        OR pg_has_role('novaflow_runtime', 'postgres', 'MEMBER') THEN
        RAISE EXCEPTION 'Preview ownership/role isolation verification failed';
    END IF;

    FOREACH denied_role IN ARRAY ARRAY['anon', 'authenticated', 'service_role'] LOOP
        IF has_schema_privilege(denied_role, 'novaflow', 'USAGE')
            OR has_schema_privilege(denied_role, 'novaflow', 'CREATE') THEN
            RAISE EXCEPTION 'Data API role unexpectedly has private schema access';
        END IF;
    END LOOP;

    IF EXISTS (
        SELECT 1 FROM pg_roles
        WHERE rolname IN ('novaflow_migrator', 'novaflow_runtime')
            AND (rolcanlogin OR rolsuper OR rolcreaterole OR rolcreatedb OR rolreplication OR rolbypassrls)
    ) THEN
        RAISE EXCEPTION 'Preview role has unexpected login or administrative capability';
    END IF;
END;
$verify$;
