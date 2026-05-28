import zipfile
import xml.etree.ElementTree as ET
import re
import json
from pathlib import Path

NS = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}

def col_row(cell_ref):
    m = re.match(r'([A-Z]+)(\d+)', cell_ref)
    if not m:
        return 0, 0
    col, row = m.group(1), int(m.group(2))
    n = sum((ord(c) - 64) * (26 ** i) for i, c in enumerate(reversed(col)))
    return n - 1, row - 1

def read_xlsx(path):
    with zipfile.ZipFile(path) as z:
        strings = []
        if 'xl/sharedStrings.xml' in z.namelist():
            root = ET.fromstring(z.read('xl/sharedStrings.xml'))
            for si in root.findall('.//m:si', NS):
                strings.append(''.join(si.itertext()))
        sheet = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
        rows = {}
        for c in sheet.findall('.//m:c', NS):
            ref = c.get('r')
            col, row = col_row(ref)
            t = c.get('t')
            v = c.find('m:v', NS)
            if v is None:
                val = ''
            elif t == 's':
                val = strings[int(v.text)]
            else:
                val = v.text or ''
            rows.setdefault(row, {})[col] = val.strip()
        max_row = max(rows.keys()) if rows else 0
        table = []
        for r in range(max_row + 1):
            if r not in rows:
                continue
            cols = rows[r]
            max_c = max(cols.keys())
            table.append([cols.get(i, '') for i in range(max_c + 1)])
        return table

def extract_names(table):
    names = []
    seen = set()
    for row in table:
        if len(row) < 2:
            continue
        name = row[1].strip()
        if not name or name.upper() in ('NOME', 'DATA', 'ASSINATURA'):
            continue
        if name.replace('.', '').replace(',', '').isdigit():
            continue
        key = name.upper()
        if key in seen:
            continue
        seen.add(key)
        names.append(name)
    return names

base = Path(__file__).parent
xlsx = base / 'lista-almoco.xlsx'
out = base.parent / 'data' / 'employees-list.json'
table = read_xlsx(xlsx)
names = extract_names(table)
out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(json.dumps(names, ensure_ascii=False, indent=2), encoding='utf-8')
print(f'{len(names)} nomes gravados em {out}')
