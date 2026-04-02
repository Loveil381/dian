import os
import re

file_path = 'admin/index.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Extract CSS
css_match = re.search(r'<style>(.*?)</style>', content, re.DOTALL)
if css_match:
    with open('assets/css/admin.css', 'w', encoding='utf-8') as f:
        f.write(css_match.group(1).strip() + '\n')

# 2. Extract JS (last script)
js_matches = re.findall(r'<script>(.*?)</script>', content, re.DOTALL)
if js_matches:
    with open('assets/js/admin.js', 'w', encoding='utf-8') as f:
        f.write(js_matches[-1].strip() + '\n')

# 3. Extract Helpers
helpers_match = re.search(r'(function shop_admin_flash\(.*?function shop_admin_post_checked[^}]+\})', content, re.DOTALL)
if helpers_match:
    with open('admin/includes/helpers.php', 'w', encoding='utf-8') as f:
        f.write('<?php\ndeclare(strict_types=1);\n\n')
        f.write(helpers_match.group(1) + '\n')

# 4. Extract Actions
actions_match = re.search(r"(if \(\$_SERVER\['REQUEST_METHOD'\] === 'POST'\) \{.*?header\('Location: ' \. \$adminUrl\);\s*exit;\s*\})", content, re.DOTALL)
if actions_match:
    actions_code = actions_match.group(1)
    
    # 注入 CSRF 和 Tab 参数解析
    actions_code = actions_code.replace(
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {",
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {\n    require_once __DIR__ . '/../../includes/csrf.php';\n    csrf_verify();\n    $reqTab = $_POST['tab'] ?? '';"
    )
    
    # 调整返回重定向，加入 tab
    actions_code = actions_code.replace(
        "header('Location: ' . $adminUrl);",
        "header('Location: ' . $adminUrl . ($reqTab !== '' ? '&tab=' . urlencode($reqTab) : ''));"
    )
    
    with open('admin/controllers/actions.php', 'w', encoding='utf-8') as f:
        f.write("<?php\ndeclare(strict_types=1);\n\n" + actions_code + "\n")
