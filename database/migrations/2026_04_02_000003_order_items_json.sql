-- 将订单表的 items 字段约定为 JSON 数组字符串。
-- 历史上 items 可能存储为拼接字符串，升级后建议通过业务脚本逐步迁移旧数据。
-- JSON 结构示例：
-- [{"product_id":1,"name":"魔法茶杯","sku_name":"标准款","price":99.00,"quantity":2}]

ALTER TABLE `{PREFIX}orders`
    MODIFY `items` TEXT NULL COMMENT '订单商品 JSON 数组';
