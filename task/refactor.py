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

# Split for header
split_marker = '<main class="relative pt-16 w-full px-space-lg bg-surface"><div class="flex flex-col w-full pb-space-xl">'
parts = phanquyen_content.split(split_marker)

header_content = parts[0] + split_marker
with open(os.path.join(includes_dir, 'header.php'), 'w', encoding='utf-8') as f:
    f.write(header_content)

# Footer content: just the closing tags
footer_content = '</div></main></div>\n</body>\n</html>'
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
            if split_marker in content:
                main_inner = content.split(split_marker)[1]
                
                # We want to extract everything up to `</main></div>` (excluding it)
                # But sometimes there are scripts after the content before `</main>`
                # So we replace the closing tags with nothing.
                
                # Replace the exact sequence of closing tags
                main_inner = re.sub(r'</div>\s*</main>\s*</div>\s*</body>\s*</html>', '', main_inner)
                main_inner = re.sub(r'</script>\s*</main>\s*</div>\s*</body>\s*</html>', '</script>', main_inner)
                
                # Another case, like `phanquyen.html`, it might be `</script></main></div>` then `</body></html>`
                main_inner = re.sub(r'</main></div>\s*</body>\s*</html>', '', main_inner)
                
                # For safety, find `</main>` and take everything before it.
                main_close_idx = main_inner.rfind('</main>')
                if main_close_idx != -1:
                    # check if there's a </div> right before </main> that needs to be removed
                    # `<div class="flex flex-col w-full pb-space-xl">` needs a closing `</div>`.
                    # so we should take up to the closing `</div>` of that div.
                    # Usually it's `</div></main>`.
                    main_content_up_to_main = main_inner[:main_close_idx]
                    
                    # check if it ends with `</div>`
                    div_close_idx = main_content_up_to_main.rfind('</div>')
                    if div_close_idx != -1 and len(main_content_up_to_main) - div_close_idx < 20: # just trailing spaces
                        # actually, just keep everything before `</div>\s*</main>`
                        match = re.search(r'(.*?)</main>', main_inner, re.DOTALL)
                        if match:
                            extracted = match.group(1)
                            # remove the trailing `</div>` that belongs to `split_marker`
                            if extracted.strip().endswith('</div>'):
                                extracted = extracted[:extracted.rfind('</div>')]
                            main_inner = extracted

                final_content = "<?php include 'includes/header.php'; ?>\n" + main_inner + "\n<?php include 'includes/footer.php'; ?>\n"
                
                with open(php_filepath, 'w', encoding='utf-8') as f:
                    f.write(final_content)
            else:
                print(f"Warning: split_marker not found in {filename}")

        # Remove the html file after converting
        os.remove(filepath)

print("Refactoring completed.")
