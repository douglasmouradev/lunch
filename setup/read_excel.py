import zipfile
import xml.etree.ElementTree as ET
import re
import json
import sys

NS = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}

def col_row(cell_ref):
    m = re.match(r'([A-Z]+)(\d+)', cell_ref)
    if not m:
        return 0, 0
    col, row = m.group(1), int(m.group(2))
    n = 0
    for c in col:
        n = n * 26 + (ord(c) - 64)
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

if __name__ == '__main__':
    path = sys.argv[1] if len(sys.argv) > 1 else 'lista-almoco.xlsx'
    data = read_xlsx(path)
    print(json.dumps(data, ensure_ascii=False, indent=2))
