-- 发货方式类型表
CREATE TABLE IF NOT EXISTS {prefix}fulfillment_types (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(30) NOT NULL,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(200) NOT NULL DEFAULT '',
    icon VARCHAR(50) NOT NULL DEFAULT 'local_shipping',
    badge_color VARCHAR(30) NOT NULL DEFAULT '#6a37d4',
    allow_zero_stock TINYINT(1) NOT NULL DEFAULT 0,
    sort INT NOT NULL DEFAULT 0,
    status VARCHAR(10) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_fulfillment_types_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 预置两个常用类型
INSERT IGNORE INTO {prefix}fulfillment_types (slug, name, description, icon, badge_color, allow_zero_stock, sort) VALUES
('in_stock', '国内现货', '国内仓库有货，1-3天内发出', 'inventory_2', '#34d399', 0, 1),
('presale', '预售', '预售商品，付款后7-15天发货', 'schedule', '#fbbf24', 1, 2);
