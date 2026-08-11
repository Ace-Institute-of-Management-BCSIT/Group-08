-- Run once on databases created before worker hourly offers were added.
ALTER TABLE worker_profiles
    ADD COLUMN hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 2000.00 AFTER experience_years;

UPDATE worker_profiles wp
INNER JOIN worker_categories wc ON wc.worker_id = wp.worker_id
INNER JOIN categories c ON c.category_id = wc.category_id
SET wp.hourly_rate = CASE c.category_name
    WHEN 'House Work' THEN 2200.00
    WHEN 'Culinary Aid' THEN 2600.00
    WHEN 'Culinary Service' THEN 3000.00
    WHEN 'Home Tuition' THEN 4000.00
    WHEN 'Education' THEN 4000.00
    WHEN 'Pet Care' THEN 2500.00
    WHEN 'Self Care' THEN 3500.00
    WHEN 'Elderly Care' THEN 3800.00
    WHEN 'Babysitting' THEN 2800.00
    WHEN 'Gardening' THEN 2300.00
    WHEN 'Plumbing' THEN 4500.00
    WHEN 'Electrical Work' THEN 5000.00
    WHEN 'Personal' THEN 3500.00
    WHEN 'Repair' THEN 4800.00
    WHEN 'Other Services' THEN 2600.00
    ELSE 2000.00
END;
