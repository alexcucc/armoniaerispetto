from bs4 import BeautifulSoup
import os
import json

def extract_content(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
        if file_path.endswith(('.php', '.html')):
            soup = BeautifulSoup(content, 'html.parser')

            # Remove script, style, header, footer, and PHP code
            for elem in soup(['script', 'style', 'header', 'footer']):
                elem.decompose()
            
            # Remove PHP code by stripping <?php ... ?> blocks
            text = soup.get_text()
            php_free_text = ' '.join(
                part.strip() for part in text.split('<?php') if '?>' not in part
            )
            return ' '.join(php_free_text.split())
    return ''

def generate_index():
    index = []
    excluded_dirs = [
        '.git',
        '.github',
        '.venv',
        'db',
        'docker',
        'documents',
        'images'
    ]
    excluded_files = [
        'search_index.json',
        'generate_search_index.py',
        'footer.php',
        'header.php',
        'common-head.php',
        'send_mail.php',
        'login_handler.php',
        'signup_handler.php',
        'logout.php'
    ]
    
    for root, dirs, files in os.walk('.'):

        dirs[:] = [d for d in dirs if d not in excluded_dirs]
        
        for file in files:

            if file.endswith(('.php', '.html')):

                if file in excluded_files:
                    continue

                file_path = os.path.join(root, file)
                try:
                    content = extract_content(file_path)
                    with open(file_path, 'r', encoding='utf-8') as f:
                        soup = BeautifulSoup(f, 'html.parser')
                        title = soup.title.string if soup.title else file
                    
                    index.append({
                        'path': file_path[2:].replace('\\', '/'),
                        'title': title,
                        'content': content
                    })
                except Exception as e:
                    print(f"Error processing {file_path}: {e}")

    with open('search_index.json', 'w', encoding='utf-8') as f:
        json.dump(index, f, ensure_ascii=False, indent=2)

if __name__ == '__main__':
    generate_index()