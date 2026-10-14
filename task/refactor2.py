import os
import re

ui_dir = 'Website/ui'
includes_dir = os.path.join(ui_dir, 'includes')
assets_css_dir = os.path.join(ui_dir, 'assets', 'css')
assets_js_dir = os.path.join(ui_dir, 'assets', 'js')

os.makedirs(includes_dir, exist_ok=True)
os.makedirs(assets_css_dir, exist_ok=True)
os.makedirs(assets_js_dir, exist_ok=True)

# 1. Parse phanquyen.html
with open(os.path.join(ui_dir, 'phanquyen.html'), 'r', encoding='utf-8') as f:
    phanquyen_content = f.read()

# Extract style
style_match = re.search(r'<style>(.*?)</style>', phanquyen_content, re.DOTALL)
if style_match:
    with open(os.path.join(assets_css_dir, 'style.css'), 'w', encoding='utf-8') as f:
        f.write(style_match.group(1))
    phanquyen_content = phanquyen_content.replace(style_match.group(0), '<link rel="stylesheet" href="assets/css/style.css">')

# Extract js
js_match = re.search(r'<script id="tailwind-config">(.*?)</script>', phanquyen_content, re.DOTALL)
if js_match:
    with open(os.path.join(assets_js_dir, 'tailwind.config.js'), 'w', encoding='utf-8') as f:
        f.write(js_match.group(1))
    phanquyen_content = phanquyen_content.replace(js_match.group(0), '<script src="assets/js/tailwind.config.js"></script>')

# Split for header based on <main>
# Find the exact <main> tag in phanquyen_content
main_match = re.search(r'(<main[^>]*>)', phanquyen_content)
if not main_match:
    print("Error: Could not find <main> in phanquyen.html")
    exit(1)

main_tag = main_match.group(1)
main_idx = phanquyen_content.find(main_tag)

header_content = phanquyen_content[:main_idx + len(main_tag)] + '\n'
with open(os.path.join(includes_dir, 'header.php'), 'w', encoding='utf-8') as f:
    f.write(header_content)

# Footer content
footer_content = '\n</main>\n</div>\n</body>\n</html>\n'
with open(os.path.join(includes_dir, 'footer.php'), 'w', encoding='utf-8') as f:
    f.write(footer_content)

for filename in os.listdir(ui_dir):
    if filename.endswith('.html'):
        filepath = os.path.join(ui_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        php_filename = filename.replace('.html', '.php')
        php_filepath = os.path.join(ui_dir, php_filename)
        
        if filename == 'login.html':
            # Handle login differently
            if style_match:
                content = content.replace(style_match.group(0), '<link rel="stylesheet" href="assets/css/style.css">')
            if js_match:
                content = content.replace(js_match.group(0), '<script src="assets/js/tailwind.config.js"></script>')
            with open(php_filepath, 'w', encoding='utf-8') as f:
                f.write(content)
        else:
            # Extract content inside <main>...</main>
            main_start_match = re.search(r'<main[^>]*>', content)
            main_end_idx = content.rfind('</main>')
            
            if main_start_match and main_end_idx != -1:
                start_idx = main_start_match.end()
                inner_content = content[start_idx:main_end_idx].strip()
                
                final_content = "<?php include 'includes/header.php'; ?>\n" + inner_content + "\n<?php include 'includes/footer.php'; ?>\n"
                
                with open(php_filepath, 'w', encoding='utf-8') as f:
                    f.write(final_content)
            else:
                print(f"Warning: <main> tags not found properly in {filename}")

        # Remove the html file after converting
        os.remove(filepath)

print("Refactoring completed successfully.")
