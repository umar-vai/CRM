-- Administrator-only regression probe. Run atomically; never use production data.
-- Every test object and row is rolled back by an inner subtransaction, including
-- the SET LOCAL ROLE changes. Failure escapes and aborts the caller's transaction.
DO $probe$
DECLARE
    row_count integer;
    denied_role text;
BEGIN
    IF to_regclass('novaflow.__bootstrap_privilege_probe') IS NOT NULL
        OR to_regprocedure('novaflow.__bootstrap_privilege_function()') IS NOT NULL THEN
        RAISE EXCEPTION 'Probe names already exist; refusing to modify existing objects';
    END IF;

    BEGIN
        SET LOCAL ROLE novaflow_migrator;
        CREATE TABLE novaflow.__bootstrap_privilege_probe (
            id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            tenant_id bigint NOT NULL,
            probe_value integer NOT NULL
        );
        CREATE FUNCTION novaflow.__bootstrap_privilege_function() RETURNS integer
            LANGUAGE sql SECURITY INVOKER AS 'SELECT 1';

        FOREACH denied_role IN ARRAY ARRAY['anon', 'authenticated', 'service_role', 'novaflow_runtime'] LOOP
            IF has_table_privilege(denied_role, 'novaflow.__bootstrap_privilege_probe', 'SELECT')
                OR has_table_privilege(denied_role, 'novaflow.__bootstrap_privilege_probe', 'INSERT')
                OR has_function_privilege(denied_role, 'novaflow.__bootstrap_privilege_function()', 'EXECUTE') THEN
                RAISE EXCEPTION 'Unexpected automatic object privilege';
            END IF;
        END LOOP;

        GRANT SELECT, INSERT ON novaflow.__bootstrap_privilege_probe TO novaflow_runtime;
        GRANT USAGE ON SEQUENCE novaflow.__bootstrap_privilege_probe_id_seq TO novaflow_runtime;
        SET LOCAL ROLE novaflow_runtime;
        INSERT INTO novaflow.__bootstrap_privilege_probe (tenant_id, probe_value) VALUES (1, 7);
        SELECT count(*) INTO row_count FROM novaflow.__bootstrap_privilege_probe WHERE tenant_id = 1;
        IF row_count <> 1 THEN
            RAISE EXCEPTION 'Explicit runtime DML grants did not work';
        END IF;

        BEGIN
            UPDATE novaflow.__bootstrap_privilege_probe SET probe_value = 8 WHERE tenant_id = 1;
            RAISE EXCEPTION 'Runtime unexpectedly has UPDATE permission';
        EXCEPTION WHEN insufficient_privilege THEN NULL;
        END;
        BEGIN
            CREATE TABLE novaflow.__bootstrap_forbidden_probe (id integer);
            RAISE EXCEPTION 'Runtime unexpectedly has schema CREATE permission';
        EXCEPTION WHEN insufficient_privilege THEN NULL;
        END;
        BEGIN
            TRUNCATE novaflow.__bootstrap_privilege_probe;
            RAISE EXCEPTION 'Runtime unexpectedly has TRUNCATE permission';
        EXCEPTION WHEN insufficient_privilege THEN NULL;
        END;
        BEGIN
            PERFORM novaflow.__bootstrap_privilege_function();
            RAISE EXCEPTION 'Runtime unexpectedly has function EXECUTE permission';
        EXCEPTION WHEN insufficient_privilege THEN NULL;
        END;

        -- A private sentinel rolls back this complete inner block, not just DML.
        RAISE EXCEPTION USING ERRCODE = 'NF001', MESSAGE = 'Successful probe rollback';
    EXCEPTION WHEN SQLSTATE 'NF001' THEN NULL;
    END;

    IF to_regclass('novaflow.__bootstrap_privilege_probe') IS NOT NULL
        OR to_regclass('novaflow.__bootstrap_forbidden_probe') IS NOT NULL
        OR to_regprocedure('novaflow.__bootstrap_privilege_function()') IS NOT NULL THEN
        RAISE EXCEPTION 'Probe rollback left an object behind';
    END IF;
END;
$probe$;
