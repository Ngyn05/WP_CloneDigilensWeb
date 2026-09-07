import urllib.request
import re
import ssl
import sys

sys.stdout.reconfigure(encoding='utf-8')

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

urls = [
    'https://digilens.vn/',
    'https://digilens.vn/argo/',
    'https://digilens.vn/store/',
    'https://digilens.vn/waveguides/',
    'https://digilens.vn/product/argo-enterprise/',
    'https://digilens.vn/company/',
    'https://digilens.vn/partners/',
    'https://digilens.vn/contact/',
    'https://digilens.vn/media/'
]

headers = {'User-Agent': 'Googlebot/2.1 (+http://www.google.com/bot.html)'}

for u in urls:
    try:
        req = urllib.request.Request(u, headers=headers)
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            status = resp.status
            x_robots = resp.headers.get('x-robots-tag', 'None')
            body = resp.read().decode('utf-8', errors='ignore')
            meta_m = re.search(r'<meta[^>]+name=[\'"]robots[\'"][^>]+content=[\'"]([^\'"]+)[\'"]', body, re.I)
            meta = meta_m.group(1) if meta_m else 'Default (index, follow)'
            can_m = re.search(r'<link[^>]+rel=[\'"]canonical[\'"][^>]+href=[\'"]([^\'"]+)[\'"]', body, re.I)
            can = can_m.group(1) if can_m else 'None'
            title_m = re.search(r'<title>(.*?)</title>', body, re.I | re.S)
            title = title_m.group(1).strip() if title_m else 'No title'
            print(f"URL: {u}")
            print(f"  HTTP Status: {status}")
            print(f"  Title: {title}")
            print(f"  Header X-Robots-Tag: {x_robots}")
            print(f"  Meta Robots HTML: {meta}")
            print(f"  Canonical: {can}")
            print("-" * 50)
    except Exception as e:
        print(f"URL: {u} -> Error: {e}")
