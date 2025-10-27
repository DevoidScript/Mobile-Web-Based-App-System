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

-- Policy: Donors can only see their own subscriptions
CREATE POLICY "Donors can view own subscriptions" ON push_subscriptions
    FOR SELECT USING (donor_id = current_setting('app.current_donor_id')::INTEGER);

-- Policy: Donors can insert their own subscriptions
CREATE POLICY "Donors can insert own subscriptions" ON push_subscriptions
    FOR INSERT WITH CHECK (donor_id = current_setting('app.current_donor_id')::INTEGER);

-- Policy: Donors can delete their own subscriptions
CREATE POLICY "Donors can delete own subscriptions" ON push_subscriptions
    FOR DELETE USING (donor_id = current_setting('app.current_donor_id')::INTEGER);

-- Policy: Service role can do everything (for backend operations)
CREATE POLICY "Service role full access to push_subscriptions" ON push_subscriptions
    FOR ALL USING (current_user = 'service_role');

CREATE POLICY "Service role full access to donor_notifications" ON donor_notifications
    FOR ALL USING (current_user = 'service_role');

-- Policy: Donors can view their own notifications
CREATE POLICY "Donors can view own notifications" ON donor_notifications
    FOR SELECT USING (donor_id = current_setting('app.current_donor_id')::INTEGER);

-- Add comments for documentation
COMMENT ON TABLE push_subscriptions IS 'Stores Web Push subscriptions for each donor';
COMMENT ON TABLE donor_notifications IS 'Logs all push notifications sent to donors';
COMMENT ON COLUMN push_subscriptions.endpoint IS 'Browser push service endpoint URL';
COMMENT ON COLUMN push_subscriptions.p256dh IS 'Client public key for encryption';
COMMENT ON COLUMN push_subscriptions.auth IS 'Client authentication secret';
COMMENT ON COLUMN donor_notifications.payload_json IS 'Full notification payload sent';
COMMENT ON COLUMN donor_notifications.status IS 'Delivery status: sent, failed, or pending';




