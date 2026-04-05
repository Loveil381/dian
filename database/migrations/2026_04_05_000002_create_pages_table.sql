-- 创建页面表：隐私政策、用户协议、退换货政策、关于我们、联系方式
CREATE TABLE IF NOT EXISTS `{PREFIX}pages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(50) NOT NULL,
    `title`      VARCHAR(100) NOT NULL,
    `content`    LONGTEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `{PREFIX}pages` (`slug`, `title`, `content`) VALUES
('about', '关于我们', '<h2>关于我们</h2>\n<p>魔女的小店是一家专注于健康保健品的独立小店，主要提供来自印度及国内的优质保健营养补充品。</p>\n<p>我们致力于为有需要的朋友提供安全、实惠、可靠的健康产品，让每位顾客都能以合理的价格获得所需的保健支持。</p>\n<p>所有产品均来自正规厂家，附有批号及效期信息。</p>'),
('privacy', '隐私政策', '<h2>隐私政策</h2>\n<p>我们收集以下信息用于完成订单：</p>\n<ul>\n<li>姓名、收货地址、联系电话</li>\n<li>支付记录（不储存银行卡信息）</li>\n</ul>\n<p>我们承诺：</p>\n<ul>\n<li>您的个人信息仅用于订单处理及物流配送</li>\n<li>不会向第三方出售或共享您的个人信息</li>\n<li>订单完成后信息将在180天后删除</li>\n</ul>\n<p>如需查询或删除您的数据，请联系我们。</p>'),
('terms', '用户协议', '<h2>用户协议</h2>\n<p>请在购买前仔细阅读以下条款：</p>\n<h3>购买须知</h3>\n<p>本店所售产品为保健营养补充品，购买者须年满18周岁，并自行了解产品适用情况。购买即视为同意本条款。</p>\n<h3>买卖双方权责</h3>\n<p><strong>买方：</strong>提供准确的收货信息，确保签收。</p>\n<p><strong>卖方：</strong>按时发货，提供真实的产品信息及凭证。</p>\n<h3>物流说明</h3>\n<p>本店使用国际邮政/顺丰发货，运费以结算页面为准。国际运输存在清关风险，买方需了解目的地进口规定。因海关扣押导致的损失，本店不承担全额赔偿责任，但将协助处理后续事宜。</p>\n<h3>退换货政策</h3>\n<ul>\n<li>产品破损或发错货：联系我们补发或退款</li>\n<li>个人原因不退换</li>\n<li>清关被扣：按供应商政策处理（运费50%双方共担）</li>\n<li>退款处理时间：3-5个工作日</li>\n</ul>'),
('contact', '联系方式', '<h2>联系方式</h2>\n<p>如有任何问题，欢迎通过以下方式联系我们：</p>\n<ul>\n<li><strong>微信：</strong>[你的微信号]</li>\n<li><strong>邮箱：</strong>[可选]</li>\n<li><strong>工作时间：</strong>周一至周六 10:00 - 22:00（北京时间）</li>\n</ul>\n<p>我们将在24小时内回复您的咨询。</p>'),
('refund', '退换货政策', '<h2>退换货政策</h2>\n<h3>退款条件</h3>\n<ul>\n<li>产品破损、漏液、变质</li>\n<li>发错产品</li>\n<li>物流丢失（需等待45天确认）</li>\n</ul>\n<h3>不予退款</h3>\n<ul>\n<li>个人原因不想要</li>\n<li>清关被扣（按条款另行处理）</li>\n<li>地址填写错误导致无法投递</li>\n</ul>\n<h3>处理流程</h3>\n<ol>\n<li>拍照留证，联系客服</li>\n<li>确认情况后48小时内回复方案</li>\n<li>退款原路返回，3-5工作日到账</li>\n</ol>');
