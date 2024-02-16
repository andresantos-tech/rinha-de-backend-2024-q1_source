DO $$
BEGIN

UPDATE accounts SET current_balance = 0;
TRUNCATE TABLE transactions;

END;
$$;
