DO $$
BEGIN

TRUNCATE TABLE transactions;

INSERT INTO transactions (name, limit_amount, current_balance)
VALUES
    ('Daenerys Targaryen', 1000 * 100, 0),
    ('Jon Snow', 800 * 100, 0),
    ('Cersei Lannister', 10000 * 100, 0),
    ('Tywin Lannister', 100000 * 100, 0),
    ('Arya Stark', 5000 * 100, 0);

END;
$$;
