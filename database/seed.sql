-- =====================================================================
-- database/seed.sql
-- Data contoh TOKO HANDPHONE (kategori, supplier, produk, relasi produk-supplier).
-- Merek/model/harga bersifat fiktif untuk contoh; boleh diganti sesuai kebutuhan.
-- Akun admin/staff TIDAK ada di file ini karena password harus di-hash.
-- Buat akun dengan: php database/create_user.php (lihat docs/PANDUAN.md).
-- Impor SETELAH schema.sql.
-- =====================================================================
USE inventory_db;

INSERT INTO categories (id, name, description) VALUES
(1, 'Smartphone',          'Handphone pintar (Android/iOS)'),
(2, 'Feature Phone',       'Handphone tombol / non-smartphone'),
(3, 'Casing & Pelindung',  'Casing, tempered glass, anti-gores'),
(4, 'Charger & Kabel',     'Adaptor charger dan kabel data'),
(5, 'Audio',               'Earphone, headset, dan earbuds'),
(6, 'Powerbank',           'Baterai cadangan portabel');

INSERT INTO suppliers (id, name, phone, email, address) VALUES
(1, 'PT Distributor Handphone Nusantara', '061-4521001', 'sales@dhn.example',        'Jl. Gatot Subroto No. 10, Medan'),
(2, 'CV Mobile Prima',                    '061-4521002', 'order@mobileprima.example','Jl. Iskandar Muda No. 25, Medan'),
(3, 'PT Aksesori Global',                 '061-4521003', 'info@aksesoriglobal.example','Jl. Sisingamangaraja No. 8, Medan'),
(4, 'UD Gadget Sentosa',                  '061-4521004', 'cs@gadgetsentosa.example', 'Jl. Putri Hijau No. 3, Medan');

INSERT INTO products (id, category_id, sku, name, price, stock, minimum_stock) VALUES
(1,  1, 'HP-NVX5-256', 'Nova X5 8/256GB Hitam',        3999000.00, 15,  3),
(2,  1, 'HP-NVX5-128', 'Nova X5 6/128GB Biru',         3199000.00, 20,  3),
(3,  1, 'HP-ZNP10',    'Zenith P10 8/256GB',           5499000.00,  2,  3),
(4,  1, 'HP-ZNL7',     'Zenith L7 4/64GB',             1699000.00,  0,  3),
(5,  2, 'FP-NVK1',     'Nova K1 Dual SIM',              349000.00, 25,  5),
(6,  3, 'CS-NVX5',     'Casing Silikon Nova X5',         45000.00, 60, 15),
(7,  3, 'TG-NVX5',     'Tempered Glass Nova X5',         35000.00,  8, 15),
(8,  3, 'TG-ZNP10',    'Tempered Glass Zenith P10',      35000.00, 40, 15),
(9,  4, 'CHG-20W',     'Charger Fast Charging 20W',      95000.00, 30, 10),
(10, 4, 'CBL-USBC',    'Kabel Data USB-C 1m',            40000.00, 50, 15),
(11, 4, 'CBL-MICRO',   'Kabel Data Micro USB 1m',        30000.00,  6, 10),
(12, 5, 'EAR-WR1',     'Earphone Berkabel Stereo',       55000.00, 35, 10),
(13, 5, 'TWS-ZNB2',    'Earbuds TWS Zenith Buds 2',     385000.00, 12,  5),
(14, 6, 'PWB-10K',     'Powerbank 10.000 mAh',          165000.00, 18,  5);

INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
(1, 1, 3750000.00), (1, 2, 3790000.00),
(2, 1, 2980000.00),
(3, 1, 5150000.00), (3, 2, 5200000.00),
(4, 2, 1550000.00),
(5, 2, 300000.00),
(6, 3, 30000.00),
(7, 3, 20000.00),
(8, 3, 20000.00),
(9, 3, 75000.00), (9, 4, 77000.00),
(10, 3, 28000.00), (10, 4, 27000.00),
(11, 4, 20000.00),
(12, 4, 38000.00),
(13, 1, 330000.00), (13, 3, 335000.00),
(14, 3, 135000.00), (14, 4, 138000.00);
