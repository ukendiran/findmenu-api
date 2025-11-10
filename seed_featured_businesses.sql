-- Seed Featured Businesses for Partners Section
-- This script adds sample featured businesses with logos

-- Update existing businesses to be featured (if any exist)
UPDATE businesses 
SET 
    status = 1,
    is_featured = 1,
    logo = CONCAT('images/', code, '/logo.png')
WHERE id <= 6 AND logo IS NULL;

-- Insert sample featured businesses if table is empty
INSERT INTO businesses (name, email, code, mobile, address, logo, type, status, is_featured, created_at, updated_at)
SELECT * FROM (
    SELECT 
        'McDonald\'s India' as name,
        'contact@mcdonalds.in' as email,
        'mcdonalds-india' as code,
        '+91-1234567890' as mobile,
        'Mumbai, India' as address,
        'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/McDonald%27s_Golden_Arches.svg/200px-McDonald%27s_Golden_Arches.svg.png' as logo,
        'restaurant' as type,
        1 as status,
        1 as is_featured,
        NOW() as created_at,
        NOW() as updated_at
    UNION ALL
    SELECT 
        'Starbucks Coffee',
        'info@starbucks.in',
        'starbucks-coffee',
        '+91-1234567891',
        'Delhi, India',
        'https://upload.wikimedia.org/wikipedia/en/thumb/d/d3/Starbucks_Corporation_Logo_2011.svg/200px-Starbucks_Corporation_Logo_2011.svg.png',
        'cafe',
        1,
        1,
        NOW(),
        NOW()
    UNION ALL
    SELECT 
        'Subway India',
        'contact@subway.in',
        'subway-india',
        '+91-1234567892',
        'Bangalore, India',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Subway_2016_logo.svg/200px-Subway_2016_logo.svg.png',
        'restaurant',
        1,
        1,
        NOW(),
        NOW()
    UNION ALL
    SELECT 
        'KFC India',
        'info@kfc.in',
        'kfc-india',
        '+91-1234567893',
        'Chennai, India',
        'https://upload.wikimedia.org/wikipedia/en/thumb/b/bf/KFC_logo.svg/200px-KFC_logo.svg.png',
        'restaurant',
        1,
        1,
        NOW(),
        NOW()
    UNION ALL
    SELECT 
        'Pizza Hut India',
        'contact@pizzahut.in',
        'pizza-hut-india',
        '+91-1234567894',
        'Pune, India',
        'https://upload.wikimedia.org/wikipedia/en/thumb/d/d2/Pizza_Hut_logo.svg/200px-Pizza_Hut_logo.svg.png',
        'restaurant',
        1,
        1,
        NOW(),
        NOW()
    UNION ALL
    SELECT 
        'Domino\'s Pizza',
        'info@dominos.in',
        'dominos-pizza',
        '+91-1234567895',
        'Hyderabad, India',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/74/Dominos_pizza_logo.svg/200px-Dominos_pizza_logo.svg.png',
        'restaurant',
        1,
        1,
        NOW(),
        NOW()
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM businesses WHERE code IN (
        'mcdonalds-india', 'starbucks-coffee', 'subway-india', 
        'kfc-india', 'pizza-hut-india', 'dominos-pizza'
    )
);

-- Verify the data
SELECT id, name, code, logo, type, status, is_featured 
FROM businesses 
WHERE is_featured = 1 
LIMIT 10;
