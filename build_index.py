import os
import re

with open('admin/index.php', 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Replace helpers with require
code = re.sub(r'function shop_admin_flash\(.*?function shop_admin_post_checked[^}]+\}', "require_once __DIR__ . '/includes/helpers.php';", code, flags=re.DOTALL)

# 2. Replace POST action block with require
code = re.sub(r"if \(\$_SERVER\['REQUEST_METHOD'\] === 'POST'\) \{.*?header\('Location: ' \. \$adminUrl\);\s*exit;\s*\}", "require_once __DIR__ . '/controllers/actions.php';", code, flags=re.DOTALL)

# 3. Remove CSS
code = re.sub(r'<style>.*?</style>', '', code, flags=re.DOTALL)

# 4. Remove trailing JS
code = re.sub(r'<script>\s*document\.addEventListener.*?</script>', '', code, flags=re.DOTALL)

# 5. Extract all PHP logic before HTML
html_index = code.find('<!DOCTYPE html>')
if html_index != -1:
    code = code[:html_index]

# 6. Append dynamic tab handling and layout require
code += "\n$currentTab = $_GET['tab'] ?? 'dashboard';\n"
code += "require __DIR__ . '/views/layout.php';\n"

# Rename original to admin_backup and write new index
os.rename('admin/index.php', 'admin/index.original.php')
with open('admin/index.php', 'w', encoding='utf-8') as f:
    f.write(code)
