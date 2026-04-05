-- 订单表增加优惠券字段
ALTER TABLE `{prefix}orders` ADD COLUMN `coupon_code` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `{prefix}orders` ADD COLUMN `coupon_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
