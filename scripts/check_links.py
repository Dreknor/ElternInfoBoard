import os

found = []
for root, dirs, files in os.walk('app'):
    for f in files:
        if f.endswith('.php'):
            path = os.path.join(root, f)
            with open(path, 'r', encoding='utf-8', errors='ignore') as fh:
                for i, line in enumerate(fh, 1):
                    if 'home#' in line:
                        found.append(f'{path}:{i}: {line.rstrip()}')

print(f'Remaining home# occurrences: {len(found)}')
for item in found:
    print(item)

