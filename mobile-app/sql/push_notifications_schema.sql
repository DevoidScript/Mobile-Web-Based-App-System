-- Push Notifications Schema for Supabase
-- Run this SQL in your Supabase SQL Editor

-- Table to store push subscriptions for each donor
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    donor_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(donor_id, endpoint)
);

-- Table to log all push notifications sent
CREATE TABLE IF NOT EXISTS donor_notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    donor_id INTEGER NOT NULL,
    payload_json JSONB NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('sent', 'failed', 'pending')),
    sent_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    blood_drive_id INTEGER,
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_push_subscriptions_donor_id ON push_subscriptions(donor_id);
CREATE INDEX IF NOT EXISTS idx_push_subscriptions_created_at ON push_subscriptions(created_at);
CREATE INDEX IF NOT EXISTS idx_donor_notifications_donor_id ON donor_notifications(donor_id);
CREATE INDEX IF NOT EXISTS idx_donor_notifications_status ON donor_notifications(status);
CREATE INDEX IF NOT EXISTS idx_donor_notifications_blood_drive_id ON donor_notifications(blood_drive_id);

-- Add RLS (Row Level Security) policies
ALTER TABLE push_subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE donor_notifications ENABLE ROW LEVEL SECURITY;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'push_subscriptions'
          AND policyname = 'Donors can view own subscriptions'
    ) THEN
        CREATE POLICY "Donors can view own subscriptions" ON push_subscriptions
            FOR SELECT USING (donor_id = current_setting('app.current_donor_id')::INTEGER);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'push_subscriptions'
          AND policyname = 'Donors can insert own subscriptions'
    ) THEN
        CREATE POLICY "Donors can insert own subscriptions" ON push_subscriptions
            FOR INSERT WITH CHECK (donor_id = current_setting('app.current_donor_id')::INTEGER);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'push_subscriptions'
          AND policyname = 'Donors can delete own subscriptions'
    ) THEN
        CREATE POLICY "Donors can delete own subscriptions" ON push_subscriptions
            FOR DELETE USING (donor_id = current_setting('app.current_donor_id')::INTEGER);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'push_subscriptions'
          AND policyname = 'Service role full access to push_subscriptions'
    ) THEN
        CREATE POLICY "Service role full access to push_subscriptions" ON push_subscriptions
            FOR ALL USING (current_user = 'service_role');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'donor_notifications'
          AND policyname = 'Service role full access to donor_notifications'
    ) THEN
        CREATE POLICY "Service role full access to donor_notifications" ON donor_notifications
            FOR ALL USING (current_user = 'service_role');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE schemaname = 'public'
          AND tablename = 'donor_notifications'
          AND policyname = 'Donors can view own notifications'
    ) THEN
        CREATE POLICY "Donors can view own notifications" ON donor_notifications
            FOR SELECT USING (donor_id = current_setting('app.current_donor_id')::INTEGER);
    END IF;
END $$;

-- Add comments for documentation
COMMENT ON TABLE push_subscriptions IS 'Stores Web Push subscriptions for each donor';
COMMENT ON TABLE donor_notifications IS 'Logs all push notifications sent to donors';
COMMENT ON COLUMN push_subscriptions.endpoint IS 'Browser push service endpoint URL';
COMMENT ON COLUMN push_subscriptions.p256dh IS 'Client public key for encryption';
COMMENT ON COLUMN push_subscriptions.auth IS 'Client authentication secret';
COMMENT ON COLUMN donor_notifications.payload_json IS 'Full notification payload sent';
COMMENT ON COLUMN donor_notifications.status IS 'Delivery status: sent, failed, or pending';

-- Enforce at most one subscription per donor
DO $$
BEGIN
    -- Try to create a unique index on donor_id. If duplicates exist, this will fail gracefully.
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes WHERE schemaname = 'public' AND indexname = 'idx_push_subscriptions_unique_donor'
    ) THEN
        BEGIN
            EXECUTE 'CREATE UNIQUE INDEX idx_push_subscriptions_unique_donor ON public.push_subscriptions(donor_id)';
        EXCEPTION WHEN unique_violation THEN
            RAISE NOTICE 'Cannot create unique index on push_subscriptions(donor_id) due to duplicate donor_id rows. Please deduplicate existing data.';
        WHEN others THEN
            RAISE NOTICE 'Skipping unique index creation on push_subscriptions(donor_id): %', SQLERRM;
        END;
    END IF;
END $$;


-- Ensure donor_id columns reference the canonical donor record
-- Assumes donor_form.id is the primary key for donors
DO $$
DECLARE
    donor_pk_column TEXT;
BEGIN
    -- Detect donor_form primary key column: prefer id, fallback to donor_id
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'donor_form' AND column_name = 'id'
    ) THEN
        donor_pk_column := 'id';
    ELSIF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'donor_form' AND column_name = 'donor_id'
    ) THEN
        donor_pk_column := 'donor_id';
    ELSE
        RAISE NOTICE 'Could not find id or donor_id on donor_form; skipping FK for push_subscriptions.';
        RETURN;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'fk_push_subscriptions_donor'
    ) THEN
        EXECUTE format(
            'ALTER TABLE push_subscriptions ADD CONSTRAINT fk_push_subscriptions_donor FOREIGN KEY (donor_id) REFERENCES donor_form(%I) ON DELETE CASCADE',
            donor_pk_column
        );
    END IF;
END $$;

DO $$
DECLARE
    donor_pk_column TEXT;
BEGIN
    -- Detect donor_form primary key column: prefer id, fallback to donor_id
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'donor_form' AND column_name = 'id'
    ) THEN
        donor_pk_column := 'id';
    ELSIF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'donor_form' AND column_name = 'donor_id'
    ) THEN
        donor_pk_column := 'donor_id';
    ELSE
        RAISE NOTICE 'Could not find id or donor_id on donor_form; skipping FK for donor_notifications.';
        RETURN;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'fk_donor_notifications_donor'
    ) THEN
        EXECUTE format(
            'ALTER TABLE donor_notifications ADD CONSTRAINT fk_donor_notifications_donor FOREIGN KEY (donor_id) REFERENCES donor_form(%I) ON DELETE CASCADE',
            donor_pk_column
        );
    END IF;
END $$;




