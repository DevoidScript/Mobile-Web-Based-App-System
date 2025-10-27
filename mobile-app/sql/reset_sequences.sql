-- Fix out-of-sync sequences after synthetic inserts
-- Run this in Supabase SQL editor once to install the helper functions

-- Function: check_sequences
-- Lists sequences in public schema with their current last_value vs table max(id)
CREATE OR REPLACE FUNCTION public.check_sequences()
RETURNS TABLE (
  schema_name text,
  table_name text,
  column_name text,
  sequence_name text,
  last_value bigint,
  is_called boolean,
  max_id bigint,
  next_nextval bigint,
  needs_reset boolean
)
LANGUAGE plpgsql
AS $$
DECLARE
  r record;
  max_val bigint;
  last_val bigint;
  called boolean;
  projected_next bigint;
BEGIN
  FOR r IN
    SELECT ns.nspname AS schema_name,
           t.relname  AS table_name,
           a.attname  AS column_name,
           s.relname  AS sequence_name
    FROM pg_class s
    JOIN pg_depend d ON d.objid = s.oid AND d.deptype IN ('a','i')
    JOIN pg_class t ON d.refobjid = t.oid
    JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.refobjsubid
    JOIN pg_namespace ns ON ns.oid = t.relnamespace
    WHERE s.relkind = 'S' AND ns.nspname = 'public'
  LOOP
    EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I.%I', r.column_name, r.schema_name, r.table_name)
      INTO max_val;
    EXECUTE format('SELECT last_value, is_called FROM %I.%I', r.schema_name, r.sequence_name)
      INTO last_val, called;

    -- Predict what the next nextval() would return for this sequence
    projected_next := last_val + CASE WHEN called THEN 1 ELSE 0 END;

    schema_name := r.schema_name;
    table_name := r.table_name;
    column_name := r.column_name;
    sequence_name := r.sequence_name;
    last_value := last_val;
    is_called := called;
    max_id := max_val;
    next_nextval := projected_next;
    -- Needs reset only if the next nextval would be <= current MAX(id)
    needs_reset := projected_next <= max_val;
    RETURN NEXT;
  END LOOP;
END;
$$ SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.check_sequences() TO anon, authenticated;

-- Function: reset_all_sequences
-- Resets each sequence to table MAX(id) so the nextval generates max+1
CREATE OR REPLACE FUNCTION public.reset_all_sequences()
RETURNS TABLE (
  schema_name text,
  table_name text,
  column_name text,
  sequence_name text,
  old_last_value bigint,
  set_to bigint
)
LANGUAGE plpgsql
AS $$
DECLARE
  r record;
  max_val bigint;
  old_val bigint;
  fqseq text;
  desired_val bigint;
  desired_is_called boolean;
BEGIN
  FOR r IN
    SELECT ns.nspname AS schema_name,
           t.relname  AS table_name,
           a.attname  AS column_name,
           s.relname  AS sequence_name
    FROM pg_class s
    JOIN pg_depend d ON d.objid = s.oid AND d.deptype IN ('a','i')
    JOIN pg_class t ON d.refobjid = t.oid
    JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.refobjsubid
    JOIN pg_namespace ns ON ns.oid = t.relnamespace
    WHERE s.relkind = 'S' AND ns.nspname = 'public'
  LOOP
    EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I.%I', r.column_name, r.schema_name, r.table_name)
      INTO max_val;
    EXECUTE format('SELECT last_value FROM %I.%I', r.schema_name, r.sequence_name)
      INTO old_val;

    fqseq := r.schema_name || '.' || r.sequence_name;

    -- For empty tables (max=0), set to 1 with is_called=false so nextval returns 1
    IF max_val <= 0 THEN
      desired_val := 1;
      desired_is_called := false;
    ELSE
      desired_val := max_val;
      desired_is_called := true;
    END IF;

    EXECUTE format('SELECT setval(%L::regclass, %s, %s)', fqseq, desired_val::text, CASE WHEN desired_is_called THEN 'true' ELSE 'false' END);

    schema_name := r.schema_name;
    table_name := r.table_name;
    column_name := r.column_name;
    sequence_name := r.sequence_name;
    old_last_value := old_val;
    set_to := desired_val;
    RETURN NEXT;
  END LOOP;
END;
$$ SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.reset_all_sequences() TO anon, authenticated;


