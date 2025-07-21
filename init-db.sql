-- init-db.sql

-- 1) Create both databases
CREATE DATABASE transaction_db;
CREATE DATABASE transaction_web_db;

-- 2) Schema & seed for transaction_db
\c transaction_db;

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active','inactive','blocked')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_users_email      ON users(email);
CREATE INDEX idx_users_status     ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);

-- transactions table
CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('topup','payment','transfer')),
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(15,2)   DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'IDR',
    status VARCHAR(20) DEFAULT 'pending'
       CHECK (status IN ('pending','processing','completed','failed','cancelled')),
    reference VARCHAR(100) UNIQUE NOT NULL,
    payment_gateway_id VARCHAR(255),
    description TEXT,
    metadata JSONB DEFAULT '{}'::jsonb,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_transactions_user_id    ON transactions(user_id);
CREATE INDEX idx_transactions_status     ON transactions(status);
CREATE INDEX idx_transactions_type       ON transactions(type);
CREATE INDEX idx_transactions_reference  ON transactions(reference);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);

-- 3) Seed data for users
INSERT INTO users (email, password, first_name, last_name, phone, balance, status)
VALUES
  ('john.doe@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John',   'Doe',     '+6281234567890', 1000000.00, 'active'),
  ('jane.smith@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane',   'Smith',   '+6281234567891',  500000.00, 'active'),
  ('bob.johnson@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob',    'Johnson', '+6281234567892',  750000.00, 'active'),
  ('alice.williams@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Alice', 'Williams','+6281234567893',  250000.00, 'inactive'),
  ('charlie.brown@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Charlie','Brown',   '+6281234567894',       0.00, 'blocked');

-- 4) Seed data for transactions
INSERT INTO transactions (user_id, type, amount, fee, currency, reference, payment_gateway_id, description, metadata, status, processed_at)
VALUES
  (
    -- John top-up
    (SELECT id FROM users WHERE email='john.doe@example.com'),
    'topup', 100000.00, 2500.00, 'IDR',
    'ref-topup-001', gen_random_uuid(),
    'Initial balance top-up', '{}'::jsonb,
    'completed', NOW() - INTERVAL '7 days'
  ),
  (
    -- Jane payment
    (SELECT id FROM users WHERE email='jane.smith@example.com'),
    'payment', 50000.00, 1500.00, 'IDR',
    'ref-payment-001', gen_random_uuid(),
    'E-commerce purchase', '{}'::jsonb,
    'completed', NOW() - INTERVAL '5 days'
  ),
  (
    -- Transfer John → Jane
    (SELECT id FROM users WHERE email='john.doe@example.com'),
    'transfer', 25000.00, 500.00, 'IDR',
    'ref-transfer-001', gen_random_uuid(),
    'Money transfer to friend', '{}'::jsonb,
    'completed', NOW() - INTERVAL '3 days'
  ),
  (
    -- Bob pending top-up
    (SELECT id FROM users WHERE email='bob.johnson@example.com'),
    'topup', 200000.00, 5000.00, 'IDR',
    'ref-topup-002', gen_random_uuid(),
    'Monthly top-up', '{}'::jsonb,
    'pending', NULL
  ),
  (
    -- Jane failed payment
    (SELECT id FROM users WHERE email='jane.smith@example.com'),
    'payment', 75000.00, 2000.00, 'IDR',
    'ref-payment-002', gen_random_uuid(),
    'Subscription payment', '{}'::jsonb,
    'failed', NULL
  );

-- 5) (Optional) Seed system_settings table if sudah dibuat oleh Laravel
\c transaction_db;

CREATE TABLE IF NOT EXISTS system_settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT NOT NULL,
    type VARCHAR(20) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO system_settings (key, value, type, description)
VALUES
  ('transaction_fee_percentage', '2.5',       'string',  'Default transaction fee percentage'),
  ('max_transaction_amount',      '10000000',  'integer', 'Maximum transaction amount in IDR'),
  ('min_transaction_amount',      '10000',     'integer', 'Minimum transaction amount in IDR'),
  ('auto_approve_threshold',      '100000',    'integer', 'Auto-approve transactions below this amount'),
  ('maintenance_mode',            'false',     'boolean', 'Enable/disable maintenance mode'),
  ('supported_payment_methods',   '["credit_card","bca_va","bni_va","bri_va","gopay","shopeepay","indomaret","alfamart"]','json','List of supported payment methods');

-- 6) Switch back to default DB
\c postgres;
