-- 商品表新增发货方式选项列（JSON）
ALTER TABLE {prefix}products ADD COLUMN fulfillment_options TEXT DEFAULT NULL;
